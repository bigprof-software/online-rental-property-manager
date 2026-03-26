<?php
	define('PREPEND_PATH', '');
	include_once(__DIR__ . '/lib.php');

	@header('Content-type: application/json');

	// Validate CSRF token for POST and DELETE requests
	$method = $_SERVER['REQUEST_METHOD'];

	// if method is DELETE, parse input stream to populate $_REQUEST
	if($method === 'DELETE') {
		parse_str(file_get_contents("php://input"), $delete_vars);
		$_REQUEST = array_merge($_REQUEST, $delete_vars);
	}

	// validate CSRF token for POST and DELETE requests
	if(in_array($method, ['POST', 'DELETE']) && !csrf_token(true)) {
		@header('HTTP/1.0 403 Forbidden');
		exit(json_encode([
			'success' => false,
			'error' => $Translation['csrf token expired or invalid'],
		]));
	}

	// Don't allow guests
	if(Authentication::isGuest()) {
		@header('HTTP/1.0 403 Forbidden');
		exit(json_encode([
			'success' => false,
			'error' => $Translation['Not allowed']
		]));
	}

	// Get current user
	$username = getLoggedMemberID();

	// Create table if not exists
	update_appgini_saved_filters();

	// Handle different request methods
	switch($method) {
		case 'POST':
			handlePost($username);
			break;

		case 'DELETE':
			handleDelete($username);
			break;

		case 'GET':
		default:
			handleGet($username);
			break;
	}

	####################################################################

	function handlePost($username) {
		global $Translation;

		$title = trim(strip_tags(Request::val('title', '')));
		$link = trim(Request::val('link', ''));

		// Validate inputs
		if(!$title || !$link) {
			@header('HTTP/1.0 400 Bad Request');
			exit(json_encode([
				'success' => false,
				'error' => $Translation['saved filter title and link required']
			]));
		}

		// Validate title length
		if(mb_strlen($title) > 150) {
			@header('HTTP/1.0 400 Bad Request');
			exit(json_encode([
				'success' => false,
				'error' => $Translation['saved filter title too long']
			]));
		}

		// Check if user has reached the limit
		$safe_username = makeSafe($username);
		$count = sqlValue("SELECT COUNT(*) FROM `appgini_saved_filters` WHERE `username`='{$safe_username}'");

		if($count >= MAX_FILTER_LINKS_PER_USER) {
			@header('HTTP/1.0 429 Too Many Requests');
			exit(json_encode([
				'success' => false,
				'error' => sprintf($Translation['saved filter limit reached'], MAX_FILTER_LINKS_PER_USER)
			]));
		}

		// Insert or update the filter link
		$safe_title = makeSafe($title);
		$safe_link = makeSafe($link);

		$eo = ['silentErrors' => true];
		$result = sql("INSERT INTO `appgini_saved_filters`
			SET `username`='{$safe_username}',
				`title`='{$safe_title}',
				`link`='{$safe_link}',
				`created_at`=NOW()
			ON DUPLICATE KEY UPDATE
				`link`='{$safe_link}',
				`created_at`=NOW()", $eo);

		if(!$result) {
			@header('HTTP/1.0 500 Internal Server Error');
			exit(json_encode([
				'success' => false,
				'error' => $Translation['saved filter save failed']
			]));
		}

		// Return updated list
		returnFilterList($username);
	}

	####################################################################

	function handleDelete($username) {
		global $Translation;

		$title = trim(Request::val('title', ''));

		// Validate input
		if(!$title) {
			@header('HTTP/1.0 400 Bad Request');
			exit(json_encode([
				'success' => false,
				'error' => $Translation['saved filter title required']
			]));
		}

		// Delete the filter link (case-sensitive)
		$safe_username = makeSafe($username);
		$safe_title = makeSafe($title);

		$eo = ['silentErrors' => true];
		$result = sql("DELETE FROM `appgini_saved_filters`
			WHERE `username`='{$safe_username}'
			AND `title`='{$safe_title}'", $eo);

		if(!$result) {
			@header('HTTP/1.0 500 Internal Server Error');
			exit(json_encode([
				'success' => false,
				'error' => $Translation['saved filter delete failed']
			]));
		}

		// Return updated list
		returnFilterList($username);
	}

	####################################################################

	function handleGet($username) {
		returnFilterList($username);
	}

	####################################################################

	function returnFilterList($username) {
		$safe_username = makeSafe($username);
		$filters = [];

		$eo = ['silentErrors' => true];
		$res = sql("SELECT `title`, `link`, `created_at`
			FROM `appgini_saved_filters`
			WHERE `username`='{$safe_username}'
			ORDER BY `link`", $eo);

		if($res) {
			while($row = db_fetch_assoc($res)) {
				// extract tablename from link to retrieve table icon. Link pattern: {tablename}_view.php?{filters}
				// use regex to extract tablename
				preg_match('/\/?([a-zA-Z0-9_]+)_view\.php/', $row['link'], $matches);
				$tablename = $matches[1] ?? 'N/A';

				$icon = get_tables_info()[$tablename]['tableIcon'] ?? 'table.gif';

				$filters[] = [
					'title' => $row['title'],
					'link' => $row['link'],
					'created_at' => $row['created_at'],
					'icon' => $icon
				];
			}
		}

		exit(json_encode([
			'success' => true,
			'filters' => $filters
		]));
	}
