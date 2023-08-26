<?php
	// For help on using hooks, please refer to https://bigprof.com/appgini/help/working-with-generated-web-database-application/hooks

	function applicants_and_tenants_init(&$options, $memberInfo, &$args) {
		/* Inserted by Search Page Maker for AppGini on 2020-11-18 12:19:27 */
		$options->FilterPage = 'hooks/applicants_and_tenants_filter.php';
		/* End of Search Page Maker for AppGini code */


		return TRUE;
	}

	function applicants_and_tenants_header($contentType, $memberInfo, &$args) {
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

	function applicants_and_tenants_footer($contentType, $memberInfo, &$args) {
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

	function applicants_and_tenants_before_insert(&$data, $memberInfo, &$args) {

		return TRUE;
	}

	function applicants_and_tenants_after_insert($data, $memberInfo, &$args) {

		return TRUE;
	}

	function applicants_and_tenants_before_update(&$data, $memberInfo, &$args) {

		return TRUE;
	}

	function applicants_and_tenants_after_update($data, $memberInfo, &$args) {

		return TRUE;
	}

	function applicants_and_tenants_before_delete($selectedID, &$skipChecks, $memberInfo, &$args) {

		return TRUE;
	}

	function applicants_and_tenants_after_delete($selectedID, $memberInfo, &$args) {

	}

	function applicants_and_tenants_dv($selectedID, $memberInfo, &$html, &$args) {

	}

	function applicants_and_tenants_csv($query, $memberInfo, &$args) {

		return $query;
	}
	function applicants_and_tenants_batch_actions(&$args) {
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
			'm8xfpspfv9y3762768or' => array(
				'title' => "Change status",
				'function' => 'massUpdateCommand_m8xfpspfv9y3762768or',
				'icon' => 'circle-arrow-right'
			),
			'li3ml58uplm5oyp54b8x' => array(
				'title' => "Edit Notes",
				'function' => 'massUpdateCommand_li3ml58uplm5oyp54b8x',
				'icon' => 'pencil'
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
							$command['m8xfpspfv9y3762768or'],
							$command['li3ml58uplm5oyp54b8x']
						),
						$custom_actions_bottom
					);
		}


		/* End of Mass Update code */


		return array();
	}
