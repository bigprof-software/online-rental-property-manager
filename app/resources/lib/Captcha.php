<?php
/*
	# Captcha class

	This class provides methods to provide a captcha challenge to users. It can be used to prevent automated form submissions.

	## Image captchas

	Image captchas require the GD extension to be installed. The following code can be used to check this:

	```php
	if(!Captcha::available('image')) {
		// GD extension not installed
		// handle error
	}
	```

	The following code can be used to generate a captcha image:

	```php
	Captcha::generate(true);
	```

	The above code will output the image to the browser with the correct headers and exit, assuming no output has been sent to the browser yet.

	If you want to use the image in an `img` tag, you can use the following code:

	```php
	// return a captcha image resource
	$image = Captcha::generate();

	// use the image resource in an `img` tag
	echo '<img src="' . $image . '" alt="Captcha" />';
	```

	### Generate stand-alone captcha form

	The following code can be used to generate a stand-alone captcha form that can be embedded in any page:

	```php
	echo Captcha::standAloneForm('submit.php', 'image');
	```

	Change `submit.php` to the URL of the page that will process the form submission.

	### Verify captcha code

	Captcha code should be submitted as `captcha` parameter in a POST request. The following code can be used to verify the captcha code:

	```php
	if(!Captcha::verified('image')) {
		// captcha code is incorrect
		// handle error
	}
	```

	### A full example

	Place the following code in the page that handles the form submission, before any output is sent to the browser,
	and before any other processing is done:

	```php
	if(Captcha::available('image') && !Captcha::verified('image')) {
		echo Captcha::standAloneForm('submit.php', 'image');
		exit; // stop further processing
	}

	// captcha code is correct or captcha is not available
	// continue processing
	```

	Change `submit.php` to the URL of the page that will process the form submission.

	## Google ReCAPTCHA

	Google ReCAPTCHA requires the following configuration options to be set in `config.php`, under the `adminConfig` key:

	```php
	'googleRecaptchaSiteKey' => 'your-site-key',
	'googleRecaptchaSecretKey' => 'your-secret-key',
	```

	To obtain these keys, visit [https://www.google.com/recaptcha/admin](https://www.google.com/recaptcha/admin) and register your site.

	### Stand-alone ReCAPTCHA form full example

	```php
	if(Captcha::available('recaptcha') && !Captcha::verified('recaptcha')) {
		echo Captcha::standAloneForm('submit.php', 'recaptcha');
		exit; // stop further processing
	}
	```

	Change `submit.php` to the URL of the page that will process the form submission.

	## Automatic captcha type

	In the above examples, the captcha type is specified explicitly.
	If you don't specify the captcha type, the class will automatically use the 
	first available captcha type,
	checking for availability in the order specified in `Captcha::TYPES`.
*/

