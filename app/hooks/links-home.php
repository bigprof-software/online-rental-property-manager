<?php
	/*
	 * You can add custom links in the home page by appending them here ...
	 * The format for each link is:
		$homeLinks[] = array(
			'url' => 'path/to/link', 
			'title' => 'Link title', 
			'description' => 'Link text',
			'groups' => array('group1', 'group2'), // groups allowed to see this link, use '*' if you want to show the link to all groups
			'grid_column_classes' => '', // optional CSS classes to apply to link block. See: http://getbootstrap.com/css/#grid
			'panel_classes' => '', // optional CSS classes to apply to panel. See: http://getbootstrap.com/components/#panels
			'link_classes' => '', // optional CSS classes to apply to link. See: http://getbootstrap.com/css/#buttons
			'icon' => 'path/to/icon' // optional icon to use with the link
		);
	 */


	/* summary_reports links */
	$homeLinks[] = array(
		'url' => 'hooks/summary-reports-list.php',
		'title' => 'Summary Reports',
		'groups' => array('*'),
		'table_group' => 'Leases',
		'description' => '',
		'grid_column_classes' => 'col-xs-12 col-sm-6 col-md-6 col-lg-6',
		'panel_classes' => '',
		'link_classes' => '',
		'icon' => 'hooks/summary_reports-logo-md.png'
	);

	/* calendar links */
		$homeLinks[] = array(
			'url' => 'hooks/calendar-lease-start-end.php',
			'icon' => 'resources/table_icons/calendar.png',
			'title' => 'Leases starting/ending',
			'description' => '',
			'groups' => array('Admins'),
			'grid_column_classes' => 'col-sm-6 col-md-4 col-lg-3',
			'panel_classes' => 'panel-info',
			'link_classes' => 'btn-info',
			'table_group' => 'Leases',
		);

	/* end of calendar links */