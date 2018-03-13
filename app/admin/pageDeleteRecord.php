<?php
	$currDir=dirname(__FILE__);
	require("$currDir/incCommon.php");

	// validate input
	$recID=intval($_GET['recID']);

	$res=sql("select tableName, pkValue from membership_userrecords where recID='$recID'", $eo);
	if($row=db_fetch_row($res)){
		sql("delete from membership_userrecords where recID='$recID'", $eo);
		if($pkName=getPKFieldName($row[0])){
			sql("delete from `$row[0]` where `$pkName`='".addslashes($row[1])."'", $eo);
		}
	}

	if($_SERVER['HTTP_REFERER']){
		redirect($_SERVER['HTTP_REFERER'], TRUE);
	}else{
		redirect("admin/pageViewRecords.php");
	}

?>