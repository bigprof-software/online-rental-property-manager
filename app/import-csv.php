<?php
	define('PREPEND_PATH', '');
	include_once(__DIR__ . '/lib.php');

	// accept a record as an assoc array, return transformed row ready to insert to table
	$transformFunctions = [
		'applicants_and_tenants' => function($data, $options = []) {
			if(isset($data['phone'])) $data['phone'] = str_replace('-', '', $data['phone']);
			if(isset($data['birth_date'])) $data['birth_date'] = guessMySQLDateTime($data['birth_date']);
			if(isset($data['monthly_gross_pay'])) $data['monthly_gross_pay'] = preg_replace('/[^\d\.]/', '', $data['monthly_gross_pay']);
			if(isset($data['additional_income'])) $data['additional_income'] = preg_replace('/[^\d\.]/', '', $data['additional_income']);
			if(isset($data['assets'])) $data['assets'] = preg_replace('/[^\d\.]/', '', $data['assets']);

			return $data;
		},
		'applications_leases' => function($data, $options = []) {
			if(isset($data['tenants'])) $data['tenants'] = pkGivenLookupText($data['tenants'], 'applications_leases', 'tenants');
			if(isset($data['property'])) $data['property'] = pkGivenLookupText($data['property'], 'applications_leases', 'property');
			if(isset($data['unit'])) $data['unit'] = pkGivenLookupText($data['unit'], 'applications_leases', 'unit');
			if(isset($data['start_date'])) $data['start_date'] = guessMySQLDateTime($data['start_date']);
			if(isset($data['end_date'])) $data['end_date'] = guessMySQLDateTime($data['end_date']);
			if(isset($data['next_due_date'])) $data['next_due_date'] = guessMySQLDateTime($data['next_due_date']);
			if(isset($data['rent'])) $data['rent'] = preg_replace('/[^\d\.]/', '', $data['rent']);
			if(isset($data['security_deposit'])) $data['security_deposit'] = preg_replace('/[^\d\.]/', '', $data['security_deposit']);
			if(isset($data['security_deposit_date'])) $data['security_deposit_date'] = guessMySQLDateTime($data['security_deposit_date']);

			return $data;
		},
		'residence_and_rental_history' => function($data, $options = []) {
			if(isset($data['tenant'])) $data['tenant'] = pkGivenLookupText($data['tenant'], 'residence_and_rental_history', 'tenant');
			if(isset($data['monthly_rent'])) $data['monthly_rent'] = preg_replace('/[^\d\.]/', '', $data['monthly_rent']);
			if(isset($data['duration_of_residency_from'])) $data['duration_of_residency_from'] = guessMySQLDateTime($data['duration_of_residency_from']);
			if(isset($data['to'])) $data['to'] = guessMySQLDateTime($data['to']);

			return $data;
		},
		'employment_and_income_history' => function($data, $options = []) {
			if(isset($data['tenant'])) $data['tenant'] = pkGivenLookupText($data['tenant'], 'employment_and_income_history', 'tenant');
			if(isset($data['employer_phone'])) $data['employer_phone'] = str_replace('-', '', $data['employer_phone']);
			if(isset($data['employed_from'])) $data['employed_from'] = guessMySQLDateTime($data['employed_from']);
			if(isset($data['employed_till'])) $data['employed_till'] = guessMySQLDateTime($data['employed_till']);

			return $data;
		},
		'references' => function($data, $options = []) {
			if(isset($data['tenant'])) $data['tenant'] = pkGivenLookupText($data['tenant'], 'references', 'tenant');
			if(isset($data['phone'])) $data['phone'] = str_replace('-', '', $data['phone']);

			return $data;
		},
		'rental_owners' => function($data, $options = []) {
			if(isset($data['date_of_birth'])) $data['date_of_birth'] = guessMySQLDateTime($data['date_of_birth']);
			if(isset($data['phone'])) $data['phone'] = str_replace('-', '', $data['phone']);

			return $data;
		},
		'properties' => function($data, $options = []) {
			if(isset($data['owner'])) $data['owner'] = pkGivenLookupText($data['owner'], 'properties', 'owner');
			if(isset($data['property_reserve'])) $data['property_reserve'] = preg_replace('/[^\d\.]/', '', $data['property_reserve']);

			return $data;
		},
		'property_photos' => function($data, $options = []) {
			if(isset($data['property'])) $data['property'] = pkGivenLookupText($data['property'], 'property_photos', 'property');

			return $data;
		},
		'units' => function($data, $options = []) {
			if(isset($data['property'])) $data['property'] = pkGivenLookupText($data['property'], 'units', 'property');
			if(isset($data['market_rent'])) $data['market_rent'] = preg_replace('/[^\d\.]/', '', $data['market_rent']);
			if(isset($data['rental_amount'])) $data['rental_amount'] = preg_replace('/[^\d\.]/', '', $data['rental_amount']);
			if(isset($data['deposit_amount'])) $data['deposit_amount'] = preg_replace('/[^\d\.]/', '', $data['deposit_amount']);
			if(isset($data['country'])) $data['country'] = thisOr($data['property'], pkGivenLookupText($data['country'], 'units', 'country'));
			if(isset($data['street'])) $data['street'] = thisOr($data['property'], pkGivenLookupText($data['street'], 'units', 'street'));
			if(isset($data['city'])) $data['city'] = thisOr($data['property'], pkGivenLookupText($data['city'], 'units', 'city'));
			if(isset($data['state'])) $data['state'] = thisOr($data['property'], pkGivenLookupText($data['state'], 'units', 'state'));
			if(isset($data['postal_code'])) $data['postal_code'] = thisOr($data['property'], pkGivenLookupText($data['postal_code'], 'units', 'postal_code'));

			return $data;
		},
		'unit_photos' => function($data, $options = []) {
			if(isset($data['unit'])) $data['unit'] = pkGivenLookupText($data['unit'], 'unit_photos', 'unit');

			return $data;
		},
	];

	// accept a record as an assoc array, return a boolean indicating whether to import or skip record
	$filterFunctions = [
		'applicants_and_tenants' => function($data, $options = []) { return true; },
		'applications_leases' => function($data, $options = []) { return true; },
		'residence_and_rental_history' => function($data, $options = []) { return true; },
		'employment_and_income_history' => function($data, $options = []) { return true; },
		'references' => function($data, $options = []) { return true; },
		'rental_owners' => function($data, $options = []) { return true; },
		'properties' => function($data, $options = []) { return true; },
		'property_photos' => function($data, $options = []) { return true; },
		'units' => function($data, $options = []) { return true; },
		'unit_photos' => function($data, $options = []) { return true; },
	];

	/*
	Hook file for overwriting/amending $transformFunctions and $filterFunctions:
	hooks/import-csv.php
	If found, it's included below

	The way this works is by either completely overwriting any of the above 2 arrays,
	or, more commonly, overwriting a single function, for example:
		$transformFunctions['tablename'] = function($data, $options = []) {
			// new definition here
			// then you must return transformed data
			return $data;
		};

	Another scenario is transforming a specific field and leaving other fields to the default
	transformation. One possible way of doing this is to store the original transformation function
	in GLOBALS array, calling it inside the custom transformation function, then modifying the
	specific field:
		$GLOBALS['originalTransformationFunction'] = $transformFunctions['tablename'];
		$transformFunctions['tablename'] = function($data, $options = []) {
			$data = call_user_func_array($GLOBALS['originalTransformationFunction'], [$data, $options]);
			$data['fieldname'] = 'transformed value';
			return $data;
		};
	*/

	@include(__DIR__ . '/hooks/import-csv.php');

	$ui = new CSVImportUI($transformFunctions, $filterFunctions);
