<?php
	require(__DIR__ . '/incCommon.php');

	// validate input
	$memberID = makeSafe(strtolower(Request::val('memberID')));

	if(!csrf_token(true)) die($Translation['csrf token expired or invalid']);

	$eo = ['silentErrors' => true];
	sql("DELETE FROM `membership_users` WHERE LCASE(`memberID`)='$memberID'", $eo);
	sql("DELETE FROM `membership_userpermissions` WHERE LCASE(`memberID`)='$memberID'", $eo);
	sql("DELETE FROM `membership_usersessions` WHERE LCASE(`memberID`)='$memberID'", $eo);
	sql("UPDATE `membership_userrecords` SET `memberID`='' WHERE LCASE(`memberID`)='$memberID'", $eo);

	if($_SERVER['HTTP_REFERER']) {
		redirect($_SERVER['HTTP_REFERER'], TRUE);
	} else {
		redirect('admin/pageViewMembers.php');
	}