Class Captcha {

	// captcha image width and height in pixels
	private static $width = 100;
	private static $height = 30;

	// supported captcha types (const array)
	public const TYPES = ['recaptcha', 'image'];

	/**
	 * Generate captcha image and store code in session
	 * @param bool $output - if false, return image resource, otherwise output image to browser. Default false
	 * @return resource - image resource suitable for use in `src` attribute of `img` tag
	 */
	public static function generate($output = false) {
		$code = self::generateCode();
		$_SESSION['captcha'] = $code;

		// create image
		$image = imagecreatetruecolor(self::$width, self::$height);

		// allocate colors
		$bgColor = imagecolorallocate($image, 255, 255, 255);
		$textColor = imagecolorallocate($image, 0, 0, 0);
		$noiseColor = imagecolorallocate($image, 100, 120, 180);
		$lineColor = imagecolorallocate($image, 100, 120, 180);

		// fill background
		imagefilledrectangle($image, 0, 0, self::$width, self::$height, $bgColor);

		// add noise
		for($i = 0; $i < (self::$width * self::$height) / 3; $i++) {
			imagefilledellipse(
				$image,
				mt_rand(0, self::$width),
				mt_rand(0, self::$height),
				1,
				1,
				$noiseColor
			);
		}

		// add random lines
		for($i = 0; $i < mt_rand(3, 8); $i++) {
			$startX = mt_rand(0, self::$width / 3);

			imageline(
				$image,
				mt_rand(0, $startX),
				mt_rand(0, self::$height),
				mt_rand($startX * 2, self::$width),
				mt_rand(0, self::$height),
				$lineColor
			);
		}

		// add text using a built-in font
		$font = 5;
		$fontWidth = imagefontwidth($font);
		$fontHeight = imagefontheight($font);
		$textWidth = $fontWidth * strlen($code);
		$textHeight = $fontHeight;
		$x = (self::$width - $textWidth) / 2;
		$y = (self::$height - $textHeight) / 2;

		// vertically shift each character randomly
		for($i = 0; $i < strlen($code); $i++) {
			$y += mt_rand(-2, 2);
			imagechar($image, $font, $x, $y, $code[$i], $textColor);
			$x += $fontWidth;
		}

		// output image to browser if $output is true
		if($output) {
			header("Content-type: image/png");
			imagepng($image);
			imagedestroy($image);
			exit;
		}

		// get images in variable
		ob_start();
		imagepng($image);
		$imageBin = ob_get_clean();

		// return image resource for use in `src` attribute of `img` tag
		$resource = 'data:image/png;base64,' . base64_encode($imageBin);
		imagedestroy($image);
		return $resource;
	}

	/**
	 * Verify user has entered correct code or has been verified before
	 * @param string $type - captcha type. Supported values are those defined in `Captcha::TYPES`. Default 'image'
	 * @return bool - true if user has entered correct code or has been verified before, false otherwise
	 * 
	 * @todo Add new cases for new captcha types
	 */
	public static function verified($type = '') {
		if(isset($_SESSION['captcha_verified'])) return true;

		$type = self::firstAvailableType($type);
		
		switch($type) {
			case 'recaptcha':
				if(!self::verifiedRecaptcha()) return false;
				break;

			case 'image':
				if(
					!isset($_SESSION['captcha'])
					|| !Request::has('captcha')
					|| $_SESSION['captcha'] != trim(strtoupper(Request::val('captcha')))
				) return false;
				break;
			default:
				return false;
		}
		
		$_SESSION['captcha_verified'] = true;
		return true;
	}

	/**
	 * Generate an embeddable stand alone captcha form
	 * @param string $action - form action attribute
	 * @param string $type - form submit button type. Default 'image'. Supported values: 'image', 'recaptcha'
	 * @return string - HTML form
	 */
	public static function standAloneForm($action = '', $type = '') {
		ob_start();
		?>
		<form method="post" action="<?php echo $action; ?>" class="captch-form">
			<?php echo self::captchaCode($type); ?>
		</form>

		<style>
			.captch-form {
				width: 100%;
				max-width: 30em;
				margin: 1em auto;
			}

			.captch-form img,
			.captch-form input,
			.captch-form button {
				display: block;
				margin-bottom: 2em;
				width: 100%;
			}

			.captch-form input {
				padding: .5em;
			}

			.captch-form label {
				display: block;
				margin-bottom: 0.5em;
			}

			.captch-form button {
				padding: 5px;
			}
		</style>

		<script>
			// set mobile device viewport if not already set
			if(!document.querySelector('meta[name="viewport"]')) {
				var meta = document.createElement('meta');
				meta.name = 'viewport';
				meta.content = 'width=device-width, initial-scale=1.0';
				document.head.appendChild(meta);
			}
		</script>
		<?php

		return ob_get_clean();
	}

	/**
	 * Check if captcha is available
	 * @param string $type - captcha type. Supported values are those defined in `Captcha::TYPES`. Default 'image'
	 * @return bool - true if captcha is available, false otherwise
	 */
	public static function available($type = '') {
		switch($type) {
			case 'recaptcha':
				return !empty(config('adminConfig')['googleRecaptchaSiteKey']) && !empty(config('adminConfig')['googleRecaptchaSecretKey']);

			case 'image':
			default:
				return function_exists('gd_info');
		}
	}

	/**
	 * Generate captcha code
	 * @param string $type - captcha type. Supported values are those defined in `Captcha::TYPES`
	 * @return string - captcha code
	 * 
	 * @todo Add new cases for new captcha types
	 */
	public static function captchaCode($type = '') {
		global $Translation;

		$type = self::firstAvailableType($type);

		ob_start();
		switch($type) {
			case 'recaptcha':
				?>
				<script src="https://www.google.com/recaptcha/api.js" async defer></script>
				<div class="g-recaptcha" data-sitekey="<?php echo html_attr(config('adminConfig')['googleRecaptchaSiteKey'] ?? ''); ?>"></div>
				<script>
					// periodically check if recaptcha is verified and submit form if it is
					const recaptchaChecker = setInterval(() => {
						if(!grecaptcha?.getResponse().length) return;
						
						clearInterval(recaptchaChecker);

						const form = document.querySelector('.g-recaptcha').closest('form');
						if(!form) {
							console.error('ReCAPTCHA not in a form. Cannot auto-submit.');
							return;
						}
						
						form.submit();
					}, 200);
				</script>
				<?php
				break;
			case 'image':
				$image = self::generate();
				?>
				<img src="<?php echo $image; ?>" alt="Captcha" />			
				<label for="captcha"><?php echo $Translation['captcha label']; ?></label>
				<input type="text" name="captcha" id="captcha" autocomplete="off" autofocus required />
				<button type="submit"><?php echo $Translation['Submit']; ?></button>
				<?php
				break;
		}

		return ob_get_clean();
	}

	/**
	 * Generate a random code for use in image captcha
	 * @return string - captcha code
	 */
	private static function generateCode() {
		$chars = 'ABCDEFGHJKLMNPRTWXY23456789'; // list of easily distinguishable characters
		$code = '';
		for($i = 0; $i < 6; $i++) {
			$index = mt_rand(0, strlen($chars) - 1);
			$code .= $chars[$index];

			// remove the character from the list so it can't be used again
			$chars = substr($chars, 0, $index) . substr($chars, $index + 1);
		}

		return $code;
	}

	/**
	 * Verify reCAPTCHA response
	 * @return bool - true if reCAPTCHA response is valid, false otherwise
	 */
	private static function verifiedRecaptcha() {
		if(!self::available('recaptcha')) return false;

		// get reCAPTCHA response from login form
		$reCaptchaResponse = Request::val('g-recaptcha-response');
		if(!$reCaptchaResponse) return false;
		 
		if(!function_exists('curl_init')) return false;
		 
		// send a POST request to Google's reCAPTCHA validation API endpoint
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => 'https://www.google.com/recaptcha/api/siteverify',
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query([
			'secret' => config('adminConfig')['googleRecaptchaSecretKey'],
			'response' => $reCaptchaResponse,
			]),
			CURLOPT_RETURNTRANSFER => true,
		]);
		 
		$googleRespJson = curl_exec($ch);
		curl_close($ch);
		 
		// if error response, abort
		if($googleRespJson === false) return false;
		$googleResp = @json_decode($googleRespJson, true);
		if($googleResp === null) return false;

		return !empty($googleResp['success']);
	}

	/**
	 * Get first available captcha type
	 * @param string $type - captcha type. Supported values are those defined in `Captcha::TYPES`
	 * @return string - first available captcha type. If no captcha types are available, returns empty string
	 */
	private static function firstAvailableType($type = '') {
		if($type && self::available($type)) return $type;

		foreach(self::TYPES as $type) {
			if(self::available($type)) return $type;
		}

		return '';
	}
}