<?php
	define('PREPEND_PATH', '');
	include_once(__DIR__ . '/lib.php');

	/*
	 * calculated fields configuration array, $calc:
	 *         table => [calculated fields], ..
	 *         where calculated fields:
	 *             field => query, ...
	 */
	$calc = calculated_fields();
	cleanup_calc_fields($calc);

	list($table, $id) = get_params();

	// $id could be a single ID or an array
	if((!is_array($id) && !strlen($id)) || !$table) return_json([], 'Access denied or invalid parameters');
	if(!isset($calc[$table]))
		return_json(['table' => $table], 'No fields to calculate in this table');

	/*
		update_calc_fields($table, $id, $calc[$table])

		then, for each parent of $table and its parent's $parent_id 
		stored in record $id of $table:
		update_calc_fields($parent_table, $parent_id, $calc[$parent_table])
	 */
	$caluclations_made = [];
	if(is_array($id)) {
		foreach ($id as $singleId) {
			if(!strlen($singleId)) continue;
			$caluclations_made[] = update_calc_fields($table, $singleId, $calc[$table]);
			update_parents($table, $singleId, $calc, $caluclations_made);
		}
	} else {
		$caluclations_made[] = update_calc_fields($table, $id, $calc[$table]);
		update_parents($table, $id, $calc, $caluclations_made);
	}

	return_json($caluclations_made);

	#############################################################

	/* update parents of given record */
	function update_parents($table, $id, $calc, &$caluclations_made) {
		// get parents of current table
		$parents = get_parent_tables($table);
		$pk = getPKFieldName($table);
		$safe_id = makeSafe($id);
		foreach($parents as $pt => $mlufs /* main lookup fields in child */) {
			if(!isset($calc[$pt])) continue; // parent table has no calc fields

			foreach($mlufs as $mluf) {
				// retrieve parent record ID as stored in lookup field of current table
				$pid = sqlValue("SELECT `{$mluf}` FROM `{$table}` WHERE `{$pk}`='{$safe_id}'");
				if(!strlen($pid)) continue;

				$caluclations_made[] = update_calc_fields($pt, $pid, $calc[$pt]);
			}
		}
	}

	/* get and validate params */
	function get_params() {
		$ret_error = [false, false];

		$table = Request::val('table');
		$id = Request::val('id');
		if(!get_sql_from($table)) return $ret_error;

		if(is_array($id)) {
			$id = array_filter($id, function($singleId) use ($table) {
				return check_record_permission($table, $singleId);
			});
			if(!count($id)) return $ret_error;
		} else {
			if(!check_record_permission($table, $id)) return $ret_error;
		}

		return [$table, $id];
	}

	function return_json($data = [], $error = '') {
		@header('Content-type: application/json');
		die(json_encode(['data' => $data, 'error' => $error]));
	}

	function cleanup_calc_fields(&$calc) {
		foreach($calc as $tn => $conf) {
			if(!count($conf)) unset($calc[$tn]);
		}
	}
