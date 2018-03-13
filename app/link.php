<?php
	$currDir=dirname(__FILE__);
	include("$currDir/defaultLang.php");
	include("$currDir/language.php");
	include("$currDir/lib.php");

	// upload paths
	$p=array(   
		'properties' => array(
			'photo' => $Translation['ImageFolder'],
			'primary key' => 'id'
		),
		'units' => array(
			'photo' => $Translation['ImageFolder'],
			'primary key' => 'id'
		)
	);

	if(!count($p)) getLink();

	// default links
	$dL=array(  
	);

	// receive user input
	$t=$_GET['t']; // table name
	$f=$_GET['f']; // field name
	$i=makeSafe($_GET['i']); // id

	// validate input
	if(!in_array($t, array_keys($p)))  getLink();
	if(!in_array($f, array_keys($p[$t])) || $f=='primary key')  getLink();
	if(!$i && !$dL[$t][$f]) getLink();

	// user has view access to the requested table?
	if(!check_record_permission($t, $_GET['i'])) getLink();

	// send default link if no id provided, e.g. new record
	if(!$i){
		$path=$p[$t][$f];
		if(preg_match('/^(http|ftp)/i', $dL[$t][$f])){ $path=''; }
		@header("Location: {$path}{$dL[$t][$f]}");
		exit;
	}

	getLink($t, $f, $p[$t]['primary key'], $i, $p[$t][$f]);

	function getLink($table='', $linkField='', $pk='', $id='', $path=''){
		if(!$id || !$table || !$linkField || !$pk){ // default link to return
			exit;
		}

		if(preg_match('/^Lookup: (.*?)::(.*?)::(.*?)$/', $path, $m)){
			$linkID=makeSafe(sqlValue("select `$linkField` from `$table` where `$pk`='$id'"));
			$link=sqlValue("select `{$m[3]}` from `{$m[1]}` where `{$m[2]}`='$linkID'");
		}else{
			$link=sqlValue("select `$linkField` from `$table` where `$pk`='$id'");
		}

		if(!$link){
			exit;
		}

		if(preg_match('/^(http|ftp)/i', $link)){    // if the link points to an external url, don't prepend path
			$path='';
		}elseif(!is_file(dirname(__FILE__)."/$path$link")){    // if the file doesn't exist in the given path, try to find it without the path
			$path='';
		}

		@header("Location: $path$link");
		exit;
	}