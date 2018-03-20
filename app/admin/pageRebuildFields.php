<?php
	$currDir = dirname(__FILE__);
	require("{$currDir}/incCommon.php");
	$GLOBALS['page_title'] = $Translation['view or rebuild fields'];
	include("{$currDir}/incHeader.php");

	/*
		$schema: [ tablename => [ fieldname => [ appgini => '...', 'db' => '...'], ... ], ... ]
	*/

	/* application schema as created in AppGini */
	$schema = array(   
		'applicants_and_tenants' => array(   
			'id' => array('appgini' => 'INT unsigned not null primary key auto_increment '),
			'last_name' => array('appgini' => 'VARCHAR(15) '),
			'first_name' => array('appgini' => 'VARCHAR(15) '),
			'email' => array('appgini' => 'VARCHAR(80) '),
			'phone' => array('appgini' => 'VARCHAR(15) '),
			'birth_date' => array('appgini' => 'DATE '),
			'driver_license_number' => array('appgini' => 'VARCHAR(15) '),
			'driver_license_state' => array('appgini' => 'VARCHAR(15) '),
			'requested_lease_term' => array('appgini' => 'VARCHAR(15) '),
			'monthly_gross_pay' => array('appgini' => 'DECIMAL(8,2) '),
			'additional_income' => array('appgini' => 'DECIMAL(8,2) '),
			'assets' => array('appgini' => 'DECIMAL(8,2) '),
			'status' => array('appgini' => 'VARCHAR(40) not null default \'Applicant\' '),
			'notes' => array('appgini' => 'TEXT ')
		),
		'applications_leases' => array(   
			'id' => array('appgini' => 'INT unsigned not null primary key auto_increment '),
			'tenants' => array('appgini' => 'INT unsigned '),
			'status' => array('appgini' => 'VARCHAR(40) not null default \'Application\' '),
			'property' => array('appgini' => 'INT unsigned '),
			'unit' => array('appgini' => 'INT unsigned '),
			'type' => array('appgini' => 'VARCHAR(40) not null default \'Fixed\' '),
			'total_number_of_occupants' => array('appgini' => 'VARCHAR(15) '),
			'start_date' => array('appgini' => 'DATE '),
			'end_date' => array('appgini' => 'DATE '),
			'recurring_charges_frequency' => array('appgini' => 'VARCHAR(40) not null default \'Monthly\' '),
			'next_due_date' => array('appgini' => 'DATE '),
			'rent' => array('appgini' => 'DECIMAL(8,2) '),
			'security_deposit' => array('appgini' => 'DECIMAL(15,2) '),
			'security_deposit_date' => array('appgini' => 'DATE '),
			'emergency_contact' => array('appgini' => 'VARCHAR(100) '),
			'co_signer_details' => array('appgini' => 'VARCHAR(100) '),
			'notes' => array('appgini' => 'TEXT '),
			'agreement' => array('appgini' => 'VARCHAR(40) ')
		),
		'residence_and_rental_history' => array(   
			'id' => array('appgini' => 'INT unsigned not null primary key auto_increment '),
			'tenant' => array('appgini' => 'INT unsigned '),
			'address' => array('appgini' => 'VARCHAR(40) '),
			'landlord_or_manager_name' => array('appgini' => 'VARCHAR(15) '),
			'landlord_or_manager_phone' => array('appgini' => 'VARCHAR(15) '),
			'monthly_rent' => array('appgini' => 'DECIMAL(6,2) '),
			'duration_of_residency_from' => array('appgini' => 'DATE '),
			'to' => array('appgini' => 'DATE '),
			'reason_for_leaving' => array('appgini' => 'VARCHAR(40) '),
			'notes' => array('appgini' => 'TEXT ')
		),
		'employment_and_income_history' => array(   
			'id' => array('appgini' => 'INT unsigned not null primary key auto_increment '),
			'tenant' => array('appgini' => 'INT unsigned '),
			'employer_name' => array('appgini' => 'VARCHAR(15) '),
			'city' => array('appgini' => 'VARCHAR(20) '),
			'employer_phone' => array('appgini' => 'VARCHAR(15) '),
			'employed_from' => array('appgini' => 'DATE '),
			'employed_till' => array('appgini' => 'DATE '),
			'occupation' => array('appgini' => 'VARCHAR(40) '),
			'notes' => array('appgini' => 'TEXT ')
		),
		'references' => array(   
			'id' => array('appgini' => 'INT unsigned not null primary key auto_increment '),
			'tenant' => array('appgini' => 'INT unsigned '),
			'reference_name' => array('appgini' => 'VARCHAR(15) '),
			'phone' => array('appgini' => 'VARCHAR(15) ')
		),
		'rental_owners' => array(   
			'id' => array('appgini' => 'INT unsigned not null primary key auto_increment '),
			'first_name' => array('appgini' => 'VARCHAR(40) '),
			'last_name' => array('appgini' => 'VARCHAR(40) '),
			'company_name' => array('appgini' => 'VARCHAR(40) '),
			'date_of_birth' => array('appgini' => 'DATE '),
			'primary_email' => array('appgini' => 'VARCHAR(40) '),
			'alternate_email' => array('appgini' => 'VARCHAR(40) '),
			'phone' => array('appgini' => 'VARCHAR(40) '),
			'country' => array('appgini' => 'VARCHAR(40) '),
			'street' => array('appgini' => 'VARCHAR(40) '),
			'city' => array('appgini' => 'VARCHAR(40) '),
			'state' => array('appgini' => 'VARCHAR(40) '),
			'zip' => array('appgini' => 'DECIMAL(15,0) '),
			'comments' => array('appgini' => 'TEXT ')
		),
		'properties' => array(   
			'id' => array('appgini' => 'INT unsigned not null primary key auto_increment '),
			'property_name' => array('appgini' => 'TEXT not null '),
			'type' => array('appgini' => 'VARCHAR(40) not null '),
			'number_of_units' => array('appgini' => 'DECIMAL(15,0) '),
			'photo' => array('appgini' => 'VARCHAR(40) '),
			'owner' => array('appgini' => 'INT unsigned '),
			'operating_account' => array('appgini' => 'VARCHAR(40) '),
			'property_reserve' => array('appgini' => 'DECIMAL(15,0) '),
			'lease_term' => array('appgini' => 'VARCHAR(15) '),
			'country' => array('appgini' => 'VARCHAR(40) '),
			'street' => array('appgini' => 'VARCHAR(40) '),
			'City' => array('appgini' => 'VARCHAR(40) '),
			'State' => array('appgini' => 'VARCHAR(40) '),
			'ZIP' => array('appgini' => 'DECIMAL(15,0) ')
		),
		'property_photos' => array(   
			'id' => array('appgini' => 'INT unsigned not null primary key auto_increment '),
			'property' => array('appgini' => 'INT unsigned '),
			'photo' => array('appgini' => 'VARCHAR(40) '),
			'description' => array('appgini' => 'TEXT ')
		),
		'units' => array(   
			'id' => array('appgini' => 'INT unsigned not null primary key auto_increment '),
			'property' => array('appgini' => 'INT unsigned '),
			'unit_number' => array('appgini' => 'VARCHAR(40) '),
			'photo' => array('appgini' => 'VARCHAR(40) '),
			'status' => array('appgini' => 'VARCHAR(40) not null '),
			'size' => array('appgini' => 'VARCHAR(40) '),
			'country' => array('appgini' => 'INT unsigned '),
			'street' => array('appgini' => 'INT unsigned '),
			'city' => array('appgini' => 'INT unsigned '),
			'state' => array('appgini' => 'INT unsigned '),
			'postal_code' => array('appgini' => 'INT unsigned '),
			'rooms' => array('appgini' => 'VARCHAR(40) '),
			'bathroom' => array('appgini' => 'DECIMAL(15,0) '),
			'features' => array('appgini' => 'TEXT '),
			'market_rent' => array('appgini' => 'DECIMAL(15,0) '),
			'rental_amount' => array('appgini' => 'DECIMAL(6,2) '),
			'deposit_amount' => array('appgini' => 'DECIMAL(6,2) '),
			'description' => array('appgini' => 'TEXT ')
		),
		'unit_photos' => array(   
			'id' => array('appgini' => 'INT unsigned not null primary key auto_increment '),
			'unit' => array('appgini' => 'INT unsigned '),
			'photo' => array('appgini' => 'VARCHAR(40) '),
			'description' => array('appgini' => 'TEXT ')
		)
	);

	$table_captions = getTableList();

	/* function for preparing field definition for comparison */
	function prepare_def($def){
		$def = trim($def);
		$def = strtolower($def);

		/* ignore length for int data types */
		$def = preg_replace('/int\w*\([0-9]+\)/', 'int', $def);

		/* make sure there is always a space before mysql words */
		$def = preg_replace('/(\S)(unsigned|not null|binary|zerofill|auto_increment|default)/', '$1 $2', $def);

		/* treat 0.000.. same as 0 */
		$def = preg_replace('/([0-9])*\.0+/', '$1', $def);

		/* treat unsigned zerofill same as zerofill */
		$def = str_ireplace('unsigned zerofill', 'zerofill', $def);

		/* ignore zero-padding for date data types */
		$def = preg_replace("/date\s*default\s*'([0-9]{4})-0?([1-9])-0?([1-9])'/i", "date default '$1-$2-$3'", $def);

		return $def;
	}

	/**
	 *  @brief creates/fixes given field according to given schema
	 *  @return integer: 0 = error, 1 = field updated, 2 = field created
	 */
	function fix_field($fix_table, $fix_field, $schema, &$qry){
		if(!isset($schema[$fix_table][$fix_field])) return 0;

		$def = $schema[$fix_table][$fix_field];
		$field_added = $field_updated = false;
		$eo['silentErrors'] = true;

		// field exists?
		$res = sql("show columns from `{$fix_table}` like '{$fix_field}'", $eo);
		if($row = db_fetch_assoc($res)){
			// modify field
			$qry = "alter table `{$fix_table}` modify `{$fix_field}` {$def['appgini']}";
			sql($qry, $eo);

			// remove unique from db if necessary
			if($row['Key'] == 'UNI' && !stripos($def['appgini'], ' unique')){
				// retrieve unique index name
				$res_unique = sql("show index from `{$fix_table}` where Column_name='{$fix_field}' and Non_unique=0", $eo);
				if($row_unique = db_fetch_assoc($res_unique)){
					$qry_unique = "drop index `{$row_unique['Key_name']}` on `{$fix_table}`";
					sql($qry_unique, $eo);
					$qry .= ";\n{$qry_unique}";
				}
			}

			return 1;
		}

		// create field
		$qry = "alter table `{$fix_table}` add column `{$fix_field}` {$schema[$fix_table][$fix_field]['appgini']}";
		sql($qry, $eo);
		return 2;
	}

	/* process requested fixes */
	$fix_table = (isset($_GET['t']) ? $_GET['t'] : false);
	$fix_field = (isset($_GET['f']) ? $_GET['f'] : false);
	$fix_all = (isset($_GET['all']) ? true : false);

	if($fix_field && $fix_table) $fix_status = fix_field($fix_table, $fix_field, $schema, $qry);

	/* retrieve actual db schema */
	foreach($table_captions as $tn => $tc){
		$eo['silentErrors'] = true;
		$res = sql("show columns from `{$tn}`", $eo);
		if($res){
			while($row = db_fetch_assoc($res)){
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
				if(isset($schema[$tn][$row['Field']])){
					$schema[$tn][$row['Field']]['db'] = $field_description;
				}
			}
		}
	}

	/* handle fix_all request */
	if($fix_all){
		foreach($schema as $tn => $fields){
			foreach($fields as $fn => $fd){
				if(prepare_def($fd['appgini']) == prepare_def($fd['db'])) continue;
				fix_field($tn, $fn, $schema, $qry);
			}
		}

		redirect('admin/pageRebuildFields.php');
		exit;
	}
