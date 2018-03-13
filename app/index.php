<?php
	error_reporting(E_ERROR | E_WARNING | E_PARSE);
	$currDir = dirname(__FILE__);
	include("{$currDir}/defaultLang.php");
	include("{$currDir}/language.php");
	include("{$currDir}/lib.php");

	$x = new DataList;
	$x->TableTitle = $Translation['homepage'];
	$tablesPerRow = 2;
	$arrTables = getTableList();

	// according to provided GET parameters, either log out, show login form (possibly with a failed login message), or show homepage
	if($_GET['signOut'] == 1){
		logOutMember();
	}elseif($_GET['loginFailed'] == 1 || $_GET['signIn'] == 1){
		if(!headers_sent() && $_GET['loginFailed'] == 1) header('HTTP/1.0 403 Forbidden');
		include("{$currDir}/login.php");
	}else{
		include("{$currDir}/home.php");
	}