<?php
	require(__DIR__ . "/incCommon.php");

	if(!csrf_token(true)) {
		@header('HTTP/1.0 403 Access Denied');
		die();
	}

	$enable = Request::val('enable') ? 1 : 0;

	$eo = ['silentErrors' => true];
	sql("UPDATE `membership_groups` SET `allow_2fa`='{$enable}'", $eo);

	// check the new value and return success/failure accordingly
	if(($enable && !TFA::enabledForAllGroups()) || (!$enable && !TFA::disabledForAllGroups())) {
		@header('HTTP/1.0 500 Internal Server Error');
		die('Failed to update all groups');
	}

	// all good so a status of 200 is returned by default
