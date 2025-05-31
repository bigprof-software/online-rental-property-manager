<?php
	include_once(__DIR__ . '/lib.php');
	if(MULTI_TENANTS) redirect(call_user_func_array('SaaS::profileUrl', []), true);

	$adminConfig = config('adminConfig');

	/* no access for guests */
	if(Authentication::isGuest()) {
		@header('HTTP/1.0 403 Forbidden');
		@header('Location: index.php?signIn=1');
		exit;
	}

	$mi = getMemberInfo();

	/* save profile */
	if(Request::val('action') == 'saveProfile') {
		if(!csrf_token(true)) {
			@header('HTTP/1.0 403 Forbidden');
			echo "{$Translation['error:']} {$Translation['csrf token expired or invalid']}";
			exit;
		}

		/* process inputs */
		$email = isEmail(Request::val('email'));
		$custom1 = makeSafe(Request::val('custom1'));
		$custom2 = makeSafe(Request::val('custom2'));
		$custom3 = makeSafe(Request::val('custom3'));
		$custom4 = makeSafe(Request::val('custom4'));

		/* validate email */
		if(!$email) {
			@header('HTTP/1.0 400 Bad Request');
			echo "{$Translation['error:']} {$Translation['email invalid']}";
			echo "<script>\$j('#email').focus();</script>";
			exit;
		}

		/* update profile */
		$updateDT = date($adminConfig['PHPDateTimeFormat']);
		sql("UPDATE `membership_users` set email='$email', custom1='$custom1', custom2='$custom2', custom3='$custom3', custom4='$custom4', comments=CONCAT_WS('\\n', comments, 'member updated his profile on $updateDT from IP address {$mi['IP']}') WHERE memberID='{$mi['username']}'", $eo);

		// hook: member_activity
		if(function_exists('member_activity')) {
			$args = [];
			member_activity($mi, 'profile', $args);
		}

		exit;
	}

	/* change password */
	if(Request::val('action') == 'changePassword' && $mi['username'] != $adminConfig['adminUsername']) {
		if(!csrf_token(true)) {
			@header('HTTP/1.0 403 Forbidden');
			echo "{$Translation['error:']} {$Translation['csrf token expired or invalid']}";
			exit;
		}

		/* process inputs */
		$oldPassword = Request::val('oldPassword');
		$newPassword = Request::val('newPassword');

		/* validate password */
		$hash = sqlValue("SELECT `passMD5` FROM `membership_users` WHERE memberID='{$mi['username']}'");
		if(!password_match($oldPassword, $hash)) {
			@header('HTTP/1.0 400 Bad Request');
			echo "{$Translation['error:']} {$Translation['Wrong password']}";
			?>
			<script>
				$j(function() {
					$j('#old-password').focus();
				})
			</script>
			<?php
			exit;
		}
		if(strlen($newPassword) < 4) {
			@header('HTTP/1.0 400 Bad Request');
			echo "{$Translation['error:']} {$Translation['password invalid']}";
			?>
			<script>
				$j(function() {
					$j('#new-password').focus();
				})
			</script>
			<?php

			exit;      
		}

		/* update password */
		$updateDT = date($adminConfig['PHPDateTimeFormat']);
		sql("UPDATE `membership_users` set `passMD5`='" . password_hash($newPassword, PASSWORD_DEFAULT) . "', `comments`=CONCAT_WS('\\n', comments, 'member changed his password on $updateDT from IP address {$mi['IP']}') WHERE memberID='{$mi['username']}'", $eo);

		// hook: member_activity
		if(function_exists('member_activity')) {
			$args = [];
			member_activity($mi, 'password', $args);
		}

		exit;
	}

	/* update theme */
	$themes = getThemesList();

	if(Request::val('action') == 'updateTheme') {
		if(!csrf_token(true)) {
			@header('HTTP/1.0 403 Forbidden');
			echo "{$Translation['error:']} {$Translation['csrf token expired or invalid']}";
			exit;
		}

		/* process inputs */
		$theme = Request::val('theme');
		if(!in_array($theme, $themes)) {
			@header('HTTP/1.0 400 Bad Request');
			echo "{$Translation['error:']} {$Translation['invalid theme']}";
			exit;
		}
		$themeCompact = Request::val('themeCompact') == '1';

		/* update theme in user data */
		setUserData('theme', $theme);
		setUserData('themeCompact', $themeCompact);

		exit;
	}

	/* get user theme */
	$theme = getUserTheme();

	/* get user compact theme setting, or set it to default if not set */
	$themeCompact = getUserData('themeCompact');
	if($themeCompact === null) {
		$themeCompact = THEME_COMPACT;
		setUserData('themeCompact', $themeCompact);
	}

	/* get profile info */
	/* 
		$mi already contains the profile info, as documented at: 
		https://bigprof.com/appgini/help/advanced-topics/hooks/memberInfo-array/

		custom field names are stored in $adminConfig['custom1'] to $adminConfig['custom4']
	*/
	$permissions = [];
	$userTables = getTableList();
	if(is_array($userTables))  foreach($userTables as $tn => $tc) {
		$permissions[$tn] = getTablePermissions($tn);
	}

	/* the profile page view */
	include_once(__DIR__ . '/header.php'); ?>

	<div class="page-header">
		<h1><?php echo sprintf($Translation['Hello user'], htmlspecialchars($mi['username'])); ?></h1>
	</div>
	<div id="notify" class="alert alert-success" style="display: none;"></div>
	<div id="loader" class="alert alert-warning" style="display: none;"><i class="glyphicon glyphicon-refresh"></i> <?php echo $Translation['Loading ...']; ?></div>

	<?php echo csrf_token(); ?>
	<div class="row">

		<div class="col-md-6">

			<!-- user info form -->
			<div class="panel panel-info">
				<div class="panel-heading">
					<h3 class="panel-title">
						<i class="glyphicon glyphicon-info-sign"></i>
						<?php echo $Translation['Your info']; ?>
					</h3>
				</div>
				<div class="panel-body">
					<fieldset id="profile">
						<div class="form-group">
							<label for="email"><?php echo $Translation['email']; ?></label>
							<input type="email" id="email" name="email" value="<?php echo html_attr($mi['email']); ?>" class="form-control">
						</div>

						<?php for($i=1; $i<5; $i++) { ?>
							<div class="form-group">
								<label for="custom<?php echo $i; ?>"><?php echo htmlspecialchars($adminConfig['custom'.$i]); ?></label>
								<input type="text" id="custom<?php echo $i; ?>" name="custom<?php echo $i; ?>" value="<?php echo html_attr($mi['custom'][$i-1]); ?>" class="form-control">
							</div>
						<?php } ?>

						<div class="row">
							<div class="col-md-4 col-md-offset-4">
								<button id="update-profile" class="btn btn-success btn-block" type="button"><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation['Update profile']; ?></button>
							</div>
						</div>
					</fieldset>
				</div>
			</div>

			<!-- access permissions -->
			<div class="panel panel-info">
				<div class="panel-heading">
					<h3 class="panel-title">
						<i class="glyphicon glyphicon-lock"></i>
						<?php echo $Translation['Your access permissions']; ?>
					</h3>
				</div>
				<div class="panel-body">
					<p><strong><?php echo $Translation['Legend']; ?></strong></p>
					<div class="row">
						<div class="col-xs-2 col-md-1 text-right"><img src="admin/images/stop_icon.gif"></div>
						<div class="col-xs-10 col-md-5"><?php echo $Translation['Not allowed']; ?></div>
						<div class="col-xs-2 col-md-1 text-right"><img src="admin/images/member_icon.gif"></div>
						<div class="col-xs-10 col-md-5"><?php echo $Translation['Only your own records']; ?></div>
					</div>
					<div class="row">
						<div class="col-xs-2 col-md-1 text-right"><img src="admin/images/members_icon.gif"></div>
						<div class="col-xs-10 col-md-5"><?php echo $Translation['All records owned by your group']; ?></div>
						<div class="col-xs-2 col-md-1 text-right"><img src="admin/images/approve_icon.gif"></div>
						<div class="col-xs-10 col-md-5"><?php echo $Translation['All records']; ?></div>
					</div>

					<p class="vspacer-lg"></p>

					<div class="table-responsive">
						<table class="table table-striped table-hover table-bordered" id="permissions">
							<thead>
								<tr>
									<th><?php echo $Translation['Table']; ?></th>
									<th class="text-center"><?php echo $Translation['View']; ?></th>
									<th class="text-center"><?php echo $Translation['Add New']; ?></th>
									<th class="text-center"><?php echo $Translation['Edit']; ?></th>
									<th class="text-center"><?php echo $Translation['Delete']; ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach($permissions as $tn => $perm) { ?>
									<tr>
										<td><img src="<?php echo $userTables[$tn][2]; ?>"> <a href="<?php echo $tn; ?>_view.php"><?php echo $userTables[$tn][0]; ?></a></td>
										<td class="text-center"><img src="admin/images/<?php echo permIcon($perm['view']); ?>"></td>
										<td class="text-center"><img src="admin/images/<?php echo ($perm['insert'] ? 'approve' : 'stop'); ?>_icon.gif"></td>
										<td class="text-center"><img src="admin/images/<?php echo permIcon($perm['edit']); ?>"></td>
										<td class="text-center"><img src="admin/images/<?php echo permIcon($perm['delete']); ?>"></td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>

		</div>

		<div class="col-md-6">

			<!-- group and IP address -->
			<div class="panel panel-info">
				<div class="panel-body">
					<div class="form-group">
						<label><?php echo $Translation['Your IP address']; ?></label>
						<div class="form-control-static"><?php echo $mi['IP']; ?></div>
					</div>
				</div>
			</div>

			<!-- group and IP address -->
			<div class="panel panel-info">
				<div class="panel-body">
					<div class="form-group">
						<label><?php echo $Translation['group']; ?></label>
						<div class="form-control-static"><?php echo htmlspecialchars($mi['group']); ?></div>
					</div>
				</div>
			</div>

			<?php if($mi['username'] == $adminConfig['adminUsername']) { ?>
				<!-- change admin password from the admin area -->
				<div class="panel panel-info">
					<div class="panel-heading">
						<h3 class="panel-title">
							<i class="glyphicon glyphicon-asterisk"></i><i class="glyphicon glyphicon-asterisk"></i>
							<a href="admin/pageSettings.php"><?php echo $Translation['Change your password']; ?></a>
						</h3>
					</div>
					<div class="panel-body">
						<a href="admin/pageSettings.php"><?php echo $Translation['admin settings']; ?></a>
						<i class="glyphicon glyphicon-chevron-right rtl-mirror"></i>
						<?php echo $Translation['Preconfigured users and groups']; ?>
						<i class="glyphicon glyphicon-chevron-right rtl-mirror"></i>
						<?php echo $Translation['admin password']; ?>
					</div>
				</div>
			<?php } else { ?>
				<!-- change password -->
				<div class="panel panel-info">
					<div class="panel-heading">
						<h3 class="panel-title">
							<i class="glyphicon glyphicon-asterisk"></i><i class="glyphicon glyphicon-asterisk"></i>
							<?php echo $Translation['Change your password']; ?>
						</h3>
					</div>
					<div class="panel-body">
						<fieldset id="change-password">
							<div id="password-change-form">

								<div class="form-group">
									<label for="old-password"><?php echo $Translation['Old password']; ?></label>
									<input type="password" id="old-password" autocomplete="off" class="form-control">
								</div>

								<div class="form-group">
									<label for="new-password"><?php echo $Translation['new password']; ?></label>
									<input type="password" id="new-password" autocomplete="new-password" class="form-control">
									<p id="password-strength" class="help-block"></p>
								</div>

								<div class="form-group">
									<label for="confirm-password"><?php echo $Translation['confirm password']; ?></label>
									<input type="password" id="confirm-password" autocomplete="new-password" class="form-control">
									<p id="confirm-status" class="help-block"></p>
								</div>

								<div class="row">
									<div class="col-md-4 col-md-offset-4">
										<button id="update-password" class="btn btn-success btn-block" type="button"><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation['Update password']; ?></button>
									</div>
								</div>

							</div>
						</fieldset>
					</div>
				</div>
			<?php } ?>

			<?php if(!NO_THEME_SELECTION) { ?>
				<!-- theme selector -->
				<div class="panel panel-info">
					<div class="panel-heading">
						<h3 class="panel-title">
							<i class="glyphicon glyphicon-adjust"></i>
							<?php echo $Translation['theme selector']; ?>
						</h3>
					</div>
					<div class="panel-body" id="theme-selector">
						<div class="text-center bspacer-lg">
							<div class="btn-group" id="theme-compact" style="width: 90%; margin: 0 auto; max-width: 50em;">
								<button type="button" class="btn btn-lg btn-default<?php echo ($themeCompact ? '' : ' active'); ?>" data-value="0" style="width: 50%;">
									<i class="glyphicon glyphicon-plus"></i>
									<?php echo $Translation['large text']; ?>
									<i class="glyphicon glyphicon-text-size"></i>
								</button>
								<button type="button" class="btn btn-lg btn-default<?php echo ($themeCompact ? ' active' : ''); ?>" data-value="1" style="width: 50%;">
									<i class="glyphicon glyphicon-minus"></i>
									<?php echo $Translation['small text']; ?>
									<i class="glyphicon glyphicon-text-size"></i>
								</button>
							</div>
						</div>

						<div class="block-flex flex-wrap" style="column-gap: 1em;">
						<?php
							foreach($themes as $i => $tn) {
								$checked = ($theme == $tn ? ' checked' : '');
								?>
								<label class="text-center highlight-checked">
									<input type="radio" name="theme" value="<?php echo $tn; ?>"<?php echo $checked; ?>>
									<img src="resources/initializr/css/<?php echo $tn; ?>-desktop.png" class="hidden-xs" alt="<?php echo $tn; ?>" title="<?php echo $tn; ?>" style="height: 9em;">
									<img src="resources/initializr/css/<?php echo $tn; ?>-mobile.png" class="visible-xs" alt="<?php echo $tn; ?>" title="<?php echo $tn; ?>" style="height: 9em;">
									<div><?php echo ucfirst($tn); ?></div>
								</label>
								<?php
							}
						?>
						</div>

						<div class="text-center vspacer-lg">
							<button id="update-theme" class="btn btn-success btn-lg vspacer-lg" type="button" style="width: 15em;">
								<i class="glyphicon glyphicon-ok"></i>
								<?php echo $Translation['update theme']; ?>
							</button>
							<span class="hspacer-lg"></span>
							<button id="default-theme" class="btn btn-default btn-lg vspacer-lg" type="button">
								<i class="glyphicon glyphicon-refresh"></i> <?php echo $Translation['default theme']; ?>
							</button>
						</div>
					</div>
				</div>

				<script>
					$j(() => {
						const defaultTheme = '<?php echo DEFAULT_THEME; ?>';
						const defaultCompact = '<?php echo THEME_COMPACT ? '1' : '0'; ?>';

						// handle save theme
						const updateTheme = () => {
							// disable buttons and radios
							$j('#theme-compact .btn').prop('disabled', true);
							$j('input[name="theme"]').prop('disabled', true);

							post2(
								'<?php echo basename(__FILE__); ?>', {
									action: 'updateTheme',
									theme: $j('input[name="theme"]:checked').val(),
									themeCompact: $j('#theme-compact .btn.active').data('value'),
									csrf_token: $j('#csrf_token').val()
								},
								'notify', 'theme-selector', 'loader',
								'<?php echo basename(__FILE__); ?>?notify=<?php echo urlencode($Translation['your theme was updated successfully']); ?>'
							);
						};

						// handle click on theme compact buttons
						$j('#theme-compact .btn').on('click', function() {
							$j('#theme-compact .btn').removeClass('active');
							$j(this).addClass('active');
						});

						// handle update theme button click
						$j('#update-theme').on('click', function() {
							updateTheme();
						});

						// handle default theme button click
						$j('#default-theme').on('click', function() {
							$j('input[name="theme"]').prop('checked', false);
							$j('#theme-compact .btn').removeClass('active');
							$j('#theme-compact .btn[data-value="' + defaultCompact + '"]').addClass('active');
							$j('input[name="theme"][value="' + defaultTheme + '"]').prop('checked', true);

							updateTheme();
						});

					});
				</script>
			<?php } ?>

		</div>

	</div>


	<script>
		$j(function() {
			<?php
				/* Is there a notification to display? */
				$notify = '';
				if(Request::has('notify')) $notify = addslashes(strip_tags(Request::val('notify')));
			?>
			<?php if($notify) { ?> notify('<?php echo $notify; ?>'); <?php } ?>

			$j('#update-profile').on('click', function() {
				post2(
					'<?php echo basename(__FILE__); ?>',
					{ action: 'saveProfile', email: $j('#email').val(), custom1: $j('#custom1').val(), custom2: $j('#custom2').val(), custom3: $j('#custom3').val(), custom4: $j('#custom4').val(), csrf_token: $j('#csrf_token').val() },
					'notify', 'profile', 'loader', 
					'<?php echo basename(__FILE__); ?>?notify=<?php echo urlencode($Translation['Your profile was updated successfully']); ?>'
				);
			});

			<?php if($mi['username'] != $adminConfig['adminUsername']) { ?>
				$j('#update-password').on('click', function() {
					/* make sure passwords match */
					if($j('#new-password').val() != $j('#confirm-password').val()) {
						$j('#notify').addClass('alert-danger');
						notify('<?php echo "{$Translation['error:']} ".addslashes($Translation['password no match']); ?>');
						$j('#confirm-password').focus();
						return false;
					}

					post2(
						'<?php echo basename(__FILE__); ?>',
						{ action: 'changePassword', oldPassword: $j('#old-password').val(), newPassword: $j('#new-password').val(), csrf_token: $j('#csrf_token').val() },
						'notify', 'password-change-form', 'loader', 
						'<?php echo basename(__FILE__); ?>?notify=<?php echo urlencode($Translation['Your password was changed successfully']); ?>'
					);
				});

				/* password strength feedback */
				$j('#new-password').on('keyup', function() {
					var ps = passwordStrength($j('#new-password').val(), '<?php echo addslashes($mi['username']); ?>');

					if(ps == 'strong')
						$j('#password-strength').html('<?php echo $Translation['Password strength: strong']; ?>').css({color: 'Green'});
					else if(ps == 'good')
						$j('#password-strength').html('<?php echo $Translation['Password strength: good']; ?>').css({color: 'Gold'});
					else
						$j('#password-strength').html('<?php echo $Translation['Password strength: weak']; ?>').css({color: 'Red'});
				});

				/* inline feedback of confirm password */
				$j('#confirm-password').on('keyup', function() {
					if($j('#confirm-password').val() != $j('#new-password').val() || !$j('#confirm-password').val().length) {
						$j('#confirm-status').html('<i class="glyphicon glyphicon-remove text-danger"></i>');
					} else {
						$j('#confirm-status').html('<i class="glyphicon glyphicon-ok text-success"></i>');
					}
				});
			<?php } ?>
		});

		function notify(msg) {
			$j('#notify').html(msg).fadeIn();
			window.setTimeout(function() { $j('#notify').fadeOut(); }, 15000);
		}
	</script>

	<?php
		/* return icon file name based on given permission value */
		function permIcon($perm) {
			switch($perm) {
				case 1:
					return 'member_icon.gif';
				case 2:
					return 'members_icon.gif';
				case 3:
					return 'approve_icon.gif';
				default:
					return 'stop_icon.gif';
			}
		}
	?>

	<?php include_once(__DIR__ . '/footer.php'); ?>
