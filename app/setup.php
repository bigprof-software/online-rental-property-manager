<?php
	/* initial preps and includes */
	define('APPGINI_SETUP', true); /* needed in included files to tell that this is the setup script */
	define('DEFAULT_MYSQL_PORT', ini_get('mysqli.default_port'));

	include_once(__DIR__ . '/settings-manager.php');
	if(MULTI_TENANTS) denyAccess('Access denied');

	/*
		Determine execution scenario ...
		this script is called in 1 of 5 scenarios:
			1. to display the setup instructions no Request::val('show-form')
			2. to display the setup form Request::val('show-form'), no Request::val('test'), no Request::val('submit')
			3. to test the db info, Request::val('test') no Request::val('submit')
			4. to save setup data, Request::val('submit')
			5. to show final success message, Request::val('finish')
		below here, we determine which scenario is being called
	*/
	$submit = Request::has('submit');
	$test = Request::has('test');
	$form = Request::has('show-form');
	$finish = Request::has('finish');

	/* if config file already exists, no need to continue */
	if(!$finish && detect_config(false)) {
		@header('Location: index.php');
		exit;
	}

	if(maintenance_mode()) die($Translation['maintenance mode']);

	Authentication::initSession();

	// check if captcha supported and verified
	if(FORCE_SETUP_CAPTCHA && Captcha::available() && !Captcha::verified()) {
		http_response_code(401); // Unauthorized to allow blocking of brute force attacks in WAFs
		die(Captcha::standAloneForm('setup.php'));
	}

	/* include page header, unless we're testing db connection (ajax) */
	$_REQUEST['Embedded'] = 1; /* to prevent displaying the navigation bar */
	$x = new StdClass;
	$x->TableTitle = $Translation['Setup Data']; /* page title */
	if(!$test) include_once(__DIR__ . '/header.php');

	if($submit || $test) {

		/* receive posted data */
		if($submit) {
			$username = Request::val('username');
			if(!Authentication::validUsername($username)) $username = false;
			$email = isEmail(Request::val('email'));
			$password = Request::val('password');
			$confirmPassword = Request::val('confirmPassword');
		}
		$db_name = str_replace('`', '', Request::val('db_name'));
		$db_password = Request::val('db_password');
		$db_server = Request::val('db_server');
		$db_username = Request::val('db_username');
		$db_port = Request::val('db_port', DEFAULT_MYSQL_PORT);

		/* validate data */
		$errors = [];
		if($submit) {
			if(!$username) {
				$errors[] = $Translation['username invalid'];
			}
			if(strlen($password) < 4 || trim($password) != $password) {
				$errors[] = $Translation['password invalid'];
			}
			if($password != $confirmPassword) {
				$errors[] = $Translation['password no match'];
			}
			if(!$email) {
				$errors[] = $Translation['email invalid'];
			}
		}

		/* test database connection */
		if(strlen($db_port))
			if(!($connection = @db_connect($db_server, $db_username, $db_password, NULL, $db_port))) {
				$errors[] = $Translation['Database connection error'];
			}
		else
			if(!($connection = @db_connect($db_server, $db_username, $db_password))) {
				$errors[] = $Translation['Database connection error'];
			}

		if($connection !== false && !@db_select_db($db_name, $connection)) {
			// attempt to create the database
			if(!@db_query("CREATE DATABASE IF NOT EXISTS `$db_name`")) {
				$errors[] = @db_error($connection);
			} elseif(!@db_select_db($db_name, $connection)) {
				$errors[] = @db_error($connection);
			}
		}

		/* in case of validation errors, output them and exit */
		if(count($errors)) {
			if($test) {
				http_response_code(406);
				exit;
			}

			?>
				<div class="row">
					<div class="col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3">
						<h2 class="text-danger"><?php echo $Translation['The following errors occurred']; ?></h2>
						<div class="alert alert-danger"><ul><li><?php echo implode('</li><li>', $errors); ?></li></ul></div>
						<a class="btn btn-default btn-lg vspacer-lg" href="#" onclick="history.go(-1); return false;"><i class="glyphicon glyphicon-chevron-left"></i> <?php echo $Translation['< back']; ?></a>
					</div>
				</div>
			<?php
			include_once(__DIR__ . '/footer.php');
			exit;
		}

		/* if db test is successful, exit with HTTP status 200 OK */
		if($test) exit;

		/* create database tables */
		include_once(__DIR__ . '/updateDB.php');

		$defCfg = default_config();

		/* attempt to save db config file */
		$new_config = [
			'dbServer' => $db_server,
			'dbUsername' => $db_username,
			'dbPassword' => $db_password,
			'dbDatabase' => $db_name,
			'dbPort' => $db_port,
			'appURI' => formatUri(dirname($_SERVER['SCRIPT_NAME'])),
			'host' => (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] == '80' || $_SERVER['SERVER_PORT'] == '443' ? '' : ":{$_SERVER['SERVER_PORT']}")),

			'adminConfig' => [
				'adminUsername' => $username,
				'adminPassword' => password_hash($password, PASSWORD_DEFAULT),
				'senderEmail' => $email,
			] + $defCfg['adminConfig'],
		];

		$save_result = save_config($new_config);
		if($save_result !== true) {
			// display instructions for manually creating them if saving not successful
			$folder_path_formatted = '<strong>' . __DIR__ . '</strong>';
			?>
				<div class="row">
					<div class="col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3">
						<p style="background-color: white; padding: 20px; margin-bottom: 40px; border-radius: 4px;"><img src="logo.png"></p>
						<div class="alert alert-danger"><?php echo $Translation['error:'] . ' ' . $save_result['error']; ?></div>
						<?php printf($Translation['failed to create config instructions'], $folder_path_formatted); ?>
						<pre style="overflow: scroll; font-size: large;"><?php echo htmlspecialchars($save_result['config']); ?></pre>
					</div>
				</div>
			<?php
			exit;
		}


		/* sign in as admin if everything went ok */
		Authentication::signInAsAdmin();


		/* redirect to finish page using javascript */
		?>
		<script>
			jQuery(function() {
				var a = window.location.href + '?finish=1';

				if(jQuery('div[class="text-danger"]').length) {
					jQuery('body').append('<p class="text-center"><a href="' + a + '" class="btn btn-default vspacer-lg"><?php echo addslashes($Translation['Continue']); ?> <i class="glyphicon glyphicon-chevron-right"></i></a></p>');
				} else {
					jQuery('body').append('<div id="manual-redir" style="width: 400px; margin: 10px auto;">If not redirected automatically, <a href="<?php echo basename(__FILE__); ?>?finish=1">click here</a>!</div>');
					window.location = a;
				}
			});
		</script>
		<?php

		// exit
		include_once(__DIR__ . '/footer.php');
		exit;
	} elseif($finish) {
		detect_config();
	}
