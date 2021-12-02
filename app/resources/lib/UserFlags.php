<?php
	/*
		UserFlags are messages to a specific user that should be displayed as alerts when the user signs in.
	*/
	class UserFlags {
		private $username, $safe_username;

		public function __construct($username = null) {
			/* if no username provided, set to currently logged user */
			if($username === null) {
				$mi = getMemberInfo();
				$username = makeSafe($mi['username']);
			}

			$this->username = $username;
			$this->safe_username = makeSafe($username);
		}

		public function mark($id, $read = true) {
			$flags = $this->get();
			if(!isset($flags[$id])) return false;

			$flags[$id]['read'] = (boolean) $read;

			return $this->save($flags);
		}
		########################################################################
		public function save($flags) {
			if(!is_array($flags)) return false;
			$flags_json_safe = makeSafe(json_encode($flags));

			$eo = ['silentErrors' => true];

			return sql("UPDATE `membership_users` SET `flags`='{$flags_json_safe}' WHERE `memberID`='{$this->safe_username}'", $eo);
		}
		########################################################################
		public function add($flag) {
			if(empty($flag['message'])) return false;
			if(empty($flag['severity'])) $flag['severity'] = 'info'; // bootstrap alert classes: info, warning, danger, success
			$flag['read'] = false; // unread by default

			$flags = $this->get();
			$flags[] = $flag;

			return $this->save($flags);
		}
		########################################################################
		public function get($read = null) {
			// $read: true or false, default is all
			$flags_json = sqlValue("SELECT `flags` FROM `membership_users` WHERE `memberID`='{$this->safe_username}'");
			if(!$flags_json) return [];

			$flags = json_decode($flags_json, true);
			if(!$flags) return [];

			if($read === null) return $flags;

			$ret_flags = []; // to be populated by flags to return
			foreach ($flags as $flag) {
				if($flag['read'] == $read) $ret_flags[] = $flag;
			}

			return $ret_flags;
		}
	}