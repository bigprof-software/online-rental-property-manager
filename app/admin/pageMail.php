<?php
	$currDir = dirname(__FILE__);
	require("{$currDir}/incCommon.php");
	$GLOBALS['page_title'] = $Translation['send mail'];
	include("{$currDir}/incHeader.php");

	// check configured sender
	if(!isEmail($adminConfig['senderEmail'])){
		echo Notification::show(array(
			'message' => $Translation["can not send mail"],
			'class' => 'danger',
			'dismiss_seconds' => 3600
		));
		include("{$currDir}/incFooter.php");
	}

	// determine and validate recipients
	$memberID = new Request('memberID', 'strtolower');
	$groupID = intval($_REQUEST['groupID']);
	$sendToAll = intval($_REQUEST['sendToAll']);
	$showDebug = $_REQUEST['showDebug'] ? true : false;

	$isGroup = ($memberID->raw != '' ? false : true);

	$recipient = ($sendToAll ? $Translation['all groups'] : ($isGroup ? sqlValue("select name from membership_groups where groupID='$groupID'") : sqlValue("select memberID from membership_users where lcase(memberID)='{$memberID->sql}'")));
	if(!$recipient){
		echo Notification::show(array(
			'message' => $Translation['no recipient'],
			'class' => 'danger',
			'dismiss_seconds' => 3600
		));
		include("{$currDir}/incFooter.php");
	}

	if(isset($_POST['saveChanges'])){
		if(!csrf_token(true)){
			echo Notification::show(array(
				'message' => $Translation['csrf token expired or invalid'],
				'class' => 'warning',
				'dismiss_seconds' => 3600
			));
			include("{$currDir}/incFooter.php");
		}

		// validate and sanitize mail subject and message
		$msr = new Request('mailSubject');
		$mmr = new Request('mailMessage');
		$mailSubject = strip_tags($msr->raw);
		$mailMessage = strip_tags($mmr->raw);

		$isGroup = ($memberID->raw != '' ? false : true);
		$recipient = ($sendToAll ? $Translation["all groups"] : ($isGroup ? sqlValue("select name from membership_groups where groupID='$groupID'") : sqlValue("select lcase(memberID) from membership_users where lcase(memberID)='{$memberID->sql}'")));
		if(!$recipient){
			echo Notification::show(array(
				'message' => $Translation["no recipient"],
				'class' => 'danger',
				'dismiss_seconds' => 3600
			));
			include("{$currDir}/incFooter.php");
		}

		// create a recipients array
		$to = array();
		if($sendToAll){
			$res = sql("select email from membership_users", $eo);
		}elseif($isGroup){
			$res = sql("select email from membership_users where groupID='{$groupID}'", $eo);
		}else{
			$res = sql("select email from membership_users where lcase(memberID)='{$memberID->sql}'", $eo);
		}
		while($row = db_fetch_row($res)){
			if(!isEmail($row[0])) continue;
			$to[] = $row[0];
		}

		// check that there is at least 1 recipient
		if(count($to) < 1){
			echo Notification::show(array(
				'message' => $Translation['no recipient found'],
				'class' => 'danger',
				'dismiss_seconds' => 3600
			));
			include("{$currDir}/incFooter.php");
		}

		// save mail queue
		$queueFile = md5(microtime());
		$currDir = dirname(__FILE__);
		if(!($fp = fopen("{$currDir}/{$queueFile}.php", 'w'))){
			echo Notification::show(array(
				'message' => str_replace('<CURRDIR>', $currDir, $Translation['mail queue not saved']),
				'class' => 'danger',
				'dismiss_seconds' => 3600
			));
			include("{$currDir}/incFooter.php");
		}

		fwrite($fp, '<' . "?php\n");
		foreach($to as $recip){
			fwrite($fp, "\t\$to[] = '{$recip}';\n");
		}
		fwrite($fp, "\t\$mailSubject = \"" . addcslashes($mailSubject, "\r\n\t\"\\\$") . "\";\n");
		fwrite($fp, "\t\$mailMessage = \"" . addcslashes($mailMessage, "\r\n\t\"\\\$") . "\";\n");
		fwrite($fp, '?' . '>');
		fclose($fp);

		// showDebug checked? save to session (for use in pageSender.php, then will be reset)
		$_SESSION["debug_{$queueFile}"] = false;
		if($showDebug) $_SESSION["debug_{$queueFile}"] = true;

		// redirect to mail queue processor
		$simulate = isset($_REQUEST['simulate']) ? '&simulate=1' : '';
		redirect("admin/pageSender.php?queue={$queueFile}{$simulate}");
		include("{$currDir}/incFooter.php");
	}

	if($sendToAll){
		echo Notification::show(array(
			'message' => "<b>{$Translation['attention']}</b><br>{$Translation['send mail to all members']}",
			'class' => 'warning',
			'dismiss_days' => 3,
			'id' => 'send_mail_to_all_users'
		));
	}