?>

	<div class="row"><div class="col-md-8 col-md-offset-2 col-lg-6 col-lg-offset-3">
	<?php if(!$form && !$finish) { /* show checks and instructions */

			/* initial checks */
			$checks = []; /* populate with ['class' => 'warning|danger', 'message' => 'error message'] */

			if(!extension_loaded('mysql') && !extension_loaded('mysqli'))
				$checks[] = [
					'class' => 'danger',
					'message' => 'ERROR: PHP is not configured to connect to MySQL on this machine. Please see <a href=https://www.php.net/manual/en/ref.mysql.php>this page</a> for help on how to configure MySQL.'
				];

			if(!extension_loaded('iconv'))
				$checks[] = [
					'class' => 'danger',
					'message' => 'PHP is not configured to use iconv on this machine. Some features of this application might not function correctly. Please see <a href=https://php.net/manual/en/book.iconv.php>this page</a> for help on how to configure iconv.'
				];

			if(!extension_loaded('gd'))
				$checks[] = [
					'class' => 'warning',
					'message' => 'PHP is not configured to use GD on this machine. This will prevent creating thumbnails of uploaded images. Please see <a href=https://php.net/manual/en/book.image.php>this page</a> for help on how to configure GD.'
				];

			if(!extension_loaded('xml'))
				$checks[] = [
					'class' => 'warning',
					'message' => 'PHP is not configured to use XML extension on this machine. This will prevent some app functions. If you\'re using a Windows server, make sure to enable XML extension in <code>php.ini</code>. If you\'re using a Linux server, you should install the appropriate <code>php-xml</code> package for your Linux flavor and PHP version.'
				];

			if(!extension_loaded('mbstring'))
				$checks[] = [
					'class' => 'warning',
					'message' => 'PHP is not configured to use mbstring extension on this machine. This will prevent some app functions. If you\'re using a Windows server, make sure to enable mbstring extension in <code>php.ini</code>. If you\'re using a Linux server, you should install the appropriate <code>php-mbstring</code> package for your Linux flavor and PHP version.'
				];

			if(!@is_writable(__DIR__ . '/images'))
				$checks[] = [
					'class' => 'warning',
					'message' => '<dfn><abbr title="' . __DIR__ . '/images">images</abbr></dfn> folder is not writeable (or doesn\'t exist). This will prevent file uploads from working correctly. Please create or set that folder as writeable.<br><br>For example, you might need to <code>chmod 777</code> using FTP, or if this is a linux system and you have shell access, better try using <code>chown -R www-data:www-data ' . __DIR__ . '</code>, replacing <i>www-data</i> with the actual username running the server process if necessary.'
				];

			if(count($checks) && !Request::has('test')) {
				$stop_setup = false;
				?>
				<div class="panel panel-warning vspacer-lg">
					<div class="panel-heading"><h3 class="panel-title">Warnings</h3></div>
					<div class="panel-body">
						<?php foreach($checks as $chk) { if($chk['class'] == 'danger') { $stop_setup = true; } ?>
							<div class="text-<?php echo $chk['class']; ?> vspacer-lg" style="text-direction: ltr; text-align: left;">
								<i class="glyphicon glyphicon-<?php echo ($chk['class'] == 'danger' ? 'remove' : 'exclamation-sign'); ?>"></i>
								<?php echo $chk['message']; ?>
							</div>
						<?php } ?>
						<a href="setup.php" class="btn btn-success pull-right vspacer-lg hspacer-lg"><i class="glyphicon glyphicon-refresh"></i> Recheck</a>
					</div>
					<?php if($stop_setup) { ?>
						<div class="panel-footer">You must fix at least the issues marked with <i class="glyphicon glyphicon-remove text-danger"></i> before continuing ...</div>
					<?php } ?>
				</div>
				<?php
				if($stop_setup) exit;
			}
		?>

		<div id="intro1" class="instructions">
			<p style="background-color: white; padding: 20px; margin-bottom: 40px; border-radius: 4px;"><img src="logo.png"></p>
			<p><?php echo $Translation['setup intro 1']; ?></p>
			<br><br>
			<p class="text-center"><button class="btn btn-default" id="show-intro2" type="button"><?php echo $Translation['Continue']; ?> <i class="glyphicon glyphicon-chevron-right"></i></button></p>
		</div>

		<div id="intro2" class="instructions" style="display: none;">
			<p style="background-color: white; padding: 20px; margin-bottom: 40px; border-radius: 4px;"><img src="logo.png"></p>
			<p><?php echo $Translation['setup intro 2']; ?></p>
			<br><br>
			<p class="text-center"><button class="btn btn-success btn-lg" id="show-login-form" type="button"><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation['Lets go']; ?></button></p>
		</div>

	<?php } elseif($form) { /* show setup form */ ?>

		<div class="page-header"><h1><?php echo $Translation['Setup Data']; ?></h1></div>

		<form method="post" action="<?php echo basename(__FILE__); ?>" onSubmit="return jsValidateSetup();" id="login-form" style="display: none;">
			<fieldset id="database" class="form-horizontal">
				<legend><?php echo $Translation['Database Information']; ?></legend>

				<div class="form-group">
					<label for="db_server" class="control-label col-sm-4"><?php echo $Translation['mysql server']; ?></label>
					<div class="col-sm-8">
						<div class="input-group">
							<input type="text" class="form-control" id="db_server" name="db_server" placeholder="<?php echo htmlspecialchars($Translation['mysql server']); ?>" value="localhost">
							<span class="input-group-btn">
								<button data-toggle="collapse" tabindex="-1" data-target="#db_server-help" class="btn btn-info" type="button"><i class="glyphicon glyphicon-info-sign"></i></button>
							</span>
						</div>
						<span class="help-block collapse" id="db_server-help"><?php echo $Translation['db_server help']; ?></span>
					</div>
				</div>

				<div class="form-group">
					<label for="db_name" class="control-label col-sm-4"><?php echo $Translation['mysql db']; ?></label>
					<div class="col-sm-8">
						<div class="input-group">
							<input type="text" class="form-control" id="db_name" name="db_name" placeholder="<?php echo htmlspecialchars($Translation['mysql db']); ?>">
							<span class="input-group-btn">
								<button data-toggle="collapse" tabindex="-1" data-target="#db_name-help" class="btn btn-info" type="button"><i class="glyphicon glyphicon-info-sign"></i></button>
							</span>
						</div>
						<span class="help-block collapse" id="db_name-help"><?php echo $Translation['db_name help']; ?></span>
					</div>
				</div>

				<div class="form-group">
					<label for="db_username" class="control-label col-sm-4"><?php echo $Translation['mysql username']; ?></label>
					<div class="col-sm-8">
						<div class="input-group">
							<input type="text" class="form-control" id="db_username" name="db_username" placeholder="<?php echo htmlspecialchars($Translation['mysql username']); ?>">
							<span class="input-group-btn">
								<button data-toggle="collapse" tabindex="-1" data-target="#db_username-help" class="btn btn-info" type="button"><i class="glyphicon glyphicon-info-sign"></i></button>
							</span>
						</div>
						<span class="help-block collapse" id="db_username-help"><?php echo $Translation['db_username help']; ?></span>
					</div>
				</div>

				<div class="form-group">
					<label for="db_password" class="control-label col-sm-4"><?php echo $Translation['mysql password']; ?></label>
					<div class="col-sm-8">
						<div class="input-group">
							<input type="password" class="form-control" id="db_password" name="db_password" placeholder="<?php echo htmlspecialchars($Translation['mysql password']); ?>">
							<span class="input-group-btn">
								<button data-toggle="collapse" tabindex="-1" data-target="#db_password-help" class="btn btn-info" type="button"><i class="glyphicon glyphicon-info-sign"></i></button>
							</span>
						</div>
						<span class="help-block collapse" id="db_password-help"><?php echo $Translation['db_password help']; ?></span>
					</div>
				</div>

				<div class="form-group">
					<label for="db_port" class="control-label col-sm-4"><?php echo $Translation['mysql port']; ?></label>
					<div class="col-sm-8">
						<div class="input-group">
							<input type="number" class="form-control" id="db_port" name="db_port" placeholder="<?php echo htmlspecialchars($Translation['mysql port']); ?>" value="<?php echo htmlspecialchars(DEFAULT_MYSQL_PORT); ?>">
							<span class="input-group-btn">
								<button data-toggle="collapse" tabindex="-1" data-target="#db_port-help" class="btn btn-info" type="button"><i class="glyphicon glyphicon-info-sign"></i></button>
							</span>
						</div>
						<span class="help-block collapse" id="db_port-help"><?php printf($Translation['db_port help'], DEFAULT_MYSQL_PORT); ?></span>
					</div>
				</div>

				<div id="db_test" class="alert hidden"></div>
			</fieldset>

			<fieldset class="form-horizontal" id="inputs">
				<legend><?php echo $Translation['Admin Information']; ?></legend>

				<div class="row form-group">
					<label for="username" class="control-label col-sm-4"><?php echo $Translation['username']; ?></label>
					<div class="col-sm-8">
						<div class="input-group">
							<input type="text" required class="form-control" id="username" name="username" placeholder="<?php echo htmlspecialchars($Translation['username']); ?>">
							<span class="input-group-btn">
								<button data-toggle="collapse" tabindex="-1" data-target="#username-help" class="btn btn-info" type="button"><i class="glyphicon glyphicon-info-sign"></i></button>
							</span>
						</div>
						<span class="help-block collapse" id="username-help"><?php echo $Translation['username help']; ?></span>
					</div>
				</div>

				<div class="form-group">
					<label for="email" class="control-label col-sm-4"><?php echo $Translation['email']; ?></label>
					<div class="col-sm-8">
						<div class="input-group">
							<input type="text" required class="form-control" id="email" name="email" placeholder="<?php echo htmlspecialchars($Translation['email']); ?>">
							<span class="input-group-btn">
								<button data-toggle="collapse" tabindex="-1" data-target="#email-help" class="btn btn-info" type="button"><i class="glyphicon glyphicon-info-sign"></i></button>
							</span>
						</div>
						<span class="help-block collapse" id="email-help"><?php echo $Translation['email help']; ?></span>
					</div>
				</div>
			</fieldset>

			<div class="row">
				<div class="col-sm-6">
					<div class="form-group">
						<label for="password" class="control-label"><?php echo $Translation['password']; ?></label>
						<div class="input-group">
							<input type="password" autocomplete="new-password" required class="form-control" id="password" name="password" placeholder="<?php echo htmlspecialchars($Translation['password']); ?>">
							<span class="input-group-btn">
								<button data-toggle="collapse" tabindex="-1" data-target="#password-help" class="btn btn-info" type="button"><i class="glyphicon glyphicon-info-sign"></i></button>
							</span>
						</div>
						<span class="help-block collapse" id="password-help"><?php echo $Translation['password help']; ?></span>
					</div>
				</div>
				<div class="col-sm-6">
					<div class="form-group">
						<label for="confirmPassword" class="control-label"><?php echo $Translation['confirm password']; ?></label>
						<input type="password" autocomplete="new-password" required class="form-control" id="confirmPassword" name="confirmPassword" placeholder="<?php echo htmlspecialchars($Translation['confirm password']); ?>">
					</div>
				</div>
			</div>

			<div class="row" style="margin-top: 2em;">
				<div class="col-sm-offset-3 col-sm-6 col-lg-offset-4 col-lg-4">
					<button class="btn btn-primary btn-lg btn-block" value="submit" id="submit" type="submit" name="submit">
						<i class="glyphicon glyphicon-ok"></i> 
						<?php echo $Translation['Submit']; ?>
					</button>
				</div>
			</div>
		</form>

	<?php } elseif($finish) { ?>

		<?php
			// make sure this is an admin
			if(Authentication::getAdmin() === false) {
				?>
				<div id="manual-redir" style="width: 400px; margin: 10px auto;">If not redirected automatically, <a href="index.php">click here</a>!</div>
				<script>
					window.location = 'index.php';
				</script>
				<?php
				exit;
			}
		?>

		<div class="instructions">
			<p style="background-color: white; padding: 20px; margin-bottom: 40px; border-radius: 4px;"><img src="logo.png"></p>
			<div class="panel panel-success">
				<div class="panel-heading">
					<h3 class="panel-title"><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation['setup finished']; ?></h3>
				</div>
				<div class="panel-content">
					<ul id="next-actions" class="nav nav-pills nav-stacked">
						<li class="acive"><a href="index.php"><i class="glyphicon glyphicon-play"></i> <b><?php echo $Translation['setup next 1']; ?></b></a></li>
						<li><a href="import-csv.php"><i class="glyphicon glyphicon-upload"></i> <?php echo $Translation['setup next 2']; ?></a></li>
						<li><a href="admin/pageHome.php"><i class="glyphicon glyphicon-cog"></i> <?php echo $Translation['setup next 3']; ?></a></li>
					</ul>
				</div>
			</div>
		</div>

	<?php } ?>
	</div></div>

	<script>
	<?php if(!$form && !$finish) { ?>
		$j(function() {
			$j('#show-intro2').on('click', function() {
				$j('#intro1').addClass('hidden');
				$j('#intro2')[0].appear({ duration: 2 });
			});
			$j('#show-login-form').on('click', function() {
				var a = window.location.href;
				window.location = a + '?show-form=1';
			});
		});
	<?php } elseif($form) { ?>
		$j(function() {
			/* password strength feedback */
			$j('#password').on('keyup', function() {
				var ps = passwordStrength($j('#password').val(), $j('#username').val());

				if(ps == 'strong') {
					$j('#password').parents('.form-group').removeClass('has-error has-warning').addClass('has-success');
					$j('#password').attr('title', '<?php echo htmlspecialchars($Translation['Password strength: strong']); ?>');
				} else if(ps == 'good') {
					$j('#password').parents('.form-group').removeClass('has-error has-success').addClass('has-warning');
					$j('#password').attr('title', '<?php echo htmlspecialchars($Translation['Password strength: good']); ?>');
				} else {
					$j('#password').parents('.form-group').removeClass('has-success has-warning').addClass('has-error');
					$j('#password').attr('title', '<?php echo htmlspecialchars($Translation['Password strength: weak']); ?>');
				}
			});

			/* inline feedback of confirm password */
			$j('#confirmPassword').on('keyup', function() {
				if($j('#confirmPassword').val() != $j('#password').val() || !$j('#confirmPassword').val().length) {
					$j('#confirmPassword').parents('.form-group').removeClass('has-success').addClass('has-error');
				} else {
					$j('#confirmPassword').parents('.form-group').removeClass('has-error').addClass('has-success');
				}
			});

			/* inline feedback of email */
			$j('#email').on('change', function() {
				if(validateEmail($j('#email').val())) {
					$j('#email').parents('.form-group').removeClass('has-error').addClass('has-success');
				} else {
					$j('#email').parents('.form-group').removeClass('has-success').addClass('has-error');
				}
			});

			$j('#login-form').fadeIn(1000, function() { $j('#db_name').focus(); });

			$j('#db_name, #db_password, #db_server, #db_username, #db_port')
				.on('change', () => db_test(true));
		});

		/* validate data before submitting */
		function jsValidateSetup() {
			var p1 = $j('#password').val();
			var p2 = $j('#confirmPassword').val();
			var user = $j('#username').val();
			var email = $j('#email').val();

			/* passwords not matching? */
			if(p1 != p2) {
				modal_window({ message: '<div class="alert alert-danger"><?php echo addslashes($Translation['password no match']); ?></div>', title: "<?php echo addslashes($Translation['error:']); ?>", close: function() { jQuery('#confirmPassword').focus(); } });
				return false;
			}

			/* user invalid? */
			if(!/^\w[\w\. @]{3,100}$/.test(user) || /(@@|  |\.\.|___)/.test(user) || user.toLowerCase() == 'guest') {
				modal_window({ message: '<div class="alert alert-danger"><?php echo addslashes($Translation['username invalid']); ?></div>', title: "<?php echo addslashes($Translation['error:']); ?>", close: function() { jQuery('#username').focus(); } });
				return false;
			}

			return true;
		}

		/* test db info */
		var db_test_in_progress = false;
		function db_test(delay) {
			if(db_test_in_progress) return;

			if(
				!$j('#db_name').val().length
				|| !$j('#db_username').val().length
				|| !$j('#db_server').val().length
				|| $j('#db_password:focus').length
			) return;

			const testFeedback = $j('#db_test');

			setTimeout(function() {
				if(db_test_in_progress) return;

				db_test_in_progress = true;

				testFeedback
					.html(AppGini.Translate._map['checking database info'])
					.removeClass('alert-danger alert-success hidden')
					.addClass('alert-warning');

				$j.ajax({
					url: '<?php echo basename(__FILE__); ?>?test=1',
					type: 'POST',
					data: $j('#db_name, #db_server, #db_password, #db_username, #db_port').serialize(),
					success: (resp) => {
						testFeedback
							.addClass('alert-success')
							.html(AppGini.Translate._map['Database info is correct'])
					},
					error: () => {
						testFeedback
							.addClass('alert-danger')
							.html(AppGini.Translate._map['Database connection error'])
					},
					complete: () => {
						db_test_in_progress = false;
						testFeedback.removeClass('alert-warning');
						$j('<button type="button" class="btn btn-default btn-alert pull-right"><i class="glyphicon glyphicon-refresh"></i></button>')
							.on('click', () => db_test())
							.appendTo(testFeedback)
					}
				});
			}, delay ? 1000 : 50);
		}
	<?php } ?>
	</script>

	<style>
		legend{ font-weight: bold; }

		.instructions{
			padding: 30px;
			margin: 40px auto;
			border: solid 1px silver;
			border-radius: 4px;
		}
		.instructions img{ display: block; margin: auto; }
		.instructions .buttons{
			display: block;
			height: 1px;
			margin: 30px auto;
		}
		ul#next-actions li { font-size: 1.3em; }
		ul#next-actions { padding: 2em;     }
	</style>

<?php include_once(__DIR__ . '/footer.php');
