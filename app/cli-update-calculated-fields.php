<?php
	include_once(__DIR__ . '/lib.php');

	// this script can run only in CLI mode
	if(php_sapi_name() != "cli") die('this script can run only in CLI mode');

	$fn = basename(__FILE__);
	$help = implode("\n", [
		'Updates calculated fields.',
		'',
		'Supported arguments:',
		'  -t: comma-separated list of tables to update. all tables will be updated if this argument is not specified',
		'  -s: comma-separated list of starting record numbers. Default is 0 (beginning of each table)',
		'  -l: comma-separated list of records count to update in each table. Default is records count - start',
		'  -x: comma-separated list of tables to exclude from updating, overrides -t',
		'  -u: username to use in queries that have %USERNAME% placeholder, default is admin user',
		'  -h: displays this help message',
		'',
		'Examples:',
		"php {$fn}",
		'  Updates all records of all tables. Not recommended for large databases.',
		"php {$fn} -s 2000 -l 1000",
		'  Updates 1000 records starting from rec# 2000 in all tables.',
		"php {$fn} -t clients,orders -s 100,1000 -l 10,100",
		'  Updates records 100:110 of clients table and 1000:1100 of orders table.',
		"php {$fn} -x clients",
		'  Updates all records of all tables excluding clients table.',
		"php {$fn} -u bob",
		'  Updates all records of all tables as user bob.',
		'',
	]);

	// if -h argument is provided, show the help message and quit
	if(in_array('-h', $argv)) die($help);

	$allowed_args = ['-t', '-s', '-l', '-x'];

	$tables = getTableList(true);

	// prepare args in an array ['switch' => 'value', ...]
	array_shift($argv);
	$args = [];
	for($i = 0; $i < count($argv); $i += 2) {
		if(!in_array($argv[$i], $allowed_args)) continue;
		$args[$argv[$i]] = array_map('trim', explode(',', $argv[$i + 1]));
	}

	$calc = calculated_fields();

	// if no tables provided, assume all tables
	if(!isset($args['-t'])) $args['-t'] = array_keys($calc);

	// default exclude tables, as specified with -x: none
	if(!isset($args['-x'])) $args['-x'] = [];

	if(!isset($args['-s'])) $args['-s'] = [];
	if(!isset($args['-l'])) $args['-l'] = [];

	$mi = getMemberInfo($args['-u']);
	if(!$mi) {
		$conf = config('adminConfig');
		$mi = getMemberInfo($conf['adminUsername']);
	}

	$start = 0;
	$length = pow(2, 45); // default length is a huge # to span the whole table (if your table really has more rows than 2^45, just increase this value!)
	$eo = ['silentErrors' => true];

	// start updating specified tables
	for($i = 0; $i < count($args['-t']); $i++) {
		$tn = $args['-t'][$i];

		if(!isset($calc[$tn])) {
			echo "`{$tn}` table not found.\n";
			continue;
		}

		if(in_array($tn, $args['-x'])) {
			echo "`{$tn}` table excluded.\n";
			continue;
		}

		if(!count($calc[$tn])) {
			echo "`{$tn}` table has no calculated fields.\n";
			continue;
		}

		echo "Processing `{$tn}` table ... ";

		if(isset($args['-s'][$i]) && intval($args['-s'][$i]) > 0) $start = intval($args['-s'][$i]);
		if(isset($args['-l'][$i]) && intval($args['-l'][$i]) > 1) $length = intval($args['-l'][$i]);

		// retrieve PKs of specified range in given table
		$ids = [];
		$pk = getPKFieldName($tn);
		$res = sql("SELECT `{$pk}` FROM `{$tn}` LIMIT {$start}, {$length}", $eo);
		while($row = db_fetch_row($res)) $ids[] = $row[0];

		foreach($ids as $id) update_calc_fields($tn, $id, $calc[$tn], $mi);
		echo count($ids) . " updated.\n";
	}
