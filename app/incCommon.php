<?php

	#########################################################
	/*
	~~~~~~ LIST OF FUNCTIONS ~~~~~~
		get_table_groups() -- returns an associative array (table_group => tables_array)
		getTablePermissions($tn) -- returns an array of permissions allowed for logged member to given table (allowAccess, allowInsert, allowView, allowEdit, allowDelete) -- allowAccess is set to true if any access level is allowed
		get_sql_fields($tn) -- returns the SELECT part of the table view query
		get_sql_from($tn[, true, [, false]]) -- returns the FROM part of the table view query, with full joins (unless third paramaeter is set to true), optionally skipping permissions if true passed as 2nd param.
		get_joined_record($table, $id[, true]) -- returns assoc array of record values for given PK value of given table, with full joins, optionally skipping permissions if true passed as 3rd param.
		get_defaults($table) -- returns assoc array of table fields as array keys and default values (or empty), excluding automatic values as array values
		htmlUserBar() -- returns html code for displaying user login status to be used on top of pages.
		showNotifications($msg, $class) -- returns html code for displaying a notification. If no parameters provided, processes the GET request for possible notifications.
		parseMySQLDate(a, b) -- returns a if valid mysql date, or b if valid mysql date, or today if b is true, or empty if b is false.
		parseCode(code) -- calculates and returns special values to be inserted in automatic fields.
		addFilter(i, filterAnd, filterField, filterOperator, filterValue) -- enforce a filter over data
		clearFilters() -- clear all filters
		loadView($view, $data) -- passes $data to templates/{$view}.php and returns the output
		loadTable($table, $data) -- loads table template, passing $data to it
		br2nl($text) -- replaces all variations of HTML <br> tags with a new line character
		entitiesToUTF8($text) -- convert unicode entities (e.g. &#1234;) to actual UTF8 characters, requires multibyte string PHP extension
		func_get_args_byref() -- returns an array of arguments passed to a function, by reference
		permissions_sql($table, $level) -- returns an array containing the FROM and WHERE additions for applying permissions to an SQL query
		error_message($msg[, $back_url]) -- returns html code for a styled error message .. pass explicit false in second param to suppress back button
		toMySQLDate($formattedDate, $sep = datalist_date_separator, $ord = datalist_date_format)
		reIndex(&$arr) -- returns a copy of the given array, with keys replaced by 1-based numeric indices, and values replaced by original keys
		get_embed($provider, $url[, $width, $height, $retrieve]) -- returns embed code for a given url (supported providers: youtube, googlemap)
		check_record_permission($table, $id, $perm = 'view') -- returns true if current user has the specified permission $perm ('view', 'edit' or 'delete') for the given recors, false otherwise
		NavMenus($options) -- returns the HTML code for the top navigation menus. $options is not implemented currently.
		StyleSheet() -- returns the HTML code for included style sheet files to be placed in the <head> section.
		getUploadDir($dir) -- if dir is empty, returns upload dir configured in defaultLang.php, else returns $dir.
		PrepareUploadedFile($FieldName, $MaxSize, $FileTypes={image file types}, $NoRename=false, $dir="") -- validates and moves uploaded file for given $FieldName into the given $dir (or the default one if empty)
		get_home_links($homeLinks, $default_classes, $tgroup) -- process $homeLinks array and return custom links for homepage. Applies $default_classes to links if links have classes defined, and filters links by $tgroup (using '*' matches all table_group values)
		quick_search_html($search_term, $label, $separate_dv = true) -- returns HTML code for the quick search box.
	~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	*/

	#########################################################

	function get_table_groups($skip_authentication = false) {
		$tables = getTableList($skip_authentication);
		$all_groups = ['Leases', 'Assets/Setup'];

		$groups = [];
		foreach($all_groups as $grp) {
			foreach($tables as $tn => $td) {
				if($td[3] && $td[3] == $grp) $groups[$grp][] = $tn;
				if(!$td[3]) $groups[0][] = $tn;
			}
		}

		return $groups;
	}

	#########################################################

	function getTablePermissions($tn) {
		static $table_permissions = [];
		if(isset($table_permissions[$tn])) return $table_permissions[$tn];

		$groupID = getLoggedGroupID();
		$memberID = makeSafe(getLoggedMemberID());
		$res_group = sql("SELECT `tableName`, `allowInsert`, `allowView`, `allowEdit`, `allowDelete` FROM `membership_grouppermissions` WHERE `groupID`='{$groupID}'", $eo);
		$res_user  = sql("SELECT `tableName`, `allowInsert`, `allowView`, `allowEdit`, `allowDelete` FROM `membership_userpermissions`  WHERE LCASE(`memberID`)='{$memberID}'", $eo);

		while($row = db_fetch_assoc($res_group)) {
			$table_permissions[$row['tableName']] = [
				1 => intval($row['allowInsert']),
				2 => intval($row['allowView']),
				3 => intval($row['allowEdit']),
				4 => intval($row['allowDelete']),
				'insert' => intval($row['allowInsert']),
				'view' => intval($row['allowView']),
				'edit' => intval($row['allowEdit']),
				'delete' => intval($row['allowDelete'])
			];
		}

		// user-specific permissions, if specified, overwrite his group permissions
		while($row = db_fetch_assoc($res_user)) {
			$table_permissions[$row['tableName']] = [
				1 => intval($row['allowInsert']),
				2 => intval($row['allowView']),
				3 => intval($row['allowEdit']),
				4 => intval($row['allowDelete']),
				'insert' => intval($row['allowInsert']),
				'view' => intval($row['allowView']),
				'edit' => intval($row['allowEdit']),
				'delete' => intval($row['allowDelete'])
			];
		}

		// if user has any type of access, set 'access' flag
		foreach($table_permissions as $t => $p) {
			$table_permissions[$t]['access'] = $table_permissions[$t][0] = false;

			if($p['insert'] || $p['view'] || $p['edit'] || $p['delete']) {
				$table_permissions[$t]['access'] = $table_permissions[$t][0] = true;
			}
		}

		return $table_permissions[$tn] ?? [];
	}

	#########################################################

	function get_sql_fields($table_name) {
		$sql_fields = [
			'applicants_and_tenants' => "`applicants_and_tenants`.`id` as 'id', `applicants_and_tenants`.`last_name` as 'last_name', `applicants_and_tenants`.`first_name` as 'first_name', `applicants_and_tenants`.`email` as 'email', CONCAT_WS('-', LEFT(`applicants_and_tenants`.`phone`,3), MID(`applicants_and_tenants`.`phone`,4,3), RIGHT(`applicants_and_tenants`.`phone`,4)) as 'phone', if(`applicants_and_tenants`.`birth_date`,date_format(`applicants_and_tenants`.`birth_date`,'%m/%d/%Y'),'') as 'birth_date', `applicants_and_tenants`.`driver_license_number` as 'driver_license_number', `applicants_and_tenants`.`driver_license_state` as 'driver_license_state', `applicants_and_tenants`.`requested_lease_term` as 'requested_lease_term', CONCAT('$', FORMAT(`applicants_and_tenants`.`monthly_gross_pay`, 2)) as 'monthly_gross_pay', CONCAT('$', FORMAT(`applicants_and_tenants`.`additional_income`, 2)) as 'additional_income', CONCAT('$', FORMAT(`applicants_and_tenants`.`assets`, 2)) as 'assets', `applicants_and_tenants`.`status` as 'status', `applicants_and_tenants`.`notes` as 'notes'",
			'applications_leases' => "`applications_leases`.`id` as 'id', IF(    CHAR_LENGTH(`applicants_and_tenants1`.`first_name`) || CHAR_LENGTH(`applicants_and_tenants1`.`last_name`), CONCAT_WS('',   `applicants_and_tenants1`.`first_name`, ' ', `applicants_and_tenants1`.`last_name`), '') as 'tenants', `applications_leases`.`status` as 'status', IF(    CHAR_LENGTH(`properties1`.`property_name`), CONCAT_WS('',   `properties1`.`property_name`), '') as 'property', IF(    CHAR_LENGTH(`units1`.`unit_number`), CONCAT_WS('',   `units1`.`unit_number`), '') as 'unit', `applications_leases`.`type` as 'type', `applications_leases`.`total_number_of_occupants` as 'total_number_of_occupants', if(`applications_leases`.`start_date`,date_format(`applications_leases`.`start_date`,'%m/%d/%Y'),'') as 'start_date', if(`applications_leases`.`end_date`,date_format(`applications_leases`.`end_date`,'%m/%d/%Y'),'') as 'end_date', `applications_leases`.`recurring_charges_frequency` as 'recurring_charges_frequency', if(`applications_leases`.`next_due_date`,date_format(`applications_leases`.`next_due_date`,'%m/%d/%Y'),'') as 'next_due_date', CONCAT('$', FORMAT(`applications_leases`.`rent`, 2)) as 'rent', CONCAT('$', FORMAT(`applications_leases`.`security_deposit`, 2)) as 'security_deposit', if(`applications_leases`.`security_deposit_date`,date_format(`applications_leases`.`security_deposit_date`,'%m/%d/%Y'),'') as 'security_deposit_date', `applications_leases`.`emergency_contact` as 'emergency_contact', `applications_leases`.`co_signer_details` as 'co_signer_details', `applications_leases`.`notes` as 'notes', `applications_leases`.`agreement` as 'agreement'",
			'residence_and_rental_history' => "`residence_and_rental_history`.`id` as 'id', IF(    CHAR_LENGTH(`applicants_and_tenants1`.`first_name`) || CHAR_LENGTH(`applicants_and_tenants1`.`last_name`), CONCAT_WS('',   `applicants_and_tenants1`.`first_name`, ' ', `applicants_and_tenants1`.`last_name`), '') as 'tenant', `residence_and_rental_history`.`address` as 'address', `residence_and_rental_history`.`landlord_or_manager_name` as 'landlord_or_manager_name', `residence_and_rental_history`.`landlord_or_manager_phone` as 'landlord_or_manager_phone', CONCAT('$', FORMAT(`residence_and_rental_history`.`monthly_rent`, 2)) as 'monthly_rent', if(`residence_and_rental_history`.`duration_of_residency_from`,date_format(`residence_and_rental_history`.`duration_of_residency_from`,'%m/%d/%Y'),'') as 'duration_of_residency_from', if(`residence_and_rental_history`.`to`,date_format(`residence_and_rental_history`.`to`,'%m/%d/%Y'),'') as 'to', `residence_and_rental_history`.`reason_for_leaving` as 'reason_for_leaving', `residence_and_rental_history`.`notes` as 'notes'",
			'employment_and_income_history' => "`employment_and_income_history`.`id` as 'id', IF(    CHAR_LENGTH(`applicants_and_tenants1`.`first_name`) || CHAR_LENGTH(`applicants_and_tenants1`.`last_name`), CONCAT_WS('',   `applicants_and_tenants1`.`first_name`, ' ', `applicants_and_tenants1`.`last_name`), '') as 'tenant', `employment_and_income_history`.`employer_name` as 'employer_name', `employment_and_income_history`.`city` as 'city', CONCAT_WS('-', LEFT(`employment_and_income_history`.`employer_phone`,3), MID(`employment_and_income_history`.`employer_phone`,4,3), RIGHT(`employment_and_income_history`.`employer_phone`,4)) as 'employer_phone', if(`employment_and_income_history`.`employed_from`,date_format(`employment_and_income_history`.`employed_from`,'%m/%d/%Y'),'') as 'employed_from', if(`employment_and_income_history`.`employed_till`,date_format(`employment_and_income_history`.`employed_till`,'%m/%d/%Y'),'') as 'employed_till', `employment_and_income_history`.`occupation` as 'occupation', `employment_and_income_history`.`notes` as 'notes'",
			'references' => "`references`.`id` as 'id', IF(    CHAR_LENGTH(`applicants_and_tenants1`.`first_name`) || CHAR_LENGTH(`applicants_and_tenants1`.`last_name`), CONCAT_WS('',   `applicants_and_tenants1`.`first_name`, ' ', `applicants_and_tenants1`.`last_name`), '') as 'tenant', `references`.`reference_name` as 'reference_name', CONCAT_WS('-', LEFT(`references`.`phone`,3), MID(`references`.`phone`,4,3), RIGHT(`references`.`phone`,4)) as 'phone'",
			'rental_owners' => "`rental_owners`.`id` as 'id', `rental_owners`.`first_name` as 'first_name', `rental_owners`.`last_name` as 'last_name', `rental_owners`.`company_name` as 'company_name', if(`rental_owners`.`date_of_birth`,date_format(`rental_owners`.`date_of_birth`,'%m/%d/%Y'),'') as 'date_of_birth', `rental_owners`.`primary_email` as 'primary_email', `rental_owners`.`alternate_email` as 'alternate_email', CONCAT_WS('-', LEFT(`rental_owners`.`phone`,3), MID(`rental_owners`.`phone`,4,3), RIGHT(`rental_owners`.`phone`,4)) as 'phone', `rental_owners`.`country` as 'country', `rental_owners`.`street` as 'street', `rental_owners`.`city` as 'city', `rental_owners`.`state` as 'state', `rental_owners`.`zip` as 'zip', `rental_owners`.`comments` as 'comments'",
			'properties' => "`properties`.`id` as 'id', `properties`.`property_name` as 'property_name', `properties`.`photo` as 'photo', `properties`.`type` as 'type', `properties`.`number_of_units` as 'number_of_units', IF(    CHAR_LENGTH(`rental_owners1`.`first_name`) || CHAR_LENGTH(`rental_owners1`.`last_name`), CONCAT_WS('',   `rental_owners1`.`first_name`, ' ', `rental_owners1`.`last_name`), '') as 'owner', `properties`.`operating_account` as 'operating_account', CONCAT('$', FORMAT(`properties`.`property_reserve`, 2)) as 'property_reserve', `properties`.`lease_term` as 'lease_term', `properties`.`country` as 'country', `properties`.`street` as 'street', `properties`.`City` as 'City', `properties`.`State` as 'State', `properties`.`ZIP` as 'ZIP'",
			'property_photos' => "`property_photos`.`id` as 'id', IF(    CHAR_LENGTH(`properties1`.`property_name`), CONCAT_WS('',   `properties1`.`property_name`), '') as 'property', `property_photos`.`photo` as 'photo', `property_photos`.`description` as 'description'",
			'units' => "`units`.`id` as 'id', IF(    CHAR_LENGTH(`properties1`.`property_name`), CONCAT_WS('',   `properties1`.`property_name`), '') as 'property', `units`.`unit_number` as 'unit_number', `units`.`photo` as 'photo', `units`.`status` as 'status', `units`.`size` as 'size', IF(    CHAR_LENGTH(`properties1`.`country`), CONCAT_WS('',   `properties1`.`country`), '') as 'country', IF(    CHAR_LENGTH(`properties1`.`street`), CONCAT_WS('',   `properties1`.`street`), '') as 'street', IF(    CHAR_LENGTH(`properties1`.`City`), CONCAT_WS('',   `properties1`.`City`), '') as 'city', IF(    CHAR_LENGTH(`properties1`.`State`), CONCAT_WS('',   `properties1`.`State`), '') as 'state', IF(    CHAR_LENGTH(`properties1`.`ZIP`), CONCAT_WS('',   `properties1`.`ZIP`), '') as 'postal_code', `units`.`rooms` as 'rooms', `units`.`bathroom` as 'bathroom', `units`.`features` as 'features', FORMAT(`units`.`market_rent`, 0) as 'market_rent', CONCAT('$', FORMAT(`units`.`rental_amount`, 2)) as 'rental_amount', CONCAT('$', FORMAT(`units`.`deposit_amount`, 2)) as 'deposit_amount', `units`.`description` as 'description'",
			'unit_photos' => "`unit_photos`.`id` as 'id', IF(    CHAR_LENGTH(`properties1`.`property_name`) || CHAR_LENGTH(`units1`.`unit_number`), CONCAT_WS('',   `properties1`.`property_name`, ' - unit# ', `units1`.`unit_number`), '') as 'unit', `unit_photos`.`photo` as 'photo', `unit_photos`.`description` as 'description'",
		];

		if(isset($sql_fields[$table_name])) return $sql_fields[$table_name];

		return false;
	}

	#########################################################

	function get_sql_from($table_name, $skip_permissions = false, $skip_joins = false, $lower_permissions = false) {
		$sql_from = [
			'applicants_and_tenants' => "`applicants_and_tenants` ",
			'applications_leases' => "`applications_leases` LEFT JOIN `applicants_and_tenants` as applicants_and_tenants1 ON `applicants_and_tenants1`.`id`=`applications_leases`.`tenants` LEFT JOIN `properties` as properties1 ON `properties1`.`id`=`applications_leases`.`property` LEFT JOIN `units` as units1 ON `units1`.`id`=`applications_leases`.`unit` ",
			'residence_and_rental_history' => "`residence_and_rental_history` LEFT JOIN `applicants_and_tenants` as applicants_and_tenants1 ON `applicants_and_tenants1`.`id`=`residence_and_rental_history`.`tenant` ",
			'employment_and_income_history' => "`employment_and_income_history` LEFT JOIN `applicants_and_tenants` as applicants_and_tenants1 ON `applicants_and_tenants1`.`id`=`employment_and_income_history`.`tenant` ",
			'references' => "`references` LEFT JOIN `applicants_and_tenants` as applicants_and_tenants1 ON `applicants_and_tenants1`.`id`=`references`.`tenant` ",
			'rental_owners' => "`rental_owners` ",
			'properties' => "`properties` LEFT JOIN `rental_owners` as rental_owners1 ON `rental_owners1`.`id`=`properties`.`owner` ",
			'property_photos' => "`property_photos` LEFT JOIN `properties` as properties1 ON `properties1`.`id`=`property_photos`.`property` ",
			'units' => "`units` LEFT JOIN `properties` as properties1 ON `properties1`.`id`=`units`.`property` ",
			'unit_photos' => "`unit_photos` LEFT JOIN `units` as units1 ON `units1`.`id`=`unit_photos`.`unit` LEFT JOIN `properties` as properties1 ON `properties1`.`id`=`units1`.`property` ",
		];

		$pkey = [
			'applicants_and_tenants' => 'id',
			'applications_leases' => 'id',
			'residence_and_rental_history' => 'id',
			'employment_and_income_history' => 'id',
			'references' => 'id',
			'rental_owners' => 'id',
			'properties' => 'id',
			'property_photos' => 'id',
			'units' => 'id',
			'unit_photos' => 'id',
		];

		if(!isset($sql_from[$table_name])) return false;

		$from = ($skip_joins ? "`{$table_name}`" : $sql_from[$table_name]);

		if($skip_permissions) return $from . ' WHERE 1=1';

		// mm: build the query based on current member's permissions
		// allowing lower permissions if $lower_permissions set to 'user' or 'group'
		$perm = getTablePermissions($table_name);
		if($perm['view'] == 1 || ($perm['view'] > 1 && $lower_permissions == 'user')) { // view owner only
			$from .= ", `membership_userrecords` WHERE `{$table_name}`.`{$pkey[$table_name]}`=`membership_userrecords`.`pkValue` AND `membership_userrecords`.`tableName`='{$table_name}' AND LCASE(`membership_userrecords`.`memberID`)='" . getLoggedMemberID() . "'";
		} elseif($perm['view'] == 2 || ($perm['view'] > 2 && $lower_permissions == 'group')) { // view group only
			$from .= ", `membership_userrecords` WHERE `{$table_name}`.`{$pkey[$table_name]}`=`membership_userrecords`.`pkValue` AND `membership_userrecords`.`tableName`='{$table_name}' AND `membership_userrecords`.`groupID`='" . getLoggedGroupID() . "'";
		} elseif($perm['view'] == 3) { // view all
			$from .= ' WHERE 1=1';
		} else { // view none
			return false;
		}

		return $from;
	}

	#########################################################

	function get_joined_record($table, $id, $skip_permissions = false) {
		$sql_fields = get_sql_fields($table);
		$sql_from = get_sql_from($table, $skip_permissions);

		if(!$sql_fields || !$sql_from) return false;

		$pk = getPKFieldName($table);
		if(!$pk) return false;

		$safe_id = makeSafe($id, false);
		$sql = "SELECT {$sql_fields} FROM {$sql_from} AND `{$table}`.`{$pk}`='{$safe_id}'";
		$eo = ['silentErrors' => true];
		$res = sql($sql, $eo);
		if($row = db_fetch_assoc($res)) return $row;

		return false;
	}

	#########################################################

	function get_defaults($table) {
		/* array of tables and their fields, with default values (or empty), excluding automatic values */
		$defaults = [
			'applicants_and_tenants' => [
				'id' => '',
				'last_name' => '',
				'first_name' => '',
				'email' => '',
				'phone' => '',
				'birth_date' => '',
				'driver_license_number' => '',
				'driver_license_state' => '',
				'requested_lease_term' => '',
				'monthly_gross_pay' => '',
				'additional_income' => '',
				'assets' => '',
				'status' => 'Applicant',
				'notes' => '',
			],
			'applications_leases' => [
				'id' => '',
				'tenants' => '',
				'status' => 'Application',
				'property' => '',
				'unit' => '',
				'type' => 'Fixed',
				'total_number_of_occupants' => '',
				'start_date' => '1',
				'end_date' => '1',
				'recurring_charges_frequency' => 'Monthly',
				'next_due_date' => '1',
				'rent' => '',
				'security_deposit' => '',
				'security_deposit_date' => '',
				'emergency_contact' => '',
				'co_signer_details' => '',
				'notes' => '',
				'agreement' => '',
			],
			'residence_and_rental_history' => [
				'id' => '',
				'tenant' => '',
				'address' => '',
				'landlord_or_manager_name' => '',
				'landlord_or_manager_phone' => '',
				'monthly_rent' => '',
				'duration_of_residency_from' => '',
				'to' => '',
				'reason_for_leaving' => '',
				'notes' => '',
			],
			'employment_and_income_history' => [
				'id' => '',
				'tenant' => '',
				'employer_name' => '',
				'city' => '',
				'employer_phone' => '',
				'employed_from' => '',
				'employed_till' => '',
				'occupation' => '',
				'notes' => '',
			],
			'references' => [
				'id' => '',
				'tenant' => '',
				'reference_name' => '',
				'phone' => '',
			],
			'rental_owners' => [
				'id' => '',
				'first_name' => '',
				'last_name' => '',
				'company_name' => '',
				'date_of_birth' => '',
				'primary_email' => '',
				'alternate_email' => '',
				'phone' => '',
				'country' => '',
				'street' => '',
				'city' => '',
				'state' => '',
				'zip' => '',
				'comments' => '',
			],
			'properties' => [
				'id' => '',
				'property_name' => '',
				'photo' => '',
				'type' => '',
				'number_of_units' => '',
				'owner' => '',
				'operating_account' => '',
				'property_reserve' => '',
				'lease_term' => '',
				'country' => '',
				'street' => '',
				'City' => '',
				'State' => '',
				'ZIP' => '',
			],
			'property_photos' => [
				'id' => '',
				'property' => '',
				'photo' => '',
				'description' => '',
			],
			'units' => [
				'id' => '',
				'property' => '',
				'unit_number' => '',
				'photo' => '',
				'status' => '',
				'size' => '',
				'country' => '',
				'street' => '',
				'city' => '',
				'state' => '',
				'postal_code' => '',
				'rooms' => '',
				'bathroom' => '',
				'features' => '',
				'market_rent' => '',
				'rental_amount' => '',
				'deposit_amount' => '',
				'description' => '',
			],
			'unit_photos' => [
				'id' => '',
				'unit' => '',
				'photo' => '',
				'description' => '',
			],
		];

		return isset($defaults[$table]) ? $defaults[$table] : [];
	}

	#########################################################

	function htmlUserBar() {
		global $Translation;
		if(!defined('PREPEND_PATH')) define('PREPEND_PATH', '');

		$mi = getMemberInfo();
		$adminConfig = config('adminConfig');
		$home_page = (basename($_SERVER['PHP_SELF']) == 'index.php');
		ob_start();

		?>
		<nav class="navbar navbar-default navbar-fixed-top hidden-print" role="navigation">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<!-- application title is obtained from the name besides the yellow database icon in AppGini, use underscores for spaces -->
				<a class="navbar-brand" href="<?php echo PREPEND_PATH; ?>index.php"><i class="glyphicon glyphicon-home"></i> <?php echo APP_TITLE; ?></a>
			</div>
			<div class="collapse navbar-collapse">
				<ul class="nav navbar-nav"><?php echo ($home_page ? '' : NavMenus()); ?></ul>

				<?php if(userCanImport()){ ?>
					<ul class="nav navbar-nav">
						<a href="<?php echo PREPEND_PATH; ?>import-csv.php" class="btn btn-default navbar-btn hidden-xs btn-import-csv" title="<?php echo html_attr($Translation['import csv file']); ?>"><i class="glyphicon glyphicon-th"></i> <?php echo $Translation['import CSV']; ?></a>
						<a href="<?php echo PREPEND_PATH; ?>import-csv.php" class="btn btn-default navbar-btn visible-xs btn-lg btn-import-csv" title="<?php echo html_attr($Translation['import csv file']); ?>"><i class="glyphicon glyphicon-th"></i> <?php echo $Translation['import CSV']; ?></a>
					</ul>
				<?php } ?>

				<?php if(getLoggedAdmin() !== false) { ?>
					<ul class="nav navbar-nav">
						<a href="<?php echo PREPEND_PATH; ?>admin/pageHome.php" class="btn btn-danger navbar-btn hidden-xs" title="<?php echo html_attr($Translation['admin area']); ?>"><i class="glyphicon glyphicon-cog"></i> <?php echo $Translation['admin area']; ?></a>
						<a href="<?php echo PREPEND_PATH; ?>admin/pageHome.php" class="btn btn-danger navbar-btn visible-xs btn-lg" title="<?php echo html_attr($Translation['admin area']); ?>"><i class="glyphicon glyphicon-cog"></i> <?php echo $Translation['admin area']; ?></a>
					</ul>
				<?php } ?>

				<?php if(!Request::val('signIn') && !Request::val('loginFailed')) { ?>
					<?php if(!$mi['username'] || $mi['username'] == $adminConfig['anonymousMember']) { ?>
						<p class="navbar-text navbar-right hidden-xs">&nbsp;</p>
						<a href="<?php echo PREPEND_PATH; ?>index.php?signIn=1" class="btn btn-success navbar-btn navbar-right hidden-xs"><?php echo $Translation['sign in']; ?></a>
						<p class="navbar-text navbar-right hidden-xs">
							<?php echo $Translation['not signed in']; ?>
						</p>
						<a href="<?php echo PREPEND_PATH; ?>index.php?signIn=1" class="btn btn-success btn-block btn-lg navbar-btn visible-xs">
							<?php echo $Translation['not signed in']; ?>
							<i class="glyphicon glyphicon-chevron-right"></i> 
							<?php echo $Translation['sign in']; ?>
						</a>
					<?php } else { ?>
						<ul class="nav navbar-nav navbar-right hidden-xs">
							<!-- logged user profile menu -->
							<li class="dropdown" title="<?php echo html_attr("{$Translation['signed as']} {$mi['username']}"); ?>">
								<a href="#" class="dropdown-toggle profile-menu-icon" data-toggle="dropdown"><i class="glyphicon glyphicon-user icon"></i><span class="profile-menu-text"><?php echo $mi['username']; ?></span><b class="caret"></b></a>
								<ul class="dropdown-menu profile-menu">
									<li class="user-profile-menu-item" title="<?php echo html_attr("{$Translation['Your info']}"); ?>">
										<a href="<?php echo PREPEND_PATH; ?>membership_profile.php"><i class="glyphicon glyphicon-user"></i> <span class="username"><?php echo $mi['username']; ?></span></a>
									</li>
									<li class="keyboard-shortcuts-menu-item" title="<?php echo html_attr("{$Translation['keyboard shortcuts']}"); ?>" class="hidden-xs">
										<a href="#" class="help-shortcuts-launcher">
											<img src="<?php echo PREPEND_PATH; ?>resources/images/keyboard.png">
											<?php echo html_attr($Translation['keyboard shortcuts']); ?>
										</a>
									</li>
									<li class="sign-out-menu-item" title="<?php echo html_attr("{$Translation['sign out']}"); ?>">
										<a href="<?php echo PREPEND_PATH; ?>index.php?signOut=1"><i class="glyphicon glyphicon-log-out"></i> <?php echo $Translation['sign out']; ?></a>
									</li>
								</ul>
							</li>
						</ul>
						<ul class="nav navbar-nav visible-xs">
							<a class="btn navbar-btn btn-default btn-lg visible-xs" href="<?php echo PREPEND_PATH; ?>index.php?signOut=1"><i class="glyphicon glyphicon-log-out"></i> <?php echo $Translation['sign out']; ?></a>
							<p class="navbar-text text-center signed-in-as">
								<?php echo $Translation['signed as']; ?> <strong><a href="<?php echo PREPEND_PATH; ?>membership_profile.php" class="navbar-link username"><?php echo $mi['username']; ?></a></strong>
							</p>
						</ul>
						<script>
							/* periodically check if user is still signed in */
							setInterval(function() {
								$j.ajax({
									url: '<?php echo PREPEND_PATH; ?>ajax_check_login.php',
									success: function(username) {
										if(!username.length) window.location = '<?php echo PREPEND_PATH; ?>index.php?signIn=1';
									}
								});
							}, 60000);
						</script>
					<?php } ?>
				<?php } ?>
			</div>
		</nav>
		<?php

		return ob_get_clean();
	}

	#########################################################

	function showNotifications($msg = '', $class = '', $fadeout = true) {
		global $Translation;
		if($error_message = strip_tags(Request::val('error_message')))
			$error_message = '<div class="text-bold">' . $error_message . '</div>';

		if(!$msg) { // if no msg, use url to detect message to display
			if(Request::val('record-added-ok')) {
				$msg = $Translation['new record saved'];
				$class = 'alert-success';
			} elseif(Request::val('record-added-error')) {
				$msg = $Translation['Couldn\'t save the new record'] . $error_message;
				$class = 'alert-danger';
				$fadeout = false;
			} elseif(Request::val('record-updated-ok')) {
				$msg = $Translation['record updated'];
				$class = 'alert-success';
			} elseif(Request::val('record-updated-error')) {
				$msg = $Translation['Couldn\'t save changes to the record'] . $error_message;
				$class = 'alert-danger';
				$fadeout = false;
			} elseif(Request::val('record-deleted-ok')) {
				$msg = $Translation['The record has been deleted successfully'];
				$class = 'alert-success';
			} elseif(Request::val('record-deleted-error')) {
				$msg = $Translation['Couldn\'t delete this record'] . $error_message;
				$class = 'alert-danger';
				$fadeout = false;
			} else {
				return '';
			}
		}
		$id = 'notification-' . rand();

		ob_start();
		// notification template
		?>
		<div id="%%ID%%" class="alert alert-dismissable %%CLASS%%" style="opacity: 1; padding-top: 6px; padding-bottom: 6px; animation: fadeIn 1.5s ease-out; z-index: 100; position: relative;">
			<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
			%%MSG%%
		</div>
		<script>
			$j(function() {
				var autoDismiss = <?php echo $fadeout ? 'true' : 'false'; ?>,
					embedded = !$j('nav').length,
					messageDelay = 10, fadeDelay = 1.5;

				if(!autoDismiss) {
					if(embedded)
						$j('#%%ID%%').before('<div style="height: 2rem;"></div>');
					else
						$j('#%%ID%%').css({ margin: '0 0 1rem' });

					return;
				}

				// below code runs only in case of autoDismiss

				if(embedded)
					$j('#%%ID%%').css({ margin: '1rem 0 -1rem' });
				else
					$j('#%%ID%%').css({ margin: '-15px 0 -20px' });

				setTimeout(function() {
					$j('#%%ID%%').css({    animation: 'fadeOut ' + fadeDelay + 's ease-out' });
				}, messageDelay * 1000);

				setTimeout(function() {
					$j('#%%ID%%').css({    visibility: 'hidden' });
				}, (messageDelay + fadeDelay) * 1000);
			})
		</script>
		<style>
			@keyframes fadeIn {
				0%   { opacity: 0; }
				100% { opacity: 1; }
			}
			@keyframes fadeOut {
				0%   { opacity: 1; }
				100% { opacity: 0; }
			}
		</style>

		<?php
		$out = ob_get_clean();

		$out = str_replace('%%ID%%', $id, $out);
		$out = str_replace('%%MSG%%', $msg, $out);
		$out = str_replace('%%CLASS%%', $class, $out);

		return $out;
	}

	#########################################################

	function validMySQLDate($date) {
		$date = trim($date);

		try {
			$dtObj = new DateTime($date);
		} catch(Exception $e) {
			return false;
		}

		$parts = explode('-', $date);
		return (
			count($parts) == 3
			// see https://dev.mysql.com/doc/refman/8.0/en/datetime.html
			&& intval($parts[0]) >= 1000
			&& intval($parts[0]) <= 9999
			&& intval($parts[1]) >= 1
			&& intval($parts[1]) <= 12
			&& intval($parts[2]) >= 1
			&& intval($parts[2]) <= 31
		);
	}

	#########################################################

	function parseMySQLDate($date, $altDate) {
		// is $date valid?
		if(validMySQLDate($date)) return trim($date);

		if($date != '--' && validMySQLDate($altDate)) return trim($altDate);

		if($date != '--' && $altDate && is_numeric($altDate))
			return @date('Y-m-d', @time() + ($altDate >= 1 ? $altDate - 1 : $altDate) * 86400);

		return '';
	}

	#########################################################

	function parseCode($code, $isInsert = true, $rawData = false) {
		$mi = Authentication::getUser();

		if($isInsert) {
			$arrCodes = [
				'<%%creatorusername%%>' => $mi['username'],
				'<%%creatorgroupid%%>' => $mi['groupId'],
				'<%%creatorip%%>' => $_SERVER['REMOTE_ADDR'],
				'<%%creatorgroup%%>' => $mi['group'],

				'<%%creationdate%%>' => ($rawData ? date('Y-m-d') : date(app_datetime_format('phps'))),
				'<%%creationtime%%>' => ($rawData ? date('H:i:s') : date(app_datetime_format('phps', 't'))),
				'<%%creationdatetime%%>' => ($rawData ? date('Y-m-d H:i:s') : date(app_datetime_format('phps', 'dt'))),
				'<%%creationtimestamp%%>' => ($rawData ? date('Y-m-d H:i:s') : time()),
			];
		} else {
			$arrCodes = [
				'<%%editorusername%%>' => $mi['username'],
				'<%%editorgroupid%%>' => $mi['groupId'],
				'<%%editorip%%>' => $_SERVER['REMOTE_ADDR'],
				'<%%editorgroup%%>' => $mi['group'],

				'<%%editingdate%%>' => ($rawData ? date('Y-m-d') : date(app_datetime_format('phps'))),
				'<%%editingtime%%>' => ($rawData ? date('H:i:s') : date(app_datetime_format('phps', 't'))),
				'<%%editingdatetime%%>' => ($rawData ? date('Y-m-d H:i:s') : date(app_datetime_format('phps', 'dt'))),
				'<%%editingtimestamp%%>' => ($rawData ? date('Y-m-d H:i:s') : time()),
			];
		}

		$pc = str_ireplace(array_keys($arrCodes), array_values($arrCodes), $code);

		return $pc;
	}

	#########################################################

	function addFilter($index, $filterAnd, $filterField, $filterOperator, $filterValue) {
		// validate input
		if($index < 1 || $index > 80 || !is_int($index)) return false;
		if($filterAnd != 'or')   $filterAnd = 'and';
		$filterField = intval($filterField);

		/* backward compatibility */
		if(in_array($filterOperator, FILTER_OPERATORS)) {
			$filterOperator = array_search($filterOperator, FILTER_OPERATORS);
		}

		if(!in_array($filterOperator, array_keys(FILTER_OPERATORS))) {
			$filterOperator = 'like';
		}

		if(!$filterField) {
			$filterOperator = '';
			$filterValue = '';
		}

		$_REQUEST['FilterAnd'][$index] = $filterAnd;
		$_REQUEST['FilterField'][$index] = $filterField;
		$_REQUEST['FilterOperator'][$index] = $filterOperator;
		$_REQUEST['FilterValue'][$index] = $filterValue;

		return true;
	}

	#########################################################

	function clearFilters() {
		for($i=1; $i<=80; $i++) {
			addFilter($i, '', 0, '', '');
		}
	}

	#########################################################

	/**
	* Loads a given view from the templates folder, passing the given data to it
	* @param $view the name of a php file (without extension) to be loaded from the 'templates' folder
	* @param $the_data_to_pass_to_the_view (optional) associative array containing the data to pass to the view
	* @return string the output of the parsed view
	*/
	function loadView($view, $the_data_to_pass_to_the_view = false) {
		global $Translation;

		$view = __DIR__ . "/templates/$view.php";
		if(!is_file($view)) return false;

		if(is_array($the_data_to_pass_to_the_view)) {
			foreach($the_data_to_pass_to_the_view as $data_k => $data_v)
				$$data_k = $data_v;
		}
		unset($the_data_to_pass_to_the_view, $data_k, $data_v);

		ob_start();
		@include($view);
		return ob_get_clean();
	}

	#########################################################

	/**
	* Loads a table template from the templates folder, passing the given data to it
	* @param $table_name the name of the table whose template is to be loaded from the 'templates' folder
	* @param $the_data_to_pass_to_the_table associative array containing the data to pass to the table template
	* @return the output of the parsed table template as a string
	*/
	function loadTable($table_name, $the_data_to_pass_to_the_table = []) {
		$dont_load_header = $the_data_to_pass_to_the_table['dont_load_header'];
		$dont_load_footer = $the_data_to_pass_to_the_table['dont_load_footer'];

		$header = $table = $footer = '';

		if(!$dont_load_header) {
			// try to load tablename-header
			if(!($header = loadView("{$table_name}-header", $the_data_to_pass_to_the_table))) {
				$header = loadView('table-common-header', $the_data_to_pass_to_the_table);
			}
		}

		$table = loadView($table_name, $the_data_to_pass_to_the_table);

		if(!$dont_load_footer) {
			// try to load tablename-footer
			if(!($footer = loadView("{$table_name}-footer", $the_data_to_pass_to_the_table))) {
				$footer = loadView('table-common-footer', $the_data_to_pass_to_the_table);
			}
		}

		return "{$header}{$table}{$footer}";
	}

	#########################################################

	function br2nl($text) {
		return  preg_replace('/\<br(\s*)?\/?\>/i', "\n", $text);
	}

	#########################################################

	function entitiesToUTF8($input) {
		return preg_replace_callback('/(&#[0-9]+;)/', '_toUTF8', $input);
	}

	function _toUTF8($m) {
		if(function_exists('mb_convert_encoding')) {
			return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
		} else {
			return $m[1];
		}
	}

	#########################################################

	function func_get_args_byref() {
		if(!function_exists('debug_backtrace')) return false;

		$trace = debug_backtrace();
		return $trace[1]['args'];
	}

	#########################################################

	function permissions_sql($table, $level = 'all') {
		if(!in_array($level, ['user', 'group'])) { $level = 'all'; }
		$perm = getTablePermissions($table);
		$from = '';
		$where = '';
		$pk = getPKFieldName($table);

		if($perm['view'] == 1 || ($perm['view'] > 1 && $level == 'user')) { // view owner only
			$from = 'membership_userrecords';
			$where = "(`$table`.`$pk`=membership_userrecords.pkValue and membership_userrecords.tableName='$table' and lcase(membership_userrecords.memberID)='" . getLoggedMemberID() . "')";
		} elseif($perm['view'] == 2 || ($perm['view'] > 2 && $level == 'group')) { // view group only
			$from = 'membership_userrecords';
			$where = "(`$table`.`$pk`=membership_userrecords.pkValue and membership_userrecords.tableName='$table' and membership_userrecords.groupID='" . getLoggedGroupID() . "')";
		} elseif($perm['view'] == 3) { // view all
			// no further action
		} elseif($perm['view'] == 0) { // view none
			return false;
		}

		return ['where' => $where, 'from' => $from, 0 => $where, 1 => $from];
	}

	#########################################################

	function error_message($msg, $back_url = '', $full_page = true) {
		global $Translation;

		ob_start();

		if($full_page) include(__DIR__ . '/header.php');

		echo '<div class="panel panel-danger">';
			echo '<div class="panel-heading"><h3 class="panel-title">' . $Translation['error:'] . '</h3></div>';
			echo '<div class="panel-body"><p class="text-danger">' . $msg . '</p>';
			if($back_url !== false) { // explicitly passing false suppresses the back link completely
				echo '<div class="text-center">';
				if($back_url) {
					echo '<a href="' . $back_url . '" class="btn btn-danger btn-lg vspacer-lg"><i class="glyphicon glyphicon-chevron-left"></i> ' . $Translation['< back'] . '</a>';
				// in embedded mode, close modal window
				} elseif(Request::val('Embedded')) {
					echo '<button class="btn btn-danger btn-lg" type="button" onclick="AppGini.closeParentModal();"><i class="glyphicon glyphicon-chevron-left"></i> ' . $Translation['< back'] . '</button>';
				} else {
					echo '<a href="#" class="btn btn-danger btn-lg vspacer-lg" onclick="history.go(-1); return false;"><i class="glyphicon glyphicon-chevron-left"></i> ' . $Translation['< back'] . '</a>';
				}
				echo '</div>';
			}
			echo '</div>';
		echo '</div>';

		if($full_page) include(__DIR__ . '/footer.php');

		return ob_get_clean();
	}

	#########################################################

	function toMySQLDate($formattedDate, $sep = datalist_date_separator, $ord = datalist_date_format) {
		// extract date elements
		$de=explode($sep, $formattedDate);
		$mySQLDate=intval($de[strpos($ord, 'Y')]).'-'.intval($de[strpos($ord, 'm')]).'-'.intval($de[strpos($ord, 'd')]);
		return $mySQLDate;
	}

	#########################################################

	function reIndex(&$arr) {
		$i=1;
		foreach($arr as $n=>$v) {
			$arr2[$i]=$n;
			$i++;
		}
		return $arr2;
	}

	#########################################################

	function get_embed($provider, $url, $max_width = '', $max_height = '', $retrieve = 'html') {
		global $Translation;
		if(!$url) return '';

		$providers = [
			'youtube' => ['oembed' => 'https://www.youtube.com/oembed?'],
			'googlemap' => ['oembed' => '', 'regex' => '/^http.*\.google\..*maps/i'],
		];

		if(!$max_height) $max_height = 360;
		if(!$max_width) $max_width = 480;

		if(!isset($providers[$provider])) {
			return '<div class="text-danger">' . $Translation['invalid provider'] . '</div>';
		}

		if(isset($providers[$provider]['regex']) && !preg_match($providers[$provider]['regex'], $url)) {
			return '<div class="text-danger">' . $Translation['invalid url'] . '</div>';
		}

		if($providers[$provider]['oembed']) {
			$oembed = $providers[$provider]['oembed'] . 'url=' . urlencode($url) . "&amp;maxwidth={$max_width}&amp;maxheight={$max_height}&amp;format=json";
			$data_json = request_cache($oembed);

			$data = json_decode($data_json, true);
			if($data === null) {
				/* an error was returned rather than a json string */
				if($retrieve == 'html') return "<div class=\"text-danger\">{$data_json}\n<!-- {$oembed} --></div>";
				return '';
			}

			return (isset($data[$retrieve]) ? $data[$retrieve] : $data['html']);
		}

		/* special cases (where there is no oEmbed provider) */
		if($provider == 'googlemap') return get_embed_googlemap($url, $max_width, $max_height, $retrieve);

		return '<div class="text-danger">Invalid provider!</div>';
	}

	#########################################################

	function get_embed_googlemap($url, $max_width = '', $max_height = '', $retrieve = 'html') {
		global $Translation;
		$url_parts = parse_url($url);
		$coords_regex = '/-?\d+(\.\d+)?[,+]-?\d+(\.\d+)?(,\d{1,2}z)?/'; /* https://stackoverflow.com/questions/2660201 */

		if(preg_match($coords_regex, $url_parts['path'] . '?' . $url_parts['query'], $m)) {
			list($lat, $long, $zoom) = explode(',', $m[0]);
			$zoom = intval($zoom);
			if(!$zoom) $zoom = 10; /* default zoom */
			if(!$max_height) $max_height = 360;
			if(!$max_width) $max_width = 480;

			$api_key = config('adminConfig')['googleAPIKey'];
			$embed_url = "https://www.google.com/maps/embed/v1/view?key={$api_key}&amp;center={$lat},{$long}&amp;zoom={$zoom}&amp;maptype=roadmap";
			$thumbnail_url = "https://maps.googleapis.com/maps/api/staticmap?key={$api_key}&amp;center={$lat},{$long}&amp;zoom={$zoom}&amp;maptype=roadmap&amp;size={$max_width}x{$max_height}";

			if($retrieve == 'html') {
				return "<iframe width=\"{$max_width}\" height=\"{$max_height}\" frameborder=\"0\" style=\"border:0\" src=\"{$embed_url}\"></iframe>";
			} else {
				return $thumbnail_url;
			}
		} else {
			return '<div class="text-danger">' . $Translation['cant retrieve coordinates from url'] . '</div>';
		}
	}

	#########################################################

	function request_cache($request, $force_fetch = false) {
		$max_cache_lifetime = 7 * 86400; /* max cache lifetime in seconds before refreshing from source */

		// force fetching request if no cache table exists
		$cache_table_exists = sqlValue("show tables like 'membership_cache'");
		if(!$cache_table_exists)
			return request_cache($request, true);

		/* retrieve response from cache if exists */
		if(!$force_fetch) {
			$res = sql("select response, request_ts from membership_cache where request='" . md5($request) . "'", $eo);
			if(!$row = db_fetch_array($res)) return request_cache($request, true);

			$response = $row[0];
			$response_ts = $row[1];
			if($response_ts < time() - $max_cache_lifetime) return request_cache($request, true);
		}

		/* if no response in cache, issue a request */
		if(!$response || $force_fetch) {
			$response = @file_get_contents($request);
			if($response === false) {
				$error = error_get_last();
				$error_message = preg_replace('/.*: (.*)/', '$1', $error['message']);
				return $error_message;
			} elseif($cache_table_exists) {
				/* store response in cache */
				$ts = time();
				sql("replace into membership_cache set request='" . md5($request) . "', request_ts='{$ts}', response='" . makeSafe($response, false) . "'", $eo);
			}
		}

		return $response;
	}

	#########################################################

	function check_record_permission($table, $id, $perm = 'view') {
		if($perm != 'edit' && $perm != 'delete') $perm = 'view';

		$perms = getTablePermissions($table);
		if(!$perms[$perm]) return false;

		$safe_id = makeSafe($id);
		$safe_table = makeSafe($table);

		// fix for zero-fill: quote id only if not numeric
		if(!is_numeric($safe_id)) $safe_id = "'$safe_id'";

		if($perms[$perm] == 1) { // own records only
			$username = getLoggedMemberID();
			$owner = sqlValue("select memberID from membership_userrecords where tableName='{$safe_table}' and pkValue={$safe_id}");
			if($owner == $username) return true;
		} elseif($perms[$perm] == 2) { // group records
			$group_id = getLoggedGroupID();
			$owner_group_id = sqlValue("select groupID from membership_userrecords where tableName='{$safe_table}' and pkValue={$safe_id}");
			if($owner_group_id == $group_id) return true;
		} elseif($perms[$perm] == 3) { // all records
			return true;
		}

		return false;
	}

	#########################################################

	function NavMenus($options = []) {
		if(!defined('PREPEND_PATH')) define('PREPEND_PATH', '');
		global $Translation;
		$prepend_path = PREPEND_PATH;

		/* default options */
		if(empty($options)) {
			$options = ['tabs' => 7];
		}

		$table_group_name = array_keys(get_table_groups()); /* 0 => group1, 1 => group2 .. */
		/* if only one group named 'None', set to translation of 'select a table' */
		if((count($table_group_name) == 1 && $table_group_name[0] == 'None') || count($table_group_name) < 1) $table_group_name[0] = $Translation['select a table'];
		$table_group_index = array_flip($table_group_name); /* group1 => 0, group2 => 1 .. */
		$menu = array_fill(0, count($table_group_name), '');

		$t = time();
		$arrTables = getTableList();
		if(is_array($arrTables)) {
			foreach($arrTables as $tn => $tc) {
				/* ---- list of tables where hide link in nav menu is set ---- */
				$tChkHL = array_search($tn, ['residence_and_rental_history','employment_and_income_history','references','property_photos','unit_photos']);

				/* ---- list of tables where filter first is set ---- */
				$tChkFF = array_search($tn, []);
				if($tChkFF !== false && $tChkFF !== null) {
					$searchFirst = '&Filter_x=1';
				} else {
					$searchFirst = '';
				}

				/* when no groups defined, $table_group_index['None'] is NULL, so $menu_index is still set to 0 */
				$menu_index = intval($table_group_index[$tc[3]]);
				if(!$tChkHL && $tChkHL !== 0) $menu[$menu_index] .= "<li><a href=\"{$prepend_path}{$tn}_view.php?t={$t}{$searchFirst}\"><img src=\"{$prepend_path}" . ($tc[2] ? $tc[2] : 'blank.gif') . "\" height=\"32\"> {$tc[0]}</a></li>";
			}
		}

		// custom nav links, as defined in "hooks/links-navmenu.php" 
		global $navLinks;
		if(is_array($navLinks)) {
			$memberInfo = getMemberInfo();
			$links_added = [];
			foreach($navLinks as $link) {
				if(!isset($link['url']) || !isset($link['title'])) continue;
				if(getLoggedAdmin() !== false || @in_array($memberInfo['group'], $link['groups']) || @in_array('*', $link['groups'])) {
					$menu_index = intval($link['table_group']);
					if(!$links_added[$menu_index]) $menu[$menu_index] .= '<li class="divider"></li>';

					/* add prepend_path to custom links if they aren't absolute links */
					if(!preg_match('/^(http|\/\/)/i', $link['url'])) $link['url'] = $prepend_path . $link['url'];
					if(!preg_match('/^(http|\/\/)/i', $link['icon']) && $link['icon']) $link['icon'] = $prepend_path . $link['icon'];

					$menu[$menu_index] .= "<li><a href=\"{$link['url']}\"><img src=\"" . ($link['icon'] ? $link['icon'] : "{$prepend_path}blank.gif") . "\" height=\"32\"> {$link['title']}</a></li>";
					$links_added[$menu_index]++;
				}
			}
		}

		$menu_wrapper = '';
		for($i = 0; $i < count($menu); $i++) {
			$menu_wrapper .= <<<EOT
				<li class="dropdown">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown">{$table_group_name[$i]} <b class="caret"></b></a>
					<ul class="dropdown-menu" role="menu">{$menu[$i]}</ul>
				</li>
EOT;
		}

		return $menu_wrapper;
	}

	#########################################################

	function StyleSheet() {
		if(!defined('PREPEND_PATH')) define('PREPEND_PATH', '');
		$prepend_path = PREPEND_PATH;
		$mtime = filemtime( __DIR__ . '/dynamic.css');

		$css_links = <<<EOT

			<link rel="stylesheet" href="{$prepend_path}resources/initializr/css/bootstrap.css">
			<link rel="stylesheet" href="{$prepend_path}resources/initializr/css/bootstrap-theme.css">
			<link rel="stylesheet" href="{$prepend_path}resources/lightbox/css/lightbox.css" media="screen">
			<link rel="stylesheet" href="{$prepend_path}resources/select2/select2.css" media="screen">
			<link rel="stylesheet" href="{$prepend_path}resources/timepicker/bootstrap-timepicker.min.css" media="screen">
			<link rel="stylesheet" href="{$prepend_path}dynamic.css?{$mtime}">
EOT;

		return $css_links;
	}

	#########################################################

	function PrepareUploadedFile($FieldName, $MaxSize, $FileTypes = 'jpg|jpeg|gif|png|webp', $NoRename = false, $dir = '') {
		global $Translation;
		$f = $_FILES[$FieldName];
		if($f['error'] == 4 || !$f['name']) return '';

		$dir = getUploadDir($dir);

		/* get php.ini upload_max_filesize in bytes */
		$php_upload_size_limit = toBytes(ini_get('upload_max_filesize'));
		$MaxSize = min($MaxSize, $php_upload_size_limit);

		if($f['size'] > $MaxSize || $f['error']) {
			echo error_message(str_replace(['<MaxSize>', '{MaxSize}'], intval($MaxSize / 1024), $Translation['file too large']));
			exit;
		}
		if(!preg_match('/\.(' . $FileTypes . ')$/i', $f['name'], $ft)) {
			echo error_message(str_replace(['<FileTypes>', '{FileTypes}'], str_replace('|', ', ', $FileTypes), $Translation['invalid file type']));
			exit;
		}

		$name = str_replace(' ', '_', $f['name']);
		if(!$NoRename) $name = substr(md5(microtime() . rand(0, 100000)), -17) . $ft[0];

		if(!file_exists($dir)) @mkdir($dir, 0777);

		if(!@move_uploaded_file($f['tmp_name'], $dir . $name)) {
			echo error_message("Couldn't save the uploaded file. Try chmoding the upload folder '{$dir}' to 777.");
			exit;
		}

		@chmod($dir . $name, 0666);
		return $name;
	}

	#########################################################

	function get_home_links($homeLinks, $default_classes, $tgroup = '') {
		if(!is_array($homeLinks) || !count($homeLinks)) return '';

		$memberInfo = getMemberInfo();

		ob_start();
		foreach($homeLinks as $link) {
			if(!isset($link['url']) || !isset($link['title'])) continue;
			if($tgroup != $link['table_group'] && $tgroup != '*') continue;

			/* fall-back classes if none defined */
			if(!$link['grid_column_classes']) $link['grid_column_classes'] = $default_classes['grid_column'];
			if(!$link['panel_classes']) $link['panel_classes'] = $default_classes['panel'];
			if(!$link['link_classes']) $link['link_classes'] = $default_classes['link'];

			if(getLoggedAdmin() !== false || @in_array($memberInfo['group'], $link['groups']) || @in_array('*', $link['groups'])) {
				?>
				<div class="col-xs-12 <?php echo $link['grid_column_classes']; ?>">
					<div class="panel <?php echo $link['panel_classes']; ?>">
						<div class="panel-body">
							<a class="btn btn-block btn-lg <?php echo $link['link_classes']; ?>" title="<?php echo preg_replace("/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", html_attr(strip_tags($link['description']))); ?>" href="<?php echo $link['url']; ?>"><?php echo ($link['icon'] ? '<img src="' . $link['icon'] . '">' : ''); ?><strong><?php echo $link['title']; ?></strong></a>
							<div class="panel-body-description"><?php echo $link['description']; ?></div>
						</div>
					</div>
				</div>
				<?php
			}
		}

		return ob_get_clean();
	}

	#########################################################

	function quick_search_html($search_term, $label, $separate_dv = true) {
		global $Translation;

		$safe_search = html_attr($search_term);
		$safe_label = html_attr($label);
		$safe_clear_label = html_attr($Translation['Reset Filters']);

		if($separate_dv) {
			$reset_selection = "document.myform.SelectedID.value = '';";
		} else {
			$reset_selection = "document.myform.writeAttribute('novalidate', 'novalidate');";
		}
		$reset_selection .= ' document.myform.NoDV.value=1; return true;';

		$html = <<<EOT
		<div class="input-group" id="quick-search">
			<input type="text" id="SearchString" name="SearchString" value="{$safe_search}" class="form-control" placeholder="{$safe_label}">
			<span class="input-group-btn">
				<button name="Search_x" value="1" id="Search" type="submit" onClick="{$reset_selection}" class="btn btn-default" title="{$safe_label}"><i class="glyphicon glyphicon-search"></i></button>
				<button name="ClearQuickSearch" value="1" id="ClearQuickSearch" type="submit" onClick="\$j('#SearchString').val(''); {$reset_selection}" class="btn btn-default" title="{$safe_clear_label}"><i class="glyphicon glyphicon-remove-circle"></i></button>
			</span>
		</div>
EOT;
		return $html;
	}

	#########################################################

	function getLookupFields($skipPermissions = false, $filterByPermission = 'view') {
		$pcConfig = [
			'applicants_and_tenants' => [
			],
			'applications_leases' => [
				'tenants' => [
					'parent-table' => 'applicants_and_tenants',
					'parent-primary-key' => 'id',
					'child-primary-key' => 'id',
					'child-primary-key-index' => 0,
					'tab-label' => 'Applications/Leases <span class="hidden child-label-applications_leases child-field-caption">(Applicant/ Tenant)</span>',
					'auto-close' => false,
					'table-icon' => 'resources/table_icons/curriculum_vitae.png',
					'display-refresh' => true,
					'display-add-new' => true,
					'forced-where' => '',
					'display-fields' => [1 => 'Applicant/ Tenant', 2 => 'Application status', 3 => 'Property', 4 => 'Unit applied for', 5 => 'Lease type', 6 => 'Total number of occupants', 7 => 'Lease period from', 8 => 'to', 11 => 'Rental amount', 12 => 'Security deposit', 14 => 'Emergency contact', 17 => 'Applicant agrees'],
					'display-field-names' => [1 => 'tenants', 2 => 'status', 3 => 'property', 4 => 'unit', 5 => 'type', 6 => 'total_number_of_occupants', 7 => 'start_date', 8 => 'end_date', 11 => 'rent', 12 => 'security_deposit', 14 => 'emergency_contact', 17 => 'agreement'],
					'sortable-fields' => [0 => '`applications_leases`.`id`', 1 => 2, 2 => 3, 3 => '`properties1`.`property_name`', 4 => '`units1`.`unit_number`', 5 => 6, 6 => 7, 7 => '`applications_leases`.`start_date`', 8 => '`applications_leases`.`end_date`', 9 => 10, 10 => '`applications_leases`.`next_due_date`', 11 => '`applications_leases`.`rent`', 12 => '`applications_leases`.`security_deposit`', 13 => '`applications_leases`.`security_deposit_date`', 14 => 15, 15 => 16, 16 => 17, 17 => 18],
					'records-per-page' => 10,
					'default-sort-by' => false,
					'default-sort-direction' => 'asc',
					'open-detail-view-on-click' => true,
					'display-page-selector' => true,
					'show-page-progress' => true,
					'template' => 'children-applications_leases',
					'template-printable' => 'children-applications_leases-printable',
					'query' => "SELECT `applications_leases`.`id` as 'id', IF(    CHAR_LENGTH(`applicants_and_tenants1`.`first_name`) || CHAR_LENGTH(`applicants_and_tenants1`.`last_name`), CONCAT_WS('',   `applicants_and_tenants1`.`first_name`, ' ', `applicants_and_tenants1`.`last_name`), '') as 'tenants', `applications_leases`.`status` as 'status', IF(    CHAR_LENGTH(`properties1`.`property_name`), CONCAT_WS('',   `properties1`.`property_name`), '') as 'property', IF(    CHAR_LENGTH(`units1`.`unit_number`), CONCAT_WS('',   `units1`.`unit_number`), '') as 'unit', `applications_leases`.`type` as 'type', `applications_leases`.`total_number_of_occupants` as 'total_number_of_occupants', if(`applications_leases`.`start_date`,date_format(`applications_leases`.`start_date`,'%m/%d/%Y'),'') as 'start_date', if(`applications_leases`.`end_date`,date_format(`applications_leases`.`end_date`,'%m/%d/%Y'),'') as 'end_date', `applications_leases`.`recurring_charges_frequency` as 'recurring_charges_frequency', if(`applications_leases`.`next_due_date`,date_format(`applications_leases`.`next_due_date`,'%m/%d/%Y'),'') as 'next_due_date', CONCAT('$', FORMAT(`applications_leases`.`rent`, 2)) as 'rent', CONCAT('$', FORMAT(`applications_leases`.`security_deposit`, 2)) as 'security_deposit', if(`applications_leases`.`security_deposit_date`,date_format(`applications_leases`.`security_deposit_date`,'%m/%d/%Y'),'') as 'security_deposit_date', `applications_leases`.`emergency_contact` as 'emergency_contact', `applications_leases`.`co_signer_details` as 'co_signer_details', `applications_leases`.`notes` as 'notes', concat('<i class=\"glyphicon glyphicon-', if(`applications_leases`.`agreement`, 'check', 'unchecked'), '\"></i>') as 'agreement' FROM `applications_leases` LEFT JOIN `applicants_and_tenants` as applicants_and_tenants1 ON `applicants_and_tenants1`.`id`=`applications_leases`.`tenants` LEFT JOIN `properties` as properties1 ON `properties1`.`id`=`applications_leases`.`property` LEFT JOIN `units` as units1 ON `units1`.`id`=`applications_leases`.`unit` "
				],
				'property' => [
					'parent-table' => 'properties',
					'parent-primary-key' => 'id',
					'child-primary-key' => 'id',
					'child-primary-key-index' => 0,
					'tab-label' => 'Applications/leases <span class="hidden child-label-applications_leases child-field-caption">(Property)</span>',
					'auto-close' => false,
					'table-icon' => 'resources/table_icons/curriculum_vitae.png',
					'display-refresh' => true,
					'display-add-new' => true,
					'forced-where' => '',
					'display-fields' => [1 => 'Applicant/ Tenant', 2 => 'Application status', 3 => 'Property', 4 => 'Unit applied for', 5 => 'Lease type', 6 => 'Total number of occupants', 7 => 'Lease period from', 8 => 'to', 11 => 'Rental amount', 12 => 'Security deposit', 14 => 'Emergency contact', 17 => 'Applicant agrees'],
					'display-field-names' => [1 => 'tenants', 2 => 'status', 3 => 'property', 4 => 'unit', 5 => 'type', 6 => 'total_number_of_occupants', 7 => 'start_date', 8 => 'end_date', 11 => 'rent', 12 => 'security_deposit', 14 => 'emergency_contact', 17 => 'agreement'],
					'sortable-fields' => [0 => '`applications_leases`.`id`', 1 => 2, 2 => 3, 3 => '`properties1`.`property_name`', 4 => '`units1`.`unit_number`', 5 => 6, 6 => 7, 7 => '`applications_leases`.`start_date`', 8 => '`applications_leases`.`end_date`', 9 => 10, 10 => '`applications_leases`.`next_due_date`', 11 => '`applications_leases`.`rent`', 12 => '`applications_leases`.`security_deposit`', 13 => '`applications_leases`.`security_deposit_date`', 14 => 15, 15 => 16, 16 => 17, 17 => 18],
					'records-per-page' => 10,
					'default-sort-by' => false,
					'default-sort-direction' => 'asc',
					'open-detail-view-on-click' => true,
					'display-page-selector' => true,
					'show-page-progress' => true,
					'template' => 'children-applications_leases',
					'template-printable' => 'children-applications_leases-printable',
					'query' => "SELECT `applications_leases`.`id` as 'id', IF(    CHAR_LENGTH(`applicants_and_tenants1`.`first_name`) || CHAR_LENGTH(`applicants_and_tenants1`.`last_name`), CONCAT_WS('',   `applicants_and_tenants1`.`first_name`, ' ', `applicants_and_tenants1`.`last_name`), '') as 'tenants', `applications_leases`.`status` as 'status', IF(    CHAR_LENGTH(`properties1`.`property_name`), CONCAT_WS('',   `properties1`.`property_name`), '') as 'property', IF(    CHAR_LENGTH(`units1`.`unit_number`), CONCAT_WS('',   `units1`.`unit_number`), '') as 'unit', `applications_leases`.`type` as 'type', `applications_leases`.`total_number_of_occupants` as 'total_number_of_occupants', if(`applications_leases`.`start_date`,date_format(`applications_leases`.`start_date`,'%m/%d/%Y'),'') as 'start_date', if(`applications_leases`.`end_date`,date_format(`applications_leases`.`end_date`,'%m/%d/%Y'),'') as 'end_date', `applications_leases`.`recurring_charges_frequency` as 'recurring_charges_frequency', if(`applications_leases`.`next_due_date`,date_format(`applications_leases`.`next_due_date`,'%m/%d/%Y'),'') as 'next_due_date', CONCAT('$', FORMAT(`applications_leases`.`rent`, 2)) as 'rent', CONCAT('$', FORMAT(`applications_leases`.`security_deposit`, 2)) as 'security_deposit', if(`applications_leases`.`security_deposit_date`,date_format(`applications_leases`.`security_deposit_date`,'%m/%d/%Y'),'') as 'security_deposit_date', `applications_leases`.`emergency_contact` as 'emergency_contact', `applications_leases`.`co_signer_details` as 'co_signer_details', `applications_leases`.`notes` as 'notes', concat('<i class=\"glyphicon glyphicon-', if(`applications_leases`.`agreement`, 'check', 'unchecked'), '\"></i>') as 'agreement' FROM `applications_leases` LEFT JOIN `applicants_and_tenants` as applicants_and_tenants1 ON `applicants_and_tenants1`.`id`=`applications_leases`.`tenants` LEFT JOIN `properties` as properties1 ON `properties1`.`id`=`applications_leases`.`property` LEFT JOIN `units` as units1 ON `units1`.`id`=`applications_leases`.`unit` "
				],
				'unit' => [
					'parent-table' => 'units',
					'parent-primary-key' => 'id',
					'child-primary-key' => 'id',
					'child-primary-key-index' => 0,
					'tab-label' => 'Applications/Leases <span class="hidden child-label-applications_leases child-field-caption">(Unit applied for)</span>',
					'auto-close' => false,
					'table-icon' => 'resources/table_icons/curriculum_vitae.png',
					'display-refresh' => true,
					'display-add-new' => true,
					'forced-where' => '',
					'display-fields' => [1 => 'Applicant/ Tenant', 2 => 'Application status', 3 => 'Property', 4 => 'Unit applied for', 5 => 'Lease type', 6 => 'Total number of occupants', 7 => 'Lease period from', 8 => 'to', 11 => 'Rental amount', 12 => 'Security deposit', 14 => 'Emergency contact', 17 => 'Applicant agrees'],
					'display-field-names' => [1 => 'tenants', 2 => 'status', 3 => 'property', 4 => 'unit', 5 => 'type', 6 => 'total_number_of_occupants', 7 => 'start_date', 8 => 'end_date', 11 => 'rent', 12 => 'security_deposit', 14 => 'emergency_contact', 17 => 'agreement'],
					'sortable-fields' => [0 => '`applications_leases`.`id`', 1 => 2, 2 => 3, 3 => '`properties1`.`property_name`', 4 => '`units1`.`unit_number`', 5 => 6, 6 => 7, 7 => '`applications_leases`.`start_date`', 8 => '`applications_leases`.`end_date`', 9 => 10, 10 => '`applications_leases`.`next_due_date`', 11 => '`applications_leases`.`rent`', 12 => '`applications_leases`.`security_deposit`', 13 => '`applications_leases`.`security_deposit_date`', 14 => 15, 15 => 16, 16 => 17, 17 => 18],
					'records-per-page' => 10,
					'default-sort-by' => false,
					'default-sort-direction' => 'asc',
					'open-detail-view-on-click' => true,
					'display-page-selector' => true,
					'show-page-progress' => true,
					'template' => 'children-applications_leases',
					'template-printable' => 'children-applications_leases-printable',
					'query' => "SELECT `applications_leases`.`id` as 'id', IF(    CHAR_LENGTH(`applicants_and_tenants1`.`first_name`) || CHAR_LENGTH(`applicants_and_tenants1`.`last_name`), CONCAT_WS('',   `applicants_and_tenants1`.`first_name`, ' ', `applicants_and_tenants1`.`last_name`), '') as 'tenants', `applications_leases`.`status` as 'status', IF(    CHAR_LENGTH(`properties1`.`property_name`), CONCAT_WS('',   `properties1`.`property_name`), '') as 'property', IF(    CHAR_LENGTH(`units1`.`unit_number`), CONCAT_WS('',   `units1`.`unit_number`), '') as 'unit', `applications_leases`.`type` as 'type', `applications_leases`.`total_number_of_occupants` as 'total_number_of_occupants', if(`applications_leases`.`start_date`,date_format(`applications_leases`.`start_date`,'%m/%d/%Y'),'') as 'start_date', if(`applications_leases`.`end_date`,date_format(`applications_leases`.`end_date`,'%m/%d/%Y'),'') as 'end_date', `applications_leases`.`recurring_charges_frequency` as 'recurring_charges_frequency', if(`applications_leases`.`next_due_date`,date_format(`applications_leases`.`next_due_date`,'%m/%d/%Y'),'') as 'next_due_date', CONCAT('$', FORMAT(`applications_leases`.`rent`, 2)) as 'rent', CONCAT('$', FORMAT(`applications_leases`.`security_deposit`, 2)) as 'security_deposit', if(`applications_leases`.`security_deposit_date`,date_format(`applications_leases`.`security_deposit_date`,'%m/%d/%Y'),'') as 'security_deposit_date', `applications_leases`.`emergency_contact` as 'emergency_contact', `applications_leases`.`co_signer_details` as 'co_signer_details', `applications_leases`.`notes` as 'notes', concat('<i class=\"glyphicon glyphicon-', if(`applications_leases`.`agreement`, 'check', 'unchecked'), '\"></i>') as 'agreement' FROM `applications_leases` LEFT JOIN `applicants_and_tenants` as applicants_and_tenants1 ON `applicants_and_tenants1`.`id`=`applications_leases`.`tenants` LEFT JOIN `properties` as properties1 ON `properties1`.`id`=`applications_leases`.`property` LEFT JOIN `units` as units1 ON `units1`.`id`=`applications_leases`.`unit` "
				],
			],
			'residence_and_rental_history' => [
				'tenant' => [
					'parent-table' => 'applicants_and_tenants',
					'parent-primary-key' => 'id',
					'child-primary-key' => 'id',
					'child-primary-key-index' => 0,
					'tab-label' => 'Residence and rental history <span class="hidden child-label-residence_and_rental_history child-field-caption">(Tenant)</span>',
					'auto-close' => false,
					'table-icon' => 'resources/table_icons/document_comment_above.png',
					'display-refresh' => true,
					'display-add-new' => true,
					'forced-where' => '',
					'display-fields' => [2 => 'Address', 3 => 'Landlord/manager name', 4 => 'Landlord/manager phone', 5 => 'Monthly rent', 6 => 'Duration of residency from', 7 => 'to', 8 => 'Reason for leaving'],
					'display-field-names' => [2 => 'address', 3 => 'landlord_or_manager_name', 4 => 'landlord_or_manager_phone', 5 => 'monthly_rent', 6 => 'duration_of_residency_from', 7 => 'to', 8 => 'reason_for_leaving'],
					'sortable-fields' => [0 => '`residence_and_rental_history`.`id`', 1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => '`residence_and_rental_history`.`monthly_rent`', 6 => '`residence_and_rental_history`.`duration_of_residency_from`', 7 => '`residence_and_rental_history`.`to`', 8 => 9, 9 => 10],
					'records-per-page' => 10,
					'default-sort-by' => false,
					'default-sort-direction' => 'asc',
					'open-detail-view-on-click' => true,
					'display-page-selector' => true,
					'show-page-progress' => true,
					'template' => 'children-residence_and_rental_history',
					'template-printable' => 'children-residence_and_rental_history-printable',
					'query' => "SELECT `residence_and_rental_history`.`id` as 'id', IF(    CHAR_LENGTH(`applicants_and_tenants1`.`first_name`) || CHAR_LENGTH(`applicants_and_tenants1`.`last_name`), CONCAT_WS('',   `applicants_and_tenants1`.`first_name`, ' ', `applicants_and_tenants1`.`last_name`), '') as 'tenant', `residence_and_rental_history`.`address` as 'address', `residence_and_rental_history`.`landlord_or_manager_name` as 'landlord_or_manager_name', `residence_and_rental_history`.`landlord_or_manager_phone` as 'landlord_or_manager_phone', CONCAT('$', FORMAT(`residence_and_rental_history`.`monthly_rent`, 2)) as 'monthly_rent', if(`residence_and_rental_history`.`duration_of_residency_from`,date_format(`residence_and_rental_history`.`duration_of_residency_from`,'%m/%d/%Y'),'') as 'duration_of_residency_from', if(`residence_and_rental_history`.`to`,date_format(`residence_and_rental_history`.`to`,'%m/%d/%Y'),'') as 'to', `residence_and_rental_history`.`reason_for_leaving` as 'reason_for_leaving', `residence_and_rental_history`.`notes` as 'notes' FROM `residence_and_rental_history` LEFT JOIN `applicants_and_tenants` as applicants_and_tenants1 ON `applicants_and_tenants1`.`id`=`residence_and_rental_history`.`tenant` "
				],
			],
			'employment_and_income_history' => [
				'tenant' => [
					'parent-table' => 'applicants_and_tenants',
					'parent-primary-key' => 'id',
					'child-primary-key' => 'id',
					'child-primary-key-index' => 0,
					'tab-label' => 'Employment and income history <span class="hidden child-label-employment_and_income_history child-field-caption">(Tenant)</span>',
					'auto-close' => false,
					'table-icon' => 'resources/table_icons/cash_stack.png',
					'display-refresh' => true,
					'display-add-new' => true,
					'forced-where' => '',
					'display-fields' => [2 => 'Employer name', 3 => 'City', 4 => 'Employer phone', 5 => 'employed from', 6 => 'Employed till', 7 => 'Occupation', 8 => 'Notes'],
					'display-field-names' => [2 => 'employer_name', 3 => 'city', 4 => 'employer_phone', 5 => 'employed_from', 6 => 'employed_till', 7 => 'occupation', 8 => 'notes'],
					'sortable-fields' => [0 => '`employment_and_income_history`.`id`', 1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => '`employment_and_income_history`.`employed_from`', 6 => '`employment_and_income_history`.`employed_till`', 7 => 8, 8 => 9],
					'records-per-page' => 10,
					'default-sort-by' => false,
					'default-sort-direction' => 'asc',
					'open-detail-view-on-click' => true,
					'display-page-selector' => true,
					'show-page-progress' => true,
					'template' => 'children-employment_and_income_history',
					'template-printable' => 'children-employment_and_income_history-printable',
					'query' => "SELECT `employment_and_income_history`.`id` as 'id', IF(    CHAR_LENGTH(`applicants_and_tenants1`.`first_name`) || CHAR_LENGTH(`applicants_and_tenants1`.`last_name`), CONCAT_WS('',   `applicants_and_tenants1`.`first_name`, ' ', `applicants_and_tenants1`.`last_name`), '') as 'tenant', `employment_and_income_history`.`employer_name` as 'employer_name', `employment_and_income_history`.`city` as 'city', CONCAT_WS('-', LEFT(`employment_and_income_history`.`employer_phone`,3), MID(`employment_and_income_history`.`employer_phone`,4,3), RIGHT(`employment_and_income_history`.`employer_phone`,4)) as 'employer_phone', if(`employment_and_income_history`.`employed_from`,date_format(`employment_and_income_history`.`employed_from`,'%m/%d/%Y'),'') as 'employed_from', if(`employment_and_income_history`.`employed_till`,date_format(`employment_and_income_history`.`employed_till`,'%m/%d/%Y'),'') as 'employed_till', `employment_and_income_history`.`occupation` as 'occupation', `employment_and_income_history`.`notes` as 'notes' FROM `employment_and_income_history` LEFT JOIN `applicants_and_tenants` as applicants_and_tenants1 ON `applicants_and_tenants1`.`id`=`employment_and_income_history`.`tenant` "
				],
			],
			'references' => [
				'tenant' => [
					'parent-table' => 'applicants_and_tenants',
					'parent-primary-key' => 'id',
					'child-primary-key' => 'id',
					'child-primary-key-index' => 0,
					'tab-label' => 'References <span class="hidden child-label-references child-field-caption">(Tenant)</span>',
					'auto-close' => false,
					'table-icon' => 'resources/table_icons/application_from_storage.png',
					'display-refresh' => true,
					'display-add-new' => true,
					'forced-where' => '',
					'display-fields' => [2 => 'Reference name', 3 => 'Reference phone'],
					'display-field-names' => [2 => 'reference_name', 3 => 'phone'],
					'sortable-fields' => [0 => '`references`.`id`', 1 => 2, 2 => 3, 3 => 4],
					'records-per-page' => 10,
					'default-sort-by' => false,
					'default-sort-direction' => 'asc',
					'open-detail-view-on-click' => true,
					'display-page-selector' => true,
					'show-page-progress' => true,
					'template' => 'children-references',
					'template-printable' => 'children-references-printable',
					'query' => "SELECT `references`.`id` as 'id', IF(    CHAR_LENGTH(`applicants_and_tenants1`.`first_name`) || CHAR_LENGTH(`applicants_and_tenants1`.`last_name`), CONCAT_WS('',   `applicants_and_tenants1`.`first_name`, ' ', `applicants_and_tenants1`.`last_name`), '') as 'tenant', `references`.`reference_name` as 'reference_name', CONCAT_WS('-', LEFT(`references`.`phone`,3), MID(`references`.`phone`,4,3), RIGHT(`references`.`phone`,4)) as 'phone' FROM `references` LEFT JOIN `applicants_and_tenants` as applicants_and_tenants1 ON `applicants_and_tenants1`.`id`=`references`.`tenant` "
				],
			],
			'rental_owners' => [
			],
			'properties' => [
				'owner' => [
					'parent-table' => 'rental_owners',
					'parent-primary-key' => 'id',
					'child-primary-key' => 'id',
					'child-primary-key-index' => 0,
					'tab-label' => 'Properties <span class="hidden child-label-properties child-field-caption">(Owner)</span>',
					'auto-close' => false,
					'table-icon' => 'resources/table_icons/application_home.png',
					'display-refresh' => true,
					'display-add-new' => true,
					'forced-where' => '',
					'display-fields' => [1 => 'Property Name', 2 => 'Cover photo', 3 => 'Type', 4 => 'Number of units', 5 => 'Owner', 6 => 'Operating account', 7 => 'Property reserve', 10 => 'Street', 11 => 'City', 12 => 'State', 13 => 'ZIP'],
					'display-field-names' => [1 => 'property_name', 2 => 'photo', 3 => 'type', 4 => 'number_of_units', 5 => 'owner', 6 => 'operating_account', 7 => 'property_reserve', 10 => 'street', 11 => 'City', 12 => 'State', 13 => 'ZIP'],
					'sortable-fields' => [0 => '`properties`.`id`', 1 => 2, 2 => 3, 3 => 4, 4 => '`properties`.`number_of_units`', 5 => 6, 6 => 7, 7 => '`properties`.`property_reserve`', 8 => 9, 9 => 10, 10 => 11, 11 => 12, 12 => 13, 13 => '`properties`.`ZIP`'],
					'records-per-page' => 10,
					'default-sort-by' => 0,
					'default-sort-direction' => 'desc',
					'open-detail-view-on-click' => true,
					'display-page-selector' => true,
					'show-page-progress' => true,
					'template' => 'children-properties',
					'template-printable' => 'children-properties-printable',
					'query' => "SELECT `properties`.`id` as 'id', `properties`.`property_name` as 'property_name', `properties`.`photo` as 'photo', `properties`.`type` as 'type', `properties`.`number_of_units` as 'number_of_units', IF(    CHAR_LENGTH(`rental_owners1`.`first_name`) || CHAR_LENGTH(`rental_owners1`.`last_name`), CONCAT_WS('',   `rental_owners1`.`first_name`, ' ', `rental_owners1`.`last_name`), '') as 'owner', `properties`.`operating_account` as 'operating_account', CONCAT('$', FORMAT(`properties`.`property_reserve`, 2)) as 'property_reserve', `properties`.`lease_term` as 'lease_term', `properties`.`country` as 'country', `properties`.`street` as 'street', `properties`.`City` as 'City', `properties`.`State` as 'State', `properties`.`ZIP` as 'ZIP' FROM `properties` LEFT JOIN `rental_owners` as rental_owners1 ON `rental_owners1`.`id`=`properties`.`owner` "
				],
			],
			'property_photos' => [
				'property' => [
					'parent-table' => 'properties',
					'parent-primary-key' => 'id',
					'child-primary-key' => 'id',
					'child-primary-key-index' => 0,
					'tab-label' => 'Property photos <span class="hidden child-label-property_photos child-field-caption">(Property)</span>',
					'auto-close' => false,
					'table-icon' => 'resources/table_icons/camera_link.png',
					'display-refresh' => true,
					'display-add-new' => true,
					'forced-where' => '',
					'display-fields' => [2 => 'Photo', 3 => 'Description'],
					'display-field-names' => [2 => 'photo', 3 => 'description'],
					'sortable-fields' => [0 => '`property_photos`.`id`', 1 => '`properties1`.`property_name`', 2 => 3, 3 => 4],
					'records-per-page' => 10,
					'default-sort-by' => false,
					'default-sort-direction' => 'asc',
					'open-detail-view-on-click' => true,
					'display-page-selector' => true,
					'show-page-progress' => true,
					'template' => 'children-property_photos',
					'template-printable' => 'children-property_photos-printable',
					'query' => "SELECT `property_photos`.`id` as 'id', IF(    CHAR_LENGTH(`properties1`.`property_name`), CONCAT_WS('',   `properties1`.`property_name`), '') as 'property', `property_photos`.`photo` as 'photo', `property_photos`.`description` as 'description' FROM `property_photos` LEFT JOIN `properties` as properties1 ON `properties1`.`id`=`property_photos`.`property` "
				],
			],
			'units' => [
				'property' => [
					'parent-table' => 'properties',
					'parent-primary-key' => 'id',
					'child-primary-key' => 'id',
					'child-primary-key-index' => 0,
					'tab-label' => 'Units <span class="hidden child-label-units child-field-caption">(Property)</span>',
					'auto-close' => false,
					'table-icon' => 'resources/table_icons/change_password.png',
					'display-refresh' => true,
					'display-add-new' => true,
					'forced-where' => '',
					'display-fields' => [1 => 'Property', 2 => 'Unit', 3 => 'Cover photo', 4 => 'Status', 5 => 'Area (sq. feet)', 7 => 'Street', 8 => 'City', 9 => 'State', 11 => 'Rooms', 12 => 'Bathroom', 13 => 'Features', 15 => 'Rental amount'],
					'display-field-names' => [1 => 'property', 2 => 'unit_number', 3 => 'photo', 4 => 'status', 5 => 'size', 7 => 'street', 8 => 'city', 9 => 'state', 11 => 'rooms', 12 => 'bathroom', 13 => 'features', 15 => 'rental_amount'],
					'sortable-fields' => [0 => '`units`.`id`', 1 => '`properties1`.`property_name`', 2 => 3, 3 => 4, 4 => 5, 5 => 6, 6 => '`properties1`.`country`', 7 => '`properties1`.`street`', 8 => '`properties1`.`City`', 9 => '`properties1`.`State`', 10 => '`properties1`.`ZIP`', 11 => 12, 12 => '`units`.`bathroom`', 13 => 14, 14 => '`units`.`market_rent`', 15 => '`units`.`rental_amount`', 16 => '`units`.`deposit_amount`', 17 => 18],
					'records-per-page' => 10,
					'default-sort-by' => 1,
					'default-sort-direction' => 'asc',
					'open-detail-view-on-click' => true,
					'display-page-selector' => true,
					'show-page-progress' => true,
					'template' => 'children-units',
					'template-printable' => 'children-units-printable',
					'query' => "SELECT `units`.`id` as 'id', IF(    CHAR_LENGTH(`properties1`.`property_name`), CONCAT_WS('',   `properties1`.`property_name`), '') as 'property', `units`.`unit_number` as 'unit_number', `units`.`photo` as 'photo', `units`.`status` as 'status', `units`.`size` as 'size', IF(    CHAR_LENGTH(`properties1`.`country`), CONCAT_WS('',   `properties1`.`country`), '') as 'country', IF(    CHAR_LENGTH(`properties1`.`street`), CONCAT_WS('',   `properties1`.`street`), '') as 'street', IF(    CHAR_LENGTH(`properties1`.`City`), CONCAT_WS('',   `properties1`.`City`), '') as 'city', IF(    CHAR_LENGTH(`properties1`.`State`), CONCAT_WS('',   `properties1`.`State`), '') as 'state', IF(    CHAR_LENGTH(`properties1`.`ZIP`), CONCAT_WS('',   `properties1`.`ZIP`), '') as 'postal_code', `units`.`rooms` as 'rooms', `units`.`bathroom` as 'bathroom', `units`.`features` as 'features', FORMAT(`units`.`market_rent`, 0) as 'market_rent', CONCAT('$', FORMAT(`units`.`rental_amount`, 2)) as 'rental_amount', CONCAT('$', FORMAT(`units`.`deposit_amount`, 2)) as 'deposit_amount', `units`.`description` as 'description' FROM `units` LEFT JOIN `properties` as properties1 ON `properties1`.`id`=`units`.`property` "
				],
			],
			'unit_photos' => [
				'unit' => [
					'parent-table' => 'units',
					'parent-primary-key' => 'id',
					'child-primary-key' => 'id',
					'child-primary-key-index' => 0,
					'tab-label' => 'Unit photos <span class="hidden child-label-unit_photos child-field-caption">(Unit)</span>',
					'auto-close' => false,
					'table-icon' => 'resources/table_icons/camera_link.png',
					'display-refresh' => true,
					'display-add-new' => true,
					'forced-where' => '',
					'display-fields' => [2 => 'Photo', 3 => 'Description'],
					'display-field-names' => [2 => 'photo', 3 => 'description'],
					'sortable-fields' => [0 => '`unit_photos`.`id`', 1 => 2, 2 => 3, 3 => 4],
					'records-per-page' => 10,
					'default-sort-by' => false,
					'default-sort-direction' => 'asc',
					'open-detail-view-on-click' => true,
					'display-page-selector' => true,
					'show-page-progress' => true,
					'template' => 'children-unit_photos',
					'template-printable' => 'children-unit_photos-printable',
					'query' => "SELECT `unit_photos`.`id` as 'id', IF(    CHAR_LENGTH(`properties1`.`property_name`) || CHAR_LENGTH(`units1`.`unit_number`), CONCAT_WS('',   `properties1`.`property_name`, ' - unit# ', `units1`.`unit_number`), '') as 'unit', `unit_photos`.`photo` as 'photo', `unit_photos`.`description` as 'description' FROM `unit_photos` LEFT JOIN `units` as units1 ON `units1`.`id`=`unit_photos`.`unit` LEFT JOIN `properties` as properties1 ON `properties1`.`id`=`units1`.`property` "
				],
			],
		];

		if($skipPermissions) return $pcConfig;

		if(!in_array($filterByPermission, ['access', 'insert', 'edit', 'delete'])) $filterByPermission = 'view';

		/**
		* dynamic configuration based on current user's permissions
		* $userPCConfig array is populated only with parent tables where the user has access to
		* at least one child table
		*/
		$userPCConfig = [];
		foreach($pcConfig as $tn => $lookupFields) {
			$perm = getTablePermissions($tn);
			if(!$perm[$filterByPermission]) continue;

			foreach($lookupFields as $fn => $ChildConfig) {
				$permParent = getTablePermissions($ChildConfig['parent-table']);
				if(!$permParent[$filterByPermission]) continue;

				$userPCConfig[$tn][$fn] = $pcConfig[$tn][$fn];
				// show add new only if configured above AND the user has insert permission
				$userPCConfig[$tn][$fn]['display-add-new'] = ($perm['insert'] && $pcConfig[$tn][$fn]['display-add-new']);
			}
		}

		return $userPCConfig;
	}

	#########################################################

	function getChildTables($parentTable, $skipPermissions = false, $filterByPermission = 'view') {
		$pcConfig = getLookupFields($skipPermissions, $filterByPermission);
		$childTables = [];
		foreach($pcConfig as $tn => $lookupFields)
			foreach($lookupFields as $fn => $ChildConfig)
				if($ChildConfig['parent-table'] == $parentTable)
					$childTables[$tn][$fn] = $ChildConfig;

		return $childTables;
	}
