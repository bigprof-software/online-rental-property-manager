<?php
	// For help on using hooks, please refer to https://bigprof.com/appgini/help/working-with-generated-web-database-application/hooks

	function applications_leases_init(&$options, $memberInfo, &$args) {
		/* Inserted by Search Page Maker for AppGini on 2020-11-18 12:19:27 */
		$options->FilterPage = 'hooks/applications_leases_filter.php';
		/* End of Search Page Maker for AppGini code */


		return TRUE;
	}

	function applications_leases_header($contentType, $memberInfo, &$args) {
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

	function applications_leases_footer($contentType, $memberInfo, &$args) {
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

	function applications_leases_before_insert(&$data, $memberInfo, &$args) {
		// can current user view this parent?
		if(!check_record_permission('applicants_and_tenants', $data['tenants'])) return false;

		return TRUE;
	}

	function applications_leases_after_insert($data, $memberInfo, &$args) {

		return TRUE;
	}

	function applications_leases_before_update(&$data, $memberInfo, &$args) {
		// can current user view this parent?
		if(!check_record_permission('applicants_and_tenants', $data['tenants'])) return false;

		return TRUE;
	}

	function applications_leases_after_update($data, $memberInfo, &$args) {

		return TRUE;
	}

	function applications_leases_before_delete($selectedID, &$skipChecks, $memberInfo, &$args) {

		return TRUE;
	}

	function applications_leases_after_delete($selectedID, $memberInfo, &$args) {

	}

	function applications_leases_dv($selectedID, $memberInfo, &$html, &$args) {

	}

	function applications_leases_csv($query, $memberInfo, &$args) {

		return $query;
	}
	function applications_leases_batch_actions(&$args) {
		/* Inserted by Mass Update on 2020-11-19 11:55:55 */
		
		/*
		 * Q: How do I return other custom batch commands not defined in mass_update plugin?
		 * 
		 * A: Define your commands ABOVE the 'Inserted by Mass Update' comment above 
		 * in an array named $custom_actions_top to display them above the commands 
		 * created by the mass_update plugin.
		 * 
		 * You can also define commands in an array named $custom_actions_bottom
		 * (also ABOVE the 'Inserted by Mass Update' comment block) to display them 
		 * below the commands created by the mass_update plugin.
		 * 
		*/

		if(!isset($custom_actions_top) || !is_array($custom_actions_top))
			$custom_actions_top = array();

		if(!isset($custom_actions_bottom) || !is_array($custom_actions_bottom))
			$custom_actions_bottom = array();

		$command = array(
			'ghqe4agakj7de10gc0ba' => array(
				'title' => "Approve application",
				'function' => 'massUpdateCommand_ghqe4agakj7de10gc0ba',
				'icon' => 'ok'
			),
		);

		$mi = getMemberInfo();
		switch($mi['group']) {
			default:
				/* for all other logged users, enable the following commands */
				if($mi['username'] && $mi['username'] != 'guest')
					return array_merge(
						$custom_actions_top,
						array(
							$command['ghqe4agakj7de10gc0ba']
						),
						$custom_actions_bottom
					);
		}


		/* End of Mass Update code */


		return array();
	}
