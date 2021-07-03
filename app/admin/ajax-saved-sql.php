<?php
	/*
	  Manage stored SQL queries for admin user.
	  Parameters:
		queries: (optional) a json string [{name, query}, ..]) to store.
	  Response:
		stored queries (as a json string).

	  queries are stored in the membership_users.data field for the current user, under the key 'storedQueries'
	*/

	$currDir = dirname(__FILE__);
	require("{$currDir}/incCommon.php");

	if(!csrf_token(true)) {
		@header('HTTP/1.0 403 Access Denied');
		die();
	}

	// store queries if provided
	if(isset($_REQUEST['queries'])) {
		$queries = $_REQUEST['queries'];
		setUserData('storedQueries', $queries);
	}

	echo getUserData('storedQueries');
