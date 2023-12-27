<?php
/* includes and bootstrapping code common to setup, users area and admin area */

	error_reporting(E_ERROR | E_PARSE);

	// if hooks/__bootstrap.php is found, it's included before any other files
	// to define the very first booting steps performed by app. Useful for controlling
	// session behavior, overriding definitions ... etc.
	//
	// WARNING: adding code to __bootstrap.php is meant for the hard core developers only!
	// Unless you know what you're doing, I highly discourage adding code to that file!
	// You've been warned! Don't blame me ;)
	//
	@include_once(__DIR__ . '/hooks/__bootstrap.php');

	require_once(__DIR__ . '/definitions.php');

	@date_default_timezone_set(TIMEZONE);

	require_once(APP_DIR . '/defaultLang.php');
	include_once(APP_DIR . '/language.php');
	$Translation = array_merge($TranslationEn, $Translation);

	require_once(APP_DIR . '/db.php');
	require_once(APP_DIR . '/admin/incFunctions.php');

	// autoloading classes
	spl_autoload_register(function($class) {
		// convert namespace separators to directory separators
		$class = str_replace('\\', '/', $class);
		@include_once(APP_DIR . "/resources/lib/{$class}.php");
	});

	/* trim $_POST, $_GET, $_REQUEST */
	if(count($_POST)) $_POST = array_trim($_POST);
	if(count($_GET)) $_GET = array_trim($_GET);
	if(count($_REQUEST)) $_REQUEST = array_trim($_REQUEST);

