<?php
// This script and data application were generated by AppGini 5.95
// Download AppGini for free from https://bigprof.com/appgini/download/

	$currDir = dirname(__FILE__);
	include_once("{$currDir}/lib.php");
	@include_once("{$currDir}/hooks/employment_and_income_history.php");
	include_once("{$currDir}/employment_and_income_history_dml.php");

	// mm: can the current member access this page?
	$perm = getTablePermissions('employment_and_income_history');
	if(!$perm['access']) {
		echo error_message($Translation['tableAccessDenied'], false);
		echo '<script>setTimeout(function() { window.location = "index.php?signOut=1"; }, 2000);</script>';
		exit;
	}

	$x = new DataList;
	$x->TableName = 'employment_and_income_history';

	// Fields that can be displayed in the table view
	$x->QueryFieldsTV = [
		"`employment_and_income_history`.`id`" => "id",
		"IF(    CHAR_LENGTH(`applicants_and_tenants1`.`first_name`) || CHAR_LENGTH(`applicants_and_tenants1`.`last_name`), CONCAT_WS('',   `applicants_and_tenants1`.`first_name`, ' ', `applicants_and_tenants1`.`last_name`), '') /* Tenant */" => "tenant",
		"`employment_and_income_history`.`employer_name`" => "employer_name",
		"`employment_and_income_history`.`city`" => "city",
		"CONCAT_WS('-', LEFT(`employment_and_income_history`.`employer_phone`,3), MID(`employment_and_income_history`.`employer_phone`,4,3), RIGHT(`employment_and_income_history`.`employer_phone`,4))" => "employer_phone",
		"if(`employment_and_income_history`.`employed_from`,date_format(`employment_and_income_history`.`employed_from`,'%m/%d/%Y'),'')" => "employed_from",
		"if(`employment_and_income_history`.`employed_till`,date_format(`employment_and_income_history`.`employed_till`,'%m/%d/%Y'),'')" => "employed_till",
		"`employment_and_income_history`.`occupation`" => "occupation",
		"`employment_and_income_history`.`notes`" => "notes",
	];
	// mapping incoming sort by requests to actual query fields
	$x->SortFields = [
		1 => '`employment_and_income_history`.`id`',
		2 => 2,
		3 => 3,
		4 => 4,
		5 => 5,
		6 => '`employment_and_income_history`.`employed_from`',
		7 => '`employment_and_income_history`.`employed_till`',
		8 => 8,
		9 => 9,
	];

	// Fields that can be displayed in the csv file
	$x->QueryFieldsCSV = [
		"`employment_and_income_history`.`id`" => "id",
		"IF(    CHAR_LENGTH(`applicants_and_tenants1`.`first_name`) || CHAR_LENGTH(`applicants_and_tenants1`.`last_name`), CONCAT_WS('',   `applicants_and_tenants1`.`first_name`, ' ', `applicants_and_tenants1`.`last_name`), '') /* Tenant */" => "tenant",
		"`employment_and_income_history`.`employer_name`" => "employer_name",
		"`employment_and_income_history`.`city`" => "city",
		"CONCAT_WS('-', LEFT(`employment_and_income_history`.`employer_phone`,3), MID(`employment_and_income_history`.`employer_phone`,4,3), RIGHT(`employment_and_income_history`.`employer_phone`,4))" => "employer_phone",
		"if(`employment_and_income_history`.`employed_from`,date_format(`employment_and_income_history`.`employed_from`,'%m/%d/%Y'),'')" => "employed_from",
		"if(`employment_and_income_history`.`employed_till`,date_format(`employment_and_income_history`.`employed_till`,'%m/%d/%Y'),'')" => "employed_till",
		"`employment_and_income_history`.`occupation`" => "occupation",
		"`employment_and_income_history`.`notes`" => "notes",
	];
	// Fields that can be filtered
	$x->QueryFieldsFilters = [
		"`employment_and_income_history`.`id`" => "ID",
		"IF(    CHAR_LENGTH(`applicants_and_tenants1`.`first_name`) || CHAR_LENGTH(`applicants_and_tenants1`.`last_name`), CONCAT_WS('',   `applicants_and_tenants1`.`first_name`, ' ', `applicants_and_tenants1`.`last_name`), '') /* Tenant */" => "Tenant",
		"`employment_and_income_history`.`employer_name`" => "Employer name",
		"`employment_and_income_history`.`city`" => "City",
		"`employment_and_income_history`.`employer_phone`" => "Employer phone",
		"`employment_and_income_history`.`employed_from`" => "employed from",
		"`employment_and_income_history`.`employed_till`" => "Employed till",
		"`employment_and_income_history`.`occupation`" => "Occupation",
		"`employment_and_income_history`.`notes`" => "Notes",
	];

	// Fields that can be quick searched
	$x->QueryFieldsQS = [
		"`employment_and_income_history`.`id`" => "id",
		"IF(    CHAR_LENGTH(`applicants_and_tenants1`.`first_name`) || CHAR_LENGTH(`applicants_and_tenants1`.`last_name`), CONCAT_WS('',   `applicants_and_tenants1`.`first_name`, ' ', `applicants_and_tenants1`.`last_name`), '') /* Tenant */" => "tenant",
		"`employment_and_income_history`.`employer_name`" => "employer_name",
		"`employment_and_income_history`.`city`" => "city",
		"CONCAT_WS('-', LEFT(`employment_and_income_history`.`employer_phone`,3), MID(`employment_and_income_history`.`employer_phone`,4,3), RIGHT(`employment_and_income_history`.`employer_phone`,4))" => "employer_phone",
		"if(`employment_and_income_history`.`employed_from`,date_format(`employment_and_income_history`.`employed_from`,'%m/%d/%Y'),'')" => "employed_from",
		"if(`employment_and_income_history`.`employed_till`,date_format(`employment_and_income_history`.`employed_till`,'%m/%d/%Y'),'')" => "employed_till",
		"`employment_and_income_history`.`occupation`" => "occupation",
		"`employment_and_income_history`.`notes`" => "notes",
	];

	// Lookup fields that can be used as filterers
	$x->filterers = ['tenant' => 'Tenant', ];

	$x->QueryFrom = "`employment_and_income_history` LEFT JOIN `applicants_and_tenants` as applicants_and_tenants1 ON `applicants_and_tenants1`.`id`=`employment_and_income_history`.`tenant` ";
	$x->QueryWhere = '';
	$x->QueryOrder = '';

	$x->AllowSelection = 1;
	$x->HideTableView = ($perm['view'] == 0 ? 1 : 0);
	$x->AllowDelete = $perm['delete'];
	$x->AllowMassDelete = true;
	$x->AllowInsert = $perm['insert'];
	$x->AllowUpdate = $perm['edit'];
	$x->SeparateDV = 1;
	$x->AllowDeleteOfParents = 0;
	$x->AllowFilters = 1;
	$x->AllowSavingFilters = 1;
	$x->AllowSorting = 1;
	$x->AllowNavigation = 1;
	$x->AllowPrinting = 1;
	$x->AllowPrintingDV = 1;
	$x->AllowCSV = 1;
	$x->RecordsPerPage = 10;
	$x->QuickSearch = 1;
	$x->QuickSearchText = $Translation['quick search'];
	$x->ScriptFileName = 'employment_and_income_history_view.php';
	$x->RedirectAfterInsert = 'employment_and_income_history_view.php?SelectedID=#ID#';
	$x->TableTitle = 'Employment and income history';
	$x->TableIcon = 'resources/table_icons/cash_stack.png';
	$x->PrimaryKey = '`employment_and_income_history`.`id`';

	$x->ColWidth = [80, 100, 100, 80, 80, 100, 50, ];
	$x->ColCaption = ['Employer name', 'City', 'Employer phone', 'employed from', 'Employed till', 'Occupation', 'Notes', ];
	$x->ColFieldName = ['employer_name', 'city', 'employer_phone', 'employed_from', 'employed_till', 'occupation', 'notes', ];
	$x->ColNumber  = [3, 4, 5, 6, 7, 8, 9, ];

	// template paths below are based on the app main directory
	$x->Template = 'templates/employment_and_income_history_templateTV.html';
	$x->SelectedTemplate = 'templates/employment_and_income_history_templateTVS.html';
	$x->TemplateDV = 'templates/employment_and_income_history_templateDV.html';
	$x->TemplateDVP = 'templates/employment_and_income_history_templateDVP.html';

	$x->ShowTableHeader = 1;
	$x->TVClasses = "";
	$x->DVClasses = "";
	$x->HasCalculatedFields = false;
	$x->AllowConsoleLog = false;
	$x->AllowDVNavigation = true;

	// mm: build the query based on current member's permissions
	$DisplayRecords = $_REQUEST['DisplayRecords'];
	if(!in_array($DisplayRecords, ['user', 'group'])) { $DisplayRecords = 'all'; }
	if($perm['view'] == 1 || ($perm['view'] > 1 && $DisplayRecords == 'user' && !$_REQUEST['NoFilter_x'])) { // view owner only
		$x->QueryFrom .= ', `membership_userrecords`';
		$x->QueryWhere = "WHERE `employment_and_income_history`.`id`=`membership_userrecords`.`pkValue` AND `membership_userrecords`.`tableName`='employment_and_income_history' AND LCASE(`membership_userrecords`.`memberID`)='" . getLoggedMemberID() . "'";
	} elseif($perm['view'] == 2 || ($perm['view'] > 2 && $DisplayRecords == 'group' && !$_REQUEST['NoFilter_x'])) { // view group only
		$x->QueryFrom .= ', `membership_userrecords`';
		$x->QueryWhere = "WHERE `employment_and_income_history`.`id`=`membership_userrecords`.`pkValue` AND `membership_userrecords`.`tableName`='employment_and_income_history' AND `membership_userrecords`.`groupID`='" . getLoggedGroupID() . "'";
	} elseif($perm['view'] == 3) { // view all
		// no further action
	} elseif($perm['view'] == 0) { // view none
		$x->QueryFields = ['Not enough permissions' => 'NEP'];
		$x->QueryFrom = '`employment_and_income_history`';
		$x->QueryWhere = '';
		$x->DefaultSortField = '';
	}
	// hook: employment_and_income_history_init
	$render = true;
	if(function_exists('employment_and_income_history_init')) {
		$args = [];
		$render = employment_and_income_history_init($x, getMemberInfo(), $args);
	}

	if($render) $x->Render();

	// hook: employment_and_income_history_header
	$headerCode = '';
	if(function_exists('employment_and_income_history_header')) {
		$args = [];
		$headerCode = employment_and_income_history_header($x->ContentType, getMemberInfo(), $args);
	}

	if(!$headerCode) {
		include_once("{$currDir}/header.php"); 
	} else {
		ob_start();
		include_once("{$currDir}/header.php");
		echo str_replace('<%%HEADER%%>', ob_get_clean(), $headerCode);
	}

	echo $x->HTML;

	// hook: employment_and_income_history_footer
	$footerCode = '';
	if(function_exists('employment_and_income_history_footer')) {
		$args = [];
		$footerCode = employment_and_income_history_footer($x->ContentType, getMemberInfo(), $args);
	}

	if(!$footerCode) {
		include_once("{$currDir}/footer.php"); 
	} else {
		ob_start();
		include_once("{$currDir}/footer.php");
		echo str_replace('<%%FOOTER%%>', ob_get_clean(), $footerCode);
	}
