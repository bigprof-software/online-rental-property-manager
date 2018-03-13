<?php

define('datalist_filters_count', 20);
define('datalist_image_uploads_exist', True);
define('datalist_max_records_multi_selection', 1000);
define('datalist_max_page_lump', 50);
define('datalist_max_records_dv_print', 100);
define('datalist_auto_complete_size', 1000);
define('datalist_date_separator', '/');
define('datalist_date_format', 'mdY');

$curr_dir = dirname(__FILE__);
require_once($curr_dir . '/combo.class.php');
require_once($curr_dir . '/data_combo.class.php');
require_once($curr_dir . '/date_combo.class.php');

class DataList{
	// this class generates the data table ...

	var $QueryFieldsTV,
		$QueryFieldsCSV,
		$QueryFieldsFilters,
		$QueryFieldsQS,
		$QueryFrom,
		$QueryWhere,
		$QueryOrder,
		$filterers,

		$ColWidth, // array of field widths
		$DataHeight,
		$TableName,

		$AllowSelection,
		$AllowDelete,
		$AllowMassDelete,
		$AllowDeleteOfParents,
		$AllowInsert,
		$AllowUpdate,
		$SeparateDV,
		$Permissions,
		$AllowFilters,
		$AllowSavingFilters,
		$AllowSorting,
		$AllowNavigation,
		$AllowPrinting,
		$HideTableView,
		$AllowCSV,
		$CSVSeparator,

		$QuickSearch, // 0 to 3

		$RecordsPerPage,
		$ScriptFileName,
		$RedirectAfterInsert,
		$TableTitle,
		$PrimaryKey,
		$DefaultSortField,
		$DefaultSortDirection,

		// Templates variables
		$Template,
		$SelectedTemplate,
		$TemplateDV,
		$TemplateDVP,
		$ShowTableHeader, // 1 = show standard table headers
		$ShowRecordSlots, // 1 = show empty record slots in table view
		$TVClasses,
		$DVClasses,
		// End of templates variables

		$ContentType,    // set by DataList to 'tableview', 'detailview', 'tableview+detailview', 'print-tableview', 'print-detailview' or 'filters'
		$HTML;           // generated html after calling Render()

	function __construct(){  // PHP 7 compatibility
		$this->DataList();
	}

	function DataList(){     // Constructor function
		$this->DataHeight = 150;

		$this->AllowSelection = 1;
		$this->AllowDelete = 1;
		$this->AllowInsert = 1;
		$this->AllowUpdate = 1;
		$this->AllowFilters = 1;
		$this->AllowNavigation = 1;
		$this->AllowPrinting = 1;
		$this->HideTableView = 0;
		$this->QuickSearch = 0;
		$this->AllowCSV = 0;
		$this->CSVSeparator = ",";
		$this->HighlightColor = '#FFF0C2';  // default highlight color

		$this->RecordsPerPage = 10;
		$this->Template = '';
		$this->HTML = '';
		$this->filterers = array();
	}

	function showTV(){
		if($this->SeparateDV){
			$this->HideTableView = ($this->Permissions[2]==0 ? 1 : 0);
		}
	}

	function hideTV(){
		if($this->SeparateDV){
			$this->HideTableView = 1;
		}
	}

