<?php
	require(__DIR__ . '/incCommon.php');

	if(!csrf_token(true)) {
		@header('HTTP/1.0 403 Access Denied');
		die();
	}

	$sql = trim(Request::val('sql'));
	if(
		!preg_match('/^SELECT\s+.*?\s+FROM\s+\S+/i', $sql)
		&& !preg_match('/^SHOW\s+/i', $sql)
	) {
		@header('HTTP/1.0 404 Not Found');
		die("Invalid query");
	}

	// force a limit of 1000 to SELECT queries in case no limit specified
	if(!preg_match('/\s+limit\s+\d+(\s*,\s*\d+)?/i', $sql) && stripos($sql, 'SELECT') === 0)
		$sql .= ' LIMIT 1000';

	$resp = ['titles' => [], 'data' => [], 'error' => ''];
	$eo = ['silentErrors' => true];

	$res = sql($sql, $eo);
	if(!$res)
		$resp['error'] = $eo['error'];
	else while($row = db_fetch_assoc($res)) {
		if(!count($resp['titles']))
			$resp['titles'] = array_keys($row);

		$resp['data'][] = array_map('htmlspecialchars', array_values($row));
	}

	@header('Content-type: application/json');
	echo json_encode($resp, JSON_PARTIAL_OUTPUT_ON_ERROR);
