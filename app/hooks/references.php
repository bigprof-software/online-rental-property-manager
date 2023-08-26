<?php
	// For help on using hooks, please refer to https://bigprof.com/appgini/help/working-with-generated-web-database-application/hooks

	function references_init(&$options, $memberInfo, &$args) {
		/* Inserted by Search Page Maker for AppGini on 2020-11-18 12:19:27 */
		$options->FilterPage = 'hooks/references_filter.php';
		/* End of Search Page Maker for AppGini code */


		return TRUE;
	}

	function references_header($contentType, $memberInfo, &$args) {
		$header='';

		switch($contentType) {
			case 'tableview':
				$header='';
				break;

			case 'detailview':
				$header='';
				break;

			case 'tableview+detailview':
				$header='';
				break;

			case 'print-tableview':
				$header='';
				break;

			case 'print-detailview':
				$header='';
				break;

			case 'filters':
				$header='';
				break;
		}

		return $header;
	}

	function references_footer($contentType, $memberInfo, &$args) {
		$footer='';

		switch($contentType) {
			case 'tableview':
				$footer='';
				break;

			case 'detailview':
				$footer='';
				break;

			case 'tableview+detailview':
				$footer='';
				break;

			case 'print-tableview':
				$footer='';
				break;

			case 'print-detailview':
				$footer='';
				break;

			case 'filters':
				$footer='';
				break;
		}

		return $footer;
	}

	function references_before_insert(&$data, $memberInfo, &$args) {
		// can current user view this parent?
		if(!check_record_permission('applicants_and_tenants', $data['tenant'])) return false;

		return TRUE;
	}

	function references_after_insert($data, $memberInfo, &$args) {

		return TRUE;
	}

	function references_before_update(&$data, $memberInfo, &$args) {
		// can current user view this parent?
		if(!check_record_permission('applicants_and_tenants', $data['tenant'])) return false;

		return TRUE;
	}

	function references_after_update($data, $memberInfo, &$args) {

		return TRUE;
	}

	function references_before_delete($selectedID, &$skipChecks, $memberInfo, &$args) {

		return TRUE;
	}

	function references_after_delete($selectedID, $memberInfo, &$args) {

	}

	function references_dv($selectedID, $memberInfo, &$html, &$args) {

	}

	function references_csv($query, $memberInfo, &$args) {

		return $query;
	}
	function references_batch_actions(&$args) {

		return [];
	}
