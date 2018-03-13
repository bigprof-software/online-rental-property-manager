<?php
	// check this file's MD5 to make sure it wasn't called before
	$prevMD5=@implode('', @file(dirname(__FILE__).'/setup.md5'));
	$thisMD5=md5(@implode('', @file("./updateDB.php")));
	if($thisMD5==$prevMD5){
		$setupAlreadyRun=true;
	}else{
		// set up tables
		if(!isset($silent)){
			$silent=true;
		}

		// set up tables
		setupTable('applications_leases', "create table if not exists `applications_leases` (   `id` INT unsigned not null auto_increment , primary key (`id`), `tenants` INT unsigned , `status` VARCHAR(40) not null default 'Application' , `property` INT unsigned , `unit` INT unsigned , `type` VARCHAR(40) not null default 'Fixed' , `total_number_of_occupants` VARCHAR(15) , `start_date` DATE , `end_date` DATE , `recurring_charges_frequency` VARCHAR(40) not null default 'Monthly' , `next_due_date` DATE , `rent` DECIMAL(8,2) , `security_deposit` DECIMAL(15,2) , `security_deposit_date` DATE , `emergency_contact` VARCHAR(100) , `co_signer_details` VARCHAR(100) , `notes` TEXT , `agreement` VARCHAR(40) ) CHARSET utf8", $silent);
		setupIndexes('applications_leases', array('tenants','property','unit'));
		setupTable('residence_and_rental_history', "create table if not exists `residence_and_rental_history` (   `id` INT unsigned not null auto_increment , primary key (`id`), `tenant` INT unsigned , `address` VARCHAR(40) , `landlord_or_manager_name` VARCHAR(15) , `landlord_or_manager_phone` VARCHAR(15) , `monthly_rent` DECIMAL(6,2) , `duration_of_residency_from` DATE , `to` DATE , `reason_for_leaving` VARCHAR(40) , `notes` TEXT ) CHARSET utf8", $silent);
		setupIndexes('residence_and_rental_history', array('tenant'));
		setupTable('employment_and_income_history', "create table if not exists `employment_and_income_history` (   `id` INT unsigned not null auto_increment , primary key (`id`), `tenant` INT unsigned , `employer_name` VARCHAR(15) , `city` VARCHAR(20) , `employer_phone` VARCHAR(15) , `employed_from` DATE , `employed_till` DATE , `occupation` VARCHAR(40) , `notes` TEXT ) CHARSET utf8", $silent);
		setupIndexes('employment_and_income_history', array('tenant'));
		setupTable('references', "create table if not exists `references` (   `id` INT unsigned not null auto_increment , primary key (`id`), `tenant` INT unsigned , `reference_name` VARCHAR(15) , `phone` VARCHAR(15) ) CHARSET utf8", $silent);
		setupIndexes('references', array('tenant'));
		setupTable('applicants_and_tenants', "create table if not exists `applicants_and_tenants` (   `id` INT unsigned not null auto_increment , primary key (`id`), `last_name` VARCHAR(15) , `first_name` VARCHAR(15) , `email` VARCHAR(80) , `phone` VARCHAR(15) , `birth_date` DATE , `driver_license_number` VARCHAR(15) , `driver_license_state` VARCHAR(15) , `requested_lease_term` VARCHAR(15) , `monthly_gross_pay` DECIMAL(8,2) , `additional_income` DECIMAL(8,2) , `assets` DECIMAL(8,2) , `status` VARCHAR(40) not null default 'Applicant' , `notes` TEXT ) CHARSET utf8", $silent);
		setupTable('properties', "create table if not exists `properties` (   `id` INT unsigned not null auto_increment , primary key (`id`), `property_name` TEXT not null , `type` VARCHAR(40) not null , `number_of_units` DECIMAL(15,0) , `photo` VARCHAR(40) , `owner` INT unsigned , `operating_account` VARCHAR(40) , `property_reserve` DECIMAL(15,0) , `lease_term` VARCHAR(15) , `country` VARCHAR(40) , `street` VARCHAR(40) , `City` VARCHAR(40) , `State` VARCHAR(40) , `ZIP` DECIMAL(15,0) ) CHARSET utf8", $silent);
		setupIndexes('properties', array('owner'));
		setupTable('units', "create table if not exists `units` (   `id` INT unsigned not null auto_increment , primary key (`id`), `property` INT unsigned , `unit_number` VARCHAR(40) , `photo` VARCHAR(40) , `status` VARCHAR(40) not null , `size` VARCHAR(40) , `country` INT unsigned , `street` INT unsigned , `city` INT unsigned , `state` INT unsigned , `postal_code` INT unsigned , `rooms` VARCHAR(40) , `bathroom` DECIMAL(15,0) , `features` TEXT , `market_rent` DECIMAL(15,0) , `rental_amount` DECIMAL(6,2) , `deposit_amount` DECIMAL(6,2) , `description` TEXT ) CHARSET utf8", $silent);
		setupIndexes('units', array('property'));
		setupTable('rental_owners', "create table if not exists `rental_owners` (   `id` INT unsigned not null auto_increment , primary key (`id`), `first_name` VARCHAR(40) , `last_name` VARCHAR(40) , `company_name` VARCHAR(40) , `date_of_birth` DATE , `primary_email` VARCHAR(40) , `alternate_email` VARCHAR(40) , `phone` VARCHAR(40) , `country` VARCHAR(40) , `street` VARCHAR(40) , `city` VARCHAR(40) , `state` VARCHAR(40) , `zip` DECIMAL(15,0) , `comments` TEXT ) CHARSET utf8", $silent);


		// save MD5
		if($fp=@fopen(dirname(__FILE__).'/setup.md5', 'w')){
			fwrite($fp, $thisMD5);
			fclose($fp);
		}
	}


	function setupIndexes($tableName, $arrFields){
		if(!is_array($arrFields)){
			return false;
		}

		foreach($arrFields as $fieldName){
			if(!$res=@db_query("SHOW COLUMNS FROM `$tableName` like '$fieldName'")){
				continue;
			}
			if(!$row=@db_fetch_assoc($res)){
				continue;
			}
			if($row['Key']==''){
				@db_query("ALTER TABLE `$tableName` ADD INDEX `$fieldName` (`$fieldName`)");
			}
		}
	}


	function setupTable($tableName, $createSQL='', $silent=true, $arrAlter=''){
		global $Translation;
		ob_start();

		echo '<div style="padding: 5px; border-bottom:solid 1px silver; font-family: verdana, arial; font-size: 10px;">';

		// is there a table rename query?
		if(is_array($arrAlter)){
			$matches=array();
			if(preg_match("/ALTER TABLE `(.*)` RENAME `$tableName`/", $arrAlter[0], $matches)){
				$oldTableName=$matches[1];
			}
		}

		if($res=@db_query("select count(1) from `$tableName`")){ // table already exists
			if($row = @db_fetch_array($res)){
				echo str_replace("<TableName>", $tableName, str_replace("<NumRecords>", $row[0],$Translation["table exists"]));
				if(is_array($arrAlter)){
					echo '<br>';
					foreach($arrAlter as $alter){
						if($alter!=''){
							echo "$alter ... ";
							if(!@db_query($alter)){
								echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
								echo '<div class="text-danger">' . $Translation['mysql said'] . ' ' . db_error(db_link()) . '</div>';
							}else{
								echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
							}
						}
					}
				}else{
					echo $Translation["table uptodate"];
				}
			}else{
				echo str_replace("<TableName>", $tableName, $Translation["couldnt count"]);
			}
		}else{ // given tableName doesn't exist

			if($oldTableName!=''){ // if we have a table rename query
				if($ro=@db_query("select count(1) from `$oldTableName`")){ // if old table exists, rename it.
					$renameQuery=array_shift($arrAlter); // get and remove rename query

					echo "$renameQuery ... ";
					if(!@db_query($renameQuery)){
						echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
						echo '<div class="text-danger">' . $Translation['mysql said'] . ' ' . db_error(db_link()) . '</div>';
					}else{
						echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
					}

					if(is_array($arrAlter)) setupTable($tableName, $createSQL, false, $arrAlter); // execute Alter queries on renamed table ...
				}else{ // if old tableName doesn't exist (nor the new one since we're here), then just create the table.
					setupTable($tableName, $createSQL, false); // no Alter queries passed ...
				}
			}else{ // tableName doesn't exist and no rename, so just create the table
				echo str_replace("<TableName>", $tableName, $Translation["creating table"]);
				if(!@db_query($createSQL)){
					echo '<span class="label label-danger">' . $Translation['failed'] . '</span>';
					echo '<div class="text-danger">' . $Translation['mysql said'] . db_error(db_link()) . '</div>';
				}else{
					echo '<span class="label label-success">' . $Translation['ok'] . '</span>';
				}
			}
		}

		echo "</div>";

		$out=ob_get_contents();
		ob_end_clean();
		if(!$silent){
			echo $out;
		}
	}
?>