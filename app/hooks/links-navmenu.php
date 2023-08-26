<?php
	/*
	* You can add custom links to the navigation menu by appending them here ...
	* The format for each link is:
		$navLinks[] = [
			'url' => 'path/to/link', 
			'title' => 'Link title', 
			'groups' => ['group1', 'group2'], // groups allowed to see this link, use '*' if you want to show the link to all groups
			'icon' => 'path/to/icon',
			'table_group' => 0, // optional index of table group, default is 0
		];
	*/


	/* summary_reports links */
	$navLinks[] = array(
		'url' => 'hooks/summary-reports-list.php',
		'title' => 'Summary Reports',
		'groups' => array('*'),
		'icon' => 'hooks/summary_reports-logo-md.png'
	);

	/* calendar links */
		$navLinks[] = array(
			'url' => 'hooks/calendar-lease-start-end.php',
			'icon' => 'resources/table_icons/calendar.png',
			'title' => 'Leases starting/ending',
			'groups' => array('Admins'),
			'table_group' => '0',
		);

	/* end of calendar links */