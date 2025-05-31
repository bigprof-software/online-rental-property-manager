<?php if(function_exists('set_headers')) { set_headers(); } ?>
<?php if(!isset($Translation)) die('No direct access allowed!'); ?><!DOCTYPE html>
<?php if(!defined('PREPEND_PATH')) define('PREPEND_PATH', ''); ?>
<?php
	$theme = getUserTheme();
	$bootstrap3d = '';
	if($theme == 'bootstrap' && BOOTSTRAP_3D_EFFECTS) {
		$bootstrap3d = '<link rel="stylesheet" href="' . PREPEND_PATH . 'resources/initializr/css/bootstrap-theme.css" media="screen">' . "\n";
	}
?>
<html class="no-js">
	<head>
		<meta charset="<?php echo datalist_db_encoding; ?>">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="">

		<title><?php echo APP_TITLE . (isset($x->TableTitle) ? ' | ' . $x->TableTitle : ''); ?></title>
		<link id="browser_favicon" rel="shortcut icon" href="<?php echo PREPEND_PATH; ?>resources/images/appgini-icon.png">
		<?php if(!defined('APPGINI_SETUP')) { ?>
			<link rel="manifest" href="<?php echo PREPEND_PATH; ?>manifest.json.php">
			<meta name="theme-color" content="#000000">
		<?php } ?>

		<link rel="stylesheet" href="<?php echo PREPEND_PATH; ?>resources/initializr/css/<?php echo $theme; ?>.css">
		<?php echo $bootstrap3d; ?>
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
		<script src="<?php echo PREPEND_PATH; ?>resources/select2/select2.min.js"></script>
		<script src="<?php echo PREPEND_PATH; ?>resources/timepicker/bootstrap-timepicker.min.js"></script>
		<script src="<?php echo PREPEND_PATH; ?>resources/datepicker/js/datepicker.packed.js"></script>
		<script src="<?php echo PREPEND_PATH; ?>resources/bootstrap-datetimepicker/bootstrap-datetimepicker.min.js"></script>
		<script src="<?php echo PREPEND_PATH; ?>resources/hotkeys/jquery.hotkeys.min.js"></script>
		<script src="<?php echo PREPEND_PATH; ?>nicEdit.js"></script>

		<script>
			<?php
				$jsAppConfig = [
					'imgFolder' => rtrim(config('adminConfig')['baseUploadPath'], '\\/') . '/',
					'url' => application_url(),
					'uri' => application_uri(),
					'googleAPIKey' => config('adminConfig')['googleAPIKey'],
					'appTitle' => APP_TITLE,
				];
			?>
			var AppGini = AppGini || {};

			AppGini.config = <?php echo json_encode($jsAppConfig, JSON_PARTIAL_OUTPUT_ON_ERROR); ?>

			<?php if(!defined('APPGINI_SETUP')) { ?>
				// register service worker for PWA and improve caching
				if('serviceWorker' in navigator) {
					window.addEventListener('load', function() {
						navigator.serviceWorker.register('<?php echo PREPEND_PATH; ?>service-worker.js?<?php echo filemtime( __DIR__ . '/service-worker.js'); ?>').then(function(registration) {
							console.info('ServiceWorker registration successful with scope: ', registration.scope);
						}, function(err) {
							console.error('ServiceWorker registration failed: ', err);
						});
					});
				}
			<?php } ?>
		</script>

		<?php if(!defined('APPGINI_SETUP')) { ?>
			<script src="<?php echo PREPEND_PATH; ?>lang.js.php?<?php echo filemtime( __DIR__ . '/language.php'); ?>"></script>
		<?php } ?>
		<script src="<?php echo PREPEND_PATH; ?>common.js?<?php echo filemtime( __DIR__ . '/common.js'); ?>"></script>
		<script src="<?php echo PREPEND_PATH; ?>shortcuts.js?<?php echo filemtime( __DIR__ . '/shortcuts.js'); ?>"></script>
		<?php if(isset($x->TableName) && is_file(__DIR__ . "/hooks/{$x->TableName}-tv.js") && strpos(@$x->ContentType, 'tableview') !== false) { ?>
			<script src="<?php echo PREPEND_PATH; ?>hooks/<?php echo $x->TableName; ?>-tv.js?<?php echo @filemtime(__DIR__ . "/hooks/{$x->TableName}-tv.js"); ?>"></script>
		<?php } ?>

	</head>
	<body>
		<div class="users-area container theme-<?php echo $theme; ?> <?php echo getUserThemeCompact(); ?><?php echo BOOTSTRAP_3D_EFFECTS ? ' theme-3d' : ''; ?>">
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
				<div class="modal-top-spacer"></div>
			<?php } ?>

			<?php if(!defined('APPGINI_SETUP') && is_file(__DIR__ . '/hooks/header-extras.php')) { include(__DIR__ . '/hooks/header-extras.php'); } ?>
			<!-- Add header template below here .. -->

