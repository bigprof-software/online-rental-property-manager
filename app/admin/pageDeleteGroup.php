<?php
	$currDir = dirname(__FILE__);
	require("{$currDir}/incCommon.php");

	// validate input
	$groupID = intval($_GET['groupID']);

	// make sure group has no members
	if(sqlValue("select count(1) from membership_users where groupID='{$groupID}'")){
		errorMsg($Translation["can not delete group remove members"]);
		include("{$currDir}/incFooter.php");
	}

	// make sure group has no records
	if(sqlValue("select count(1) from membership_userrecords where groupID='{$groupID}'")){
		errorMsg($Translation["can not delete group transfer records"]);
		include("{$currDir}/incFooter.php");
	}


	sql("delete from membership_groups where groupID='{$groupID}'", $eo);
	sql("delete from membership_grouppermissions where groupID='{$groupID}'", $eo);

	if($_SERVER['HTTP_REFERER']){
		redirect($_SERVER['HTTP_REFERER'], TRUE);
	}else{
		redirect("admin/pageViewGroups.php");
	}

?>
