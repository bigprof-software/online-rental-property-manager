<?php

include_once(__DIR__ . '/lib.php');

// Set the content type to JavaScript
header('Content-Type: application/javascript');

// Calculate the cache duration (in seconds)
// Here, we set it to 1 week (7 days)
$cacheDuration = 7 * 24 * 60 * 60; // 1 week in seconds

// Set the Cache-Control header
// 'public' means it can be cached by both the client and intermediate proxies
// 'max-age' specifies the maximum amount of time in seconds that the resource is considered fresh
header('Cache-Control: public, max-age=' . $cacheDuration);

// Set the Expires header
// This specifies the date/time after which the response is considered stale
// We set it to the current time plus the cache duration
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cacheDuration) . ' GMT');

// Set the Last-Modified header to the last modified time of language.php
// This helps with conditional requests and validation
$lastModifiedTime = filemtime(__DIR__ . '/language.php');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModifiedTime) . ' GMT');

// make a UTF8 version of $Translation
$translationUTF8 = $Translation;
if(datalist_db_encoding != 'UTF-8')
	$translationUTF8 = array_map(function($str) {
		return iconv(datalist_db_encoding, 'UTF-8', $str);
	}, $translationUTF8);
?>

var AppGini = AppGini || {};

/* translation strings */
AppGini.Translate = {
	_map: <?php echo json_encode($translationUTF8, JSON_PRETTY_PRINT); ?>,
	_encoding: '<?php echo datalist_db_encoding; ?>',
	apply: () => {
		// find elements with data-translate attribute that don't have .translated class
		const contentEls = document.querySelectorAll('[data-translate]:not(.translated)');

		// find elements with data-title attribute that don't have .translated class
		const titleEls = document.querySelectorAll('[data-title]:not(.translated)');

		// abort if no elements found
		if(!contentEls.length && !titleEls.length) return;

		// translate content of elements that have data-translate attribute
		contentEls.forEach(el => {
			const key = el.getAttribute('data-translate');
			if(!key) return;

			const translation = AppGini.Translate._map[key];
			if(!translation) return;

			el.innerHTML = translation;
			el.classList.add('translated');
		});

		// translate title of elements that have data-title attribute
		titleEls.forEach(el => {
			const key = el.getAttribute('data-title');
			if(!key) return;

			const translation = AppGini.Translate._map[key];
			if(!translation) return;

			el.setAttribute('title', translation);
			el.classList.add('translated');
		});
	},
}
