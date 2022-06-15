<?php
	require(__DIR__ . '/incCommon.php');
	$GLOBALS['page_title'] = $Translation['view or rebuild fields'];
	include(__DIR__ . '/incHeader.php');

	/* delete setup.md5 and force creating any missing tables */
	if(empty($_GET)) {
		$tenantId = Authentication::tenantIdPadded();
		@unlink(__DIR__ . "/../setup{$tenantId}.md5");
		@include(__DIR__ . '/../updateDB.php');
	}

	/*
		$schema: [ tablename => [ fieldname => [ appgini => '...', 'db' => '...'], ... ], ... ]
	*/

	/* application schema as created in AppGini */
	$schema = get_table_fields();

	$table_captions = getTableList();

	/* function for preparing field definition for comparison */
	function prepare_def($def) {
		$def = strtolower($def);

		/* ignore 'null' */
		$def = preg_replace('/\s+not\s+null\s*/i', '%%NOT_NULL%%', $def);
		$def = preg_replace('/\s+null\s*/i', ' ', $def);
		$def = str_replace('%%NOT_NULL%%', ' not null ', $def);

		/* ignore length for int data types */
		$def = preg_replace('/int\s*\([0-9]+\)/i', 'int', $def);

		/* make sure there is always a space before mysql words */
		$def = preg_replace('/(\S)(unsigned|not null|binary|zerofill|auto_increment|default)/i', '$1 $2', $def);

		/* ignore 'not null' for auto_increment fields */
		$def = preg_replace('/\s+not\s+null\s+(.*?)\s+auto_increment/i', ' $1 auto_increment', $def);

		/* treat 0.000.. same as 0 */
		$def = preg_replace('/([0-9])*\.0+/', '$1', $def);

		/* treat unsigned zerofill same as zerofill */
		$def = str_ireplace('unsigned zerofill', 'zerofill', $def);

		/* ignore zero-padding for date data types */
		$def = preg_replace("/date\s*default\s*'([0-9]{4})-0?([1-9])-0?([1-9])'/i", "date default '$1-$2-$3'", $def);

		return trim($def);
	}

	/**
	 *  @brief creates/fixes given field according to given schema
	 *  @return integer: 0 = error, 1 = field updated, 2 = field created
	 */
	function fix_field($fix_table, $fix_field, $schema, &$qry) {
		if(!isset($schema[$fix_table][$fix_field])) return 0;

		$def = $schema[$fix_table][$fix_field];
		$field_added = $field_updated = false;
		$eo = ['silentErrors' => true];
		$qry = '';

		// field exists?
		$res = sql("SHOW COLUMNS FROM `{$fix_table}` LIKE '{$fix_field}'", $eo);
		if($row = db_fetch_assoc($res)) {
			// modify field
			$qry = "ALTER TABLE `{$fix_table}` MODIFY `{$fix_field}` {$def['appgini']}";
			sql($qry, $eo);

			// remove unique from db if necessary
			if($row['Key'] == 'UNI' && !stripos($def['appgini'], ' unique')) {
				// retrieve unique index name
				$res_unique = sql("SHOW INDEX FROM `{$fix_table}` WHERE Column_name='{$fix_field}' AND Non_unique=0", $eo);
				if($row_unique = db_fetch_assoc($res_unique)) {
					$qry_unique = "DROP INDEX `{$row_unique['Key_name']}` ON `{$fix_table}`";
					sql($qry_unique, $eo);
					$qry .= ";\n{$qry_unique}";
				}
			}

			return 1;
		}

		// missing field is defined as PK and table has another PK field?
		$current_pk = getPKFieldName($fix_table);
		if(stripos($def['appgini'], 'primary key') !== false && $current_pk !== false) {
			// if current PK is not another AppGini-defined field, then rename it.
			if(!isset($schema[$fix_table][$current_pk])) {
				// no need to include 'primary key' in definition since it's already a PK field
				$redef = str_ireplace(' primary key', '', $def['appgini']);
				$qry = "ALTER TABLE `{$fix_table}` CHANGE `{$current_pk}` `{$fix_field}` {$redef}";
				sql($qry, $eo);
				return 1;
			}

			// current PK field is another AppGini-defined field
			// this happens if table had a PK field in AppGini then it was unset as PK
			// and another field was created and set as PK
			// in that case, drop PK index from current PK
			// and also remove auto_increment from it if defined
			// then proceed to creating the missing PK field
			$pk_def = str_ireplace(' auto_increment', '', $schema[$fix_table][$current_pk]);
			sql("ALTER TABLE `{$fix_table}` MODIFY `{$current_pk}` {$pk_def}", $eo);
		}

		// create field
		$qry = "ALTER TABLE `{$fix_table}` ADD COLUMN `{$fix_field}` {$def['appgini']}";
		sql($qry, $eo);
		return 2;
	}

	/* process requested fixes */
	$fix_table = Request::val('t', false);
	$fix_field = Request::val('f', false);
	$fix_all = Request::val('all', false);
	$fix_utf8 = Request::val('utf8', false);
	$fix_status = $qry = null;
	$eo = ['silentErrors' => true];

	if($fix_field && $fix_table) $fix_status = fix_field($fix_table, $fix_field, $schema, $qry);

	/* retrieve actual db schema */
	foreach($table_captions as $tn => $tc) {
		$res = sql("SHOW COLUMNS FROM `{$tn}`", $eo);
		if($res) {
			while($row = db_fetch_assoc($res)) {
				if(!isset($schema[$tn][$row['Field']]['appgini'])) continue;
				$field_description = strtoupper(str_replace(' ', '', $row['Type']));
				$field_description = str_ireplace('unsigned', ' unsigned', $field_description);
				$field_description = str_ireplace('zerofill', ' zerofill', $field_description);
				$field_description = str_ireplace('binary', ' binary', $field_description);
				$field_description .= ($row['Null'] == 'NO' ? ' not null' : '');
				$field_description .= ($row['Key'] == 'PRI' ? ' primary key' : '');
				$field_description .= ($row['Key'] == 'UNI' ? ' unique' : '');
				$field_description .= ($row['Default'] != '' ? " default '" . makeSafe($row['Default']) . "'" : '');
				$field_description .= ($row['Extra'] == 'auto_increment' ? ' auto_increment' : '');

				$schema[$tn][$row['Field']]['db'] = '';
				if(isset($schema[$tn][$row['Field']])) {
					$schema[$tn][$row['Field']]['db'] = $field_description;
				}
			}
		}
	}

	/* handle fix_all request */
	if($fix_all) {
		foreach($schema as $tn => $fields) {
			foreach($fields as $fn => $fd) {
				if(prepare_def($fd['appgini']) == prepare_def($fd['db'])) continue;
				fix_field($tn, $fn, $schema, $qry);
			}
		}

		redirect('admin/pageRebuildFields.php');
		exit;
	}

	// mysql_charset set to utf8mb4?
	$collation = '';
	$dbDatabase = config('dbDatabase');
	if(mysql_charset == 'utf8mb4') {
		// check which collation is supported
		if(db_num_rows(sql("SHOW COLLATION WHERE Charset = 'utf8mb4' AND Collation LIKE 'utf8mb4_unicode_520_ci'", $eo)))
			$collation = 'utf8mb4_unicode_520_ci';
		elseif(db_num_rows(sql("SHOW COLLATION WHERE Charset = 'utf8mb4' AND Collation LIKE 'utf8mb4_unicode_ci'", $eo)))
			$collation = 'utf8mb4_unicode_ci';
	}

	// handle fix_utf8 request if a utf8mb4 collation is supported
	if($fix_utf8 && $collation) {
		sql("ALTER DATABASE `$dbDatabase` CHARACTER SET = utf8mb4 COLLATE = $collation", $eo);
		foreach($schema as $tn => $ti) {
			sql("ALTER TABLE `$tn` CONVERT TO CHARACTER SET utf8mb4 COLLATE $collation", $eo);
			sql("REPAIR TABLE `$tn`", $eo);
			sql("OPTIMIZE TABLE `$tn`", $eo);
		}
	}

	// check db and table utf8 statuses
	$encodingError = false;
	if(mysql_charset == 'utf8mb4') {
		$dbCharset = sqlValue("SELECT default_character_set_name FROM information_schema.SCHEMATA WHERE schema_name='$dbDatabase'");
		$tableCharset = [];
		foreach($schema as $tn => $ti)
			$tableCharset[$tn] = sqlValue(
				"SELECT c.character_set_name FROM information_schema.`TABLES` t,
				information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` c
				WHERE c.collation_name = t.table_collation
				AND t.table_schema = '$dbDatabase'
				AND t.table_name = '$tn'" 
			);

		$encodingError = (
			$dbCharset != 'utf8mb4'
			|| count(array_filter($tableCharset, function($enc) { return $enc != 'utf8mb4'; }))
		);
	}
?>

<?php if($fix_status == 1 || $fix_status == 2) { ?>
	<div class="alert alert-info alert-dismissable">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<i class="glyphicon glyphicon-info-sign"></i>
		<?php 
			$originalValues = ['<ACTION>', '<FIELD>', '<TABLE>', '<QUERY>'];
			$action = ($fix_status == 2 ? 'create' : 'update');
			$replaceValues = [$action, $fix_field, $fix_table, $qry];
			echo str_replace($originalValues, $replaceValues, $Translation['create or update table']);
			echo '<br><a href="' . application_url('admin/pageQueryLogs.php?type=error') . '">' . $Translation['if error persists check query log'] . '</a>';
		?>
	</div>
<?php } ?>

<div class="page-header"><h1>
	<?php echo $Translation['view or rebuild fields'] ; ?>
	<button type="button" class="btn btn-default" id="show_deviations_only"><i class="glyphicon glyphicon-eye-close"></i> <?php echo $Translation['show deviations only'] ; ?></button>
	<button type="button" class="btn btn-default hidden" id="show_all_fields"><i class="glyphicon glyphicon-eye-open"></i> <?php echo $Translation['show all fields'] ; ?></button>
</h1></div>

<p class="lead"><?php echo $Translation['compare tables page'] ; ?></p>

<div class="alert summary"></div>

<div class="alert alert-warning alert-utf8 alert-dismissable hidden">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
	<div class="text-bold"><?php echo $Translation['unicode needs fixing']; ?></div>
	<div>
		<?php echo $Translation['unicode fix details']; ?>
		<small><a class="alert-link" href="https://mathiasbynens.be/notes/mysql-utf8mb4" target="_blank"><?php echo $Translation['more info']; ?></a></small>
	</div>
	<button type="button" class="btn btn-warning btn-fix-utf8 tspacer-lg">
		<i class="glyphicon glyphicon-ok"></i> <?php echo $Translation['fix unicode']; ?>
	</button>
</div>
<script>
	$j(() => {
		const encodingError = <?php echo ($encodingError ? 'true' : 'false'); ?>;
		if(!encodingError) return;

		$j('.alert-utf8').removeClass('hidden');
		$j('.btn-fix-utf8').click(() => {
			if(!confirm(<?php echo json_encode($Translation['backup before fix']); ?>)) return;
			location.href = 'pageRebuildFields.php?utf8=1';
		});
	})
</script>

<table class="table table-responsive table-hover table-striped">
	<thead><tr>
		<th></th>
		<th><?php echo $Translation['field'] ; ?></th>
		<th><?php echo $Translation['AppGini definition'] ; ?></th>
		<th><?php echo $Translation['database definition'] ; ?></th>
		<th id="fix_all"></th>
	</tr></thead>

	<tbody>
	<?php foreach($schema as $tn => $fields) { ?>
		<tr class="text-info"><td colspan="5">
			<h4 data-placement="auto top" data-toggle="tooltip" title="<?php echo str_replace ( "<TABLENAME>" , $tn , $Translation['table name title']) ; ?>"><img src="../<?php echo $table_captions[$tn][2]; ?>"> <?php echo $table_captions[$tn][0]; ?></h4>
			<?php if(mysql_charset == 'utf8mb4' && $tableCharset[$tn] != 'utf8mb4') { ?>
				<span class="label label-danger"><?php echo $Translation['unicode error']; ?></span>
			<?php } ?>
		</td></tr>
		<?php foreach($fields as $fn => $fd) { ?>
			<?php $diff = ((prepare_def($fd['appgini']) == prepare_def($fd['db'])) ? false : true); ?>
			<?php $no_db = ($fd['db'] ? false : true); ?>
			<tr class="<?php echo ($diff ? 'warning' : 'field_ok'); ?>">
				<td><i class="glyphicon glyphicon-<?php echo ($diff ? 'remove text-danger' : 'ok text-success'); ?>"></i></td>
				<td><?php echo $fn; ?></td>
				<td class="<?php echo ($diff ? 'bold text-success' : ''); ?>"><samp><?php echo $fd['appgini']; ?></samp></td>
				<td class="<?php echo ($diff ? 'bold text-danger' : ''); ?>"><?php echo thisOr("<samp>{$fd['db']}</samp>", $Translation['does not exist']); ?></td>
				<td>
					<?php if($diff && $no_db) { ?>
						<a href="pageRebuildFields.php?t=<?php echo $tn; ?>&f=<?php echo $fn; ?>" class="btn btn-success btn-xs btn_create" data-toggle="tooltip" data-placement="auto top" title="<?php echo $Translation['create field'] ; ?>"><i class="glyphicon glyphicon-plus"></i> <?php echo $Translation['create it'] ; ?></a>
					<?php } elseif($diff) { ?>
						<a href="pageRebuildFields.php?t=<?php echo $tn; ?>&f=<?php echo $fn; ?>" class="btn btn-warning btn-xs btn_update" data-toggle="tooltip" data-placement="auto top" title="<?php echo $Translation['fix field'] ; ?>"><i class="glyphicon glyphicon-cog"></i> <?php echo $Translation['fix it'] ; ?></a>
					<?php } ?>
				</td>
			</tr>
		<?php } ?>
		<?php if(count($fields) % 2) { ?><tr class="field_ok"><td colspan="5"></td></tr><?php } ?>
	<?php } ?>
	</tbody>
</table>
<div class="alert summary"></div>

<style>
	.bold{ font-weight: bold; }
	[data-toggle="tooltip"]{ display: inline-block !important; }
</style>

<script>
	$j(function() {
		$j('[data-toggle="tooltip"]').tooltip();

		$j('#show_deviations_only').click(function() {
			$j(this).addClass('hidden');
			$j('#show_all_fields').removeClass('hidden');
			$j('.field_ok').hide();
		});

		$j('#show_all_fields').click(function() {
			$j(this).addClass('hidden');
			$j('#show_deviations_only').removeClass('hidden');
			$j('.field_ok').show();
		});

		$j('.btn_update, #fix_all').click(function() {
			return confirm('<?php echo addslashes($Translation['field update warning']); ?>');
		});

		var count_updates = $j('.btn_update').length;
		var count_creates = $j('.btn_create').length;
		if(!count_creates && !count_updates) {
			$j('.summary').addClass('alert-success').html("<?php echo $Translation['no deviations found'] ; ?>");
		} else {
			var fieldsCount = "<?php echo $Translation['error fields']; ?>";
			fieldsCount = fieldsCount.replace(/<CREATENUM>/, count_creates ).replace(/<UPDATENUM>/, count_updates);


			$j('.summary')
				.addClass('alert-warning')
				.html(
					fieldsCount + 
					'<br><br>' + 
					'<a href="pageBackupRestore.php" class="alert-link">' +
						'<b><?php echo addslashes($Translation['backup before fix']); ?></b>' +
					'</a>'
				);

			$j('<a href="pageRebuildFields.php?all=1" class="btn btn-danger btn-block"><i class="glyphicon glyphicon-cog"></i> <?php echo addslashes($Translation['fix all']); ?></a>').appendTo('#fix_all');
		}
	});
</script>

<?php include(__DIR__ . '/incFooter.php');
