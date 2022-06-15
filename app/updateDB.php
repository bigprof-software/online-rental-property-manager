<?php
	// check this file's MD5 to make sure it wasn't called before
	$tenantId = Authentication::tenantIdPadded();
	$setupHash = __DIR__ . "/setup{$tenantId}.md5";

	$prevMD5 = @file_get_contents($setupHash);
	$thisMD5 = md5_file(__FILE__);

	// check if this setup file already run
	if($thisMD5 != $prevMD5) {
		// set up tables
		setupTable(
			'applicants_and_tenants', " 
			CREATE TABLE IF NOT EXISTS `applicants_and_tenants` ( 
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`id`),
				`last_name` VARCHAR(40) NULL,
				`first_name` VARCHAR(40) NULL,
				`email` VARCHAR(80) NULL,
				`phone` VARCHAR(15) NULL,
				`birth_date` DATE NULL,
				`driver_license_number` VARCHAR(15) NULL,
				`driver_license_state` VARCHAR(15) NULL,
				`requested_lease_term` VARCHAR(15) NULL,
				`monthly_gross_pay` DECIMAL(8,2) NULL,
				`additional_income` DECIMAL(8,2) NULL,
				`assets` DECIMAL(8,2) NULL,
				`status` VARCHAR(40) NOT NULL DEFAULT 'Applicant',
				`notes` TEXT NULL
			) CHARSET utf8"
		);

		setupTable(
			'applications_leases', " 
			CREATE TABLE IF NOT EXISTS `applications_leases` ( 
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`id`),
				`tenants` INT UNSIGNED NULL,
				`status` VARCHAR(40) NOT NULL DEFAULT 'Application',
				`property` INT UNSIGNED NULL,
				`unit` INT UNSIGNED NULL,
				`type` VARCHAR(40) NOT NULL DEFAULT 'Fixed',
				`total_number_of_occupants` VARCHAR(15) NULL,
				`start_date` DATE NULL,
				`end_date` DATE NULL,
				`recurring_charges_frequency` VARCHAR(40) NOT NULL DEFAULT 'Monthly',
				`next_due_date` DATE NULL,
				`rent` DECIMAL(10,2) NULL,
				`security_deposit` DECIMAL(15,2) NULL,
				`security_deposit_date` DATE NULL,
				`emergency_contact` VARCHAR(100) NULL,
				`co_signer_details` VARCHAR(100) NULL,
				`notes` TEXT NULL,
				`agreement` VARCHAR(40) NULL
			) CHARSET utf8"
		);
		setupIndexes('applications_leases', ['tenants','property','unit',]);

		setupTable(
			'residence_and_rental_history', " 
			CREATE TABLE IF NOT EXISTS `residence_and_rental_history` ( 
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`id`),
				`tenant` INT UNSIGNED NULL,
				`address` VARCHAR(40) NULL,
				`landlord_or_manager_name` VARCHAR(100) NULL,
				`landlord_or_manager_phone` VARCHAR(15) NULL,
				`monthly_rent` DECIMAL(10,2) NULL,
				`duration_of_residency_from` DATE NULL,
				`to` DATE NULL,
				`reason_for_leaving` VARCHAR(40) NULL,
				`notes` TEXT NULL
			) CHARSET utf8"
		);
		setupIndexes('residence_and_rental_history', ['tenant',]);

		setupTable(
			'employment_and_income_history', " 
			CREATE TABLE IF NOT EXISTS `employment_and_income_history` ( 
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`id`),
				`tenant` INT UNSIGNED NULL,
				`employer_name` VARCHAR(100) NULL,
				`city` VARCHAR(100) NULL,
				`employer_phone` VARCHAR(15) NULL,
				`employed_from` DATE NULL,
				`employed_till` DATE NULL,
				`occupation` VARCHAR(40) NULL,
				`notes` TEXT NULL
			) CHARSET utf8"
		);
		setupIndexes('employment_and_income_history', ['tenant',]);

		setupTable(
			'references', " 
			CREATE TABLE IF NOT EXISTS `references` ( 
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`id`),
				`tenant` INT UNSIGNED NULL,
				`reference_name` VARCHAR(100) NULL,
				`phone` VARCHAR(15) NULL
			) CHARSET utf8"
		);
		setupIndexes('references', ['tenant',]);

		setupTable(
			'rental_owners', " 
			CREATE TABLE IF NOT EXISTS `rental_owners` ( 
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`id`),
				`first_name` VARCHAR(40) NULL,
				`last_name` VARCHAR(40) NULL,
				`company_name` VARCHAR(40) NULL,
				`date_of_birth` DATE NULL,
				`primary_email` VARCHAR(40) NULL,
				`alternate_email` VARCHAR(40) NULL,
				`phone` VARCHAR(40) NULL,
				`country` VARCHAR(40) NULL,
				`street` VARCHAR(40) NULL,
				`city` VARCHAR(40) NULL,
				`state` VARCHAR(40) NULL,
				`zip` DECIMAL(15,0) NULL,
				`comments` TEXT NULL
			) CHARSET utf8"
		);

		setupTable(
			'properties', " 
			CREATE TABLE IF NOT EXISTS `properties` ( 
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`id`),
				`property_name` VARCHAR(100) NOT NULL,
				`photo` VARCHAR(40) NULL,
				`type` VARCHAR(40) NOT NULL,
				`number_of_units` DECIMAL(15,0) NULL,
				`owner` INT UNSIGNED NULL,
				`operating_account` VARCHAR(40) NULL,
				`property_reserve` DECIMAL(15,0) NULL,
				`lease_term` VARCHAR(15) NULL,
				`country` VARCHAR(40) NULL,
				`street` VARCHAR(40) NULL,
				`City` VARCHAR(40) NULL,
				`State` VARCHAR(40) NULL,
				`ZIP` DECIMAL(15,0) NULL
			) CHARSET utf8"
		);
		setupIndexes('properties', ['owner',]);

		setupTable(
			'property_photos', " 
			CREATE TABLE IF NOT EXISTS `property_photos` ( 
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`id`),
				`property` INT UNSIGNED NULL,
				`photo` VARCHAR(40) NULL,
				`description` TEXT NULL
			) CHARSET utf8"
		);
		setupIndexes('property_photos', ['property',]);

		setupTable(
			'units', " 
			CREATE TABLE IF NOT EXISTS `units` ( 
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`id`),
				`property` INT UNSIGNED NULL,
				`unit_number` VARCHAR(40) NULL,
				`photo` VARCHAR(40) NULL,
				`status` VARCHAR(40) NOT NULL,
				`size` VARCHAR(40) NULL,
				`country` INT UNSIGNED NULL,
				`street` INT UNSIGNED NULL,
				`city` INT UNSIGNED NULL,
				`state` INT UNSIGNED NULL,
				`postal_code` INT UNSIGNED NULL,
				`rooms` VARCHAR(40) NULL,
				`bathroom` DECIMAL(15,0) NULL,
				`features` TEXT NULL,
				`market_rent` DECIMAL(15,2) NULL,
				`rental_amount` DECIMAL(10,2) NULL,
				`deposit_amount` DECIMAL(10,2) NULL,
				`description` TEXT NULL
			) CHARSET utf8"
		);
		setupIndexes('units', ['property',]);

		setupTable(
			'unit_photos', " 
			CREATE TABLE IF NOT EXISTS `unit_photos` ( 
				`id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
				PRIMARY KEY (`id`),
				`unit` INT UNSIGNED NULL,
				`photo` VARCHAR(40) NULL,
				`description` TEXT NULL
			) CHARSET utf8"
		);
		setupIndexes('unit_photos', ['unit',]);



		// save MD5
		@file_put_contents($setupHash, $thisMD5);
	}


	function setupIndexes($tableName, $arrFields) {
		if(!is_array($arrFields) || !count($arrFields)) return false;

		foreach($arrFields as $fieldName) {
			if(!$res = @db_query("SHOW COLUMNS FROM `$tableName` like '$fieldName'")) continue;
			if(!$row = @db_fetch_assoc($res)) continue;
			if($row['Key']) continue;

			@db_query("ALTER TABLE `$tableName` ADD INDEX `$fieldName` (`$fieldName`)");
		}
	}


	function setupTable($tableName, $createSQL = '', $arrAlter = '') {
		global $Translation;
		$oldTableName = '';
		ob_start();

		echo '<div style="padding: 5px; border-bottom:solid 1px silver; font-family: verdana, arial; font-size: 10px;">';

		// is there a table rename query?
		if(is_array($arrAlter)) {
			$matches = [];
			if(preg_match("/ALTER TABLE `(.*)` RENAME `$tableName`/i", $arrAlter[0], $matches)) {
				$oldTableName = $matches[1];
			}
		}

		if($res = @db_query("SELECT COUNT(1) FROM `$tableName`")) { // table already exists
			if($row = @db_fetch_array($res)) {
				echo str_replace(['<TableName>', '<NumRecords>'], [$tableName, $row[0]], $Translation['table exists']);
				if(is_array($arrAlter)) {
					echo '<br>';
					foreach($arrAlter as $alter) {
						if($alter != '') {
							echo "$alter ... ";
							if(!@db_query($alter)) {
								echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
								echo '<div class="text-danger">' . $Translation['mysql said'] . ' ' . db_error(db_link()) . '</div>';
							} else {
								echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
							}
						}
					}
				} else {
					echo $Translation['table uptodate'];
				}
			} else {
				echo str_replace('<TableName>', $tableName, $Translation['couldnt count']);
			}
		} else { // given tableName doesn't exist

			if($oldTableName != '') { // if we have a table rename query
				if($ro = @db_query("SELECT COUNT(1) FROM `$oldTableName`")) { // if old table exists, rename it.
					$renameQuery = array_shift($arrAlter); // get and remove rename query

					echo "$renameQuery ... ";
					if(!@db_query($renameQuery)) {
						echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
						echo '<div class="text-danger">' . $Translation['mysql said'] . ' ' . db_error(db_link()) . '</div>';
					} else {
						echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
					}

					if(is_array($arrAlter)) setupTable($tableName, $createSQL, false, $arrAlter); // execute Alter queries on renamed table ...
				} else { // if old tableName doesn't exist (nor the new one since we're here), then just create the table.
					setupTable($tableName, $createSQL, false); // no Alter queries passed ...
				}
			} else { // tableName doesn't exist and no rename, so just create the table
				echo str_replace("<TableName>", $tableName, $Translation["creating table"]);
				if(!@db_query($createSQL)) {
					echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
					echo '<div class="text-danger">' . $Translation['mysql said'] . db_error(db_link()) . '</div>';

					// create table with a dummy field
					@db_query("CREATE TABLE IF NOT EXISTS `$tableName` (`_dummy_deletable_field` TINYINT)");
				} else {
					echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
				}
			}

			// set Admin group permissions for newly created table if membership_grouppermissions exists
			if($ro = @db_query("SELECT COUNT(1) FROM `membership_grouppermissions`")) {
				// get Admins group id
				$ro = @db_query("SELECT `groupID` FROM `membership_groups` WHERE `name`='Admins'");
				if($ro) {
					$adminGroupID = intval(db_fetch_row($ro)[0]);
					if($adminGroupID) @db_query("INSERT IGNORE INTO `membership_grouppermissions` SET
						`groupID`='$adminGroupID',
						`tableName`='$tableName',
						`allowInsert`=1, `allowView`=1, `allowEdit`=1, `allowDelete`=1
					");
				}
			}
		}

		echo '</div>';

		$out = ob_get_clean();
		if(defined('APPGINI_SETUP') && APPGINI_SETUP) echo $out;
	}
