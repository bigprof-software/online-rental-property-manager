<?php
	// incCommon.php is included only in the admin area, so if this flag is defined, this indicates we're in admin area
	define('ADMIN_AREA', true);

	include_once(__DIR__ . '/../settings-manager.php');

	// check if initial setup was performed or not
	detect_config();
	migrate_config();

	$adminConfig = config('adminConfig');

	checkAppRequirements();

	ob_start();
	initSession();

	// check if membership system exists
	setupMembership();

	@include_once(__DIR__ . '/../hooks/__global.php');

	if(!Authentication::getAdmin()) Authentication::signIn();
	if(!Authentication::getAdmin()) redirect('index.php');
