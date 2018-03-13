<?php

	/*
		You can implement a new db driver in this file by uncommenting the line below
		and replacing 'mssql' with the desired driver name, then adding cases for the
		that driver in each function below ...
	*/
	// define('DATABASE', 'mssql'); 




	if(!defined('DATABASE')){
		if(function_exists('mysqli_connect')){
			define('DATABASE', 'mysqli');
		}else{
			define('DATABASE', 'mysql');
		}
	}



	define('mysql_charset', 'utf8');

	function db_link($link = NULL){
		static $db_link;
		if($link) $db_link = $link;
		return $db_link;
	}

	function db_close($link = NULL){
		db_link(false); /* close db link */
		switch(DATABASE){
			case 'mysql':
				return mysql_close($link);
			case 'mysqli':
				return mysqli_close($link);
		}
	}

	function db_select_db($dbname, $link = NULL){
		if(!$link) $link = db_link();
		switch(DATABASE){
			case 'mysql':
				return mysql_select_db($dbname, $link);
			case 'mysqli':
				return mysqli_select_db($link, $dbname);
		}
	}

	function db_fetch_array($res){
		switch(DATABASE){
			case 'mysql':
				return @mysql_fetch_array($res);
			case 'mysqli':
				return @mysqli_fetch_array($res, MYSQLI_BOTH);
		}
	}

	function db_fetch_assoc($res){
		switch(DATABASE){
			case 'mysql':
				return @mysql_fetch_assoc($res);
			case 'mysqli':
				return @mysqli_fetch_assoc($res);
		}
	}

	function db_fetch_row($res){
		switch(DATABASE){
			case 'mysql':
				return @mysql_fetch_row($res);
			case 'mysqli':
				return @mysqli_fetch_row($res);
		}
	}

	function db_num_fields($res){
		switch(DATABASE){
			case 'mysql':
				return @mysql_num_fields($res);
			case 'mysqli':
				return @mysqli_num_fields($res);
		}
	}

	function db_num_rows($res){
		switch(DATABASE){
			case 'mysql':
				return @mysql_num_rows($res);
			case 'mysqli':
				return @mysqli_num_rows($res);
		}
	}

	function db_affected_rows($link = NULL){
		if(!$link) $link = db_link();
		switch(DATABASE){
			case 'mysql':
				return mysql_affected_rows($link);
			case 'mysqli':
				return mysqli_affected_rows($link);
		}
	}

	function db_query($query, $link = NULL){
		if(!$link) $link = db_link();
		switch(DATABASE){
			case 'mysql':
				return @mysql_query($query, $link);
			case 'mysqli':
				return @mysqli_query($link, $query);
		}
	}

	function db_insert_id($link = NULL){
		if(!$link) $link = db_link();
		switch(DATABASE){
			case 'mysql':
				return mysql_insert_id($link);
			case 'mysqli':
				return mysqli_insert_id($link);
		}
	}

	function db_field_name($res, $field_offset){
		switch(DATABASE){
			case 'mysql':
				return @mysql_field_name($res, $field_offset);
			case 'mysqli':
				$fo = @mysqli_fetch_field_direct($res, $field_offset);
				if($fo === false) return false;
				return $fo->name;
		}
	}

	function db_field_type($res, $field_offset){
		switch(DATABASE){
			case 'mysql':
				return @mysql_field_type($res, $field_offset);
			case 'mysqli':
				$fo = @mysqli_fetch_field_direct($res, $field_offset);
				if($fo === false) return false;
				return $fo->type;
		}
	}

	function db_escape($str = NULL, $link = NULL){
		if(!$link) $link = db_link();
		switch(DATABASE){
			case 'mysql':
				if(function_exists('mysql_real_escape_string'))    return mysql_real_escape_string($str, $link);
				return mysql_escape_string($str);
			case 'mysqli':
				return mysqli_real_escape_string($link, $str);
		}
	}

	function db_connect($host = NULL, $username = NULL, $passwd = NULL, $dbname = NULL, $port = NULL, $socket = NULL){
		switch(DATABASE){
			case 'mysql':
				if($host === NULL) $host = ini_get("mysql.default_host");
				if($username === NULL) $username = ini_get("mysql.default_user");
				if($passwd === NULL) $passwd = ini_get("mysql.default_password");
				$link = mysql_connect($host, $username, $passwd);
				if(!$link) return false;
				db_link($link); /* db_link() can now be used to retrieve the db link from anywhere */
				@mysql_query("SET NAMES '" . mysql_charset . "'");
				if($dbname){ if(!mysql_select_db($dbname, $link)) return false; }
				return $link;
			case 'mysqli':
				if($host === NULL) $host = ini_get("mysqli.default_host");
				if($username === NULL) $username = ini_get("mysqli.default_user");
				if($passwd === NULL) $passwd = ini_get("mysqli.default_pw");
				if($dbname === NULL) $dbname = "";
				if($port === NULL) $port = ini_get("mysqli.default_port");
				if($socket === NULL) $socket = ini_get("mysqli.default_socket");
				$link = mysqli_connect($host, $username, $passwd, $dbname, $port, $socket);
				if(!$link) return false;
				db_link($link); /* db_link() can now be used to retrieve the db link from anywhere */
				mysqli_set_charset($link, mysql_charset);
				return $link;
		}
	}

	function db_errno($link = NULL, $mysqli_connect = false){
		if(!$link) $link = db_link();
		switch(DATABASE){
			case 'mysql':
				return mysql_errno($link);
			case 'mysqli':
				if($mysqli_connect) return mysqli_connect_errno();
				return mysqli_errno($link);
		}
	}

	function db_error($link = NULL, $mysqli_connect = false){
		if(!$link) $link = db_link();
		switch(DATABASE){
			case 'mysql':
				return mysql_error($link);
			case 'mysqli':
				if($mysqli_connect) return mysqli_connect_error();
				return mysqli_error($link);
		}
	}