	function Render(){
	// get post and get variables
		global $Translation;

		$adminConfig = config('adminConfig');

		$FiltersPerGroup = 4;
		$buttonWholeWidth = 136;

		$current_view = ''; /* TV, DV, TVDV, TVP, DVP, Filters */

		$Embedded = intval($_REQUEST['Embedded']);
		$AutoClose = intval($_REQUEST['AutoClose']);

		$SortField = $_REQUEST["SortField"];
		$SortDirection = $_REQUEST["SortDirection"];
		$FirstRecord = $_REQUEST["FirstRecord"];
		$ScrollUp_y = $_REQUEST["ScrollUp_y"];
		$ScrollDn_y = $_REQUEST["ScrollDn_y"];
		$Previous_x = $_REQUEST["Previous_x"];
		$Next_x = $_REQUEST["Next_x"];
		$Filter_x = $_REQUEST["Filter_x"];
		$SaveFilter_x = $_REQUEST["SaveFilter_x"];
		$NoFilter_x = $_REQUEST["NoFilter_x"];
		$CancelFilter = $_REQUEST["CancelFilter"];
		$ApplyFilter = $_REQUEST["ApplyFilter"];
		$Search_x = $_REQUEST["Search_x"];
		$SearchString = (get_magic_quotes_gpc() ? stripslashes($_REQUEST['SearchString']) : $_REQUEST['SearchString']);
		$CSV_x = $_REQUEST["CSV_x"];

		$FilterAnd = $_REQUEST["FilterAnd"];
		$FilterField = $_REQUEST["FilterField"];
		$FilterOperator = $_REQUEST["FilterOperator"];
		if(is_array($_REQUEST['FilterValue'])){
			foreach($_REQUEST['FilterValue'] as $fvi=>$fv){
				$FilterValue[$fvi]=(get_magic_quotes_gpc() ? stripslashes($fv) : $fv);
			}
		}

		$Print_x = $_REQUEST['Print_x'];
		$PrintTV = $_REQUEST['PrintTV'];
		$PrintDV = $_REQUEST['PrintDV'];
		$SelectedID = (get_magic_quotes_gpc() ? stripslashes($_REQUEST['SelectedID']) : $_REQUEST['SelectedID']);
		$insert_x = $_REQUEST['insert_x'];
		$update_x = $_REQUEST['update_x'];
		$delete_x = $_REQUEST['delete_x'];
		$SkipChecks = $_REQUEST['confirmed'];
		$deselect_x = $_REQUEST['deselect_x'];
		$addNew_x = $_REQUEST['addNew_x'];
		$dvprint_x = $_REQUEST['dvprint_x'];
		$DisplayRecords = (in_array($_REQUEST['DisplayRecords'], array('user', 'group')) ? $_REQUEST['DisplayRecords'] : 'all');

		$mi = getMemberInfo();

	// insure authenticity of user inputs:
		if(is_array($FilterAnd)){
			foreach($FilterAnd as $i => $f){
				if($f && !preg_match('/^(and|or)$/i', trim($f))){
					$FilterAnd[$i] = 'and';
				}
			}
		}
		if(is_array($FilterField)){
			foreach($FilterField as $ffi => $ffn){
				$FilterField[$ffi] = intval($ffn);
			}
		}
		if(is_array($FilterOperator)){
			foreach($FilterOperator as $i => $f){
				if($f && !in_array(trim($f), array_keys($GLOBALS['filter_operators']))){
					$FilterOperator[$i] = '';
				}
			}
		}
		if(!preg_match('/^\s*[1-9][0-9]*\s*(asc|desc)?(\s*,\s*[1-9][0-9]*\s*(asc|desc)?)*$/i', $SortField)){
			$SortField = '';
		}
		if(!preg_match('/^(asc|desc)$/i', $SortDirection)){
			$SortDirection = '';
		}

		if(!$this->AllowDelete){
			$delete_x = '';
		}
		if(!$this->AllowDeleteOfParents){
			$SkipChecks = '';
		}
		if(!$this->AllowInsert){
			$insert_x = '';
			$addNew_x = '';
		}
		if(!$this->AllowUpdate){
			$update_x = '';
		}
		if(!$this->AllowFilters){
			$Filter_x = '';
		}
		if(!$this->AllowPrinting){
			$Print_x = '';
			$PrintTV = '';
		}
		if(!$this->QuickSearch){
			$SearchString = '';
		}
		if(!$this->AllowCSV){
			$CSV_x = '';
		}

	// enforce record selection if user has edit/delete permissions on the current table
		$AllowPrintDV=1;
		$this->Permissions=getTablePermissions($this->TableName);
		if($this->Permissions[3] || $this->Permissions[4]){ // current user can edit or delete?
			$this->AllowSelection = 1;
		}elseif(!$this->AllowSelection){
			$SelectedID='';
			$AllowPrintDV=0;
			$PrintDV='';
		}

		if(!$this->AllowSelection || !$SelectedID){ $dvprint_x=''; }

		$this->QueryFieldsIndexed=reIndex($this->QueryFieldsFilters);

	// determine type of current view: TV, DV, TVDV, TVP, DVP or Filters?
		if($this->SeparateDV){
			$current_view = 'TV';
			if($Print_x != '' || $PrintTV != '') $current_view = 'TVP';
			elseif($dvprint_x != '' || $PrintDV != '') $current_view = 'DVP';
			elseif($Filter_x != '') $current_view = 'Filters';
			elseif(($SelectedID && !$deselect_x && !$delete_x) || $addNew_x != '') $current_view = 'DV';
		}else{
			$current_view = 'TVDV';
			if($Print_x != '' || $PrintTV != '') $current_view = 'TVP';
			elseif($dvprint_x != '' || $PrintDV != '') $current_view = 'DVP';
			elseif($Filter_x != '') $current_view = 'Filters';
		}

		$this->HTML .= '<div class="row"><div class="col-xs-11 col-md-12">';
		$this->HTML .= '<form ' . (datalist_image_uploads_exist ? 'enctype="multipart/form-data" ' : '') . 'method="post" name="myform" action="' . $this->ScriptFileName . '">';
		if($Embedded) $this->HTML .= '<input name="Embedded" value="1" type="hidden">';
		if($AutoClose) $this->HTML .= '<input name="AutoClose" value="1" type="hidden">';
		$this->HTML .= '<script>';
		$this->HTML .= 'function enterAction(){';
		$this->HTML .= '   if($j("input[name=SearchString]:focus").length){ $j("#Search").click(); }';
		$this->HTML .= '   return false;';
		$this->HTML .= '}';
		$this->HTML .= '</script>';
		$this->HTML .= '<input id="EnterAction" type="submit" style="position: absolute; left: 0px; top: -250px;" onclick="return enterAction();">';

		$this->ContentType='tableview'; // default content type

		if($PrintTV != ''){
			$Print_x = 1;
			$_REQUEST['Print_x'] = 1;
		}

	// handle user commands ...
		if($deselect_x != ''){
			$SelectedID = '';
			$this->showTV();
		}

		elseif($insert_x != ''){
			$SelectedID = call_user_func($this->TableName.'_insert');

			// redirect to a safe url to avoid refreshing and thus
			// insertion of duplicate records.
			$url = $this->RedirectAfterInsert;
			$insert_status = 'record-added-ok=' . rand();
			if(!$SelectedID) $insert_status = 'record-added-error=' . rand();

			// compose filters and sorting
			foreach($this->filterers as $filterer => $caption){
				if($_REQUEST['filterer_' . $filterer] != '') $filtersGET .= '&filterer_' . $filterer . '=' . urlencode($_REQUEST['filterer_' . $filterer]);
			}
			for($i = 1; $i <= (20 * $FiltersPerGroup); $i++){ // Number of filters allowed
				if($FilterField[$i] != '' && $FilterOperator[$i] != '' && ($FilterValue[$i] != '' || strpos($FilterOperator[$i], 'empty'))){
					$filtersGET .= "&FilterAnd[{$i}]={$FilterAnd[$i]}&FilterField[{$i}]={$FilterField[$i]}&FilterOperator[{$i}]={$FilterOperator[$i]}&FilterValue[{$i}]=" . urlencode($FilterValue[$i]);
				}
			}
			if($Embedded) $filtersGET .= '&Embedded=1&SelectedID=' . urlencode($SelectedID);
			if($AutoClose) $filtersGET .= '&AutoClose=1';
			$filtersGET .= "&SortField={$SortField}&SortDirection={$SortDirection}&FirstRecord={$FirstRecord}";
			$filtersGET .= "&DisplayRecords={$DisplayRecords}";
			$filtersGET .= '&SearchString=' . urlencode($SearchString);
			$filtersGET = substr($filtersGET, 1); // remove initial &

			if($url){
				/* if designer specified a redirect-after-insert url */
				$url .= (strpos($url, '?') !== false ? '&' : '?') . $insert_status;
				$url .= (strpos($url, $this->ScriptFileName) !== false ? "&{$filtersGET}" : '');
				$url = str_replace("#ID#", urlencode($SelectedID), $url);
			}else{
				/* if no redirect-after-insert url, use default */
				$url = "{$this->ScriptFileName}?{$insert_status}&{$filtersGET}";

				/* if DV and TV in same page, select new record */
				if(!$this->SeparateDV) $url .= '&SelectedID=' . urlencode($SelectedID);
			}

			@header('Location: ' . $url);
			$this->HTML .= "<META HTTP-EQUIV=\"Refresh\" CONTENT=\"0;url=" . $url ."\">";

			return;
		}

		elseif($delete_x != ''){
			$delete_res = call_user_func($this->TableName.'_delete', $SelectedID, $this->AllowDeleteOfParents, $SkipChecks);
			// handle ajax delete requests
			if(is_ajax()){
				die($delete_res ? $delete_res : 'OK');
			}

			if($delete_res){
				//$_REQUEST['record-deleted-error'] = 1;
				$this->HTML .= showNotifications($delete_res, 'alert alert-danger', false);
				$this->hideTV();
				$current_view = ($this->SeparateDV ? 'DV' : 'TVDV');
			}else{
				$_REQUEST['record-deleted-ok'] = 1;
				$SelectedID = '';
				$this->showTV();

				/* close window if embedded */
				if($Embedded){
					$this->HTML .= '<script>$j(function(){ setTimeout(function(){ window.parent.jQuery(".modal").modal("hide"); }, 2000); })</script>';
				}
			}
		}

		elseif($update_x != ''){
			$updated = call_user_func($this->TableName.'_update', $SelectedID);

			$update_status = 'record-updated-ok=' . rand();
			if($updated === false) $update_status = 'record-updated-error=' . rand();

			// compose filters and sorting
			foreach($this->filterers as $filterer => $caption){
				if($_REQUEST['filterer_' . $filterer] != '') $filtersGET .= '&filterer_' . $filterer . '=' . urlencode($_REQUEST['filterer_' . $filterer]);
			}
			for($i = 1; $i <= (20 * $FiltersPerGroup); $i++){ // Number of filters allowed
				if($FilterField[$i] != '' && $FilterOperator[$i] != '' && ($FilterValue[$i] != '' || strpos($FilterOperator[$i], 'empty'))){
					$filtersGET .= "&FilterAnd[{$i}]={$FilterAnd[$i]}&FilterField[{$i}]={$FilterField[$i]}&FilterOperator[{$i}]={$FilterOperator[$i]}&FilterValue[{$i}]=" . urlencode($FilterValue[$i]);
				}
			}
			$filtersGET .= "&SortField={$SortField}&SortDirection={$SortDirection}&FirstRecord={$FirstRecord}&Embedded={$Embedded}";
			if($AutoClose) $filtersGET .= '&AutoClose=1';
			$filtersGET .= "&DisplayRecords={$DisplayRecords}";
			$filtersGET .= '&SearchString=' . urlencode($SearchString);
			$filtersGET = substr($filtersGET, 1); // remove initial &

			$redirectUrl = $this->ScriptFileName . '?SelectedID=' . urlencode($SelectedID) . '&' . $filtersGET . '&' . $update_status;
			@header("Location: $redirectUrl");
			$this->HTML .= '<META HTTP-EQUIV="Refresh" CONTENT="0;url='.$redirectUrl.'">';
			return;
		}

		elseif($addNew_x != ''){
			$SelectedID='';
			$this->hideTV();
		}

		elseif($Print_x != ''){
			// print code here ....
			$this->AllowNavigation = 0;
			$this->AllowSelection = 0;
		}

		elseif($SaveFilter_x != '' && $this->AllowSavingFilters){
			$filter_link = $_SERVER['HTTP_REFERER'] . '?SortField=' . urlencode($SortField) . '&SortDirection=' . $SortDirection . '&';
			for($i = 1; $i <= (20 * $FiltersPerGroup); $i++){ // Number of filters allowed
				if(($FilterField[$i] != '' || $i == 1) && $FilterOperator[$i] != '' && ($FilterValue[$i] != '' || strpos($FilterOperator[$i], 'empty'))){
					$filter_link .= urlencode("FilterAnd[$i]") . '=' . urlencode($FilterAnd[$i]) . '&';
					$filter_link .= urlencode("FilterField[$i]") . '=' . urlencode($FilterField[$i]) . '&';
					$filter_link .= urlencode("FilterOperator[$i]") . '=' . urlencode($FilterOperator[$i]) . '&';
					$filter_link .= urlencode("FilterValue[$i]") . '=' . urlencode($FilterValue[$i]) . '&';
				}elseif(($i % $FiltersPerGroup == 1) && in_array($FilterAnd[$i], array('and', 'or'))){
					/* always include the and/or at the beginning of each group */
					$filter_link .= urlencode("FilterAnd[$i]") . '=' . urlencode($FilterAnd[$i]) . '&';
				}
			}
			$filter_link = substr($filter_link, 0, -1); /* trim last '&' */

			$this->HTML .= '<div id="saved_filter_source_code" class="row"><div class="col-md-6 col-md-offset-3">';
				$this->HTML .= '<div class="panel panel-info">';
					$this->HTML .= '<div class="panel-heading"><h3 class="panel-title">' . $Translation["saved filters title"] . "</h3></div>";
					$this->HTML .= '<div class="panel-body">';
						$this->HTML .= $Translation["saved filters instructions"];
						$this->HTML .= '<textarea rows="4" class="form-control vspacer-lg" style="width: 100%;" onfocus="$j(this).select();">' . "&lt;a href=\"{$filter_link}\"&gt;Saved filter link&lt;a&gt;" . '</textarea>';
						$this->HTML .= "<div><a href=\"{$filter_link}\" title=\"" . html_attr($filter_link) . "\">{$Translation['permalink']}</a></div>";
						$this->HTML .= '<button type="button" class="btn btn-default btn-block vspacer-lg" onclick="$j(\'#saved_filter_source_code\').remove();"><i class="glyphicon glyphicon-remove"></i> ' . $Translation['hide code'] . '</button>';
					$this->HTML .= '</div>';
				$this->HTML .= '</div>';
			$this->HTML .= '</div></div>';
		}

		elseif($Filter_x != ''){
			$orderBy = array();
			if($SortField){
				$sortFields = explode(',', $SortField);
				$i=0;
				foreach($sortFields as $sf){
					$tob = preg_split('/\s+/', $sf, 2);
					$orderBy[] = array(trim($tob[0]) => (strtolower(trim($tob[1]))=='desc' ? 'desc' : 'asc'));
					$i++;
				}
				$orderBy[$i-1][$tob[0]] = (strtolower(trim($SortDirection))=='desc' ? 'desc' : 'asc');
			}

			$currDir=dirname(__FILE__).'/hooks'; // path to hooks folder
			$uff="{$currDir}/{$this->TableName}.filters.{$mi['username']}.php"; // user-specific filter file
			$gff="{$currDir}/{$this->TableName}.filters.{$mi['group']}.php"; // group-specific filter file
			$tff="{$currDir}/{$this->TableName}.filters.php"; // table-specific filter file

			/*
				if no explicit filter file exists, look for filter files in the hooks folder in this order:
					1. tablename.filters.username.php ($uff)
					2. tablename.filters.groupname.php ($gff)
					3. tablename.filters.php ($tff)
			*/
			if(!is_file($this->FilterPage)){
				$this->FilterPage='defaultFilters.php';
				if(is_file($uff)){
					$this->FilterPage=$uff;
				}elseif(is_file($gff)){
					$this->FilterPage=$gff;
				}elseif(is_file($tff)){
					$this->FilterPage=$tff;
				}
			}

			if($this->FilterPage!=''){
				ob_start();
				@include($this->FilterPage);
				$out=ob_get_contents();
				ob_end_clean();
				$this->HTML .= $out;
			}
			// hidden variables ....
				$this->HTML .= '<input name="SortField" value="'.$SortField.'" type="hidden" />';
				$this->HTML .= '<input name="SortDirection" type="hidden" value="'.$SortDirection.'" />';
				$this->HTML .= '<input name="FirstRecord" type="hidden" value="1" />';

				$this->ContentType='filters';
			return;
		}

		elseif($NoFilter_x != ''){
			// clear all filters ...
			for($i = 1; $i <= (datalist_filters_count * $FiltersPerGroup); $i++){ // Number of filters allowed
				$FilterField[$i] = '';
				$FilterOperator[$i] = '';
				$FilterValue[$i] = '';
			}
			$DisplayRecords = 'all';
			$SearchString = '';
			$FirstRecord = 1;

			// clear filterers
			foreach($this->filterers as $filterer => $caption){
				$_REQUEST['filterer_' . $filterer] = '';
			}
		}

		elseif($SelectedID){
			$this->hideTV();
		}

	// apply lookup filterers to the query
		foreach($this->filterers as $filterer => $caption){
			if($_REQUEST['filterer_' . $filterer] != ''){
				if($this->QueryWhere == '')
					$this->QueryWhere = "where ";
				else
					$this->QueryWhere .= " and ";
				$this->QueryWhere .= "`{$this->TableName}`.`$filterer`='" . makeSafe($_REQUEST['filterer_' . $filterer]) . "' ";
				break; // currently, only one filterer can be applied at a time
			}
		}

	// apply quick search to the query
		if($SearchString != ''){
			if($Search_x!=''){ $FirstRecord=1; }

			if($this->QueryWhere=='')
				$this->QueryWhere = "where ";
			else
				$this->QueryWhere .= " and ";

			foreach($this->QueryFieldsQS as $fName => $fCaption){
				if(strpos($fName, '<img')===False){
					$this->QuerySearchableFields[$fName]=$fCaption;
				}
			}

			$this->QueryWhere.='('.implode(" LIKE '%".makeSafe($SearchString)."%' or ", array_keys($this->QuerySearchableFields))." LIKE '%".makeSafe($SearchString)."%')";
		}


	// set query filters
		$QueryHasWhere = 0;
		if(strpos($this->QueryWhere, 'where ')!==FALSE)
			$QueryHasWhere = 1;

		$WhereNeedsClosing = 0;
		for($i = 1; $i <= (datalist_filters_count * $FiltersPerGroup); $i+=$FiltersPerGroup){ // Number of filters allowed
			// test current filter group
			$GroupHasFilters = 0;
			for($j = 0; $j < $FiltersPerGroup; $j++){
				if($FilterField[$i+$j] != '' && $this->QueryFieldsIndexed[($FilterField[$i+$j])] != '' && $FilterOperator[$i+$j] != '' && ($FilterValue[$i+$j] != '' || strpos($FilterOperator[$i+$j], 'empty'))){
					$GroupHasFilters = 1;
					break;
				}
			}

			if($GroupHasFilters){
				if(!stristr($this->QueryWhere, "where "))
					$this->QueryWhere = "where (";
				elseif($QueryHasWhere){
					$this->QueryWhere .= " and (";
					$QueryHasWhere = 0;
				}

				$this->QueryWhere .= " <FilterGroup> " . $FilterAnd[$i] . " (";

				for($j = 0; $j < $FiltersPerGroup; $j++){
					if($FilterField[$i+$j] != '' && $this->QueryFieldsIndexed[($FilterField[$i+$j])] != '' && $FilterOperator[$i+$j] != '' && ($FilterValue[$i+$j] != '' || strpos($FilterOperator[$i+$j], 'empty'))){
						if($FilterAnd[$i+$j]==''){
							$FilterAnd[$i+$j]='and';
						}
						// test for date/time fields
						$tries=0; $isDateTime=FALSE; $isDate=FALSE;
						$fieldName=str_replace('`', '', $this->QueryFieldsIndexed[($FilterField[$i+$j])]);
						list($tn, $fn)=explode('.', $fieldName);
						while(!($res=sql("show columns from `$tn` like '$fn'", $eo)) && $tries<2){
							$tn=substr($tn, 0, -1);
							$tries++;
						}
						if($row = @db_fetch_array($res)){
							if($row['Type']=='date' || $row['Type']=='time'){
								$isDateTime=TRUE;
								if($row['Type']=='date'){
									$isDate=True;
								}
							}
						}
						// end of test
						if($FilterOperator[$i+$j]=='is-empty' && !$isDateTime){
							$this->QueryWhere .= " <FilterItem> " . $FilterAnd[$i+$j] . " (" . $this->QueryFieldsIndexed[($FilterField[$i+$j])] . "='' or " . $this->QueryFieldsIndexed[($FilterField[$i+$j])] . " is NULL) </FilterItem>";
						}elseif($FilterOperator[$i+$j]=='is-not-empty' && !$isDateTime){
							$this->QueryWhere .= " <FilterItem> " . $FilterAnd[$i+$j] . " " . $this->QueryFieldsIndexed[($FilterField[$i+$j])] . "!='' </FilterItem>";
						}elseif($FilterOperator[$i+$j]=='is-empty' && $isDateTime){
							$this->QueryWhere .= " <FilterItem> " . $FilterAnd[$i+$j] . " (" . $this->QueryFieldsIndexed[($FilterField[$i+$j])] . "=0 or " . $this->QueryFieldsIndexed[($FilterField[$i+$j])] . " is NULL) </FilterItem>";
						}elseif($FilterOperator[$i+$j]=='is-not-empty' && $isDateTime){
							$this->QueryWhere .= " <FilterItem> " . $FilterAnd[$i+$j] . " " . $this->QueryFieldsIndexed[($FilterField[$i+$j])] . "!=0 </FilterItem>";
						}elseif($FilterOperator[$i+$j]=='like' && !strstr($FilterValue[$i+$j], "%") && !strstr($FilterValue[$i+$j], "_")){
							$this->QueryWhere .= " <FilterItem> " . $FilterAnd[$i+$j] . " " . $this->QueryFieldsIndexed[($FilterField[$i+$j])] . " like '%" . makeSafe($FilterValue[$i+$j]) . "%' </FilterItem>";
						}elseif($FilterOperator[$i+$j]=='not-like' && !strstr($FilterValue[$i+$j], "%") && !strstr($FilterValue[$i+$j], "_")){
							$this->QueryWhere .= " <FilterItem> " . $FilterAnd[$i+$j] . " " . $this->QueryFieldsIndexed[($FilterField[$i+$j])] . " not like '%" . makeSafe($FilterValue[$i+$j]) . "%' </FilterItem>";
						}elseif($isDate){
							$dateValue = toMySQLDate($FilterValue[$i+$j]);
							$this->QueryWhere .= " <FilterItem> " . $FilterAnd[$i+$j] . " " . $this->QueryFieldsIndexed[($FilterField[$i+$j])] . " " . $GLOBALS['filter_operators'][$FilterOperator[$i+$j]] . " '$dateValue' </FilterItem>";
						}else{
							$this->QueryWhere .= " <FilterItem> " . $FilterAnd[$i+$j] . " " . $this->QueryFieldsIndexed[($FilterField[$i+$j])] . " " . $GLOBALS['filter_operators'][$FilterOperator[$i+$j]] . " '" . makeSafe($FilterValue[$i+$j]) . "' </FilterItem>";
						}
					}
				}

				$this->QueryWhere .= ") </FilterGroup>";
				$WhereNeedsClosing = 1;
			}
		}

		if($WhereNeedsClosing)
			$this->QueryWhere .= ")";

	// set query sort
		if(!stristr($this->QueryOrder, "order by ") && $SortField != '' && $this->AllowSorting){
			$actualSortField = $SortField;
			foreach($this->SortFields as $fieldNum => $fieldSort){
				$actualSortField = str_replace(" $fieldNum ", " $fieldSort ", " $actualSortField ");
				$actualSortField = str_replace(",$fieldNum ", ",$fieldSort ", " $actualSortField ");
			}
			$this->QueryOrder = "order by $actualSortField $SortDirection";
		}

	// clean up query
		$this->QueryWhere = str_replace('( <FilterGroup> and ', '( ', $this->QueryWhere);
		$this->QueryWhere = str_replace('( <FilterGroup> or ', '( ', $this->QueryWhere);
		$this->QueryWhere = str_replace('( <FilterItem> and ', '( ', $this->QueryWhere);
		$this->QueryWhere = str_replace('( <FilterItem> or ', '( ', $this->QueryWhere);
		$this->QueryWhere = str_replace('<FilterGroup>', '', $this->QueryWhere);
		$this->QueryWhere = str_replace('</FilterGroup>', '', $this->QueryWhere);
		$this->QueryWhere = str_replace('<FilterItem>', '', $this->QueryWhere);
		$this->QueryWhere = str_replace('</FilterItem>', '', $this->QueryWhere);

	// if no 'order by' clause found, apply default sorting if specified
		if($this->DefaultSortField != '' && $this->QueryOrder == ''){
			$this->QueryOrder="order by ".$this->DefaultSortField." ".$this->DefaultSortDirection;
		}

	// get count of matching records ...
		$TempQuery = 'SELECT count(1) from '.$this->QueryFrom.' '.$this->QueryWhere;
		$RecordCount = sqlValue($TempQuery);
		$FieldCountTV = count($this->QueryFieldsTV);
		$FieldCountCSV = count($this->QueryFieldsCSV);
		$FieldCountFilters = count($this->QueryFieldsFilters);
		if(!$RecordCount){
			$FirstRecord=1;
		}

	// Output CSV on request
		if($CSV_x != ''){
			$this->HTML = '';
			if(datalist_db_encoding == 'UTF-8') $this->HTML = "\xEF\xBB\xBF"; // BOM characters for UTF-8 output

		// execute query for CSV output
			$fieldList='';
			foreach($this->QueryFieldsCSV as $fn=>$fc)
				$fieldList.="$fn as `$fc`, ";
			$fieldList=substr($fieldList, 0, -2);
			$csvQuery = 'SELECT '.$fieldList.' from '.$this->QueryFrom.' '.$this->QueryWhere.' '.$this->QueryOrder;

			// hook: table_csv
			if(function_exists($this->TableName.'_csv')){
				$args = array();
				$mq = call_user_func_array($this->TableName . '_csv', array($csvQuery, $mi, &$args));
				$csvQuery = ($mq ? $mq : $csvQuery);
			}

			$result = sql($csvQuery, $eo);

		// output CSV field names
			for($i = 0; $i < $FieldCountCSV; $i++)
				$this->HTML .= "\"" . db_field_name($result, $i) . "\"" . $this->CSVSeparator;
			$this->HTML .= "\n\n";

		// output CSV data
			while($row = db_fetch_row($result)){
				for($i = 0; $i < $FieldCountCSV; $i++)
					$this->HTML .= "\"" . str_replace(array("\r\n", "\r", "\n", '"'), array(' ', ' ', ' ', '""'), strip_tags($row[$i])) . "\"" . $this->CSVSeparator;
				$this->HTML .= "\n\n";
			}
			$this->HTML = str_replace($this->CSVSeparator . "\n\n", "\n", $this->HTML);
			$this->HTML = substr($this->HTML, 0, - 1);

		// clean any output buffers
			while(@ob_end_clean());

		// output CSV HTTP headers ...
			header('HTTP/1.1 200 OK');
			header('Date: ' . @date("D M j G:i:s T Y"));
			header('Last-Modified: ' . @date("D M j G:i:s T Y"));
			header("Content-Type: application/force-download");
			header("Content-Length: " . (string)(strlen($this->HTML)));
			header("Content-Transfer-Encoding: Binary");
			header("Content-Disposition: attachment; filename=$this->TableName.csv");

		// send output and quit script
			echo $this->HTML;
			exit;
		}
		$t = time(); // just a random number for any purpose ...

	// should SelectedID be reset on clicking TV buttons?
		$resetSelection = ($this->SeparateDV ? "document.myform.SelectedID.value = '';" : "document.myform.writeAttribute('novalidate', 'novalidate');");

		if($current_view == 'DV' && !$Embedded){
			$this->HTML .= '<div class="page-header">';
				$this->HTML .= '<h1>';
					$this->HTML .= '<a style="text-decoration: none; color: inherit;" href="' . $this->TableName . '_view.php"><img src="' . $this->TableIcon . '"> ' . $this->TableTitle . '</a>';
					/* show add new button if user can insert and there is a selected record */
					if($SelectedID && $this->Permissions[1] && $this->SeparateDV && $this->AllowInsert){
						$this->HTML .= ' <button type="submit" id="addNew" name="addNew_x" value="1" class="btn btn-success"><i class="glyphicon glyphicon-plus-sign"></i> ' . $Translation['Add New'] . '</button>';
					}
				$this->HTML .= '</h1>';
			$this->HTML .= '</div>';
		}

	// quick search and TV action buttons  
		if(!$this->HideTableView && !($dvprint_x && $this->AllowSelection && $SelectedID) && !$PrintDV){
			$buttons_all = $quick_search_html = '';

			if($Print_x == ''){

				// display 'Add New' icon
				if($this->Permissions[1] && $this->SeparateDV && $this->AllowInsert){
					$buttons_all .= '<button type="submit" id="addNew" name="addNew_x" value="1" class="btn btn-success"><i class="glyphicon glyphicon-plus-sign"></i> ' . $Translation['Add New'] . '</button>';
					$buttonsCount++;
				}

				// display Print icon
				if($this->AllowPrinting){
					$buttons_all .= '<button onClick="document.myform.NoDV.value=1; ' . $resetSelection . ' return true;" type="submit" name="Print_x" id="Print" value="1" class="btn btn-default"><i class="glyphicon glyphicon-print"></i> ' . $Translation['Print Preview'] . '</button>';
					$buttonsCount++;
				}

				// display CSV icon
				if($this->AllowCSV){
					$buttons_all .= '<button onClick="document.myform.NoDV.value=1; ' . $resetSelection . ' return true;" type="submit" name="CSV_x" id="CSV" value="1" class="btn btn-default"><i class="glyphicon glyphicon-download-alt"></i> ' . $Translation['CSV'] . '</button>';
					$buttonsCount++;
				}

				// display Filter icon
				if($this->AllowFilters){
					$buttons_all .= '<button onClick="document.myform.NoDV.value=1; ' . $resetSelection . ' return true;" type="submit" name="Filter_x" id="Filter" value="1" class="btn btn-default"><i class="glyphicon glyphicon-filter"></i> ' . $Translation['filter'] . '</button>';
					$buttonsCount++;
				}

				// display Show All icon
				if(($this->AllowFilters)){
					$buttons_all .= '<button onClick="document.myform.NoDV.value=1; ' . $resetSelection . ' return true;" type="submit" name="NoFilter_x" id="NoFilter" value="1" class="btn btn-default"><i class="glyphicon glyphicon-remove-circle"></i> ' . $Translation['Reset Filters'] . '</button>';
					$buttonsCount++;
				}

				$quick_search_html .= '<div class="input-group" id="quick-search">';
					$quick_search_html .= '<input type="text" name="SearchString" value="' . html_attr($SearchString) . '" class="form-control" placeholder="' . html_attr($this->QuickSearchText) . '">';
					$quick_search_html .= '<span class="input-group-btn">';
						$quick_search_html .= '<button name="Search_x" value="1" id="Search" type="submit" onClick="' . $resetSelection . ' document.myform.NoDV.value=1; return true;"  class="btn btn-default" title="' . html_attr($this->QuickSearchText) . '"><i class="glyphicon glyphicon-search"></i></button>';
						$quick_search_html .= '<button name="NoFilter_x" value="1" id="NoFilter_x" type="submit" onClick="' . $resetSelection . ' document.myform.NoDV.value=1; return true;"  class="btn btn-default" title="' . html_attr($Translation['Reset Filters']) . '"><i class="glyphicon glyphicon-remove-circle"></i></button>';
					$quick_search_html .= '</span>';
				$quick_search_html .= '</div>';
			}else{
				$buttons_all .= '<button class="btn btn-primary" type="button" id="sendToPrinter" onClick="window.print();"><i class="glyphicon glyphicon-print"></i> ' . $Translation['Print'] . '</button>';
				$buttons_all .= '<button class="btn btn-default" type="submit"><i class="glyphicon glyphicon-remove-circle"></i> ' . $Translation['Cancel Printing'] . '</button>';
			}

			/* if user can print DV, add action to 'More' menu */
			$selected_records_more = array();

			if($AllowPrintDV){
				$selected_records_more[] = array(
					'function' => ($this->SeparateDV ? 'print_multiple_dv_sdv' : 'print_multiple_dv_tvdv'),
					'title' => $Translation['Print Preview Detail View'],
					'icon' => 'print'
				);
			}

			/* if user can mass-delete selected records, add action to 'More' menu */
			if($this->AllowMassDelete && $this->AllowDelete){
				$selected_records_more[] = array(
					'function' => 'mass_delete',
					'title' => $Translation['Delete'],
					'icon' => 'trash',
					'class' => 'text-danger'
				);
			}

			/* if user is admin, add 'Change owner' action to 'More' menu */
			/* also, add help link for adding more actions */
			if($mi['admin']){
				$selected_records_more[] = array(
					'function' => 'mass_change_owner',
					'title' => $Translation['Change owner'],
					'icon' => 'user'
				);
				$selected_records_more[] = array(
					'function' => 'add_more_actions_link',
					'title' => $Translation['Add more actions'],
					'icon' => 'question-sign',
					'class' => 'text-info'
				);
			}

			/* user-defined actions ... should be set in the {tablename}_batch_actions() function in hooks/{tablename}.php */
			$user_actions = array();
			if(function_exists($this->TableName.'_batch_actions')){
				$args = array();
				$user_actions = call_user_func_array($this->TableName . '_batch_actions', array(&$args));
				if(is_array($user_actions) && count($user_actions)){
					$selected_records_more = array_merge($selected_records_more, $user_actions);
				}
			}

			$actual_more_count = 0;
			$more_menu = $more_menu_js = '';
			if(count($selected_records_more)){
				$more_menu .= '<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" id="selected_records_more"><i class="glyphicon glyphicon-check"></i> ' . $Translation['More'] . ' <span class="caret"></span></button>';
				$more_menu .= '<ul class="dropdown-menu" role="menu">';
				foreach($selected_records_more as $action){
					if(!$action['function'] || !$action['title']) continue;
					$action['class'] = (!isset($action['class']) ? '' : $action['class']);
					$action['icon'] = (!isset($action['icon']) ? '' : $action['icon']);
					$actual_more_count++;
					$more_menu .= '<li>' .
							'<a href="#" id="selected_records_' . $action['function'] . '">' .
								'<span class="' . $action['class'] . '">' .
									($action['icon'] ? '<i class="glyphicon glyphicon-' . $action['icon'] . '"></i> ' : '') .
									$action['title'] .
								'</span>' .
							'</a>' .
						'</li>';

					// on clicking an action, call its js handler function, passing the current table name and an array of selected IDs to it
					$more_menu_js .= "jQuery('[id=selected_records_{$action['function']}]').click(function(){ {$action['function']}('{$this->TableName}', get_selected_records_ids()); return false; });";
				}
				$more_menu .= '</ul>';
			}

			if($Embedded){
				$this->HTML .= '<script>$j(function(){ $j(\'[id^=notification-]\').parent().css({\'margin-top\': \'15px\', \'margin-bottom\': \'0\'}); })</script>';
			}else{
				$this->HTML .= '<div class="page-header">';
					$this->HTML .= '<h1>';
						$this->HTML .= '<div class="row">';
							$this->HTML .= '<div class="col-sm-8">';
								$this->HTML .= '<a style="text-decoration: none; color: inherit;" href="' . $this->TableName . '_view.php"><img src="' . $this->TableIcon . '"> ' . $this->TableTitle . '</a>';
							$this->HTML .= '</div>';
							if($this->QuickSearch){
								$this->HTML .= '<div class="col-sm-4">';
									$this->HTML .= $quick_search_html;
								$this->HTML .= '</div>';
							}
						$this->HTML .= '</div>';
					$this->HTML .= '</h1>';
				$this->HTML .= '</div>';

				$this->HTML .= '<div id="top_buttons" class="hidden-print">';
					/* .all_records: container for buttons that don't need a selection */
					/* .selected_records: container for buttons that need a selection */
					$this->HTML .= '<div class="btn-group btn-group-lg visible-md visible-lg all_records pull-left">' . $buttons_all . '</div>';
					$this->HTML .= '<div class="btn-group btn-group-lg visible-md visible-lg selected_records hidden pull-left hspacer-lg">' . $buttons_selected . ($actual_more_count ? $more_menu : '') . '</div>';
					$this->HTML .= '<div class="btn-group-vertical btn-group-lg visible-xs visible-sm all_records">' . $buttons_all . '</div>';
					$this->HTML .= '<div class="btn-group-vertical btn-group-lg visible-xs visible-sm selected_records hidden vspacer-lg">' . $buttons_selected . ($actual_more_count ? $more_menu : '') . '</div>';
					$this->HTML .= '<div class="clearfix"></div><p></p>';
				$this->HTML .= '</div>';

				$this->HTML .= '<div class="row"><div class="table_view col-xs-12 ' . $this->TVClasses . '">';
			}

			if($Print_x != ''){
				/* fix top margin for print-preview */
				$this->HTML .= '<style>body{ padding-top: 0 !important; }</style>';

				/* disable links inside table body to prevent printing their href */
				$this->HTML .= '<script>jQuery(function(){ jQuery("tbody a").removeAttr("href").removeAttr("rel"); });</script>';
			}

			// script for focusing into the search box on loading the page
			// and for declaring record action handlers
			$this->HTML .= '<script>jQuery(function(){ jQuery("input[name=SearchString]").focus();  ' . $more_menu_js . ' });</script>';

		}

	// begin table and display table title
		if(!$this->HideTableView && !($dvprint_x && $this->AllowSelection && $SelectedID) && !$PrintDV && !$Embedded){
			$this->HTML .= '<div class="table-responsive"><table class="table table-striped table-bordered table-hover">';

			$this->HTML .= '<thead><tr>';
			if(!$Print_x) $this->HTML .= '<th style="width: 18px;" class="text-center"><input class="hidden-print" type="checkbox" title="' . html_attr($Translation['Select all records']) . '" id="select_all_records"></th>';
		// Templates
			$rowTemplate = $selrowTemplate = '';
			if($this->Template){
				$rowTemplate = @file_get_contents('./' . $this->Template);
				if($rowTemplate && $this->SelectedTemplate){
					$selrowTemplate = @file_get_contents('./' . $this->SelectedTemplate);
				}
			}

			// process translations
			if($rowTemplate){
				foreach($Translation as $symbol=>$trans){
					$rowTemplate=str_replace("<%%TRANSLATION($symbol)%%>", $trans, $rowTemplate);
				}
			}
			if($selrowTemplate){
				foreach($Translation as $symbol=>$trans){
					$selrowTemplate=str_replace("<%%TRANSLATION($symbol)%%>", $trans, $selrowTemplate);
				}
			}
		// End of templates

		// $this->ccffv: map $FilterField values to field captions as stored in ColCaption
			$this->ccffv = array();
			foreach($this->ColCaption as $captionIndex => $caption){
				$ffv = 1;
				foreach($this->QueryFieldsFilters as $uselessKey => $filterCaption){
					if($caption == $filterCaption){
						$this->ccffv[$captionIndex] = $ffv;
					}
					$ffv++;
				}
			}

		// display table headers
			$forceHeaderWidth = false;
			if($rowTemplate=='' || $this->ShowTableHeader){
				for($i = 0; $i < count($this->ColCaption); $i++){
					/* Sorting icon and link */
					$sort1 = $sort2 = $filterHint = '';
					if($this->AllowSorting == 1){
						if($current_view != 'TVP'){
							$sort1 = "<a href=\"{$this->ScriptFileName}?SortDirection=asc&SortField=".($this->ColNumber[$i])."\" onClick=\"$resetSelection document.myform.NoDV.value=1; document.myform.SortDirection.value='asc'; document.myform.SortField.value = '".($this->ColNumber[$i])."'; document.myform.submit(); return false;\" class=\"TableHeader\">";
							$sort2 = "</a>";
						}
						if($this->ColNumber[$i] == $SortField){
							$SortDirection = ($SortDirection == "asc" ? "desc" : "asc");
							if($current_view != 'TVP')
								$sort1 = "<a href=\"{$this->ScriptFileName}?SortDirection=$SortDirection&SortField=".($this->ColNumber[$i])."\" onClick=\"$resetSelection document.myform.NoDV.value=1; document.myform.SortDirection.value='$SortDirection'; document.myform.SortField.value = ".($this->ColNumber[$i])."; document.myform.submit(); return false;\" class=\"TableHeader\">";
							$sort2 = " <i class=\"text-warning glyphicon glyphicon-sort-by-attributes" . ($SortDirection == 'desc' ? '' : '-alt') . "\"></i>{$sort2}";
							$SortDirection = ($SortDirection == "asc" ? "desc" : "asc");
						}
					}

					/* Filtering icon and hint */
					if($this->AllowFilters && is_array($FilterField)){
						// check to see if there is any filter applied on the current field
						if(isset($this->ccffv[$i]) && in_array($this->ccffv[$i], $FilterField)){
							// render filter icon
							$filterHint = '&nbsp;<button type="submit" class="btn btn-default btn-xs' . ($current_view == 'TVP' ? ' disabled' : '') . '" name="Filter_x" value="1" title="'.html_attr($Translation['filtered field']).'"><i class="glyphicon glyphicon-filter"></i></button>';
						}
					}

					$this->HTML .= "\t<th class=\"{$this->TableName}-{$this->ColFieldName[$i]}\" " . ($forceHeaderWidth ? ' style="width: ' . ($this->ColWidth[$i] ? $this->ColWidth[$i] : 100) . 'px;"' : '') . ">{$sort1}{$this->ColCaption[$i]}{$sort2}{$filterHint}</th>\n";
				}
			}elseif($current_view != 'TVP'){
				// Display a Sort by drop down
				$this->HTML .= "\t<th class=\"hidden-print\" colspan=\"" . (count($this->ColCaption)) . "\">";
				$this->HTML .= "\t<div class=\"pull-right\" id=\"order-by-selector\">";

				if($this->AllowSorting == 1){
					$sortCombo = new Combo;
					for($i=0; $i < count($this->ColCaption); $i++){
						$sortCombo->ListItem[] = $this->ColCaption[$i];
						$sortCombo->ListData[] = $this->ColNumber[$i];
					}
					$sortCombo->SelectName = "FieldsList";
					$sortCombo->SelectedData = $SortField;
					$sortCombo->Class = '';
					$sortCombo->SelectedClass = '';
					$sortCombo->Render();
					$sortby_dropdown = $sortCombo->HTML;
					$sortby_dropdown = str_replace('<select ', "<select onChange=\"document.myform.SortDirection.value='$SortDirection'; document.myform.SortField.value=document.myform.FieldsList.value; document.myform.NoDV.value=1; document.myform.submit();\" ", $sortby_dropdown);
					if($SortField){
						$SortDirection = ($SortDirection == 'desc' ? 'asc' : 'desc');
						$sort_class = ($SortDirection == 'asc' ? 'sort-by-attributes-alt' : 'sort-by-attributes');
						$sort = "<a href=\"javascript: document.myform.NoDV.value=1; document.myform.SortDirection.value='{$SortDirection}'; document.myform.SortField.value='{$SortField}'; document.myform.submit();\" class=TableHeader><i class=\"text-warning glyphicon glyphicon-{$sort_class}\"></i></a>";
						$SortDirection = ($SortDirection == 'desc' ? 'asc' : 'desc');
					}else{
						$sort = '';
					}

					$sortby_sep = '<span class="hspacer-md"></span>';

					$this->HTML .= "{$Translation['order by']}{$sortby_sep}{$sortby_dropdown}{$sortby_sep}{$sort}{$sortby_sep}";
				}
				$this->HTML .= "</div><style>#s2id_FieldsList{ min-width: 12em; width: unset !important; }</style></th>\n";
			}

		// table view navigation code ...
			if($RecordCount && $this->AllowNavigation && $RecordCount>$this->RecordsPerPage){
				while($FirstRecord > $RecordCount)
					$FirstRecord -= $this->RecordsPerPage;

				if($FirstRecord == '' || $FirstRecord < 1) $FirstRecord = 1;

				if($Previous_x != ''){
					$FirstRecord -= $this->RecordsPerPage;
					if($FirstRecord <= 0)
						$FirstRecord = 1;
				}elseif($Next_x != ''){
					$FirstRecord += $this->RecordsPerPage;
					if($FirstRecord > $RecordCount)
						$FirstRecord = $RecordCount - ($RecordCount % $this->RecordsPerPage) + 1;
					if($FirstRecord > $RecordCount)
						$FirstRecord = $RecordCount - $this->RecordsPerPage + 1;
					if($FirstRecord <= 0)
						$FirstRecord = 1;
				}

			}elseif($RecordCount){
				$FirstRecord = 1;
				$this->RecordsPerPage = 2000; // a limit on max records in print preview to avoid performance drops
			}
		// end of table view navigation code
			$this->HTML .= "\n\t</tr>\n\n</thead>\n\n<tbody><!-- tv data below -->\n";

			$i = 0;
			$hc=new CI_Input();
			$hc->charset = datalist_db_encoding;
			if($RecordCount){
				$i = $FirstRecord;
			// execute query for table view
				$query_fields = array();
				foreach($this->QueryFieldsTV as $fn => $fc)
					$query_fields[] = "$fn as `$fc`";
				$fieldList = implode(', ', $query_fields);

				if($this->PrimaryKey)
					$fieldList .= ", $this->PrimaryKey as '" . str_replace('`', '', $this->PrimaryKey) . "'";

				$tvQuery = "SELECT {$fieldList} from {$this->QueryFrom} {$this->QueryWhere} {$this->QueryOrder}";
				$result = sql($tvQuery . " limit " . ($i-1) . ",{$this->RecordsPerPage}", $eo);
				while(($row = db_fetch_array($result)) && ($i < ($FirstRecord + $this->RecordsPerPage))){
					/* skip displaying the current record if we're in TVP or multiple DVP and the record is not checked */
					if(($PrintTV || $Print_x) && count($_REQUEST['record_selector']) && !in_array($row[$FieldCountTV], $_REQUEST['record_selector'])) continue;

					$attr_id = html_attr($row[$FieldCountTV]); /* pk value suitable for inserting into html tag attributes */
					$js_id = addslashes($row[$FieldCountTV]); /* pk value suitable for inserting into js strings */

					/* show record selector except in TVP */
					if($Print_x != ''){ $this->HTML .= '<tr>'; }

					if(!$Print_x){
						$this->HTML .= ($SelectedID == $row[$FieldCountTV] ? '<tr class="active">' : '<tr>');
						$checked = (is_array($_REQUEST['record_selector']) && in_array($row[$FieldCountTV], $_REQUEST['record_selector']) ? ' checked' : '');
						$this->HTML .= "<td class=\"text-center\"><input class=\"hidden-print record_selector\" type=\"checkbox\" id=\"record_selector_{$attr_id}\" name=\"record_selector[]\" value=\"{$attr_id}\"{$checked}></td>";
					}

					/* apply record templates */
					if($rowTemplate != ''){
						$rowTemp = $rowTemplate;
						if($this->AllowSelection == 1 && $SelectedID == $row[$FieldCountTV] && $selrowTemplate != ''){
							$rowTemp = $selrowTemplate;
						}

						if($this->AllowSelection == 1 && $SelectedID != $row[$FieldCountTV]){
							$rowTemp = str_replace('<%%SELECT%%>',"<a onclick=\"document.myform.SelectedField.value=this.parentNode.cellIndex; document.myform.SelectedID.value='" . addslashes($row[$FieldCountTV]) . "'; document.myform.submit(); return false;\" href=\"{$this->ScriptFileName}?SelectedID=" . html_attr($row[$FieldCountTV]) . "\" style=\"display: block; padding:0px;\">",$rowTemp);
							$rowTemp = str_replace('<%%ENDSELECT%%>','</a>',$rowTemp);
						}else{
							$rowTemp = str_replace('<%%SELECT%%>', '', $rowTemp);
							$rowTemp = str_replace('<%%ENDSELECT%%>', '', $rowTemp);
						}

						for($j = 0; $j < $FieldCountTV; $j++){
							$fieldTVCaption = current(array_slice($this->QueryFieldsTV, $j, 1));

							$fd = $row[$j];
							/* apply nl2br only for non-HTML data */
							if($row[$j] == strip_tags($row[$j])){
								$fd = nl2br($row[$j]);
							}

							/* Sanitize output against XSS attacks */
							$fd = $hc->xss_clean($fd);

							/*
								the TV template could contain field placeholders in the format 
								<%%FIELD_n%%> or <%%VALUE(Field caption)%%> or <%%HTML_ATTR(field caption)%%>
							*/
							$rowTemp = str_replace("<%%FIELD_$j%%>", thisOr($fd, ''), $rowTemp);
							$rowTemp = str_replace("<%%VALUE($fieldTVCaption)%%>", thisOr($fd, ''), $rowTemp);
							$rowTemp = str_replace("<%%HTML_ATTR($fieldTVCaption)%%>", html_attr($fd), $rowTemp);

							if(strpos($rowTemp, "<%%YOUTUBETHUMB($fieldTVCaption)%%>") !== false) $rowTemp = str_replace("<%%YOUTUBETHUMB($fieldTVCaption)%%>", thisOr(get_embed('youtube', $fd, '', '', 'thumbnail_url'), 'blank.gif'), $rowTemp);
							if(strpos($rowTemp, "<%%GOOGLEMAPTHUMB($fieldTVCaption)%%>") !== false) $rowTemp = str_replace("<%%GOOGLEMAPTHUMB($fieldTVCaption)%%>", thisOr(get_embed('googlemap', $fd, '', '', 'thumbnail_url'), 'blank.gif'), $rowTemp);
							if(thisOr($fd)=='&nbsp;' && preg_match('/<a href=".*?&nbsp;.*?<\/a>/i', $rowTemp, $m)){
								$rowTemp=str_replace($m[0], '', $rowTemp);
							}
						}

						$this->HTML .= $rowTemp;
						$rowTemp = '';

					}else{
						// default view if no template
						for($j = 0; $j < $FieldCountTV; $j++){
							if($this->AllowSelection == 1){
								$sel1 = "<a href=\"{$this->ScriptFileName}?SelectedID=" . html_attr($row[$FieldCountTV]) . "\" onclick=\"document.myform.SelectedID.value='" . addslashes($row[$FieldCountTV]) . "'; document.myform.submit(); return false;\" style=\"padding:0px;\">";
								$sel2 = "</a>";
							}else{
								$sel1 = '';
								$sel2 = '';
							}

							$this->HTML .= "<td valign=\"top\"><div>&nbsp;{$sel1}{$row[$j]}{$sel2}&nbsp;</div></td>";
						}
					}
					$this->HTML .= "</tr>\n";
					$i++;
				}
				$i--;
			}

			$this->HTML = preg_replace("/<a href=\"(mailto:)?&nbsp;[^\n]*title=\"&nbsp;\"><\/a>/", '&nbsp;', $this->HTML);
			$this->HTML = preg_replace("/<a [^>]*>(&nbsp;)*<\/a>/", '&nbsp;', $this->HTML);
			$this->HTML = preg_replace("/<%%.*%%>/U", '&nbsp;', $this->HTML);
		// end of data
			$this->HTML.='<!-- tv data above -->';
			$this->HTML .= "\n</tbody>";

			if($Print_x == ''){ // TV
				$pagesMenu = '';
				if($RecordCount > $this->RecordsPerPage){
					$pagesMenuId = "{$this->TableName}_pagesMenu";
					$pagesMenu = $Translation['go to page'] . ' <select class="input-sm" id="' . $pagesMenuId . '" onChange="document.myform.writeAttribute(\'novalidate\', \'novalidate\'); document.myform.NoDV.value=1; document.myform.FirstRecord.value=(this.value * ' . $this->RecordsPerPage . '+1); document.myform.submit();">';
					$pagesMenu .= '</select>';

					$pagesMenu .= '<script>';
					$pagesMenu .= 'var lastPage = ' . (ceil($RecordCount / $this->RecordsPerPage) - 1) . ';';
					$pagesMenu .= 'var currentPage = ' . (($FirstRecord - 1) / $this->RecordsPerPage) . ';';
					$pagesMenu .= 'var pagesMenu = document.getElementById("' . $pagesMenuId . '");';
					$pagesMenu .= 'var lump = ' . datalist_max_page_lump . ';';

					$pagesMenu .= 'if(lastPage <= lump * 3){';
					$pagesMenu .= '  addPageNumbers(0, lastPage);';
					$pagesMenu .= '}else{';
					$pagesMenu .= '  addPageNumbers(0, lump - 1);';
					$pagesMenu .= '  if(currentPage < lump) addPageNumbers(lump, currentPage + lump / 2);';
					$pagesMenu .= '  if(currentPage >= lump && currentPage < (lastPage - lump)){';
					$pagesMenu .= '    addPageNumbers(';
					$pagesMenu .= '      Math.max(currentPage - lump / 2, lump),';
					$pagesMenu .= '      Math.min(currentPage + lump / 2, lastPage - lump - 1)';
					$pagesMenu .= '    );';
					$pagesMenu .= '  }';
					$pagesMenu .= '  if(currentPage >= (lastPage - lump)) addPageNumbers(currentPage - lump / 2, lastPage - lump - 1);';
					$pagesMenu .= '  addPageNumbers(lastPage - lump, lastPage);';
					$pagesMenu .= '}';

					$pagesMenu .= 'function addPageNumbers(fromPage, toPage){';
					$pagesMenu .= '  var ellipsesIndex = 0;';
					$pagesMenu .= '  if(fromPage > toPage) return;';
					$pagesMenu .= '  if(fromPage > 0){';
					$pagesMenu .= '    if(pagesMenu.options[pagesMenu.options.length - 1].text != fromPage){';
					$pagesMenu .= '      ellipsesIndex = pagesMenu.options.length;';
					$pagesMenu .= '      fromPage--;';
					$pagesMenu .= '    }';
					$pagesMenu .= '  }';
					$pagesMenu .= '  for(i = fromPage; i <= toPage; i++){';
					$pagesMenu .= '    var option = document.createElement("option");';
					$pagesMenu .= '    option.text = (i + 1);';
					$pagesMenu .= '    option.value = i;';
					$pagesMenu .= '    if(i == currentPage){ option.selected = "selected"; }';
					$pagesMenu .= '    try{';
					$pagesMenu .= '      /* for IE earlier than version 8 */';
					$pagesMenu .= '      pagesMenu.add(option, pagesMenu.options[null]);';
					$pagesMenu .= '    }catch(e){';
					$pagesMenu .= '      pagesMenu.add(option, null);';
					$pagesMenu .= '    }';
					$pagesMenu .= '  }';
					$pagesMenu .= '  if(ellipsesIndex > 0){';
					$pagesMenu .= '    pagesMenu.options[ellipsesIndex].text = " ... ";';
					$pagesMenu .= '  }';
					$pagesMenu .= '}';
					$pagesMenu .= '</script>';
				}

				$this->HTML .= "\n\t";

				if($i){ // 1 or more records found
					$this->HTML .= "<tfoot><tr><td colspan=".(count($this->ColCaption)+1).'>';
						$this->HTML .= $Translation['records x to y of z'];
					$this->HTML .= '</td></tr></tfoot>';
				}

				if(!$i){ // no records found
					$this->HTML .= "<tfoot><tr><td colspan=".(count($this->ColCaption)+1).'>';
						$this->HTML .= '<div class="alert alert-warning">';
							$this->HTML .= '<i class="glyphicon glyphicon-warning-sign"></i> ';
							$this->HTML .= $Translation['No matches found!'];
						$this->HTML .= '</div>';
					$this->HTML .= '</td></tr></tfoot>';
				}

			}else{ // TVP
				if($i)  $this->HTML .= "\n\t<tfoot><tr><td colspan=".(count($this->ColCaption) + 1). '>' . $Translation['records x to y of z'] . '</td></tr></tfoot>';
				if(!$i) $this->HTML .= "\n\t<tfoot><tr><td colspan=".(count($this->ColCaption) + 1). '>' . $Translation['No matches found!'] . '</td></tr></tfoot>';
			}

			$this->HTML = str_replace("<FirstRecord>", number_format($FirstRecord), $this->HTML);
			$this->HTML = str_replace("<LastRecord>", number_format($i), $this->HTML);
			$this->HTML = str_replace("<RecordCount>", number_format($RecordCount), $this->HTML);
			$tvShown=true;

			$this->HTML .= "</table></div>\n";

			/* highlight quick search matches */
			if($SearchString!='') $this->HTML .= '<script>$j(function(){ $j(".table-responsive td").mark("' . html_attr($SearchString) . '", { className: "text-warning bg-warning", diacritics: false }); })</script>';

			if($Print_x == '' && $i){ // TV
				$this->HTML .= '<div class="row pagination-section">';
					$this->HTML .= '<div class="col-sm-4 col-md-3 col-lg-2 vspacer-lg">';
						$this->HTML .= '<button onClick="' . $resetSelection . ' document.myform.NoDV.value = 1; return true;" type="submit" name="Previous_x" id="Previous" value="1" class="btn btn-default btn-block"><i class="glyphicon glyphicon-chevron-left"></i> ' . $Translation['Previous'] . '</button>';
					$this->HTML .= '</div>';

					$this->HTML .= '<div class="col-sm-4 col-md-4 col-lg-2 col-md-offset-1 col-lg-offset-3 text-center vspacer-lg">';
						$this->HTML .= $pagesMenu;
					$this->HTML .= '</div>';

					$this->HTML .= '<div class="col-sm-4 col-md-3 col-lg-2 col-md-offset-1 col-lg-offset-3 text-right vspacer-lg">';
						$this->HTML .= '<button onClick="'.$resetSelection.' document.myform.NoDV.value=1; return true;" type="submit" name="Next_x" id="Next" value="1" class="btn btn-default btn-block">' . $Translation['Next'] . ' <i class="glyphicon glyphicon-chevron-right"></i></button>';
					$this->HTML .= '</div>';
				$this->HTML .= '</div>';
			}

			$this->HTML .= '</div>'; // end of div.table_view
		}
		/* that marks the end of the TV table */

	// hidden variables ....
		foreach($this->filterers as $filterer => $caption){
			if($_REQUEST['filterer_' . $filterer] != ''){
				$this->HTML .= "<input name=\"filterer_{$filterer}\" value=\"" . html_attr($_REQUEST['filterer_' . $filterer]) . "\" type=\"hidden\" />";
				break; // currently, only one filterer can be applied at a time
			}
		}

		$this->HTML .= '<!-- possible values for current_view: TV, TVP, DV, DVP, Filters, TVDV -->';
		$this->HTML .= '<input name="current_view" id="current_view" value="' . $current_view . '" type="hidden">';
		$this->HTML .= '<input name="SortField" value="' . $SortField . '" type="hidden">';
		$this->HTML .= '<input name="SelectedID" value="' . html_attr($SelectedID) . '" type="hidden">';
		$this->HTML .= '<input name="SelectedField" value="" type="hidden">';
		$this->HTML .= '<input name="SortDirection" type="hidden" value="' . $SortDirection . '">';
		$this->HTML .= '<input name="FirstRecord" type="hidden" value="' . $FirstRecord . '">';
		$this->HTML .= '<input name="NoDV" type="hidden" value="">';
		$this->HTML .= '<input name="PrintDV" type="hidden" value="">';
		if($this->QuickSearch && !strpos($this->HTML, 'SearchString')) $this->HTML .= '<input name="SearchString" type="hidden" value="' . html_attr($SearchString) . '">';
	// hidden variables: filters ...
		$FiltersCode = '';
		for($i = 1; $i <= (datalist_filters_count * $FiltersPerGroup); $i++){ // Number of filters allowed
			if($i%$FiltersPerGroup == 1 && $i != 1 && $FilterAnd[$i] != ''){
				$FiltersCode .= "<input name=\"FilterAnd[$i]\" value=\"$FilterAnd[$i]\" type=\"hidden\">\n";
			}
			if($FilterField[$i] != '' && $FilterOperator[$i] != '' && ($FilterValue[$i] != '' || strpos($FilterOperator[$i], 'empty'))){
				if(!strstr($FiltersCode, "<input name=\"FilterAnd[{$i}]\" value="))
					$FiltersCode .= "<input name=\"FilterAnd[{$i}]\" value=\"{$FilterAnd[$i]}\" type=\"hidden\">\n";
				$FiltersCode .= "<input name=\"FilterField[{$i}]\" value=\"{$FilterField[$i]}\" type=\"hidden\">\n";
				$FiltersCode .= "<input name=\"FilterOperator[{$i}]\" value=\"{$FilterOperator[$i]}\" type=\"hidden\">\n";
				$FiltersCode .= "<input name=\"FilterValue[{$i}]\" value=\"" . html_attr($FilterValue[$i]) . "\" type=\"hidden\">\n";
			}
		}
		$FiltersCode .= "<input name=\"DisplayRecords\" value=\"$DisplayRecords\" type=\"hidden\" />";
		$this->HTML .= $FiltersCode;

	// display details form ...
		if(($this->AllowSelection || $this->AllowInsert || $this->AllowUpdate || $this->AllowDelete) && $Print_x=='' && !$PrintDV){
			if(($this->SeparateDV && $this->HideTableView) || !$this->SeparateDV){
				$dvCode = call_user_func("{$this->TableName}_form", $SelectedID, $this->AllowUpdate, (($this->HideTableView && $SelectedID) ? 0 : $this->AllowInsert), $this->AllowDelete, $this->SeparateDV, $this->TemplateDV, $this->TemplateDVP);

				$this->HTML .= "\n\t<div class=\"col-xs-12 detail_view {$this->DVClasses}\">{$tv_dv_separator}<div class=\"panel panel-default\">{$dvCode}</div></div>";
				$this->HTML .= ($this->SeparateDV ? '<input name="SearchString" value="' . html_attr($SearchString) . '" type="hidden">' : '');
				if($dvCode){
					$this->ContentType = 'detailview';
					$dvShown = true;
				}
			}
		}

	// display multiple printable detail views
		if($PrintDV){
			$dvCode = '';
			$_REQUEST['dvprint_x'] = 1;

			// hidden vars
			foreach($this->filterers as $filterer => $caption){
				if($_REQUEST['filterer_' . $filterer] != ''){
					$this->HTML .= "<input name=\"filterer_{$filterer}\" value=\"" . html_attr($_REQUEST['filterer_' . $filterer]) . "\" type=\"hidden\" />";
					break; // currently, only one filterer can be applied at a time
				}
			}

			// count selected records
			$selectedRecords = 0;
			if(is_array($_REQUEST['record_selector'])) foreach($_REQUEST['record_selector'] as $id){
				$selectedRecords++;
				$this->HTML .= '<input type="hidden" name="record_selector[]" value="' . html_attr($id) . '">'."\n";
			}

			if($selectedRecords && $selectedRecords <= datalist_max_records_dv_print){ // if records selected > {datalist_max_records_dv_print} don't show DV preview to avoid db performance issues.
				foreach($_REQUEST['record_selector'] as $id){
					$dvCode .= call_user_func($this->TableName . '_form', $id, 0, 0, 0, 1, $this->TemplateDV, $this->TemplateDVP);
				}

				if($dvCode!=''){
					$dvCode = preg_replace('/<input .*?type="?image"?.*?>/', '', $dvCode);
					$this->HTML .= $dvCode;
				}
			}else{
				$this->HTML .= error_message($Translation['Maximum records allowed to enable this feature is'] . ' ' . datalist_max_records_dv_print);
				$this->HTML .= '<input type="submit" class="print-button" value="'.$Translation['Print Preview Table View'].'">';
			}
		}

		$this->HTML .= "</div></form>";
		$this->HTML .= '</div><div class="col-xs-1 md-hidden lg-hidden"></div></div>';

		// $this->HTML .= '<font face="garamond">'.html_attr($tvQuery).'</font>';  // uncomment this line for debugging the table view query

		if($dvShown && $tvShown) $this->ContentType='tableview+detailview';
		if($dvprint_x!='') $this->ContentType='print-detailview';
		if($Print_x!='') $this->ContentType='print-tableview';
		if($PrintDV!='') $this->ContentType='print-detailview';

		// call detail view javascript hook file if found
		$dvJSHooksFile=dirname(__FILE__).'/hooks/'.$this->TableName.'-dv.js';
		if(is_file($dvJSHooksFile) && ($this->ContentType=='detailview' || $this->ContentType=='tableview+detailview')){
			$this->HTML.="\n<script src=\"hooks/{$this->TableName}-dv.js\"></script>\n";
		}
	}
}

