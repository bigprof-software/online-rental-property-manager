<?php
	$curr_dir = dirname(__FILE__);
	include_once("{$curr_dir}/lib.php");

	/* check access */
	$mi = getMemberInfo();
	$admin_config = config('adminConfig');
	$guest = $admin_config['anonymousMember'];

	if($guest == $mi['username']) die();
	echo $mi['username'];
