<?php
	require(__DIR__ . '/incCommon.php');

	// validate input
	$recID = intval(Request::val('recID'));

	if(!csrf_token(true)) die($Translation['csrf token expired or invalid']);

	$eo = ['silentErrors' => true];
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
