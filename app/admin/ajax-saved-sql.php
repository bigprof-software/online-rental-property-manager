<?php
	/*
	  Manage stored SQL queries for admin user.
	  Parameters:
		queries: (optional) a json string [{name, query}, ..]) to store.
	  Response:
		stored queries (as a json string).

	  queries are stored in the membership_users.data field for the current user, under the key 'storedQueries'
	*/

	require(__DIR__ . '/incCommon.php');

	if(!csrf_token(true)) {
		@header('HTTP/1.0 403 Access Denied');
		die();
	}

	// store queries if provided
	$queries = Request::val('queries', null);
	if($queries !== null)
		setUserData('storedQueries', $queries);

	echo getUserData('storedQueries');
