<?php
	// For help on using hooks, please refer to https://bigprof.com/appgini/help/working-with-generated-web-database-application/hooks

	function rental_owners_init(&$options, $memberInfo, &$args) {
		/* Inserted by Search Page Maker for AppGini on 2020-11-18 12:19:27 */
		$options->FilterPage = 'hooks/rental_owners_filter.php';
		/* End of Search Page Maker for AppGini code */


		return TRUE;
	}

	function rental_owners_header($contentType, $memberInfo, &$args) {
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

	function rental_owners_footer($contentType, $memberInfo, &$args) {
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

	function rental_owners_before_insert(&$data, $memberInfo, &$args) {

		return TRUE;
	}

	function rental_owners_after_insert($data, $memberInfo, &$args) {

		return TRUE;
	}

	function rental_owners_before_update(&$data, $memberInfo, &$args) {

		return TRUE;
	}

	function rental_owners_after_update($data, $memberInfo, &$args) {

		return TRUE;
	}

	function rental_owners_before_delete($selectedID, &$skipChecks, $memberInfo, &$args) {

		return TRUE;
	}

	function rental_owners_after_delete($selectedID, $memberInfo, &$args) {

	}

	function rental_owners_dv($selectedID, $memberInfo, &$html, &$args) {

	}

	function rental_owners_csv($query, $memberInfo, &$args) {

		return $query;
	}
	function rental_owners_batch_actions(&$args) {

		return [];
	}
