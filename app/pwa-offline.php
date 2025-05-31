<?php
	include_once(__DIR__ . '/lib.php');
	define('PREPEND_PATH', '');
?><!DOCTYPE html>
<html>
	<head>
		<meta charset="<?php echo datalist_db_encoding; ?>">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="description" content="">

		<title><?php echo APP_TITLE . ' | ' . $Translation['offline mode']; ?></title>
		<link id="browser_favicon" rel="shortcut icon" href="<?php echo PREPEND_PATH; ?>resources/images/appgini-icon.png">
		<?php echo StyleSheet(); ?>
	</head>
	<body>
		<div class="container text-center" style="padding-top: 20vh;">
			<span class="glyphicon glyphicon-cloud-download" style="font-size: 10em; color: #ccc;"></span>
			<h1><?php echo APP_TITLE . ' | ' . $Translation['offline mode']; ?></h1>
			<div class="well well-lg"><?php echo $Translation['offline mode message']; ?></div>

			<div class="btn-group">
				<button class="btn btn-default btn-lg" onclick="window.history.back();"><i class="glyphicon glyphicon-arrow-left"></i> <?php echo $Translation['Back']; ?></button>
				<button class="btn btn-default btn-lg" onclick="window.location.reload();"><i class="glyphicon glyphicon-refresh"></i> <?php echo $Translation['retry']; ?></button>
				<button class="btn btn-default btn-lg" onclick="window.location='index.php';"><i class="glyphicon glyphicon-home"></i> <?php echo $Translation['homepage']; ?></button>
			</div>
		</div>
	</body>
</html>
