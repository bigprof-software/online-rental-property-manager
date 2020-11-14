<?php
	class RememberMe {
		/**
		 *  Separator string used in 'rememberme' cookie
		 */
		private static $_separator = ';;';
		
		/**
		 *  Number of seconds for 'rememberme' session expiry
		 */
		private static $_expiry = 2592000; // 30 days * 86400 seconds per day
		
		/**
		 *  Number of seconds for token to be considered 'fresh' and needs no renewal
		 */
		private static $_renewal_window = 1200;
		
		/**
		 *  If logging is enabled, all method calls in this class will be logged to RememberMe.log
		 *  in the same folder as this class file.
		 */
		private static $_logging = false;
		
		/**
		 *  Token will not be updated in case the request script is one of these.
		 *  See the note in ::check()
		 */
		private static $_exception_requests = array(
			'link.php',
			'thumbnail.php',
			'pageUploadCSV.php',
			'pageRebuildFields.php'
		);
		
		/**
		 *  @brief Checks if current user has a valid 'rememberme' cookie. Should be called if no login session detected for current user.
		 *  @return boolean indicating whether user should be signed in or not
		 */
		public static function check() {
			// cache return value as cookie/session token should not be changed more than once per request.
			static $last_check_call = null;
			self::_log('RememberMe::check()');
			if($last_check_call !== null) return $last_check_call;

			$session = self::_from_string(self::_cookie());

			if(!self::_active_user($session['user'])) return false;

			switch(self::_find($session)) {
				case 1: // valid ... generate new token in db and update 'rememberme' cookie
					/* 
						Caveat: Rapid consecutive requests (for example, ajax and multiple images 
						in the same	page) would invalidate the token, forcing user log out and 
						false alarms. Thus, we won't renew the token for those requests which are
						determined by ::_exception_request().
					*/
					if(!self::_exception_request($session)) {
						$session['token'] = self::_random();
						self::_update($session);
						self::_cookie(self::_to_string($session));
					}
					$last_check_call = true;
					break;

				case 0: // not found
					$last_check_call = false;
					break;
				
				case -1: // invalid -- session theft?
					self::logout(true); // remove all remember-me sessions and all cookies
					self::_flag($session['user']);
					$last_check_call = false;
					break;
			}

			return $last_check_call;
		}

		/**
		 *  @brief creates a new 'rememberme' session for current user -- should be called after user signs in having 'Remember me' checkbox checked.
		 *  @param [in] $user username for current user
		 */
		public static function login($user) {
			self::_log('RememberMe::login("' . $user . '")');
			$session = array(
				'user' => $user,
				'token' => self::_random(),
				'agent' => self::_random()
			);
			
			self::_store($session);
			self::_cookie(self::_to_string($session));
			self::_cleanup();
		}
		
		/**
		 *  @brief clears current user's 'rememberme' session for current client or all clients.
		 *  @param [in] $all boolean. If set to true, clears all 'rememberme' sessions of current user
		 */
		public static function logout($all = false) {
			self::_log('RememberMe::logout(' . ($all ? 'true' : 'false') . ')');
			$session = self::_from_string(self::_cookie());
			if( $all) self::_remove_all($session);
			if(!$all) self::_remove($session);
			
			self::_cookie('');
			self::_clear_server_session();
		}

		/**
		 *  @brief returns username from remember-me cookie
		 *  @return string username -- this is not authentication!!! You MUST use RememberMe::check() to authenticate user
		 */
		public static function user() {
			self::_log('RememberMe::user()');
			$session = self::_from_string(self::_cookie());
			return $session['user'];
		}

		/**
		 * @brief deletes remember-me cookie
		 */
		public static function delete() {
			self::_log('RememberMe::delete()');
			$session = self::_from_string(self::_cookie());
			self::_remove($session);			
			self::_cookie('');
		}
		
		private static function _exception_request($session) {
			/* see the comment in ::check() */
			return 
				is_ajax() || 
				in_array(basename($_SERVER['PHP_SELF']), self::$_exception_requests) ||
				self::_fresh_token($session);
		}

		private static function _active_user($user) {
			self::_log('RememberMe::_active_user("' . $user . '")');
			$safe_user = makeSafe(strtolower($user));
			return sqlValue("SELECT COUNT(1) FROM `membership_users` WHERE LCASE(`memberID`)='{$safe_user}' AND isBanned=0 AND isApproved=1");
		}

		private static function _random($length = 30) {
			self::_log('RememberMe::_random(' . $length . ')');
			$letters = 'abcdefghijklmnopqrstuvwxyz';
			$nums = '0123456789';
			$pool = $letters . strtoupper($letters) . $nums;
			$string = '';
			$max = strlen($pool) - 1;
			for ($i = 0; $i < $length; $i++) {
				$string .= $pool[mt_rand(0, $max)];
			}
			return $string;
		}
		
		private static function _validate_session($session) {
			self::_log('RememberMe::_validate_session(' . json_encode($session) . ')');
			// $session: ['user', 'token', 'agent']
			if(
				empty($session['user']) || 
				empty($session['token']) || 
				empty($session['agent'])
			) return false;
			
			return true;
		}
		
		/**
		 *  @return boolean indicating if session token is fresh (generated less than _renewal_window seconds ago) or not.
		 */
		private static function _fresh_token($session) {
			self::_log('RememberMe::_fresh_token(' . json_encode($session) . ')');

			if(!self::_validate_session($session)) return false;

			$session = array_map('makeSafe', $session);
			$expiry_ts = sqlValue("
				SELECT `expiry_ts` FROM `membership_usersessions` WHERE
					`memberID`='{$session['user']}' AND
					`token`='{$session['token']}' AND
					`agent`='{$session['agent']}'
			");
			if(!$expiry_ts) return false;

			$ts = time();
			$creation_ts = $expiry_ts - self::$_expiry;
			$fresh_ts = $creation_ts + self::$_renewal_window;

			return ($ts <= $fresh_ts);
		}

		private static function _find($session) {
			self::_log('RememberMe::_find(' . json_encode($session) . ')');
			// returns: 1 if valid, 0 if not found, -1 if invalid token
			if(!self::_validate_session($session)) return 0;
			
			$session = array_map('makeSafe', $session);
			$ts = time();
			$expiry_ts = sqlValue("
				SELECT `expiry_ts` FROM `membership_usersessions` WHERE
					`memberID`='{$session['user']}' AND
					`token`='{$session['token']}' AND
					`agent`='{$session['agent']}'
			");
			if($expiry_ts >= $ts) return 1; // found non-expired session
			if($expiry_ts) return 0; // found expired session = not found
			
			$stolen = sqlValue("
				SELECT `expiry_ts` FROM `membership_usersessions` WHERE
					`memberID`='{$session['user']}' AND
					`agent`='{$session['agent']}'
			");
			if(!$stolen) return 0; // not found
			
			return -1; // user and agent ok, but token not found = stolen cookie!
		}
		
		private static function _store($session) {
			self::_log('RememberMe::_store(' . json_encode($session) . ')');
			if(!self::_validate_session($session)) return false;

			$session = array_map('makeSafe', $session);
			$expiry_ts = time() + self::$_expiry;
			
			$eo = array();
			return sql("
				INSERT INTO `membership_usersessions` SET
					`memberID`='{$session['user']}',
					`token`='{$session['token']}',
					`agent`='{$session['agent']}',
					`expiry_ts`='{$expiry_ts}'
			", $eo);
		}
		
		private static function _update($session) {
			self::_log('RememberMe::_update(' . json_encode($session) . ')');
			if(!self::_validate_session($session)) return false;

			$session = array_map('makeSafe', $session);
			$expiry_ts = time() + self::$_expiry;
			
			$eo = array();
			return sql("
				UPDATE `membership_usersessions` SET
					`token`='{$session['token']}',
					`expiry_ts`='{$expiry_ts}'
				WHERE
					`memberID`='{$session['user']}' AND
					`agent`='{$session['agent']}'
			", $eo);
		}
		
		private static function _remove($session) {
			self::_log('RememberMe::_remove(' . json_encode($session) . ')');
			// only user and agent are used .. token ignored
			if(!self::_validate_session($session)) return false;

			$session = array_map('makeSafe', $session);
			$eo = array();
			return sql("
				DELETE FROM `membership_usersessions` WHERE
					`memberID`='{$session['user']}' AND
					`agent`='{$session['agent']}'
			", $eo);
		}
		
		private static function _remove_all($session) {
			self::_log('RememberMe::_remove_all(' . json_encode($session) . ')');
			// only user is used from session .. others ignored
			if(!self::_validate_session($session)) return false;

			$session = array_map('makeSafe', $session);
			$eo = array();
			return sql("DELETE FROM `membership_usersessions` WHERE `memberID`='{$session['user']}'", $eo);
		}
		
		private static function _cleanup() {
			self::_log('RememberMe::_cleanup()');
			// remove all expired entries
			$eo = array();
			$ts = time();
			return sql("DELETE FROM `membership_usersessions` WHERE `expiry_ts`<{$ts}", $eo);
		}
		
		private static function _from_string($str) {
			self::_log('RememberMe::_from_string("' . $str . '")');
			// returns ['user', 'token', 'agent'] or false if invalid
			// cookie is assumed to be formatted as: user;;token;;agent
			$parts = explode(self::$_separator, $str);
			if(count($parts) < 3) return false;
			return array(
				'user' => $parts[0],
				'token' => $parts[1],
				'agent' => $parts[2]
			);
		}
		
		private static function _to_string($session) {
			self::_log('RememberMe::_to_string(' . json_encode($session) . ')');
			// returns string suitable for sending as cookie or false if invalid session
			if(!self::_validate_session($session)) return false;

			// cookie is assumed to be formatted as: user;;token;;agent
			return '' .
				$session['user'] . self::$_separator . 
				$session['token'] . self::$_separator .
				$session['agent'];
		}
		
		private static function _cookie($val = null) {
			self::_log('RememberMe::_cookie(' . ($val === null ? '' : "'{$val}'") . ')');
			$cookie_name = session_name() . '_remember_me';
			
			// returns 'rememberme' cookie value if no value provided or sets cookie value if provided
			if($val === null) {
				if(empty($_COOKIE[$cookie_name])) return '';
				
				return $_COOKIE[$cookie_name];
			}
			
			/* set expiry ahead, or in the past if removing the cookie */
			$expiry_ts = time() + self::$_expiry;
			if($val == '') $expiry_ts -= 2 * self::$_expiry; // expiry in the past
			
			/* set the 'rememberme' cookie */
			@setcookie(
				$cookie_name,                   /* name */
				$val,                           /* value */
				$expiry_ts,                     /* expiry timestamp */
				'/' . application_uri() . '/',  /* path */
				'',                             /* domain */
				false,                          /* secure-only */
				true                            /* http-only, no js access */
			);
			
			// also update $_COOKIE for any upcoming queries in the same request
			$_COOKIE[$cookie_name] = $val;

			return $val;
		}

		private static function _clear_server_session() {
			self::_log('RememberMe::_clear_server_session()');
			$_SESSION = array();

			$params = session_get_cookie_params();
			@setcookie(
				session_name(), 
				'', 
				time() - 42000,
				$params['path'],
				$params['domain'],
				$params['secure'],
				$params['httponly']
			);
		}
		
		private static function _flag($user) {
			self::_log('RememberMe::_flag("' . $user . '")');
			global $Translation;

			$uf = new UserFlags($user);
			return $uf->add(array(
				'message' => $Translation['account token theft warning'],
				'severity' => 'danger'
			));
		}
		
		private static function _log($msg) {
			if(!self::$_logging) return;
			
			$dt = date('Y-m-d H:i:s');
			$log_file = dirname(__FILE__) . '/RememberMe.log';
			$script = $_SERVER['PHP_SELF'];
			@file_put_contents($log_file, "{$dt} | {$script} | {$msg}\n", FILE_APPEND);
		}
	}
	