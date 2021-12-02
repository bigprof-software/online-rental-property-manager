<?php
	require(__DIR__ . '/incCommon.php');

	// validate input
	$groupID = intval(Request::val('groupID'));

	if(!csrf_token(true)) die($Translation['csrf token expired or invalid']);

	// make sure group has no members
	if(sqlValue("select count(1) from membership_users where groupID='{$groupID}'")) {
		errorMsg($Translation['can not delete group remove members']);
		include(__DIR__ . '/incFooter.php');
	}

	// make sure group has no records
	if(sqlValue("select count(1) from membership_userrecords where groupID='{$groupID}'")) {
		errorMsg($Translation['can not delete group transfer records']);
		include(__DIR__ . '/incFooter.php');
	}


	$eo = ['silentErrors' => 1];
	sql("delete from membership_groups where groupID='{$groupID}'", $eo);
	sql("delete from membership_grouppermissions where groupID='{$groupID}'", $eo);

	if($_SERVER['HTTP_REFERER'])
		redirect($_SERVER['HTTP_REFERER'], TRUE);
	else
		redirect('admin/pageViewGroups.php');
