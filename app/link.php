<?php
	include_once(__DIR__ . '/lib.php');

	// upload paths
	$p = [
		'properties' => [
			'photo' => getUploadDir(''),
			'primary key' => 'id'
		],
		'property_photos' => [
			'photo' => getUploadDir(''),
			'primary key' => 'id'
		],
		'units' => [
			'photo' => getUploadDir(''),
			'primary key' => 'id'
		],
		'unit_photos' => [
			'photo' => getUploadDir(''),
			'primary key' => 'id'
		],
	];

	if(!count($p)) getLink();

	// default links
	$dL = [
	];

	// receive user input
	$t = Request::val('t'); // table name
	$f = Request::val('f'); // field name
	$i = Request::val('i'); // id
	$v = Request::val('v'); // in case value rather than id is sent, e.g. for lookup fields from other tables

	// in case value is sent instead of id, get the id using the value
	if($v && $t && $f && isset($p[$t][$f])) {
		$pk = $p[$t]['primary key'];
		$i = sqlValue("SELECT `$pk` FROM `$t` WHERE `$f`='" . makeSafe($v) . "'");
	}

	// validate input
	if(!in_array($t, array_keys($p))) getLink();
	if(!in_array($f, array_keys($p[$t])) || $f == 'primary key') getLink();
	if(!$i && !$dL[$t][$f]) getLink();

	// user has view access to the requested table?
	if(!check_record_permission($t, $i)) getLink();

	// send default link if no id provided, e.g. new record
	if(!$i) {
		$path = $p[$t][$f];
		$defaultLink = $dL[$t][$f];
		if(!$defaultLink) getLink();

		if(preg_match('/^(http|ftp)/i', $defaultLink)) $path = '';
		@header("Location: {$path}{$defaultLink}");
		exit;
	}

	getLink($t, $f, $p[$t]['primary key'], $i, $p[$t][$f]);

	function getLink($table = '', $linkField = '', $pk = '', $id = '', $path = '') {
		if(!$id || !$table || !$linkField || !$pk) // default link to return
			exit;

		$id = makeSafe($id);

		if(preg_match('/^Lookup: (.*?)::(.*?)::(.*?)$/', $path, $m)) {
			$linkID = makeSafe(sqlValue("SELECT `$linkField` FROM `$table` WHERE `$pk`='$id'"));
			$link = sqlValue("SELECT `{$m[3]}` FROM `{$m[1]}` WHERE `{$m[2]}`='$linkID'");
		} else {
			$link = sqlValue("SELECT `$linkField` FROM `$table` WHERE `$pk`='$id'");
		}

		if(!$link) exit;

		if(preg_match('/^(http|ftp)/i', $link)) {    // if the link points to an external url, redirect to it directly
			@header("Location: $link");
			exit;
		} elseif(!is_file(__DIR__ . "/{$path}{$link}")) {    // if the file doesn't exist in the given path, try to find it without the path
			$path = '';
		}

		// if not found, send 404 header
		$filePath = __DIR__ . "/{$path}{$link}";
		if(!is_file($filePath)) {
			header($_SERVER["SERVER_PROTOCOL"] . " 404 Not Found");
			exit;
		}

		// pass through the file to the browser
		$mimeType = getMimeType($filePath);
		header("Content-Type: $mimeType");
		header('Content-Disposition: inline; filename="' . $link . '"');
		readfile($filePath);
		exit;
	}
