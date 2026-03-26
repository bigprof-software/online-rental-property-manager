<?php
	require(__DIR__ . '/incCommon.php');

	if(!csrf_token(true)) {
		http_response_code(403); // Forbidden
		die();
	}

	header('Content-Type: application/json; charset=UTF-8');

	// get submitted data
	$senderEmail = Request::val('senderEmail');
	$senderName = Request::val('senderName');
	$mailFunction = Request::oneOf('mailFunction', ['smtp', 'mail']);

	// validate
	if(!isEmail($senderEmail)) {
		http_response_code(400); // Bad Request
		die(json_encode(['status' => 'error', 'message' => $Translation['invalid email']]));
	}
	if($senderName == '') {
		$senderName = $senderEmail;
	}
	if(!$mailFunction) {
		http_response_code(400); // Bad Request
		die(json_encode(['status' => 'error', 'message' => $Translation['mail_function']]));
	}

	$isSMTP = ($mailFunction == 'smtp');
	if($isSMTP) {
		$smtpServer = Request::val('smtpServer');
		$smtpEncryption = Request::val('smtpEncryption');
		$smtpPort = (int)Request::val('smtpPort');
		$smtpUser = Request::val('smtpUser');
		$smtpPass = Request::val('smtpPass');
	}

	$pm = new PHPMailer\PHPMailer\PHPMailer;
	$pm->CharSet = datalist_db_encoding;

	if($isSMTP) {
		$pm->isSMTP();
		$pm->SMTPDebug = 3;
		$pm->Debugoutput = 'text';
		$pm->Host = $smtpServer;
		$pm->Port = $smtpPort;
		$pm->SMTPAuth = !empty($smtpUser) || !empty($smtpPass);
		$pm->SMTPSecure = $smtpEncryption;
		$pm->SMTPAutoTLS = $smtpEncryption ? true : false;
		$pm->Username = $smtpUser;
		$pm->Password = $smtpPass;
	}

	$pm->setFrom($senderEmail, $senderName);
	$pm->addReplyTo($senderEmail, $senderName);
	$pm->addAddress($senderEmail, $senderName);
	$pm->Subject = 'Test email from ' . APP_TITLE;
	$pm->msgHTML('If you are seeing this, your email configuration is working!');

	ob_start();
	if(!$pm->send()) {
		http_response_code(500); // Internal Server Error
		die(json_encode(['status' => 'error', 'message' => ob_get_clean()]));
	}
	ob_end_clean();

	echo json_encode(['status' => 'ok', 'message' => str_replace('<EMAIL>', $senderEmail, $Translation['sending message ok'])]);
