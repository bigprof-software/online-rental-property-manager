<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 4.3.2 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2009, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Input Class
 *
 * Pre-processes global input data for security
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Input
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/libraries/input.html
 */
class CI_Input {
	var $use_xss_clean		= false;
	var $xss_hash			= '';
	var $ip_address			= false;
	var $user_agent			= false;
	var $allow_get_array	= false;
	var $charset			= 'iso-8859-1';

	/**
	 * List of never allowed strings
	 *
	 * @var	array
	 */
	protected $_never_allowed_str =	array(
		'document.cookie' => '[removed]',
		'(document).cookie' => '[removed]',
		'document.write'  => '[removed]',
		'(document).write'  => '[removed]',
		'.parentNode'     => '[removed]',
		'.innerHTML'      => '[removed]',
		'-moz-binding'    => '[removed]',
		'<!--'            => '&lt;!--',
		'-->'             => '--&gt;',
		'<![CDATA['       => '&lt;![CDATA[',
		'<comment>'	  => '&lt;comment&gt;',
		'<%'              => '&lt;&#37;'
	);

	/**
	 * List of never allowed regex replacements
	 *
	 * @var	array
	 */
	protected $_never_allowed_regex = array(
		'javascript\s*:',
		'(\(?document\)?|\(?window\)?(\.document)?)\.(location|on\w*)',
		'expression\s*(\(|&\#40;)', // CSS and IE
		'vbscript\s*:', // IE, surprise!
		'wscript\s*:', // IE
		'jscript\s*:', // IE
		'vbs\s*:', // IE
		'Redirect\s+30\d',
		"([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
	);

	/**
	* Constructor
	*
	* Sets whether to globally enable the XSS processing
	* and whether to allow the $_GET array
	*/

	public function __construct($charset = 'iso-8859-1') {
		$this->charset = $charset;
		$this->use_xss_clean	= false;
		$this->allow_get_array	= true;
	}

	// --------------------------------------------------------------------

	/**
	* Fetch from array
	*
	* This is a helper function to retrieve values from global arrays
	*
	* @param	array
	* @param	string
	* @param	bool
	* @return	string
	*/
	protected function _fetch_from_array(&$array, $index = '', $xss_clean = false) {
		if(!isset($array[$index])) return false;

		if($xss_clean === true) return $this->xss_clean($array[$index]);

		return $array[$index];
	}

	// --------------------------------------------------------------------

	/**
	* Fetch an item from the GET array
	*
	* @param	string
	* @param	bool
	* @return	string
	*/
	public function get($index = '', $xss_clean = false) {
		return $this->_fetch_from_array($_GET, $index, $xss_clean);
	}

	// --------------------------------------------------------------------

	/**
	* Fetch an item from the POST array
	*
	* @param	string
	* @param	bool
	* @return	string
	*/
	public function post($index = '', $xss_clean = false) {
		return $this->_fetch_from_array($_POST, $index, $xss_clean);
	}

	// --------------------------------------------------------------------

	/**
	* Fetch an item from either the GET array or the POST
	*
	* @param	string	The index key
	* @param	bool	XSS cleaning
	* @return	string
	*/
	public function get_post($index = '', $xss_clean = false) {
		if(!isset($_POST[$index])) 
			return $this->get($index, $xss_clean);
		else
			return $this->post($index, $xss_clean);
	}

	// --------------------------------------------------------------------

	/**
	* Fetch an item from the COOKIE array
	*
	* @param	string
	* @param	bool
	* @return	string
	*/
	public function cookie($index = '', $xss_clean = false) {
		return $this->_fetch_from_array($_COOKIE, $index, $xss_clean);
	}

	// --------------------------------------------------------------------

	/**
	* Fetch an item from the SERVER array
	*
	* @param	string
	* @param	bool
	* @return	string
	*/
	public function server($index = '', $xss_clean = false) {
		return $this->_fetch_from_array($_SERVER, $index, $xss_clean);
	}

