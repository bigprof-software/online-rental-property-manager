<?php
	@define('DEBUG_MODE', false);
	@define('SESSION_NAME', 'Rental_property_manager');
	@define('APP_TITLE', 'Rental Property Manager');
	@define('APP_DIR', __DIR__);
	@define('APP_VERSION', '23.17');
	@define('maxSortBy', 4);
	@define('empty_lookup_value', '{empty_value}');
	@define('MULTIPLE_SUPER_ADMINS', false);

	@define('DATABASE', 'mysqli');
	@define('mysql_charset', 'utf8');

	@define('TIMEZONE', 'America/New_York');

	@define('datalist_db_encoding', 'UTF-8');
	@define('datalist_filters_count', 20);
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
