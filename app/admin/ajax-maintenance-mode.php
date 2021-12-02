<?php
	require(__DIR__ . '/incCommon.php');

	if(!getLoggedAdmin()) exit;

	if(!csrf_token(true)) exit;

	$status = Request::val('status');
	if($status == 'on') maintenance_mode(true);
	if($status == 'off') maintenance_mode(false);

