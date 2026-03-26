<?php
	// The constants below can be overridden by defining them with different values in hooks/__bootstrap.php

	@define('DEBUG_MODE', false);
	@define('SESSION_NAME', 'Rental_property_manager');
	@define('APP_TITLE', 'Rental Property Manager');
	@define('APP_DIR', __DIR__);
	@define('APP_VERSION', '26.13');
	@define('maxSortBy', 4);
	@define('empty_lookup_value', '{empty_value}');
	@define('MULTIPLE_SUPER_ADMINS', false);

	@define('DATABASE', 'mysqli');
	@define('mysql_charset', 'utf8');

	@define('TIMEZONE', 'America/New_York');

	@define('datalist_db_encoding', 'UTF-8');
	@define('FILTER_GROUPS', 20); // maximum number of filters groups
	@define('datalist_filters_count', FILTER_GROUPS); // for backward compatibility
	@define('FILTERS_PER_GROUP', 4); // changing this value might lead to unexpected behavior as it has not been tested with other values
	@define('MAX_FILTER_LINKS_PER_USER', 100); // maximum number of saved filter links per user
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
	@define('DEFAULT_THEME', 'paper');
	@define('BOOTSTRAP_3D_EFFECTS', false); // if true, the 3D effects will be used for the bootstrap theme
	@define('THEME_COMPACT', false);
	@define('NO_THEME_SELECTION', false); // if true, the theme selection will not be available in the user profile page
	@define('HOMEPAGE_FIRST_TABLE_DOUBLE_WIDTH', false);
	@define('HOMEPAGE_TABLES_PER_ROW', 2);
	@define('HOMEPAGE_PANEL_HEIGHT', 40);
	@define('HOMEPAGE_QUICK_SEARCH_TABLES', true); // if true, the quick search box on homepage will search in user tables
	@define('DEFAULT_NAV_MENU', 'vertical');

	// default backup and restore commands, can be overridden in admin settings > Application tab
	@define('DB_BACKUP_COMMAND', 'mysqldump -y -e --no-autocommit -q --single-transaction');
	@define('DB_RESTORE_COMMAND', 'mysql');

	// if true, ajax requests will be queued to avoid too many concurrent ajax requests on slower servers
	@define('QUEUE_AJAX_REQUESTS', false);

	// 2FA Settings:
	// Expiry time for One-Time Password (OTP) in minutes. Default is 15 minutes. Min recommended is 5 minutes. Valid range is 1 to 30.
	define('OTP_EXPIRY_MINUTES', 15);
	// OTP length (number of digits). Default is 6 digits. Valid range is 4 to 10.
	define('OTP_LENGTH', 6);
	// OTP CSS styling inside the email message
	define('OTP_EMAIL_CSS', 'font-size: 24px; font-weight: bold; margin: 10px 0; border: 1px solid #ccc; padding: 10px; max-width: 225px; text-align: center; letter-spacing: 6px;');

	define('DEFAULT_LANGUAGE', 'en');

