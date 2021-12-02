<?php
	include_once(__DIR__ . '/lib.php');

	/* check access */
	$mi = getMemberInfo();
	$admin_config = config('adminConfig');
	$guest = $admin_config['anonymousMember'];

	if($guest == $mi['username']) die();
	echo $mi['username'];
