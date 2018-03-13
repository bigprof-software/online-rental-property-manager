<?php
	$currDir = dirname(__FILE__);
	require("{$currDir}/incCommon.php");
	$GLOBALS['page_title'] = $Translation['done!'];
	include("{$currDir}/incHeader.php");
	$mailsPerBatch = 5;

	?>
	<style>
		#sendmail-debug{ height: 20em; overflow: auto; }
	</style>
	<?php

	$queue = $_REQUEST['queue'];
	$simulate = (isset($_REQUEST['simulate']) ? true : false);
	if(!preg_match('/^[a-f0-9]{32}$/i', $queue)){
		echo "<div class=\"alert alert-danger\">{$Translation['invalid mail queue']}</div>";
		include("{$currDir}/incFooter.php");
	}

	$queueFile = "{$currDir}/{$queue}.php";
	if(!is_file($queueFile)){
		echo "<div class=\"alert alert-danger\">{$Translation['invalid mail queue']}</div>";
		include("{$currDir}/incFooter.php");
	}

	include($queueFile);

	// escape new lines in message and remove them in subject
	$escaped_mailMessage = strip_tags($mailMessage);
	$escaped_mailSubject = str_replace(
		array("\n", "\r"),
		'',
		strip_tags($mailSubject)
	);

	if($simulate){
		echo '<pre>' . htmlspecialchars($escaped_mailSubject) . '</pre>';
		echo '<pre>' . htmlspecialchars($escaped_mailMessage) . '</pre>';
	}

	$fLog = @fopen("{$currDir}/{$queue}.log", "a");
	// send a batch of up to $mailsPerBatch messages
	$i = 0;
	echo '<pre id="sendmail-debug" style="display: none;">';
	foreach($to as $email){
		if(!isEmail($email)) continue;
		$i++;

		$mail_status = (rand(1, 10) % 3 ? true : false);
		if(!$simulate){
			$mail_status = sendmail(array(
				'to' => $email, 
				'subject' => $escaped_mailSubject, 
				'message' => nl2br($escaped_mailMessage),
				'debug' => ($_SESSION["debug_{$queue}"] ? 2 : 0)
			));
		}

		$mail_log = str_replace("<EMAIL>", $email, $Translation['sending message ok']);
		if($mail_status !== true){
			$mail_log = str_replace("<EMAIL>",$email, $Translation['sending message failed'] . " -- {$mail_status}");
		}
		@fwrite($fLog, @date("d.m.Y H:i:s") . $mail_log . "\n");

		if($i >= $mailsPerBatch) break;
	}
	echo '</pre>';
	@fclose($fLog);

	if($i < $mailsPerBatch){
		// no more emails in queue, so delete queue and unset showDebug
		@unlink($queueFile);

		$mail_log = @file_get_contents("{$currDir}/{$queue}.log");
		?>
		<div class="page-header">
			<h1><?php echo  $Translation['done!'] ; ?></h1>
		</div>
		<?php echo  $Translation['close page'] ; ?>
		<br><br>
		<pre style="text-align: left;"><?php echo "<b>{$Translation['mail log']}</b>\n{$mail_log}"; ?></pre>

		<?php if($_SESSION["debug_{$queue}"]){ ?>
			<div id="sendmail-debug-show"></div>
			<script>
				$j(function(){
					$j('#sendmail-debug').appendTo('#sendmail-debug-show');
					$j('#sendmail-debug').css({ display : 'block'});
				})
			</script>
		<?php } ?>

		<?php
		@unlink("{$currDir}/{$queue}.log");
		unset($_SESSION["debug_{$queue}"]);
		include("{$currDir}/incFooter.php");
	}

	while($i--){ array_shift($to); }

	if(!$fp = fopen($queueFile, "w")){
		?>
		<div class="alert alert-danger">
			<?php echo str_replace("<CURRDIR>", $currDir, $Translation["mail queue not saved"]); ?>
		</div>
		<?php
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

	// redirect to mail queue processor
	if(!$simulate){
		redirect("admin/pageSender.php?queue={$queue}");
	}else{
		echo "<a href=\"pageSender.php?queue={$queue}&simulate=1\">{$Translation['next']}</a>";
	}

	include("{$currDir}/incFooter.php");
?>
