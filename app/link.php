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
	$i = makeSafe(Request::val('i')); // id

	// validate input
	if(!in_array($t, array_keys($p))) getLink();
	if(!in_array($f, array_keys($p[$t])) || $f == 'primary key') getLink();
	if(!$i && !$dL[$t][$f]) getLink();

	// user has view access to the requested table?
	if(!check_record_permission($t, Request::val('i'))) getLink();

	// send default link if no id provided, e.g. new record
	if(!$i) {
		$path = $p[$t][$f];
		if(preg_match('/^(http|ftp)/i', $dL[$t][$f])) $path = '';
		@header("Location: {$path}{$dL[$t][$f]}");
		exit;
	}

	getLink($t, $f, $p[$t]['primary key'], $i, $p[$t][$f]);

	function getLink($table = '', $linkField = '', $pk = '', $id = '', $path = '') {
		if(!$id || !$table || !$linkField || !$pk) // default link to return
			exit;

		if(preg_match('/^Lookup: (.*?)::(.*?)::(.*?)$/', $path, $m)) {
			$linkID = makeSafe(sqlValue("SELECT `$linkField` FROM `$table` WHERE `$pk`='$id'"));
			$link = sqlValue("SELECT `{$m[3]}` FROM `{$m[1]}` WHERE `{$m[2]}`='$linkID'");
		} else {
			$link = sqlValue("SELECT `$linkField` FROM `$table` WHERE `$pk`='$id'");
		}

		if(!$link) exit;

		if(preg_match('/^(http|ftp)/i', $link)) {    // if the link points to an external url, don't prepend path
			$path = '';
		} elseif(!is_file(__DIR__ . "/{$path}{$link}")) {    // if the file doesn't exist in the given path, try to find it without the path
			$path = '';
		}

		@header("Location: $path$link");
		exit;
	}