?>

<div class="page-header"><h1><?php echo $Translation['send mail']; ?></h1></div>

<form method="post" action="pageMail.php" class="form-horizontal">
	<?php echo csrf_token(); ?>
	<input type="hidden" name="memberID" value="<?php echo $memberID->attr; ?>">
	<input type="hidden" name="groupID" value="<?php echo $groupID; ?>">
	<input type="hidden" name="sendToAll" value="<?php echo $sendToAll; ?>">
	<?php if(isset($_REQUEST['simulate'])){ ?>
		<input type="hidden" name="simulate" value="1">
	<?php } ?>

	<div class="form-group">
		<label class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"><?php echo $Translation["from"]; ?></label>
		<div class="col-sm-8 col-md-9 col-lg-6">
			<p class="form-control-static">
				<?php echo "{$adminConfig['senderName']} &lt;{$adminConfig['senderEmail']}&gt;"; ?>
				<div>
					<a href="pageSettings.php#mail-settings" class="btn btn-default">
						<i class="glyphicon glyphicon-pencil"></i>
						<?php echo $Translation['configure mail settings']; ?>
					</a>
				</div>
			</p>
		</div>
	</div>

	<div class="form-group">
		<label class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"><?php echo $Translation['to']; ?></label>
		<div class="col-sm-8 col-md-9 col-lg-6">
			<p class="form-control-static">
				<?php
					$to_link = "pageEditMember.php?memberID={$memberID->url}";
					if($sendToAll)
						$to_link = "pageViewMembers.php";
					if(!$sendToAll && $isGroup)
						$to_link = "pageViewMembers.php?groupID={$groupID}";
				?>
				<a href="<?php echo $to_link; ?>">
					<i class="glyphicon glyphicon-user text-info"></i> 
					<?php echo $recipient; ?>
				</a>
				<div class="btn-group">
					<a href="pageViewGroups.php" class="btn btn-default"><?php echo $Translation['send email to all members']; ?></a>
					<a href="pageViewMembers.php" class="btn btn-default"><?php echo $Translation['send email to member']; ?></a>
				</div>
			</p>
		</div>
	</div>

	<div class="form-group">
		<label for="mailSubject" class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"><?php echo $Translation["subject"]; ?></label>
		<div class="col-sm-8 col-md-9 col-lg-6">
			<input class="form-control" name="mailSubject" id="mailSubject" autofocus>
		</div>
	</div>

	<div class="form-group">
		<label for="mailMessage" class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"><?php echo $Translation["message"]; ?></label>
		<div class="col-sm-8 col-md-9 col-lg-6">
			<textarea rows="10" class="form-control" name="mailMessage" id="mailMessage"></textarea>
		</div>
	</div>

	<?php if($adminConfig['mail_function'] == 'smtp'){ ?>
		<div class="checkbox">
			<div class="col-sm-offset-4 col-md-offset-3 col-lg-offset-4 col-sm-8 col-md-9 col-lg-6">
				<label for="showDebug">
					<input type="checkbox" name="showDebug" value="1" id="showDebug">
					<?php echo $Translation['display debugging info']; ?>
					<span class="help-block"><?php echo $Translation['debugging info hint']; ?></span>
				</label>
			</div>
		</div>
	<?php } ?>

	<div class="form-group">
		<div class="col-sm-4 col-sm-offset-4 col-md-6 col-md-offset-3 col-lg-4 col-lg-offset-4">
			<button name="saveChanges" type="submit" class="btn btn-primary btn-lg btn-block"><i class="glyphicon glyphicon-envelope"></i> <?php echo $Translation["send message"]; ?></button>
		</div>
	</div>
</form>

<script>
	$j(function(){
		$j('form').submit(function(){
			return jsShowWait();
		});
	})
</script>

<?php
	include("{$currDir}/incFooter.php");
?>
