<?php
	// support for HTTPS proxies
	if(
		!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
		&& strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https'
	) $_SERVER['HTTPS'] = 'on';

	// real IP in case of proxies
	if(!empty($_SERVER['HTTP_X_FORWARDED_FOR']))
		$_SERVER['REMOTE_ADDR'] = $_SERVER['HTTP_X_FORWARDED_FOR'];
	