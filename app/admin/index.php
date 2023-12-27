<?php
	error_reporting(E_ERROR | E_PARSE);
	$host  = $_SERVER['HTTP_HOST'];
	$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
	$ssl = (
		(!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off')
		// detect reverse proxy SSL
		|| (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https')
		|| (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) == 'on')
	);
	$http = ($ssl ? 'https://' : 'http://');

	header("Location: {$http}{$host}{$uri}/pageHome.php");
	exit;
