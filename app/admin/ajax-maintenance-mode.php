<?php
	$currDir = dirname(__FILE__);
	require("{$currDir}/incCommon.php");

	if(!getLoggedAdmin()) exit;

	$status = $_REQUEST['status'];
	if($status == 'on') maintenance_mode(true);
	if($status == 'off') maintenance_mode(false);

