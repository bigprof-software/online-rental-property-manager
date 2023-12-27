<?php if(function_exists('set_headers')) { set_headers(); } ?>
<?php if(!isset($Translation)) die('No direct access allowed!'); ?><!DOCTYPE html>
<?php if(!defined('PREPEND_PATH')) define('PREPEND_PATH', ''); ?>
<html class="no-js">
	<head>
		<meta charset="<?php echo datalist_db_encoding; ?>">
		<meta name="description" content="">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">

		<title><?php echo APP_TITLE . (isset($x->TableTitle) ? ' | ' . $x->TableTitle : ''); ?></title>
		<link id="browser_favicon" rel="shortcut icon" href="<?php echo PREPEND_PATH; ?>resources/images/appgini-icon.png">

		<link rel="stylesheet" href="<?php echo PREPEND_PATH; ?>resources/initializr/css/bootstrap.css">
		<link rel="stylesheet" href="<?php echo PREPEND_PATH; ?>resources/initializr/css/bootstrap-theme.css">
		<link rel="stylesheet" href="<?php echo PREPEND_PATH; ?>resources/lightbox/css/lightbox.css" media="screen">
		<link rel="stylesheet" href="<?php echo PREPEND_PATH; ?>resources/select2/select2.css" media="screen">
		<link rel="stylesheet" href="<?php echo PREPEND_PATH; ?>resources/timepicker/bootstrap-timepicker.min.css" media="screen">
		<link rel="stylesheet" href="<?php echo PREPEND_PATH; ?>resources/datepicker/css/datepicker.css" media="screen">
		<link rel="stylesheet" href="<?php echo PREPEND_PATH; ?>resources/bootstrap-datetimepicker/bootstrap-datetimepicker.css" media="screen">
		<link rel="stylesheet" href="<?php echo PREPEND_PATH; ?>dynamic.css?<?php echo filemtime( __DIR__ . '/dynamic.css'); ?>">

		<script src="<?php echo PREPEND_PATH; ?>resources/jquery/js/<?php echo latest_jquery(); ?>"></script>
		<script>var $j = jQuery.noConflict();</script>
		<script src="<?php echo PREPEND_PATH; ?>resources/moment/moment-with-locales.min.js"></script>
		<script src="<?php echo PREPEND_PATH; ?>resources/jquery/js/jquery.mark.min.js"></script>
		<script src="<?php echo PREPEND_PATH; ?>resources/initializr/js/vendor/bootstrap.min.js"></script>
		<script src="<?php echo PREPEND_PATH; ?>resources/lightbox/js/prototype.js"></script>
		<script src="<?php echo PREPEND_PATH; ?>resources/lightbox/js/scriptaculous.js?load=effects"></script>
		<script src="<?php echo PREPEND_PATH; ?>resources/select2/select2.min.js"></script>
		<script src="<?php echo PREPEND_PATH; ?>resources/timepicker/bootstrap-timepicker.min.js"></script>
		<script src="<?php echo PREPEND_PATH; ?>resources/datepicker/js/datepicker.packed.js"></script>
		<script src="<?php echo PREPEND_PATH; ?>resources/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js"></script>
		<script src="<?php echo PREPEND_PATH; ?>resources/hotkeys/jquery.hotkeys.min.js"></script>
		<script src="<?php echo PREPEND_PATH; ?>nicEdit.js"></script>

		<script>
			<?php
				// make a UTF8 version of $Translation
				$translationUTF8 = $Translation;
				if(datalist_db_encoding != 'UTF-8')
					$translationUTF8 = array_map(function($str) {
						return iconv(datalist_db_encoding, 'UTF-8', $str);
					}, $translationUTF8);

				$jsAppConfig = [
					'imgFolder' => rtrim(config('adminConfig')['baseUploadPath'], '\\/') . '/',
					'url' => application_url(),
					'uri' => application_uri(),
				];
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

			AppGini.config = <?php echo json_encode($jsAppConfig, JSON_PARTIAL_OUTPUT_ON_ERROR); ?>
		</script>

		<script src="<?php echo PREPEND_PATH; ?>common.js?<?php echo filemtime( __DIR__ . '/common.js'); ?>"></script>
		<script src="<?php echo PREPEND_PATH; ?>shortcuts.js?<?php echo filemtime( __DIR__ . '/shortcuts.js'); ?>"></script>
		<?php if(isset($x->TableName) && is_file(__DIR__ . "/hooks/{$x->TableName}-tv.js")) { ?>
			<script src="<?php echo PREPEND_PATH; ?>hooks/<?php echo $x->TableName; ?>-tv.js"></script>
		<?php } ?>

	</head>
	<body>
		<div class="users-area container theme-bootstrap theme-3d theme-compact">
			<?php if(function_exists('handle_maintenance')) echo handle_maintenance(true); ?>

			<?php if(!Request::val('Embedded')) { ?>
				<?php if(function_exists('htmlUserBar')) echo htmlUserBar(); ?>
				<div style="min-height: 70px;" class="hidden-print top-margin-adjuster"></div>
			<?php } ?>

			<?php echo WindowMessages::getHtml(); ?>

			<?php if(class_exists('Notification', false)) echo Notification::placeholder(); ?>

			<?php 
				// process notifications
				if(function_exists('showNotifications')) echo showNotifications();
			?>

			<?php if(Request::val('Embedded')) { ?>
				<div style="height: 2rem;"></div>
			<?php } ?>

			<?php if(!defined('APPGINI_SETUP') && is_file(__DIR__ . '/hooks/header-extras.php')) { include(__DIR__ . '/hooks/header-extras.php'); } ?>
			<!-- Add header template below here .. -->