	// --------------------------------------------------------------------

	/**
	* Fetch the IP Address
	*
	* @return	string
	*/
	public function ip_address() {
		if($this->ip_address !== false) return $this->ip_address;
		
		if(config_item('proxy_ips') != '' && $this->server('HTTP_X_FORWARDED_FOR') && $this->server('REMOTE_ADDR')) {
			$proxies = preg_split('/[\s,]/', config_item('proxy_ips'), -1, PREG_SPLIT_NO_EMPTY);
			$proxies = is_array($proxies) ? $proxies : array($proxies);

			$this->ip_address = in_array($_SERVER['REMOTE_ADDR'], $proxies) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		} elseif($this->server('REMOTE_ADDR') && $this->server('HTTP_CLIENT_IP')) {
			$this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ($this->server('REMOTE_ADDR')) {
			$this->ip_address = $_SERVER['REMOTE_ADDR'];
		} elseif ($this->server('HTTP_CLIENT_IP')) {
			$this->ip_address = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ($this->server('HTTP_X_FORWARDED_FOR')) {
			$this->ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		if($this->ip_address === false) {
			$this->ip_address = '0.0.0.0';
			return $this->ip_address;
		}

		if(strstr($this->ip_address, ',')) {
			$x = explode(',', $this->ip_address);
			$this->ip_address = trim(end($x));
		}

		if(!$this->valid_ip($this->ip_address)) {
			$this->ip_address = '0.0.0.0';
		}

		return $this->ip_address;
	}

	// --------------------------------------------------------------------

	/**
	* Validate IP Address
	*
	* Updated version suggested by Geert De Deckere
	* 
	* @param	string
	* @return	string
	*/
	public function valid_ip($ip) {
		$ip_segments = explode('.', $ip);

		// Always 4 segments needed
		if (count($ip_segments) != 4)
			return false;

		// IP can not start with 0
		if ($ip_segments[0][0] == '0')
			return false;

		// Check each segment
		foreach ($ip_segments as $segment) {
			// IP segments must be digits and can not be 
			// longer than 3 digits or greater then 255
			if($segment == '' || preg_match("/[^0-9]/", $segment) || $segment > 255 || strlen($segment) > 3) {
				return false;
			}
		}

		return true;
	}

	// --------------------------------------------------------------------

	/**
	* User Agent
	*
	* @return	string
	*/
	public function user_agent() {
		if($this->user_agent !== false)
			return $this->user_agent;

		$this->user_agent = (!isset($_SERVER['HTTP_USER_AGENT'])) ? false : $_SERVER['HTTP_USER_AGENT'];

		return $this->user_agent;
	}

	// --------------------------------------------------------------------

	/**
	* Filename Security
	*
	* @param	string
	* @return	string
	*/
	public function filename_security($str) {
		$bad = array(
			"../",
			"./",
			"<!--",
			"-->",
			"<",
			">",
			"'",
			'"',
			'&',
			'$',
			'#',
			'{',
			'}',
			'[',
			']',
			'=',
			';',
			'?',
			"%20",
			"%22",
			"%3c",		// <
			"%253c", 	// <
			"%3e", 		// >
			"%0e", 		// >
			"%28", 		// (  
			"%29", 		// ) 
			"%2528", 	// (
			"%26", 		// &
			"%24", 		// $
			"%3f", 		// ?
			"%3b", 		// ;
			"%3d"		// =
		);

		return stripslashes(str_replace($bad, '', $str));
	}

	// --------------------------------------------------------------------

	/**
	* XSS Clean
	*
	* Sanitizes data so that Cross Site Scripting Hacks can be
	* prevented.  This function does a fair amount of work but
	* it is extremely thorough, designed to prevent even the
	* most obscure XSS attempts.  Nothing is ever 100% foolproof,
	* of course, but I haven't been able to get anything passed
	* the filter.
	*
	* Note: This function should only be used to deal with data
	* upon submission.  It's not something that should
	* be used for general runtime processing.
	*
	* This function was based in part on some code and ideas I
	* got from Bitflux: http://blog.bitflux.ch/wiki/XSS_Prevention
	*
	* To help develop this script I used this great list of
	* vulnerabilities along with a few other hacks I've
	* harvested from examining vulnerabilities in other programs:
	* http://ha.ckers.org/xss.html
	*
	* @param	string
	* @return	string
	*/
	public function xss_clean($str, $is_image = false) {
		// Is the string an array?
		if(is_array($str)) {
			foreach ($str as $key => &$value) {
				$str[$key] = $this->xss_clean($value);
			}

			return $str;
		}

		// Remove Invisible Characters
		$str = $this->remove_invisible_characters($str);

		/*
		 * URL Decode
		 *
		 * Just in case stuff like this is submitted:
		 *
		 * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
		 *
		 * Note: Use rawurldecode() so it does not remove plus signs
		 */
		if(stripos($str, '%') !== false) {
			do {
				$oldstr = $str;
				$str = rawurldecode($str);
				$str = preg_replace_callback('#%(?:\s*[0-9a-f]){2,}#i', array($this, '_urldecodespaces'), $str);
			} while ($oldstr !== $str);

			unset($oldstr);
		}

		/*
		 * Convert character entities to ASCII
		 *
		 * This permits our tests below to work reliably.
		 * We only convert entities that are within tags since
		 * these are the ones that will pose security problems.
		 */
		$str = preg_replace_callback("/[^a-z0-9>]+[a-z0-9]+=([\'\"]).*?\\1/si", array($this, '_convert_attribute'), $str);
		$str = preg_replace_callback('/<\w+.*/si', array($this, '_decode_entity'), $str);

		// Remove Invisible Characters Again!
		$str = $this->remove_invisible_characters($str);

		/*
		 * Convert all tabs to spaces
		 *
		 * This prevents strings like this: ja	vascript
		 * NOTE: we deal with spaces between characters later.
		 * NOTE: preg_replace was found to be amazingly slow here on
		 * large blocks of data, so we use str_replace.
		 */
		$str = str_replace("\t", ' ', $str);

		// Capture converted string for later comparison
		$converted_string = $str;

		// Remove Strings that are never allowed
		$str = $this->_do_never_allowed($str);

		/*
		 * Makes PHP tags safe
		 *
		 * Note: XML tags are inadvertently replaced too:
		 *
		 * <?xml
		 *
		 * But it doesn't seem to pose a problem.
		 */
		if ($is_image === true) {
			// Images have a tendency to have the PHP short opening and
			// closing tags every so often so we skip those and only
			// do the long opening tags.
			$str = preg_replace('/<\?(php)/i', '&lt;?\\1', $str);
		} else {
			$str = str_replace(array('<?', '?'.'>'), array('&lt;?', '?&gt;'), $str);
		}

		/*
		 * Compact any exploded words
		 *
		 * This corrects words like:  j a v a s c r i p t
		 * These words are compacted back to their correct state.
		 */
		$words = array(
			'javascript', 'expression', 'vbscript', 'jscript', 'wscript',
			'vbs', 'script', 'base64', 'applet', 'alert', 'document',
			'write', 'cookie', 'window', 'confirm', 'prompt', 'eval'
		);

		foreach ($words as $word) {
			$word = implode('\s*', str_split($word)).'\s*';

			// We only want to do this when it is followed by a non-word character
			// That way valid stuff like "dealer to" does not become "dealerto"
			$str = preg_replace_callback('#('.substr($word, 0, -3).')(\W)#is', array($this, '_compact_exploded_words'), $str);
		}

		/*
		 * Remove disallowed Javascript in links or img tags
		 * We used to do some version comparisons and use of stripos(),
		 * but it is dog slow compared to these simplified non-capturing
		 * preg_match(), especially if the pattern exists in the string
		 *
		 * Note: It was reported that not only space characters, but all in
		 * the following pattern can be parsed as separators between a tag name
		 * and its attributes: [\d\s"\'`;,\/\=\(\x00\x0B\x09\x0C]
		 * ... however, remove_invisible_characters() above already strips the
		 * hex-encoded ones, so we'll skip them below.
		 */
		do {
			$original = $str;

			if (preg_match('/<a/i', $str)) {
				$str = preg_replace_callback('#<a(?:rea)?[^a-z0-9>]+([^>]*?)(?:>|$)#si', array($this, '_js_link_removal'), $str);
			}

			if (preg_match('/<img/i', $str)) {
				$str = preg_replace_callback('#<img[^a-z0-9]+([^>]*?)(?:\s?/?>|$)#si', array($this, '_js_img_removal'), $str);
			}

			if (preg_match('/script|xss/i', $str)) {
				$str = preg_replace('#</*(?:script|xss).*?>#si', '[removed]', $str);
			}
		}
		while ($original !== $str);
		unset($original);

		/*
		 * Sanitize naughty HTML elements
		 *
		 * If a tag containing any of the words in the list
		 * below is found, the tag gets converted to entities.
		 *
		 * So this: <blink>
		 * Becomes: &lt;blink&gt;
		 */
		$pattern = '#'
			.'<((?<slash>/*\s*)((?<tagName>[a-z0-9]+)(?=[^a-z0-9]|$)|.+)' // tag start and name, followed by a non-tag character
			.'[^\s\042\047a-z0-9>/=]*' // a valid attribute character immediately after the tag would count as a separator
			// optional attributes
			.'(?<attributes>(?:[\s\042\047/=]*' // non-attribute characters, excluding > (tag close) for obvious reasons
			.'[^\s\042\047>/=]+' // attribute characters
			// optional attribute-value
				.'(?:\s*=' // attribute-value separator
					.'(?:[^\s\042\047=><`]+|\s*\042[^\042]*\042|\s*\047[^\047]*\047|\s*(?U:[^\s\042\047=><`]*))' // single, double or non-quoted value
				.')?' // end optional attribute-value group
			.')*)' // end optional attributes group
			.'[^>]*)(?<closeTag>\>)?#isS';

		// Note: It would be nice to optimize this for speed, BUT
		//       only matching the naughty elements here results in
		//       false positives and in turn - vulnerabilities!
		do {
			$old_str = $str;
			$str = preg_replace_callback($pattern, array($this, '_sanitize_naughty_html'), $str);
		} while ($old_str !== $str);
		
		unset($old_str);

		/*
		 * Sanitize naughty scripting elements
		 *
		 * Similar to above, only instead of looking for
		 * tags it looks for PHP and JavaScript commands
		 * that are disallowed. Rather than removing the
		 * code, it simply converts the parenthesis to entities
		 * rendering the code un-executable.
		 *
		 * For example:	eval('some code')
		 * Becomes:	eval&#40;'some code'&#41;
		 */
		$str = preg_replace(
			'#(alert|prompt|confirm|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si',
			'\\1\\2&#40;\\3&#41;',
			$str
		);

		// Same thing, but for "tag functions" (e.g. eval`some code`)
		// See https://github.com/bcit-ci/CodeIgniter/issues/5420
		$str = preg_replace(
			'#(alert|prompt|confirm|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)`(.*?)`#si',
			'\\1\\2&#96;\\3&#96;',
			$str
		);

		// Final clean up
		// This adds a bit of extra precaution in case
		// something got through the above filters
		$str = $this->_do_never_allowed($str);

		/*
		 * Images are Handled in a Special Way
		 * - Essentially, we want to know that after all of the character
		 * conversion is done whether any unwanted, likely XSS, code was found.
		 * If not, we return true, as the image is clean.
		 * However, if the string post-conversion does not matched the
		 * string post-removal of XSS, then it fails, as there was unwanted XSS
		 * code found and removed/changed during processing.
		 */
		if ($is_image === true) {
			return ($str === $converted_string);
		}

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	* Random Hash for protecting URLs
	*
	* @access	public
	* @return	string
	*/
	function xss_hash() {
		if($this->xss_hash == '') {
			if (phpversion() >= 4.2)
				mt_srand();
			else
				mt_srand(hexdec(substr(md5(microtime()), -8)) & 0x7fffffff);

			$this->xss_hash = md5(time() + mt_rand(0, 1999999999));
		}

		return $this->xss_hash;
	}

	// --------------------------------------------------------------------

	/**
	* Remove Invisible Characters
	*
	* This prevents sandwiching null characters
	* between ascii characters, like Java\0script.
	*
	* @access	public
	* @param	string
	* @return	string
	*/
	function _remove_invisible_characters($str) {
		static $non_displayables;

		if(!isset($non_displayables)) {
			// every control character except newline (dec 10), carriage return (dec 13), and horizontal tab (dec 09),
			$non_displayables = array(
										'/%0[0-8bcef]/',			// url encoded 00-08, 11, 12, 14, 15
										'/%1[0-9a-f]/',				// url encoded 16-31
										'/[\x00-\x08]/',			// 00-08
										'/\x0b/', '/\x0c/',			// 11, 12
										'/[\x0e-\x1f]/'				// 14-31
									);
		}

		do {
			$cleaned = $str;
			$str = preg_replace($non_displayables, '', $str);
		} while ($cleaned != $str);

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	* Compact Exploded Words
	*
	* Callback function for xss_clean() to remove whitespace from
	* things like j a v a s c r i p t
	*
	* @access	public
	* @param	type
	* @return	type
	*/
	function _compact_exploded_words($matches) {
		return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
	}

	// --------------------------------------------------------------------

	/**
	 * Sanitize Naughty HTML
	 *
	 * Callback method for xss_clean() to remove naughty HTML elements.
	 *
	 * @param	array	$matches
	 * @return	string
	 */
	protected function _sanitize_naughty_html($matches) {
		static $naughty_tags    = array(
			'alert', 'area', 'prompt', 'confirm', 'applet', 'audio', 'basefont', 'base', 'behavior', 'bgsound',
			'blink', 'body', 'embed', 'expression', 'form', 'frameset', 'frame', 'head', 'html', 'ilayer',
			'iframe', 'input', 'button', 'select', 'isindex', 'layer', 'link', 'meta', 'keygen', 'object',
			'plaintext', 'style', 'script', 'textarea', 'title', 'math', 'video', 'svg', 'xml', 'xss'
		);

		static $evil_attributes = array(
			'on\w+', 'style', 'xmlns', 'formaction', 'form', 'xlink:href', 'FSCommand', 'seekSegmentTime'
		);

		// First, escape unclosed tags
		if (empty($matches['closeTag']))
		{
			return '&lt;'.$matches[1];
		}
		// Is the element that we caught naughty? If so, escape it
		elseif (in_array(strtolower($matches['tagName']), $naughty_tags, TRUE))
		{
			return '&lt;'.$matches[1].'&gt;';
		}
		// For other tags, see if their attributes are "evil" and strip those
		elseif (isset($matches['attributes']))
		{
			// We'll store the already filtered attributes here
			$attributes = array();

			// Attribute-catching pattern
			$attributes_pattern = '#'
				.'(?<name>[^\s\042\047>/=]+)' // attribute characters
				// optional attribute-value
				.'(?:\s*=(?<value>[^\s\042\047=><`]+|\s*\042[^\042]*\042|\s*\047[^\047]*\047|\s*(?U:[^\s\042\047=><`]*)))' // attribute-value separator
				.'#i';

			// Blacklist pattern for evil attribute names
			$is_evil_pattern = '#^('.implode('|', $evil_attributes).')$#i';

			// Each iteration filters a single attribute
			do
			{
				// Strip any non-alpha characters that may precede an attribute.
				// Browsers often parse these incorrectly and that has been a
				// of numerous XSS issues we've had.
				$matches['attributes'] = preg_replace('#^[^a-z]+#i', '', $matches['attributes']);

				if ( ! preg_match($attributes_pattern, $matches['attributes'], $attribute, PREG_OFFSET_CAPTURE))
				{
					// No (valid) attribute found? Discard everything else inside the tag
					break;
				}

				if (
					// Is it indeed an "evil" attribute?
					preg_match($is_evil_pattern, $attribute['name'][0])
					// Or does it have an equals sign, but no value and not quoted? Strip that too!
					OR (trim($attribute['value'][0]) === '')
				)
				{
					$attributes[] = 'xss=removed';
				}
				else
				{
					$attributes[] = $attribute[0][0];
				}

				$matches['attributes'] = substr($matches['attributes'], $attribute[0][1] + strlen($attribute[0][0]));
			}
			while ($matches['attributes'] !== '');

			$attributes = empty($attributes)
				? ''
				: ' '.implode(' ', $attributes);
			return '<'.$matches['slash'].$matches['tagName'].$attributes.'>';
		}

		return $matches[0];
	}

	// --------------------------------------------------------------------

	/**
	 * JS Link Removal
	 *
	 * Callback method for xss_clean() to sanitize links.
	 *
	 * This limits the PCRE backtracks, making it more performance friendly
	 * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
	 * PHP 5.2+ on link-heavy strings.
	 *
	 * @used-by	CI_Security::xss_clean()
	 * @param	array	$match
	 * @return	string
	 */
	protected function _js_link_removal($match) {
		return str_replace(
			$match[1],
			preg_replace(
				'#href=.*?(?:(?:alert|prompt|confirm)(?:\(|&\#40;|`|&\#96;)|javascript:|livescript:|mocha:|charset=|window\.|\(?document\)?\.|\.cookie|<script|<xss|d\s*a\s*t\s*a\s*:)#si',
				'',
				$this->_filter_attributes($match[1])
			),
			$match[0]
		);
	}

	/**
	 * JS Image Removal
	 *
	 * Callback method for xss_clean() to sanitize image tags.
	 *
	 * This limits the PCRE backtracks, making it more performance friendly
	 * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
	 * PHP 5.2+ on image tag heavy strings.
	 *
	 * @param	array	$match
	 * @return	string
	 */
	protected function _js_img_removal($match) {
		return str_replace(
			$match[1],
			preg_replace(
				'#src=.*?(?:(?:alert|prompt|confirm|eval)(?:\(|&\#40;|`|&\#96;)|javascript:|livescript:|mocha:|charset=|window\.|\(?document\)?\.|\.cookie|<script|<xss|base64\s*,)#si',
				'',
				$this->_filter_attributes($match[1])
			),
			$match[0]
		);
	}

	// --------------------------------------------------------------------

	/**
	* Attribute Conversion
	*
	* Used as a callback for XSS Clean
	*
	* @param	array
	* @return	string
	*/
	protected function _convert_attribute($match) {
		return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
	}

	// --------------------------------------------------------------------

	/**
	* Filter Attributes
	*
	* Filters tag attributes for consistency and safety
	*
	* @param	string   $str
	* @return	string
	*/
	protected function _filter_attributes($str) {
		$out = '';

		if(preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches))
			foreach ($matches[0] as $match)
				$out .= preg_replace("#/\*.*?\*/#s", '', $match);

		return $out;
	}

	// --------------------------------------------------------------------

	/**
	 * URL-decode taking spaces into account
	 *
	 * @see		https://github.com/bcit-ci/CodeIgniter/issues/4877
	 * @param	array	$matches
	 * @return	string
	 */
	protected function _urldecodespaces($matches) {
		$input    = $matches[0];
		$nospaces = preg_replace('#\s+#', '', $input);
		return ($nospaces === $input) ? $input : rawurldecode($nospaces);
	}

	/**
	 * HTML Entity Decode Callback
	 *
	 * @param	array	$match
	 * @return	string
	 */
	protected function _decode_entity($match) {
		// Protect GET variables in URLs
		// 901119URL5918AMP18930PROTECT8198
		$match = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-/]+)|i', $this->xss_hash().'\\1=\\2', $match[0]);

		// Decode, then un-protect URL GET vars
		return str_replace(
			$this->xss_hash(),
			'&',
			$this->entity_decode($match, $this->charset)
		);
	}

	/**
	 * HTML Entities Decode
	 *
	 * A replacement for html_entity_decode()
	 *
	 * The reason we are not using html_entity_decode() by itself is because
	 * while it is not technically correct to leave out the semicolon
	 * at the end of an entity most browsers will still interpret the entity
	 * correctly. html_entity_decode() does not convert entities without
	 * semicolons, so we are left with our own little solution here. Bummer.
	 *
	 * @link	https://secure.php.net/html-entity-decode
	 *
	 * @param	string	$str		Input
	 * @param	string	$charset	Character set
	 * @return	string
	 */
	public function entity_decode($str, $charset = NULL) {
		if (strpos($str, '&') === false)
			return $str;

		static $_entities;

		isset($charset)   OR $charset = $this->charset;
		isset($_entities) OR $_entities = array_map('strtolower', get_html_translation_table(HTML_ENTITIES, ENT_COMPAT | ENT_HTML5, $charset));

		do
		{
			$str_compare = $str;

			// Decode standard entities, avoiding false positives
			if (preg_match_all('/&[a-z]{2,}(?![a-z;])/i', $str, $matches))
			{
				$replace = array();
				$matches = array_unique(array_map('strtolower', $matches[0]));
				foreach ($matches as &$match)
				{
					if (($char = array_search($match.';', $_entities, TRUE)) !== FALSE)
					{
						$replace[$match] = $char;
					}
				}

				$str = str_replace(array_keys($replace), array_values($replace), $str);
			}

			// Decode numeric & UTF16 two byte entities
			$str = html_entity_decode(
				preg_replace('/(&#(?:x0*[0-9a-f]{2,5}(?![0-9a-f;])|(?:0*\d{2,4}(?![0-9;]))))/iS', '$1;', $str),
				ENT_COMPAT | ENT_HTML5,
				$charset
			);
		}
		while ($str_compare !== $str);
		return $str;
	}

	/**
	 * Do Never Allowed
	 *
	 * @param 	string   $str
	 * @return 	string
	 */
	protected function _do_never_allowed($str) {
		$str = str_replace(array_keys($this->_never_allowed_str), $this->_never_allowed_str, $str);

		foreach ($this->_never_allowed_regex as $regex)
			$str = preg_replace('#'.$regex.'#is', '[removed]', $str);

		return $str;
	}

	/**
	 * Remove Invisible Characters
	 *
	 * This prevents sandwiching null characters
	 * between ascii characters, like Java\0script.
	 *
	 * @param	string
	 * @param	bool
	 * @return	string
	 */
	public function remove_invisible_characters($str, $url_encoded = true) {
		$non_displayables = array();

		// every control character except newline (dec 10),
		// carriage return (dec 13) and horizontal tab (dec 09)
		if ($url_encoded) {
			$non_displayables[] = '/%0[0-8bcef]/i';	// url encoded 00-08, 11, 12, 14, 15
			$non_displayables[] = '/%1[0-9a-f]/i';	// url encoded 16-31
			$non_displayables[] = '/%7f/i';	// url encoded 127
		}

		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

		do {
			$str = preg_replace($non_displayables, '', $str, -1, $count);
		} while ($count);

		return $str;
	}

}