/************************************************/

	function configFileName() {
		$tenantId = Authentication::tenantIdPadded();
		return __DIR__ . "/config{$tenantId}.php";
	}

	function default_config() {
		return [
			'dbServer' => '',
			'dbUsername' => '',
			'dbPassword' => '',
			'dbDatabase' => '',
			'dbPort' => '',
			'appURI' => '',
			'host' => (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443' ? '' : ":{$_SERVER['SERVER_PORT']}")),

			'adminConfig' => [
				'adminUsername' => '',
				'adminPassword' => '',
				'notifyAdminNewMembers' => false,
				'defaultSignUp' => 1,
				'anonymousGroup' => 'anonymous',
				'anonymousMember' => 'guest',
				'groupsPerPage' => 10,
				'membersPerPage' => 10,
				'recordsPerPage' => 10,
				'custom1' => 'Full Name',
				'custom2' => 'Address',
				'custom3' => 'City',
				'custom4' => 'State',
				'MySQLDateFormat' => '%m/%d/%Y',
				'PHPDateFormat' => 'n/j/Y',
				'PHPDateTimeFormat' => 'm/d/Y, h:i a',
				'senderName' => 'Membership management',
				'senderEmail' => '',
				'approvalSubject' => 'Your membership is now approved',
				'approvalMessage' => "Dear member,\n\nYour membership is now approved by the admin. You can log in to your account here:\nhttp://{$_SERVER['HTTP_HOST']}" . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "\n\nRegards,\nAdmin",
				'hide_twitter_feed' => false,
				'maintenance_mode_message' => '<b>Our website is currently down for maintenance</b><br>\r\nWe expect to be back in a couple hours. Thanks for your patience.',
				'mail_function' => 'mail',
				'smtp_server' => '',
				'smtp_encryption' => '',
				'smtp_port' => 25,
				'smtp_user' => '',
				'smtp_pass' => '',
				'googleAPIKey' => '',
				'baseUploadPath' => 'images',
			]
		];
	}

	function detect_config($redirectToSetup = true) {
		$config_exists = is_readable(configFileName());

		if(!$redirectToSetup) return $config_exists;

		if(!$config_exists) {
			if(MULTI_TENANTS) redirect(SaaS::loginUrl(), true);

			$url = (request_outside_admin_folder() ? '' : '../') . 'setup.php';

			if(!headers_sent())
				@header("Location: $url");
			else
				echo '<META HTTP-EQUIV="Refresh" CONTENT="0;url=' . $url . '">' .
					 '<script>window.location = "' . $url . '";</script>';

			exit;
		}

		if(!MULTI_TENANTS) update_config_app_uri();
		return $config_exists;
	}

	function migrate_config() {
		if(MULTI_TENANTS) return false;
		if(!is_readable(__DIR__ . '/admin/incConfig.php') || !detect_config(false)) {
			return false; // nothing to migrate
		}

		@include(__DIR__ . '/admin/incConfig.php');
		@include(configFileName());

		if(empty($dbPort)) $dbPort = ini_get('mysqli.default_port');

		$config_array = [
			'dbServer' => $dbServer,
			'dbUsername' => $dbUsername,
			'dbPassword' => $dbPassword,
			'dbDatabase' => $dbDatabase,
			'dbPort' => $dbPort,
			'adminConfig' => $adminConfig
		];

		if(isset($appURI)) $config_array['appURI'] = $appURI;
		if(isset($host))
			$config_array['host'] = $host;
		else
			$config_array['host'] = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443' ? '' : ":{$_SERVER['SERVER_PORT']}"));

		if(save_config($config_array)) {
			@rename(__DIR__ . '/admin/incConfig.php', __DIR__ . '/admin/incConfig.bak.php');
			@unlink(__DIR__ . '/admin/incConfig.php');
			return true;
		}

		return false;
	}

	function save_config($config_array = []) {
		$conf_arr_err = ['error' => 'Invalid config array'];

		if(!is_array($config_array)) return $conf_arr_err;
		if(!isset($config_array['adminConfig'])) return $conf_arr_err;
		if(!is_array($config_array['adminConfig'])) return $conf_arr_err;

		if(!count($config_array) || !count($config_array['adminConfig']))
			return $conf_arr_err;

		$new_admin_config = '';

		// make sure super admin username is lower case
		$config_array['adminConfig']['adminUsername'] = strtolower($config_array['adminConfig']['adminUsername']);

		foreach($config_array['adminConfig'] as $admin_var => $admin_val)
			$new_admin_config .= "\t\t'" . addslashes($admin_var) . "' => \"" . str_replace(["\n", "\r", '"', '$'], ['\n', '\r', '\"', '\$'], $admin_val) . "\",\n";

		$new_config = "<?php\n" . 
			"\t\$dbServer = '" . addslashes($config_array['dbServer']) . "';\n" .
			"\t\$dbUsername = '" . addslashes($config_array['dbUsername']) . "';\n" .
			"\t\$dbPassword = '" . addslashes($config_array['dbPassword']) . "';\n" .
			"\t\$dbDatabase = '" . addslashes($config_array['dbDatabase']) . "';\n" .
			"\t\$dbPort = '" . addslashes($config_array['dbPort']) . "';\n" .

			(isset($config_array['appURI']) ? "\t\$appURI = '" . addslashes($config_array['appURI']) . "';\n" : '') .
			(isset($config_array['host']) ? "\t\$host = '" . addslashes($config_array['host']) . "';\n" : '') .

			"\n\t\$adminConfig = [\n" . 
				$new_admin_config .
			"\t];";

		if(detect_config(false)) {
			// attempt to back up config
			@copy(configFileName(), str_replace('.php', '.bak.php', configFileName()));
		}

		if(!$fp = @fopen(configFileName(), 'w'))
			return ['error' => 'Unable to write to config file', 'config' => $new_config];
		fwrite($fp, $new_config);
		fclose($fp);

		// flush changes
		config('adminConfig', true);

		return true;
	}

	function config($var, $force_reload = false) {
		static $config = [];

		$defCfg = default_config();

		if(empty($config) || $force_reload) {
			@include(configFileName());

			if(empty($dbPort)) $dbPort = ini_get('mysqli.default_port');

			$config['dbServer'] = $dbServer;
			$config['dbDatabase'] = $dbDatabase;
			$config['dbPassword'] = $dbPassword;
			$config['dbUsername'] = $dbUsername;
			$config['dbPort'] = $dbPort;
			$config['appURI'] = $appURI;
			$config['host'] = $host;
			$config['adminConfig'] = $adminConfig;
			if(empty($config['adminConfig']['baseUploadPath'])) $config['adminConfig']['baseUploadPath'] = 'images';
		}

		return (isset($config[$var]) ? $config[$var] : $defCfg[$var]);
	}

	/**
	 *  @brief check if given password matches given hash, preserving backward compatibility with MD5
	 *  
	 *  @param [in] $password Description for $password
	 *  @param [in] $hash Description for $hash
	 *  @return Boolean indicating match or no match
	 */
	function password_match($password, $hash) {
		if(strlen($hash) == 32) return (md5($password) == $hash); // for backward compatibility with old password hashes
		return password_verify($password, $hash);
	}

	/**
	 *  @brief Migrate MD5 pass hashes to BCrypt if supported
	 *  
	 *  @param [in] $user username to migrate
	 *  @param [in] $pass current password
	 *  @param [in] $hash stored hash of password
	 */
	function password_harden($user, $pass, $hash) {
		/* continue only if PHP 5.5+ and hash is 32 chars (md5) */
		if(version_compare(PHP_VERSION, '5.5.0') == -1 || strlen($hash) > 32) return;

		$new_hash = makeSafe(password_hash($pass, PASSWORD_DEFAULT));
		$suser = makeSafe($user, false);
		sql("UPDATE `membership_users` SET `passMD5`='{$new_hash}' WHERE `memberID`='{$suser}'", $eo);
	}

	function update_config_app_uri() {
		// update only if we're on homepage
		if(!defined('HOMEPAGE')) return;
		if(!preg_match('/index\.php$/', $_SERVER['SCRIPT_NAME'])) return;

		// config exists?
		@include(configFileName());
		if(!isset($dbServer)) return;

		if(empty($dbPort)) $dbPort = ini_get('mysqli.default_port');

		// check if appURI and host defined
		if(isset($appURI) && isset($host)) return;

		// now set appURI, knowing that we're on homepage
		save_config([
			'dbServer' => $dbServer,
			'dbUsername' => $dbUsername,
			'dbPassword' => $dbPassword,
			'dbDatabase' => $dbDatabase,
			'dbPort' => $dbPort,
			'appURI' => formatUri(dirname($_SERVER['SCRIPT_NAME'])),
			'host' => (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443' ? '' : ":{$_SERVER['SERVER_PORT']}")),
			'adminConfig' => $adminConfig
		]);
	}

	#########################################################
	function latest_jquery() {
		$jquery_dir = __DIR__ . '/resources/jquery/js';

		$files = scandir($jquery_dir, SCANDIR_SORT_DESCENDING);
		foreach($files as $entry) {
			if(preg_match('/^jquery[-0-9\.]*\.min\.js$/i', $entry))
				return $entry;
		}

		return '';
	}

