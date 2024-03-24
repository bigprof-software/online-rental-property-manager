<?php

// To allow LDAP login, the following keys and their values are expected in config.php under [adminConfig]:
// 'loginMethod' => 'ldap',
// 'ldapServer' => 'ldap.example.com',   // or 'ldaps://ldap.example.com' for SSL
											// to include port: 'ldap.example.com:389' or 'ldaps://ldap.example.com:636'
// 'ldapVersion' => 3,   // optional, default is 3
// 'ldapUsernamePrefix' => 'FDC\\',   // or whatever prefix is needed to login to LDAP, if any
// 'ldapUsernameSuffix' => ',ou=people,dc=ldapmock,dc=local',   // or whatever suffix is needed to login to LDAP, if any
// 'ldapDefaultUserGroup' => false   // if ldap user not found in db, add to this group ID. false to reject login

class LDAP {
	private static $error = '';

	private static function setError($msg) {
		self::$error = $msg;
		return false;
	}

	public static function getError() {
		return self::$error;
	}

	public static function authenticate($username, $password) {
		if (!self::enabled()) {
			return self::setError('LDAP login not enabled');
		}

		$ldap = self::connect();
		if (!$ldap) {
			return self::setError('Invalid LDAP server address');
		}

		if (!self::bind($ldap, $username, $password)) {
			return self::setError(ldap_error($ldap));
		}

		$memberInfo = Authentication::getMemberInfo($username);
		if (!$memberInfo) {
			if(!self::handleUserNotFound($username)) {
				return false; // Error message already set by handleUserNotFound
			}

			$memberInfo = Authentication::getMemberInfo($username, true); // prevent caching
		}

		return self::finalizeAuthentication($memberInfo, $username);
	}

	public static function enabled() {
		$conf = config('adminConfig');
		return ($conf['loginMethod'] ?? '') == 'ldap' && self::supported();
	}

	public static function supported() {
		return function_exists('ldap_connect');
	}

