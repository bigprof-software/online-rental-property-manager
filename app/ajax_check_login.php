<?php
	$curr_dir = dirname(__FILE__);
	include("{$curr_dir}/defaultLang.php");
	include("{$curr_dir}/language.php");
	include("{$curr_dir}/lib.php");

	/* check access */
	$mi = getMemberInfo();
	$admin_config = config('adminConfig');
	$guest = $admin_config['anonymousMember'];

	if($guest == $mi['username']) die();
	echo $mi['username'];
