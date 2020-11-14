<?php

/**
 * JSON Web Token implementation
 *
 * Minimum implementation used by Realtime auth, based on this spec:
 * http://self-issued.info/docs/draft-jones-json-web-token-01.html.
 *
 * @author Neuman Vong <neuman@twilio.com>
 */
class JWT {
	/**
	 * @param string $jwt	The JWT
	 * @param string $key	The secret key
	 * @param string $error	By-ref variable to store decoding error
	 *
	 * @return object The JWT's payload as a PHP object, or false on error.
	 */
	public static function decode($jwt, $key, &$error) {
		$tks = explode('.', $jwt);

		$error = 'Wrong number of segments';
		if(count($tks) != 3) return false;

		list($headb64, $payloadb64, $cryptob64) = $tks;

		$error = 'Invalid header';
		if(!($header = JWT::jsonDecode(JWT::urlsafeB64Decode($headb64)))) return false;
		if(!isset($header['alg'])) return false;

		$error = 'Invalid payload';
		if(!($payload = JWT::jsonDecode(JWT::urlsafeB64Decode($payloadb64)))) return false;

		$error = 'Expired token';
		if(empty($payload['exp']) || $payload['exp'] < time()) return false;

		$sig = JWT::urlsafeB64Decode($cryptob64);

		$error = 'Signature verification failed';
		if($sig != JWT::sign("{$headb64}.{$payloadb64}", $key, $header['alg'])) return false;

		$error = '';
		return $payload;
	}

	/**
	 * @param object|array $payload PHP object or array
	 * @param string	   $key	 The secret key
	 * @param string	   $algo	The signing algorithm
	 *
	 * @return string A JWT
	 */
	public static function encode($payload, $key, $algo = 'HS256') {
		$header = array('typ' => 'JWT', 'alg' => $algo);

		// default expiry is 15 minutes if not provided
		if(empty($payload['exp'])) $payload['exp'] = time() + 15 * 60;

		$segments = array();
		$segments[] = JWT::urlsafeB64Encode(JWT::jsonEncode($header));
		$segments[] = JWT::urlsafeB64Encode(JWT::jsonEncode($payload));
		$signing_input = implode('.', $segments);

		$signature = JWT::sign($signing_input, $key, $algo);
		$segments[] = JWT::urlsafeB64Encode($signature);

		return implode('.', $segments);
	}

	/**
	 * @param string $msg	The message to sign
	 * @param string $key	The secret key
	 * @param string $method The signing algorithm
	 *
	 * @return string An encrypted message
	 */
	public static function sign($msg, $key, $method = 'HS256') {
		$methods = array(
			'HS256' => 'sha256',
			'HS384' => 'sha384',
			'HS512' => 'sha512',
		);

		// Algorithm not supported?
		if(empty($methods[$method])) return false;

		return hash_hmac($methods[$method], $msg, $key, true);
	}

	/**
	 * @param string $input JSON string
	 *
	 * @return array Array representation of JSON string, or false on error
	 */
	public static function jsonDecode($input) {
		$obj = json_decode($input, true);

		if(function_exists('json_last_error') && $errno = json_last_error()) return false;
		if($obj === null && $input !== 'null') return false;

		return $obj;
	}

	/**
	 * @param object|array $input A PHP object or array
	 *
	 * @return string JSON representation of the PHP object or array, or false on error
	 */
	public static function jsonEncode($input) {
		$json = json_encode($input);

		if(function_exists('json_last_error') && $errno = json_last_error()) return false;
		if($json === 'null' && $input !== null) return false;

		return $json;
	}

	/**
	 * @param string $input A base64 encoded string
	 *
	 * @return string A decoded string
	 */
	public static function urlsafeB64Decode($input) {
		$remainder = strlen($input) % 4;
		if($remainder) {
			$padlen = 4 - $remainder;
			$input .= str_repeat('=', $padlen);
		}

		return base64_decode(strtr($input, '-_', '+/'));
	}

	/**
	 * @param string $input Anything really
	 *
	 * @return string The base64 encode of what you passed in
	 */
	public static function urlsafeB64Encode($input) {
		return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
	}
}
