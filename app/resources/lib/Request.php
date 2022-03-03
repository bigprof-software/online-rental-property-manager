<?php

/*
 Request($var) -- class for providing sanitized values of given request variable (->sql, ->attr, ->html, ->url, and ->raw)
 */
class Request {
	public $sql, $url, $attr, $html, $raw;

	public function __construct($var, $filter = false) {
		$unsafe = (isset($_REQUEST[$var]) ? $_REQUEST[$var] : '');

		if($filter) $unsafe = call_user_func_array($filter, [$unsafe]);

		$this->sql = makeSafe($unsafe, false);
		$this->url = urlencode($unsafe);
		$this->attr = html_attr($unsafe);
		$this->html = $this->attr;
		$this->raw = $unsafe;
	}

	/**
	 * Retrieve specified variable's value from HTTP REQUEST parameters
	 *
	 * @param      string  $var              The variable
	 * @param      scalar  $default          The default value to use if $var doesn't exist, is empty, or is one of $changeToDefault
	 * @param      array   $changeToDefault  If $var is one of these values, treat like it's empty
	 *
	 * @return     scalar  The value as retrieved from the Request, or $default
	 */
	public static function val($var, $default = '', $changeToDefault = []) {
		if(
			!isset($_REQUEST[$var]) || 
			$_REQUEST[$var] ===  '' ||
			in_array($_REQUEST[$var], $changeToDefault)
		) return $default; 

		return $_REQUEST[$var];
	}

	/**
	 * @return     bool    indicating if $var is part of request, regardless of its value
	 */
	public static function has($var) {
		return isset($_REQUEST[$var]);
	}

	public static function lookup($var, $default = '') {
		return self::val($var, $default, defined('empty_lookup_value') ? [empty_lookup_value] : []);
	}

	public static function dateComponents($var, $default = '') {
		return parseMySQLDate(
			intval(self::val($var .  'Year')) . '-' .
			intval(self::val($var . 'Month')) . '-' .
			intval(self::val($var .   'Day')),
			$default
		);
	}

	public static function multipleChoice($var, $default = '') {
		return is_array($_REQUEST[$var]) ? implode(', ', $_REQUEST[$var]) : $default;
	}

	public static function fileUpload($var, $options = []) {
		// set defaults for $options
		$options = array_merge([
			'maxSize' => 100, // KB
			'types' => 'jpg|jpeg|gif|png',
			'noRename' => false,
			'dir' => '',
			'id' => '',
			'removeOnSuccess' => false,
			'removeOnRequest' => false,
			// $options['failure'] => function(id) -- called if no file upload occured, return value is returned
			// $options['success'] => function(uploadedName, id) -- called if a file is uploaded successfully
			// $options['remove'] => function(id, fileRemoved) -- called when removing old file
		], $options);

		// handle remove request
		$fileRemoved = false;
		if(
			$options['removeOnRequest'] &&
			!empty($_REQUEST["{$var}_remove"]) && 
			!empty($options['remove']) && 
			is_callable($options['remove'])
		) {
			call_user_func_array($options['remove'], [$options['id']]);
			$fileRemoved = true;
		}

		$uploadedName = PrepareUploadedFile(
			$var,
			$options['maxSize'],
			$options['types'],
			$options['noRename'],
			$options['dir']
		);

		// if file upload failed, return $options['failure'] or empty if not defined
		if(!$uploadedName) {
			if(
				!empty($options['failure']) && 
				is_callable($options['failure'])
			) return call_user_func_array($options['failure'], [$options['id'], $fileRemoved]);

			return '';
		}

		// if upload is successful, call $options['success']
		if(
			!empty($options['success']) && 
			is_callable($options['success'])
		) call_user_func_array($options['success'], [$uploadedName, $options['id']]);

		// if removeOnSuccess
		if(
			$options['removeOnSuccess'] &&
			!empty($options['remove']) && 
			is_callable($options['remove']) &&
			!$fileRemoved
		) {
			call_user_func_array($options['remove'], [$options['id']]);
			$fileRemoved = true;
		}

		return $uploadedName;
	}

	public static function checkBox($var, $default = '0') {
		return self::val($var, $default) ? 1 : 0;
	}

	public static function oneOf($var, $arrVals, $default = '')	{
		$val = self::val($var, $default);
		if(!in_array($val, $arrVals)) return $default;
		return $val;
	}
}