	private static function connect() {
		$conf = config('adminConfig');
		$ldap = @ldap_connect($conf['ldapServer'] ?? '');
		if ($ldap) {
			@ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $conf['ldapVersion'] ?? 3);
		}
		return $ldap;
	}

	private static function bind($ldap, $username, $password) {
		$conf = config('adminConfig');
		$ldapUser = ($conf['ldapUsernamePrefix'] ?? '') . $username . ($conf['ldapUsernameSuffix'] ?? '');
		return @ldap_bind($ldap, $ldapUser, $password);
	}

	private static function handleUserNotFound($username) {
		$conf = config('adminConfig');
		if (!($conf['ldapDefaultUserGroup'] ?? false)) {
			return self::setError('User not found in database');
		}

		return self::createNewUser($username);
	}

	private static function createNewUser($username) {
		// Add user to default group
		$conf = config('adminConfig');
		$groupID = intval($conf['ldapDefaultUserGroup']);
		if (!$groupID) {
			return self::setError('Trying to add new user to non-existent group');
		}

		self::insertNewUser($username, $groupID);

		// Check if user was successfully added
		$memberInfo = Authentication::getMemberInfo($username, true); // prevent caching
		if (!$memberInfo) {
			return self::setError('Failed to add new user to database');
		}

		return true;
	}

	private static function insertNewUser($username, $groupID) {
		insert('membership_users', [
			'groupID' => $groupID,
			'memberID' => strtolower($username),
			'passMD5' => password_hash(random_bytes(20), PASSWORD_DEFAULT),
			'email' => '',
			'signupDate' => @date('Y-m-d'),
			'comments' => 'LDAP user added automatically when logging in for the first time',
			'isBanned' => 0,
			'isApproved' => 1,
		]);
	}

	private static function finalizeAuthentication($memberInfo, $username) {
		if ($memberInfo['banned']) {
			return self::setError('User is banned');
		}

		if (!$memberInfo['approved']) {
			return self::setError('User is not approved');
		}

		// Prepare session
		Authentication::signInAs($username);

		return true;
	}

	private static function groups() {
		global $Translation;
		$adminConfig = config('adminConfig');

		$groups = ['0' => $Translation['ldap disable user signup']];
		$eo = ['silentErrors' => true];
		$res = sql("SELECT `groupID`, `name` FROM `membership_groups` ORDER BY `name`", $eo);
		while ($row = db_fetch_row($res)) {
			if($row[1] == $adminConfig['anonymousGroup']) continue; // skip anonymous group
			if($row[1] == 'Admins') continue; // skip admins group

			$groups[$row[0]] = $row[1];
		}

		return $groups;
	}

	public static function settingsTab() {
		if(!self::supported()) return ''; // LDAP not supported

		global $Translation;
		ob_start(); ?>
		<li><a href="#ldap-settings" data-toggle="tab"><i class="glyphicon glyphicon-folder-open"></i> <?php echo $Translation['ldap settings']; ?></a></li>
		<?php return ob_get_clean();
	}

	public static function settingsTabContent() {
		if(!self::supported()) return ''; // LDAP not supported

		global $Translation;
		$adminConfig = config('adminConfig');

		ob_start(); ?>
		<div class="tab-pane" id="ldap-settings">
			<div style="height: 3em;"></div>

			<div class="alert alert-danger text-center">
				<?php printf($Translation['ldap admin user warning'], "<code><b>{$adminConfig['adminUsername']}</b></code>"); ?>
			</div>

			<?php echo settings_radiogroup('loginMethod', $Translation['login method'], $adminConfig['loginMethod'] ?? 'default', [
				'default' => $Translation['default'],
				'ldap' => $Translation['ldap'],
			]); ?>
			<?php echo settings_textbox('ldapServer', $Translation['ldap server'], $adminConfig['ldapServer'] ?? '', $Translation['Examples: '] . '<code>ldap.example.com</code>, <code>ldaps://ldap.example.com</code>, <code>ldaps://ldap.example.com:636</code>, ...'); ?>
			<?php echo settings_textbox('ldapVersion', $Translation['ldap version'], $adminConfig['ldapVersion'] ?? '', $Translation['Examples: '] . '<code>2</code>, <code>3</code>', 'number'); ?>
			<?php echo settings_textbox('ldapUsernamePrefix', $Translation['ldap username prefix'], $adminConfig['ldapUsernamePrefix'] ?? '', $Translation['Examples: '] . '<code>FDC\\\\</code>, <code>cn=</code>, <code>uid=</code>, ...'); ?>
			<?php echo settings_textbox('ldapUsernameSuffix', $Translation['ldap username suffix'], $adminConfig['ldapUsernameSuffix'] ?? '', $Translation['Example: '] . '<code>,ou=people,dc=ldap,dc=example,dc=com</code>'); ?>
			<div id="ldapDefaultUserGroup-container">
				<?php echo settings_radiogroup('ldapDefaultUserGroup', $Translation['ldap default user group'], $adminConfig['ldapDefaultUserGroup'] ?? 0, self::groups()); ?>
			</div>
		</div>
		<style>
			#ldapDefaultUserGroup-container .radio-inline {
				display: block;
				margin: 0;
			}
		</style>
		<?php return ob_get_clean();
	}

	public static function postToSettings($post) {
		return [
			'loginMethod' => $post['loginMethod'] ?? '',
			'ldapServer' => $post['ldapServer'] ?? '',
			'ldapVersion' => $post['ldapVersion'] ?? '',
			'ldapUsernamePrefix' => $post['ldapUsernamePrefix'] ?? '',
			'ldapUsernameSuffix' => $post['ldapUsernameSuffix'] ?? '',
			'ldapDefaultUserGroup' => $post['ldapDefaultUserGroup'] ?? '',
		];
	}
}