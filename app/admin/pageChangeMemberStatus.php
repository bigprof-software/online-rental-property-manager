<?php
	require(__DIR__ . '/incCommon.php');

	if(!csrf_token(true)) die($Translation['csrf token expired or invalid']);

	// validate input
	$memberID = makeSafe(strtolower(Request::val('memberID')));
	$unban = (Request::val('unban') == 1 ? 1 : 0);
	$approve = (Request::val('approve') == 1 ? 1 : 0);
	$ban = (Request::val('ban') == 1 ? 1 : 0);

	$eo = ['silentErrors' => true];

	if($unban)
		sql("UPDATE `membership_users` SET `isBanned`=0 WHERE LCASE(`memberID`)='{$memberID}'", $eo);
	elseif($approve) {
		sql("UPDATE `membership_users` SET `isBanned`=0, `isApproved`=1 WHERE LCASE(`memberID`)='{$memberID}'", $eo);
		notifyMemberApproval($memberID);
	} elseif($ban)
		sql("UPDATE `membership_users` SET `isBanned`=1, `isApproved`=1 WHERE LCASE(`memberID`)='{$memberID}'", $eo);

	if($_SERVER['HTTP_REFERER'])
		redirect($_SERVER['HTTP_REFERER'], true);
	else
		redirect("admin/pageViewMembers.php");