?>

<?php if($fix_status == 1 || $fix_status == 2){ ?>
	<div class="alert alert-info alert-dismissable">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<i class="glyphicon glyphicon-info-sign"></i>
		<?php 
			$originalValues = array('<ACTION>', '<FIELD>', '<TABLE>', '<QUERY>');
			$action = ($fix_status == 2 ? 'create' : 'update');
			$replaceValues = array($action, $fix_field, $fix_table, $qry);
			echo str_replace($originalValues, $replaceValues, $Translation['create or update table']);
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
<table class="table table-responsive table-hover table-striped">
	<thead><tr>
		<th></th>
		<th><?php echo $Translation['field'] ; ?></th>
		<th><?php echo $Translation['AppGini definition'] ; ?></th>
		<th><?php echo $Translation['database definition'] ; ?></th>
		<th id="fix_all"></th>
	</tr></thead>

	<tbody>
	<?php foreach($schema as $tn => $fields){ ?>
		<tr class="text-info"><td colspan="5"><h4 data-placement="left" data-toggle="tooltip" title="<?php echo str_replace ( "<TABLENAME>" , $tn , $Translation['table name title']) ; ?>"><i class="glyphicon glyphicon-th-list"></i> <?php echo $table_captions[$tn]; ?></h4></td></tr>
		<?php foreach($fields as $fn => $fd){ ?>
			<?php $diff = ((prepare_def($fd['appgini']) == prepare_def($fd['db'])) ? false : true); ?>
			<?php $no_db = ($fd['db'] ? false : true); ?>
			<tr class="<?php echo ($diff ? 'warning' : 'field_ok'); ?>">
				<td><i class="glyphicon glyphicon-<?php echo ($diff ? 'remove text-danger' : 'ok text-success'); ?>"></i></td>
				<td><?php echo $fn; ?></td>
				<td class="<?php echo ($diff ? 'bold text-success' : ''); ?>"><?php echo $fd['appgini']; ?></td>
				<td class="<?php echo ($diff ? 'bold text-danger' : ''); ?>"><?php echo thisOr($fd['db'], $Translation['does not exist']); ?></td>
				<td>
					<?php if($diff && $no_db){ ?>
						<a href="pageRebuildFields.php?t=<?php echo $tn; ?>&f=<?php echo $fn; ?>" class="btn btn-success btn-xs btn_create" data-toggle="tooltip" data-placement="top" title="<?php echo $Translation['create field'] ; ?>"><i class="glyphicon glyphicon-plus"></i> <?php echo $Translation['create it'] ; ?></a>
					<?php }elseif($diff){ ?>
						<a href="pageRebuildFields.php?t=<?php echo $tn; ?>&f=<?php echo $fn; ?>" class="btn btn-warning btn-xs btn_update" data-toggle="tooltip" title="<?php echo $Translation['fix field'] ; ?>"><i class="glyphicon glyphicon-cog"></i> <?php echo $Translation['fix it'] ; ?></a>
					<?php } ?>
				</td>
			</tr>
		<?php } ?>
	<?php } ?>
	</tbody>
</table>
<div class="alert summary"></div>

<style>
	.bold{ font-weight: bold; }
	[data-toggle="tooltip"]{ display: block !important; }
</style>

<script>
	$j(function(){
		$j('[data-toggle="tooltip"]').tooltip();

		$j('#show_deviations_only').click(function(){
			$j(this).addClass('hidden');
			$j('#show_all_fields').removeClass('hidden');
			$j('.field_ok').hide();
		});

		$j('#show_all_fields').click(function(){
			$j(this).addClass('hidden');
			$j('#show_deviations_only').removeClass('hidden');
			$j('.field_ok').show();
		});

		$j('.btn_update, #fix_all').click(function(){
			return confirm("<?php echo $Translation['field update warning'] ; ?>");
		});

		var count_updates = $j('.btn_update').length;
		var count_creates = $j('.btn_create').length;
		if(!count_creates && !count_updates){
			$j('.summary').addClass('alert-success').html("<?php echo $Translation['no deviations found'] ; ?>");
		}else{
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

<?php
	include("{$currDir}/incFooter.php");
?>
