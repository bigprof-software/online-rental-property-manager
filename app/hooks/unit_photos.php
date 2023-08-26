<?php
	// For help on using hooks, please refer to https://bigprof.com/appgini/help/working-with-generated-web-database-application/hooks

	function unit_photos_init(&$options, $memberInfo, &$args) {
		/* Inserted by Search Page Maker for AppGini on 2020-11-18 12:19:27 */
		$options->FilterPage = 'hooks/unit_photos_filter.php';
		/* End of Search Page Maker for AppGini code */


		return TRUE;
	}

	function unit_photos_header($contentType, $memberInfo, &$args) {
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

	function unit_photos_footer($contentType, $memberInfo, &$args) {
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

	function unit_photos_before_insert(&$data, $memberInfo, &$args) {

		return TRUE;
	}

	function unit_photos_after_insert($data, $memberInfo, &$args) {

		return TRUE;
	}

	function unit_photos_before_update(&$data, $memberInfo, &$args) {

		return TRUE;
	}

	function unit_photos_after_update($data, $memberInfo, &$args) {

		return TRUE;
	}

	function unit_photos_before_delete($selectedID, &$skipChecks, $memberInfo, &$args) {

		return TRUE;
	}

	function unit_photos_after_delete($selectedID, $memberInfo, &$args) {

	}

	function unit_photos_dv($selectedID, $memberInfo, &$html, &$args) {

	}

	function unit_photos_csv($query, $memberInfo, &$args) {

		return $query;
	}
	function unit_photos_batch_actions(&$args) {

		return [];
	}
