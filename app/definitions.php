<?php
	// The constants below can be overridden by defining them with different values in hooks/__bootstrap.php

	@define('DEBUG_MODE', false);
	@define('SESSION_NAME', 'Rental_property_manager');
	@define('APP_TITLE', 'Rental Property Manager');
	@define('APP_DIR', __DIR__);
	@define('APP_VERSION', '25.13');
	@define('maxSortBy', 4);
	@define('empty_lookup_value', '{empty_value}');
	@define('MULTIPLE_SUPER_ADMINS', false);

	@define('DATABASE', 'mysqli');
	@define('mysql_charset', 'utf8');

	@define('TIMEZONE', 'America/New_York');

	@define('datalist_db_encoding', 'UTF-8');
	@define('datalist_filters_count', 20);
	@define('FILTERS_PER_GROUP', 4); // changing this value might lead to unexpected behavior as it has not been tested with other values
	@define('datalist_max_records_multi_selection', 1000);
	@define('datalist_max_page_lump', 50);
	@define('datalist_max_records_dv_print', 100);
	@define('datalist_auto_complete_size', 1000);
	@define('datalist_date_format', 'mdY');
	@define('datalist_max_records_per_page', 2000);
	@define('datalist_image_uploads_exist', true);
	@define('datalist_date_separator', '/');

	@define('FILTER_OPERATORS', [
		'equal-to' => '<=>',
		'not-equal-to' => '!=',
		'greater-than' => '>',
		'greater-than-or-equal-to' => '>=',
		'less-than' => '<',
		'less-than-or-equal-to' => '<=',
		'like' => 'like',
		'not-like' => 'not like',
		'is-empty' => 'isEmpty',
		'is-not-empty' => 'isNotEmpty',
	]);

	// for backward compatibility
	$GLOBALS['filter_operators'] = FILTER_OPERATORS;

	@define('MULTI_TENANTS', false);
	@define('FORCE_SETUP_CAPTCHA', true);
	@define('HOMEPAGE_NAVMENUS', false);
	@define('DEFAULT_THEME', 'bootstrap');
	@define('BOOTSTRAP_3D_EFFECTS', true); // if true, the 3D effects will be used for the bootstrap theme
	@define('THEME_COMPACT', true);
	@define('NO_THEME_SELECTION', false); // if true, the theme selection will not be available in the user profile page
