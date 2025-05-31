<?php
	define('HOMEPAGE', true);
	include_once(__DIR__ . '/lib.php');

	$x = new DataList;
	$x->TableTitle = $Translation['homepage'];

	// according to provided GET parameters, either log out, show login form (possibly with a failed login message), or show homepage
	if(Request::val('signOut')) {
		logOutUser();
		redirect((MULTI_TENANTS ? call_user_func_array('SaaS::loginUrl', []) : 'index.php?signIn=1'), MULTI_TENANTS);
	} elseif((Request::val('signIn') || Request::val('loginFailed')) && Authentication::isGuest()) {
		if(Request::val('loginFailed')) @header('HTTP/1.0 403 Forbidden');
		include(__DIR__ . '/login.php');
	} else {
		unset($_REQUEST['signIn'], $_REQUEST['loginFailed']);
		include(__DIR__ . '/home.php');
	}
