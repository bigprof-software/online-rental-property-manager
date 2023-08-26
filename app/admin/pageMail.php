<?php
	require(__DIR__ . '/incCommon.php');
	$GLOBALS['page_title'] = $Translation['send mail'];
	include(__DIR__ . '/incHeader.php');

	// check configured sender
	if(!isEmail($adminConfig['senderEmail'])) {
		echo Notification::show([
			'message' => $Translation['can not send mail'],
			'class' => 'danger',
			'dismiss_seconds' => 3600
		]);
		include(__DIR__ . '/incFooter.php');
	}

	// determine and validate recipients
	$memberID = new Request('memberID', 'strtolower');
	$groupID = intval(Request::val('groupID'));
	$sendToAll = intval(Request::val('sendToAll'));
	$showDebug = Request::val('showDebug') ? true : false;

	$isGroup = ($memberID->raw != '' ? false : true);

	$recipient = ($sendToAll ? $Translation['all groups'] : ($isGroup ? sqlValue("select name from membership_groups where groupID='$groupID'") : sqlValue("select memberID from membership_users where lcase(memberID)='{$memberID->sql}'")));
	if(!$recipient) {
		echo Notification::show([
			'message' => $Translation['no recipient'],
			'class' => 'danger',
			'dismiss_seconds' => 3600
		]);
		include(__DIR__ . '/incFooter.php');
	}

	if(Request::has('saveChanges')) {
		if(!csrf_token(true)) {
			echo Notification::show([
				'message' => $Translation['csrf token expired or invalid'],
				'class' => 'warning',
				'dismiss_seconds' => 3600
			]);
			include(__DIR__ . '/incFooter.php');
		}

		// validate and sanitize mail subject and message
		$msr = new Request('mailSubject');
		$mmr = new Request('mailMessage');
		$mailSubject = strip_tags($msr->raw);
		$mailMessage = strip_tags($mmr->raw);

		$isGroup = ($memberID->raw != '' ? false : true);
		$recipient = ($sendToAll ? $Translation["all groups"] : ($isGroup ? sqlValue("select name from membership_groups where groupID='$groupID'") : sqlValue("select lcase(memberID) from membership_users where lcase(memberID)='{$memberID->sql}'")));
		if(!$recipient) {
			echo Notification::show([
				'message' => $Translation["no recipient"],
				'class' => 'danger',
				'dismiss_seconds' => 3600
			]);
			include(__DIR__ . '/incFooter.php');
		}

		// create a recipients array
		$to = [];
		if($sendToAll) {
			$res = sql("select email from membership_users", $eo);
		} elseif($isGroup) {
			$res = sql("select email from membership_users where groupID='{$groupID}'", $eo);
		} else {
			$res = sql("select email from membership_users where lcase(memberID)='{$memberID->sql}'", $eo);
		}
		while($row = db_fetch_row($res)) {
			if(!isEmail($row[0])) continue;
			$to[] = $row[0];
		}

		// check that there is at least 1 recipient
		if(count($to) < 1) {
			echo Notification::show([
				'message' => $Translation['no recipient found'],
				'class' => 'danger',
				'dismiss_seconds' => 3600
			]);
			include(__DIR__ . '/incFooter.php');
		}

		// save mail queue
		$queueFile = substr(md5(microtime() . rand(0, 100000)), -17);
		if(!($fp = fopen(__DIR__ . "/{$queueFile}.php", 'w'))) {
			echo Notification::show([
				'message' => str_replace('<CURRDIR>', __DIR__, $Translation['mail queue not saved']),
				'class' => 'danger',
				'dismiss_seconds' => 3600
			]);
			include(__DIR__ . '/incFooter.php');
		}

		fwrite($fp, '<' . "?php\n");
		foreach($to as $recip) {
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
		$simulate = Request::val('simulate') ? '&simulate=1' : '';
		redirect("admin/pageSender.php?queue={$queueFile}{$simulate}");
		include(__DIR__ . '/incFooter.php');
	}

	// get count of recipients
	if($sendToAll) {
		$countRecipients = sqlValue("select count(1) from membership_users");
		$countRecipients--; // exclude guest user
	} elseif($isGroup) {
		$countRecipients = sqlValue("select count(1) from membership_users where groupID='{$groupID}'");
	} else {
		$countRecipients = sqlValue("select count(1) from membership_users where lcase(memberID)='{$memberID->sql}'");
	}

	if($countRecipients > 100) {
		echo Notification::show([
			'message' => "<b>{$Translation['attention']}</b><br>{$Translation['send mail to too many members']}",
			'class' => 'warning',
			'dismiss_days' => 3,
			'id' => 'send_mail_to_all_users'
		]);
	}
?>

<div class="page-header"><h1><?php echo $Translation['send mail']; ?></h1></div>

<?php
	// is the messages plugin installed?
	$messages_plugin_installed = false;
	$plugins = get_plugins();

	foreach($plugins as $plugin) 
		if($plugin['title'] == 'Messages')
			$messages_plugin_installed = true;

	if(!$messages_plugin_installed) { ?>
		<div class="row">
			<div class="col-sm-8 col-sm-offset-4 col-md-9 col-md-offset-3 col-lg-6 col-lg-offset-4">
				<a
					type="button" 
					style="white-space: normal; word-wrap: break-word; margin: 2em 0;" 
					class="btn btn-success btn-lg btn-block" 
					href="https://bigprof.com/appgini/applications/messages-plugin" 
					target="_blank" 
					>&#128161; <?php echo $Translation['messages plugin cta']; ?> &#128172;
				</a>
			</div>
		</div>
<?php } ?>

<form method="post" action="pageMail.php" class="form-horizontal">
	<?php echo csrf_token(); ?>
	<input type="hidden" name="memberID" value="<?php echo $memberID->attr; ?>">
	<input type="hidden" name="groupID" value="<?php echo $groupID; ?>">
	<input type="hidden" name="sendToAll" value="<?php echo $sendToAll; ?>">
	<?php if(Request::val('simulate')) { ?>
		<input type="hidden" name="simulate" value="1">
	<?php } ?>

	<div class="form-group">
		<label class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"><?php echo $Translation["from"]; ?></label>
		<div class="col-sm-8 col-md-9 col-lg-6">
			<p class="form-control-static">
				<?php echo strip_tags($adminConfig['senderName']) . " &lt;{$adminConfig['senderEmail']}&gt;"; ?>
				<div>
					<a href="pageSettings.php?search-settings=smtp" class="btn btn-default">
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
					elseif($isGroup)
						$to_link = "pageViewMembers.php?groupID={$groupID}";
				?>
				<a href="<?php echo $to_link; ?>"><i class="glyphicon glyphicon-user text-info"></i> <?php echo $recipient; ?></a>
				<span class="label label-primary"><?php printf($Translation['n recipients'], $countRecipients); ?></span>

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

	<?php if($adminConfig['mail_function'] == 'smtp') { ?>
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
		<div class="col-sm-4 col-sm-offset-8 col-md-4 col-md-offset-8 col-lg-2 col-lg-offset-8">
			<button name="saveChanges" type="submit" class="btn btn-primary btn-lg btn-block"><i class="glyphicon glyphicon-envelope"></i> <?php echo $Translation["send message"]; ?></button>
		</div>
	</div>
</form>

<script>
	$j(function() {
		$j('form').submit(function() {
			return jsShowWait();
		});
	})
</script>

<?php include(__DIR__ . '/incFooter.php');
