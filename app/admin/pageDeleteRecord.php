<?php
	$currDir = dirname(__FILE__);
	require("{$currDir}/incCommon.php");

	// validate input
	$recID = intval($_GET['recID']);

	$res = sql("SELECT `tableName`, `pkValue` FROM `membership_userrecords` WHERE `recID`='{$recID}'", $eo);
	if($row = db_fetch_row($res)) {
		sql("DELETE FROM `membership_userrecords` WHERE `recID`='{$recID}'", $eo);
		if($pkName = getPKFieldName($row[0])) {
			sql("DELETE FROM `{$row[0]}` WHERE `{$pkName}`='" . makeSafe($row[1]) . "'", $eo);
		}
	}

	if($_SERVER['HTTP_REFERER']) {
		redirect($_SERVER['HTTP_REFERER'], TRUE);
		exit;
	}

	redirect('admin/pageViewRecords.php');
