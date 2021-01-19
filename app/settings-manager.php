<?php

	define('mysql_charset', 'utf8');

	function detect_config($redirect_to_setup = true) {
		$config_exists = is_readable(dirname(__FILE__) . '/config.php');

		if(!$config_exists && $redirect_to_setup) {
			$url = (request_outside_admin_folder() ? '' : '../') . 'setup.php';

			if(!headers_sent()) {
				@header("Location: $url");
			} else {
				echo '<META HTTP-EQUIV="Refresh" CONTENT="0;url=' . $url . '">' .
					 '<script>window.location = "' . $url . '";</script>';
			}
			exit;
		}

		if($redirect_to_setup) update_config_app_uri();
		return $config_exists;
	}

	function migrate_config() {
		$curr_dir = dirname(__FILE__);
		if(!is_readable($curr_dir . '/admin/incConfig.php') || !detect_config(false)) {
			return false; // nothing to migrate
		}

		@include($curr_dir . '/admin/incConfig.php');
		@include($curr_dir . '/config.php');

		$config_array = array(
			'dbServer' => $dbServer,
			'dbUsername' => $dbUsername,
			'dbPassword' => $dbPassword,
			'dbDatabase' => $dbDatabase,
			'adminConfig' => $adminConfig
		);

		if(isset($appURI)) $config_array['appURI'] = $appURI;
		if(isset($host))
			$config_array['host'] = $host;
		else
			$config_array['host'] = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443' ? '' : ":{$_SERVER['SERVER_PORT']}"));

		if(save_config($config_array)) {
			@rename($curr_dir . '/admin/incConfig.php', $curr_dir . '/admin/incConfig.bak.php');
			@unlink($curr_dir . '/admin/incConfig.php');
			return true;
		}

		return false;
	}

	function save_config($config_array = []) {
		$curr_dir = dirname(__FILE__);
		if(!count($config_array) || !count($config_array['adminConfig'])) return array('error' => 'Invalid config array');

		$new_admin_config = '';
		foreach($config_array['adminConfig'] as $admin_var => $admin_val)
			$new_admin_config .= "\t\t'" . addslashes($admin_var) . "' => \"" . str_replace(["\n", "\r", '"', '$'], ['\n', '\r', '\"', '\$'], $admin_val) . "\",\n";

		$new_config = "<?php\n" . 
			"\t\$dbServer = '" . addslashes($config_array['dbServer']) . "';\n" .
			"\t\$dbUsername = '" . addslashes($config_array['dbUsername']) . "';\n" .
			"\t\$dbPassword = '" . addslashes($config_array['dbPassword']) . "';\n" .
			"\t\$dbDatabase = '" . addslashes($config_array['dbDatabase']) . "';\n" .

			(isset($config_array['appURI']) ? "\t\$appURI = '" . addslashes($config_array['appURI']) . "';\n" : '') .
			(isset($config_array['host']) ? "\t\$host = '" . addslashes($config_array['host']) . "';\n" : '') .

			"\n\t\$adminConfig = [\n" . 
				$new_admin_config .
			"\t];";

		if(detect_config(false)) {
			// attempt to back up config
			@copy($curr_dir . '/config.php', $curr_dir . '/config.bak.php');
		}

		if(!$fp = @fopen($curr_dir . '/config.php', 'w')) return ['error' => 'Unable to write to config file', 'config' => $new_config];
		fwrite($fp, $new_config);
		fclose($fp);

		return true;
	}

	function request_outside_admin_folder() {
		return (realpath(dirname(__FILE__)) == realpath(dirname($_SERVER['SCRIPT_FILENAME'])) ? true : false);
	}

	function config($var, $force_reload = false) {
		static $config;

		$default_config = [
			'dbServer' => '',
			'dbUsername' => '',
			'dbPassword' => '',
			'dbDatabase' => '',
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

		if(!isset($config) || $force_reload) {
			@include(dirname(__FILE__) . '/config.php');

			$config['dbServer'] = $dbServer;
			$config['dbDatabase'] = $dbDatabase;
			$config['dbPassword'] = $dbPassword;
			$config['dbUsername'] = $dbUsername;
			$config['appURI'] = $appURI;
			$config['host'] = $host;
			$config['adminConfig'] = $adminConfig;
			if(empty($config['adminConfig']['baseUploadPath'])) $config['adminConfig']['baseUploadPath'] = 'images';
		}

		return (isset($config[$var]) && $config[$var] ? $config[$var] : $default_config[$var]);
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
		@include(dirname(__FILE__) . '/config.php');
		if(!isset($dbServer)) return;

		// check if appURI and host defined
		if(isset($appURI) && isset($host)) return;

		// now set appURI, knowing that we're on homepage
		save_config([
			'dbServer' => $dbServer,
			'dbUsername' => $dbUsername,
			'dbPassword' => $dbPassword,
			'dbDatabase' => $dbDatabase,
			'appURI' => trim(dirname($_SERVER['SCRIPT_NAME']), '/'),
			'host' => (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443' ? '' : ":{$_SERVER['SERVER_PORT']}")),
			'adminConfig' => $adminConfig
		]);
	}

