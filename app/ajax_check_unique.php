<?php
	/**
	 * Checks if the value of a specific unique field already exists 
	 * @param t table name
	 * @param f field name
	 * @param id the value of the PK of the record if it already exists
	 * @param value the value to be checked for being a duplicate
	 * @returns json result: { "result": "ok" } or { "result": "error" }
	 */
	$currDir=dirname(__FILE__);
	include("$currDir/defaultLang.php");
	include("$currDir/language.php");
	include("$currDir/lib.php");

	/* maintenance mode */
	handle_maintenance();

	/* return json */
	header('Content-type: application/json');

	$error_return = '{ "result": "error" }';
	$ok_return = '{ "result": "ok" }';

	/* capture inputs */
	$table = new Request('t');
	$field = new Request('f');
	$id = new Request('id');
	$value = new Request('value');

	/* prevent conventional error output via a shutdown handler */
	function cancel_all_buffers(){
		while(ob_get_level()) ob_end_clean();
		echo $GLOBALS['result'];
	}
	$GLOBALS['result'] = $error_return; // error message to return if any error occurs
	ob_start();
	register_shutdown_function('cancel_all_buffers');

	/* user has access to table? */
	$table_perms = getTablePermissions($table->raw);
	if(!$table_perms[0]) exit();

	/* PK field name */
	$pk = getPKFieldName($table->raw);
	if(!$pk) exit();
	$spk = makeSafe($pk, false);

	/* check if value exists in records other than the current record */
	$where = "`{$field->sql}`='{$value->sql}'";
	if($id->raw){ // existing record to be excluded from search
		$where .= " and `{$spk}`!='{$id->sql}'";
	}
	$chk_query = "select count(1) from `{$table->sql}` where {$where}";
	$exists = sqlValue($chk_query);

	/* return error if exists or a query error took place */
	if($exists !== 0 && $exists !== '0') exit();

	/* return ok if unique */
	$GLOBALS['result'] = $ok_return;
