<?php

	class Authentication {
		const DEFAULT_POST_LOGIN_URL = 'index.php',
		      FAILED_LOGIN_URL = 'index.php?loginFailed=1',

		      // TODO: implement this (during login, if url=... is provided, redirect to it on successful login)
		      ACCEPT_REDIR_URL = true;

		/**
		 * Retrieves currently logged user's info .
		 *
		 * @return     array  The user data, or false if no valid user session found.
		 */
		public static function getUser($forceReload = false) {
			static $memberInfo = [];
			if(!empty($memberInfo) && !$forceReload) return $memberInfo;

			self::initSession();
			if(empty($_SESSION['memberID'])) {
				if(!self::setAnonymousAccess()) return false;
				return self::getUser();
			}

			$memberInfo = self::getMemberInfo($_SESSION['memberID'], $forceReload);

			if(MULTI_TENANTS && !SaaS::userBelongsToTenant($memberInfo['username'], $memberInfo['tenantId']))
				return false;

			return $memberInfo;
		}

		public static function tenantId() {
			if(!MULTI_TENANTS) return '';

			return SaaS::tenantId();
		}

		public static function padTenantId($tenantId, $n = 6) {
			if(!MULTI_TENANTS) return '';

			return sprintf(".tnnt.%0{$n}d", $tenantId);
		}

		public static function tenantIdPadded() {
			return self::padTenantId(self::tenantId());
		}

		public static function setTenant($tenantId) {
			if(!MULTI_TENANTS) return false;

			return SaaS::setTenant($tenantId);
		}

		private static function setupAdminInfo($memberID) {
			// in setup mode, avoid making a db query as tables are not ready
			// just make sure requested member is same as that in session and
			// return a simplified version of member info

			$memberID = self::safeMemberID($memberID);

			if(
				defined('APPGINI_SETUP')
				&& !empty($_SESSION['memberID'])
				&& !empty($_SESSION['adminUsername'])
				&& $_SESSION['memberID'] == $memberID
				&& $_SESSION['adminUsername'] == $memberID
			) return [
				'username' => $memberID,
				'memberID' => $memberID,
				'memberId' => $memberID,
				'groupID' => 2, // just a very bold assumption!
				'groupId' => 2,
				'group' => 'Admins', // also an assumption, though less bold :)
				'admin' => true,
			];

			return false; //otherwise
		}

		public static function getMemberInfo($memberID, $forceReload = false) {
			if(defined('APPGINI_SETUP')) return self::setupAdminInfo($memberID);

			$memberID = self::safeMemberID($memberID);
			static $mi = [];

			if(isset($mi[$memberID]) && !$forceReload) return $mi[$memberID];

			$mi[$memberID] = false;

			// we're querying the local rather than global members table here
			// in order to retrieve local member info and local group
			$eo = ['silentErrors' => true, 'noErrorQueryLog' => true];
			$res = sql("
				SELECT u.*, g.`groupID`, g.`name`
				FROM 
					`membership_users` u
					LEFT JOIN `membership_groups` g ON g.`groupID` = u.`groupID`
				WHERE u.`memberID` = '$memberID'
			", $eo);

			if(!$res) return false;

			$row = db_fetch_assoc($res);
			if(!$row) return false;

			$mi[$memberID] = [
				'username' => $memberID,
				'memberID' => $memberID,
				'memberId' => $memberID,
				'groupID' => $row['groupID'],
				'groupId' => $row['groupID'],
				'group' => $row['name'],
				'admin' => (config('adminConfig')['adminUsername'] == $memberID),
				'email' => $row['email'],
				'custom' => [
					$row['custom1'], 
					$row['custom2'], 
					$row['custom3'], 
					$row['custom4']
				],
				'banned' => ($row['isBanned'] ? true : false),
				'approved' => ($row['isApproved'] ? true : false),
				'signupDate' => app_datetime($row['signupDate']),
				'comments' => $row['comments'],
				'IP' => $_SERVER['REMOTE_ADDR'],
			];

			if(MULTI_TENANTS) $mi[$memberID]['tenantId'] = self::tenantId();

			return $mi[$memberID];
		}

		/**
		 * Checks session variables to see whether the admin is logged or not
		 *
		 * @return     string  The admin username if she's current user, or false otherwise.
		 */
		public static function getAdmin() {
			$mi = self::getUser();
			if(!$mi) return false;
			if($mi['group'] != 'Admins') return false;
			if(!$mi['admin'] && !MULTIPLE_SUPER_ADMINS) return false;

			return $mi['username'];
		}

		public static function getSuperAdmin() {
			$mi = self::getUser();
			if(!$mi) return false;
			if(!$mi['admin']) return false;

			return $mi['username'];
		}

		public static function signIn() {
			// TODO: handle signin in MULTI_TENANTS env
			// post, remember-me should be handled by tenant login page 
			// in saas app, jwt by ?
			if(self::processPostSignIn()) return true;
			if(self::processJWTSignIn()) return true;
			if(self::processRememberMeSignIn()) return true;

			return false;
		}

		private static function passwordHash($username) {
			$username = self::safeMemberID($username);

			if(MULTI_TENANTS) return SaaS::passwordHash($username);

			return self::sqlValue("
				SELECT `passMD5`
				FROM `membership_users`
				WHERE
					`memberID` = '{$username}'
					AND isApproved = 1
					AND isBanned = 0
			");
		}

		private static function processPostSignIn() {
			// TODO: 
			// This should use the shared users DB in case of MULTI_TENANTS
			// and retrieve tenantIDs
			// if there are multiple tenantIDs and no tenantID is provided
			// in sign-in request, assume last tenantID of current user 
			// --- requires storing this somewhere with a fallback to 1st tenantID if no last tenantID found
			//
			// this requires a UI method for user to switch tenants
			// and a method here to switch tenant

			if(MULTI_TENANTS || !Request::val('signIn')) return false;

			// TODO: handle ACCEPT_REDIR_URL
			$redir = self::DEFAULT_POST_LOGIN_URL;

			$username = self::safeMemberID(Request::val('username'));
			$password = Request::val('password');

			// abort check if no login request submitted yet
			if(!$username && !$password) return false;

			// fail if no user/pass provided
			if(!$username || !$password) self::exitFailedLogin();

			// fail if password incorrect/user not found
			$hash = self::passwordHash($username);
			if(!password_match($password, $hash)) self::exitFailedLogin();

			// prepare session
			self::signInAs($username);

			Request::val('rememberMe') == 1 ? RememberMe::login($username) : RememberMe::delete();

			// harden user's password hash
			password_harden($username, $password, $hash);

			// hook: login_ok
			if(function_exists('login_ok')) {
				$args = [];
				$hookRedir = login_ok(self::getMemberInfo($username), $args);
				if($hookRedir) $redir = $hookRedir;
			}

			// TODO ... handle ACCEPT_REDIR_URL
			redirect($redir);
			exit;
		}

		private static function exitFailedLogin() {
			if(function_exists('login_failed')) {
				$args = [];
				login_failed([
					'username' => Request::val('username'),
					'password' => Request::val('password'),
					'IP' => $_SERVER['REMOTE_ADDR']
				], $args);
			}

			if(!headers_sent()) @header('HTTP/1.0 403 Forbidden');
			redirect(self::FAILED_LOGIN_URL);
			exit;
		}

		public static function isGuest() {
			return !self::loggedSessionExists();
		}

		private static function loggedSessionExists() {
			self::initSession();

			return (
				!empty($_SESSION)
				&& !empty($_SESSION['memberID'])
				&& !empty($_SESSION['memberGroupID'])
				&& $_SESSION['memberID'] != config('adminConfig')['anonymousMember']
				&& (!MULTI_TENANTS || self::tenantId())
			);
		}

		private static function processJWTSignIn() {
			// TODO
			// in case of MULTI_TENANTS, this must include a tenantID
			jwt_check_login();

			$jwtSuccess = self::loggedSessionExists();
			if($jwtSuccess) self::getUser(true); // reload user info

			return $jwtSuccess;
		}

		private static function processRememberMeSignIn() {
			if(MULTI_TENANTS) return false;

			/* check if a rememberMe cookie exists and sign in user if so */
			$rememberMeExists = RememberMe::check();
			if(!$rememberMeExists) return false;

			$user = RememberMe::user();
			self::signInAs($user);
			return true;
		}

		private static function sqlValue($query) {
			if(!$res = db_query($query)) return null;
			if(!$row = db_fetch_array($res)) return null;

			return $row[0];
		}

		public static function adminGroupId() {
			static $groupId;
			if($groupId) return $groupId;

			return $groupId = self::sqlValue(
				"SELECT `groupID` FROM `membership_groups` WHERE `name` = 'Admins'"
			);
		}

		public static function getLoggedGroupId() {
			$u = self::getUser();
			if(!$u) return false;

			return $u['groupID'];
		}

		public static function groupId($username) {
			$mi = self::getMemberInfo($username);
			return empty($mi) ? false : $mi['groupID'];
		}

		public static function signInAs($username, $tenantId = false) {
			// TODO: should check if username is a valid unbanned user

			$username = self::safeMemberID($username);

			self::initSession();
			$adminUsername = self::safeMemberID(config('adminConfig')['adminUsername']);
			$_SESSION['adminUsername'] = ($adminUsername == $username ? $adminUsername : null);
			$_SESSION['memberID'] = $username;
			$_SESSION['memberGroupID'] = self::groupId($username);

			if(MULTI_TENANTS)
				if($tenantId && SaaS::userBelongsToTenant($username, $tenantId))
					self::setTenant($tenantId);
				else
					self::setTenant(SaaS::defaultTenantId($username));

			self::getUser(true); // reload user info

			self::storeSession();
		}

		public static function signInAsAdmin() {

			self::signInAs(config('adminConfig')['adminUsername']);
		}

		public static function signOut() {

			RememberMe::logout();

			// reload user info
			self::setAnonymousAccess();
			self::getUser(true);
		}

		public static function logOut() {
			// just an alias
			self::signOut();
		}

		public static function setAnonymousAccess() {
			// no anon access for multi tenants
			if(MULTI_TENANTS) return false;

			$adminConfig = config('adminConfig');
			$anon_group_safe = makeSafe($adminConfig['anonymousGroup']);
			$anon_user_safe = strtolower(makeSafe($adminConfig['anonymousMember']));

			$anonGroupID = sqlValue("SELECT `groupID` FROM `membership_groups` WHERE `name`='{$anon_group_safe}'");
			if(!$anonGroupID) return false;

			$_SESSION['memberGroupID'] = $anonGroupID;

			$anonMemberID = sqlValue("SELECT LCASE(`memberID`) FROM `membership_users` WHERE LCASE(`memberID`)='{$anon_user_safe}' AND `groupID`='{$anonGroupID}'");
			if(!$anonMemberID) return false;

			$_SESSION['memberID'] = $anonMemberID;

			$_SESSION['adminUsername'] = null;

			return true;
		}

		// allow only lower case alphanumerics
		// TODO: use this whenever a db search for memberID occurs
		public static function safeMemberID($memberID) {
			static $cache = [];
			if(!empty($cache[$memberID])) return $cache[$memberID];

			$cache[$memberID] = strtolower(preg_replace('/[^\w\. @]/', '', trim($memberID)));
			return $cache[$memberID];
		}

		// TODO: use this when validating username during:
		// user sign up
		// admin > add new user
		// admin > edit user
		// admin > update settings (when updating admin/guest username)
		public static function validUsername($username) {
			$username = trim(strtolower($username));

			if(
				!preg_match('/^\w[\w\. @]{3,100}$/', $username)
				|| preg_match('/(@@|  |\.\.|___)/', $username)
				|| (!defined('APPGINI_SETUP') && $username == config('adminConfig')['anonymousMember'])
				|| $username == 'guest' // even if anonymous user is not 'guest', this should't be allowed still
			) return false;

			return true;
		}

		private static function storeSession() {
			// TODO: stores session into DB if not already there
			// >>> see occurances of membership_usersessions table
			// the purpose of this is to keep track of all users' sessions
			// since PHP doesn't provide this functionality
			// 
			// this is very useful when we need to sign out all sessions of a user
			// and possibly other security-related tasks and checks
		}

		/**
		 * Initializes the session if not already.
		 * What this actually does is look for a session ID cookie from request.
		 * If one is provided, it's used to initiate an existing session if valid.
		 * Otherwise, a new empty session is initiated.
		 */
		public static function initSession() {
			// if our app session already initialized, just return
			if(session_id() && session_name() == SESSION_NAME) return;

			$sh = @ini_get('session.save_handler');

			$cookie_path = '/' . (MULTI_TENANTS ? '' : trim(config('appURI'), '/'));

			$options = [
				'name' => SESSION_NAME,
				'cookie_path' => $cookie_path,
				'save_handler' => stripos($sh, 'memcache') === false ? 'files' : $sh,
				'serialize_handler' => 'php',
				'cookie_lifetime' => '0',
				'cookie_httponly' => '1',
				'use_strict_mode' => '1',
				'use_cookies' => '1',
				'use_only_cookies' => '1',
				'cache_limiter' => $_SERVER['REQUEST_METHOD'] == 'POST' ? 'private' : 'nocache',
				'cache_expire' => '2',
			];

			// hook: session_options(), if defined, $options is passed to it by reference
			// to override default session behavior.
			// should be defined in hooks/__bootstrap.php
			if(function_exists('session_options')) session_options($options);

			if(session_id()) { session_write_close(); }

			session_start($options);
		}
	}