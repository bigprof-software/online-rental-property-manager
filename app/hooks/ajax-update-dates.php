<?php
	define('TESTING_MODE', false); // set to true to see the generated queries instead of executing them
    define('PREPEND_PATH', '../');
    define('APP_ROOT', dirname(__DIR__) . '/');
	include(APP_ROOT . 'lib.php');

	// find most recent applicant birth date (applicants_and_tenants.birth_date)
	$applicant_birth_date = sqlValue("SELECT MAX(birth_date) FROM `applicants_and_tenants`");

	// if that applicant is already 26 years old or younger, then we don't need to do anything
	if (floor((time() - strtotime($applicant_birth_date)) / (365 * 24 * 60 * 60)) <= 26) {
		exit;
	}

	// figure out how many years we need to add to make that applicant 23 years old
	$years_to_add = floor((time() - strtotime($applicant_birth_date)) / (365 * 24 * 60 * 60)) - 23;

	$queries = [];

	// update all applicant birth dates by adding the number of years we just calculated
	$queries[] = "UPDATE `applicants_and_tenants` SET birth_date = DATE_ADD(birth_date, INTERVAL $years_to_add YEAR)";

	// find most recent lease start date (applications_leases.start_date)
	$lease_start_date = sqlValue("SELECT MAX(start_date) FROM `applications_leases`");

	// figure out how many years we need to add to make that lease 2 years old
	$years_to_add = floor((time() - strtotime($lease_start_date)) / (365 * 24 * 60 * 60)) - 2;

	// update all lease start dates by adding the number of years we just calculated
	$queries[] = "UPDATE `applications_leases` SET start_date = DATE_ADD(start_date, INTERVAL $years_to_add YEAR)";
	// also shift end_date by the same number of years if present
	$queries[] = "UPDATE `applications_leases` SET end_date = DATE_ADD(end_date, INTERVAL $years_to_add YEAR) WHERE end_date IS NOT NULL";
	// same for next_due_date
	$queries[] = "UPDATE `applications_leases` SET next_due_date = DATE_ADD(next_due_date, INTERVAL $years_to_add YEAR) WHERE next_due_date IS NOT NULL";
	// same for security_deposit_date
	$queries[] = "UPDATE `applications_leases` SET security_deposit_date = DATE_ADD(security_deposit_date, INTERVAL $years_to_add YEAR) WHERE security_deposit_date IS NOT NULL";

	// find most recent rental_owners birth date (rental_owners.date_of_birth)
	$owner_birth_date = sqlValue("SELECT MAX(date_of_birth) FROM `rental_owners`");
	// figure out how many years we need to add to make that owner 26 years old
	$years_to_add = floor((time() - strtotime($owner_birth_date)) / (365 * 24 * 60 * 60)) - 26;
	// update all rental owner birth dates by adding the number of years we just calculated
	$queries[] = "UPDATE `rental_owners` SET date_of_birth = DATE_ADD(date_of_birth, INTERVAL $years_to_add YEAR)";


	// log queries for testing (as json response)
	if (TESTING_MODE) {
		header('Content-Type: application/json');
		echo json_encode($queries);
		exit;
	}

	// execute queries
	$eo = ['silentErrors' => true];
	foreach ($queries as $query) {
		sql($query, $eo);
	}
