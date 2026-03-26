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
		$navMenu = Request::val('navMenu') == 'horizontal' ? 'horizontal' : 'vertical';

		/* update theme in user data */
		setUserData('theme', $theme);
		setUserData('themeCompact', $themeCompact);
		setUserData('navMenu', $navMenu);

		exit;
	}

	/* update language */
	if(Request::val('action') == 'updateLanguage') {
		if(!csrf_token(true)) {
			@header('HTTP/1.0 403 Forbidden');
			echo "{$Translation['error:']} {$Translation['csrf token expired or invalid']}";
			exit;
		}

		$language = Request::val('language');
		$languages = getLanguagesList();
		if(!isset($languages[$language])) {
			$language = DEFAULT_LANGUAGE;
		}

		userLanguage($language);
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

	/* get user nav menu setting, or set it to default if not set */
	$navMenu = getUserData('navMenu');
	if($navMenu === null) {
		$navMenu = DEFAULT_NAV_MENU;
		setUserData('navMenu', $navMenu);
	}

	/* get available languages */
	$languages = getLanguagesList();
	$currentLanguage = userLanguage();
	if(!isset($languages[$currentLanguage])) {
		$languages[$currentLanguage] = $Translation['language'];
		asort($languages, SORT_NATURAL | SORT_FLAG_CASE);
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

	<ul class="nav nav-tabs" role="tablist">
		<li class="active"><a href="#tab-profile" role="tab" data-toggle="tab">
			<i class="glyphicon glyphicon-info-sign"></i>
			<?php echo $Translation['Your info']; ?>
		</a></li>
		<li><a href="#tab-permissions" role="tab" data-toggle="tab">
			<i class="glyphicon glyphicon-lock"></i>
			<?php echo $Translation['Your access permissions']; ?>
		</a></li>
		<li><a href="#tab-password" role="tab" data-toggle="tab">
			<i class="glyphicon glyphicon-asterisk"></i>
			<i class="glyphicon glyphicon-asterisk"></i>
			<?php echo $Translation['Change your password']; ?>
		</a></li>
		<li><a href="#tab-preferences" role="tab" data-toggle="tab">
			<i class="glyphicon glyphicon-adjust"></i>
			<?php echo $Translation['application preferences']; ?>
		</a></li>
	</ul>

	<div class="tab-content">
		<div class="tab-pane active" id="tab-profile">
			<!-- group and IP address -->
			<div class="panel panel-info">
				<div class="panel-body">
					<div class="form-group">
						<label><?php echo $Translation['Your IP address']; ?></label>
						<div class="form-control-static"><?php echo $mi['IP']; ?></div>
					</div>
				</div>
			</div>
			<div class="panel panel-info">
				<div class="panel-body">
					<div class="form-group">
						<label><?php echo $Translation['group']; ?></label>
						<div class="form-control-static"><?php echo htmlspecialchars($mi['group']); ?></div>
					</div>
				</div>
			</div>
			<!-- user info form -->
			<div class="panel panel-info">
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
								<button id="update-profile" class="btn btn-success btn-block btn-lg" type="button"><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation['Update profile']; ?></button>
							</div>
						</div>
					</fieldset>
				</div>
			</div>
		</div>
		<div class="tab-pane" id="tab-permissions">
			<!-- access permissions -->
			<div class="well vspacer-lg">
				<p class="text-bold"><?php echo $Translation['Legend']; ?></p>
				<div class="row">
					<div class="col-xs-6 col-md-3">
						<img src="admin/images/stop_icon.gif">
						<?php echo $Translation['Not allowed']; ?>
					</div>
					<div class="col-xs-6 col-md-3">
						<img src="admin/images/member_icon.gif">
						<?php echo $Translation['Only your own records']; ?>
					</div>
					<div class="col-xs-6 col-md-3">
						<img src="admin/images/members_icon.gif">
						<?php echo $Translation['All records owned by your group']; ?>
					</div>
					<div class="col-xs-6 col-md-3">
						<img src="admin/images/approve_icon.gif">
						<?php echo $Translation['All records']; ?>
					</div>
				</div>
			</div>
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
		<div class="tab-pane" id="tab-password">
			<!-- change password -->
			<?php if($mi['username'] == $adminConfig['adminUsername']) { ?>
				<div class="well text-center tspacer-lg">
					<a href="admin/pageSettings.php"><?php echo $Translation['admin settings']; ?></a>
					<i class="glyphicon glyphicon-chevron-right rtl-mirror"></i>
					<?php echo $Translation['Preconfigured users and groups']; ?>
					<i class="glyphicon glyphicon-chevron-right rtl-mirror"></i>
					<?php echo $Translation['admin password']; ?>
				</div>
			<?php } else { ?>
				<fieldset id="change-password" class="tspacer-lg">
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
								<button id="update-password" class="btn btn-success btn-block btn-lg" type="button"><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation['Update password']; ?></button>
							</div>
						</div>
					</div>
				</fieldset>
			<?php } ?>
		</div>
		<div class="tab-pane" id="tab-preferences">
			<div class="well tspacer-lg" id="language-selector">
				<div class="form-group">
					<svg style="width: 2em;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><!--!Font Awesome Free v7.1.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2026 Fonticons, Inc.-->
						<path d="M160 0c17.7 0 32 14.3 32 32l0 32 128 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-9.6 0-8.4 23.1c-16.4 45.2-41.1 86.5-72.2 122 14.2 8.8 29 16.6 44.4 23.5l50.4 22.4 62.2-140c5.1-11.6 16.6-19 29.2-19s24.1 7.4 29.2 19l128 288c7.2
							16.2-.1 35.1-16.2 42.2s-35.1-.1-42.2-16.2l-20-45-157.5 0-20 45c-7.2 16.2-26.1 23.4-42.2 16.2s-23.4-26.1-16.2-42.2l39.8-89.5-50.4-22.4c-23-10.2-45-22.4-65.8-36.4-21.3 17.2-44.6 32.2-69.5 44.7L78.3 380.6c-15.8 7.9-35
							1.5-42.9-14.3s-1.5-35 14.3-42.9l34.5-17.3c16.3-8.2 31.8-17.7 46.4-28.3-13.8-12.7-26.8-26.4-38.9-40.9L81.6 224.7c-11.3-13.6-9.5-33.8 4.1-45.1s33.8-9.5 45.1 4.1l10.2 12.2c11.5 13.9 24.1 26.8 37.4 38.7 27.5-30.4 49.2-66.1
							63.5-105.4l.5-1.2-210.3 0C14.3 128 0 113.7 0 96S14.3 64 32 64l96 0 0-32c0-17.7 14.3-32 32-32zM416 270.8L365.7 384 466.3 384 416 270.8z"/>
					</svg>
					<label for="language" class="text-bold"><?php echo $Translation['preferred language']; ?></label>
					<select id="language" name="language">
						<?php foreach($languages as $code => $name) { ?>
							<option value="<?php echo $code; ?>"<?php echo ($currentLanguage == $code ? ' selected' : ''); ?>><?php echo htmlspecialchars($name); ?></option>
						<?php } ?>
					</select>
				</div>
			</div>
			<?php if(!NO_THEME_SELECTION) { ?>
				<!-- theme selector -->
				<div id="theme-selector">
					<div class="bspacer-lg">
						<!-- theme compact switch buttons -->
						<div class="btn-group btn-group-lg tspacer-lg rspacer-lg" id="theme-compact" style="width: 90%; max-width: 50em;">
							<button type="button" class="btn btn-default<?php echo ($themeCompact ? '' : ' active'); ?>" data-value="0" style="width: 50%;">
								<i class="glyphicon glyphicon-plus"></i>
								<?php echo $Translation['large text']; ?>
								<i class="glyphicon glyphicon-text-size"></i>
							</button>
							<button type="button" class="btn btn-default<?php echo ($themeCompact ? ' active' : ''); ?>" data-value="1" style="width: 50%;">
								<i class="glyphicon glyphicon-minus"></i>
								<?php echo $Translation['small text']; ?>
								<i class="glyphicon glyphicon-text-size"></i>
							</button>
						</div>

						<!-- nav menu switch buttons -->
						<div class="btn-group btn-group-lg tspacer-lg rspacer-lg" id="nav-menu" style="width: 90%; max-width: 50em;">
							<button type="button" class="btn btn-default<?php echo ($navMenu == 'horizontal' ? ' active' : ''); ?>" data-value="horizontal" style="width: 50%;">
								<i class="glyphicon glyphicon-align-justify"></i>
								<?php echo $Translation['horizontal menu']; ?>
							</button>
							<button type="button" class="btn btn-default<?php echo ($navMenu == 'vertical' ? ' active' : ''); ?>" data-value="vertical" style="width: 50%;">
								<i class="glyphicon glyphicon-th-list"></i>
								<?php echo $Translation['vertical menu']; ?>
							</button>
						</div>

						<button id="default-theme" class="btn btn-warning btn-lg hspacer-lg tspacer-lg" type="button">
							<i class="glyphicon glyphicon-refresh"></i> <?php echo $Translation['default theme']; ?>
						</button>
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
				</div>
				<script>
					$j(() => {
						const defaultTheme = '<?php echo DEFAULT_THEME; ?>';
						const defaultCompact = '<?php echo THEME_COMPACT ? '1' : '0'; ?>';
						const defaultNavMenu = '<?php echo DEFAULT_NAV_MENU; ?>';

						// handle save theme
						const updateTheme = () => {
							// disable buttons and radios
							$j('#theme-compact .btn').prop('disabled', true);
							$j('#nav-menu .btn').prop('disabled', true);
							$j('input[name="theme"]').prop('disabled', true);
							$j('#notify')
								.html(AppGini.Translate._map['Loading ...'])
								.removeClass('alert-danger alert-success')
								.addClass('alert-warning').show();

							post2(
								'<?php echo basename(__FILE__); ?>', {
									action: 'updateTheme',
									theme: $j('input[name="theme"]:checked').val(),
									themeCompact: $j('#theme-compact .btn.active').data('value'),
									navMenu: $j('#nav-menu .btn.active').data('value'),
									csrf_token: $j('#csrf_token').val()
								},
								'ignore', 'theme-selector', 'ignore',
								'<?php echo basename(__FILE__); ?>?notify=<?php echo urlencode($Translation['your theme was updated successfully']); ?>'
							);
						};

						// handle click on theme compact buttons
						$j('#theme-compact .btn').on('click', function() {
							$j('#theme-compact .btn').removeClass('active');
							$j(this).addClass('active');
							updateTheme();
						});

						// handle click on nav menu buttons
						$j('#nav-menu .btn').on('click', function() {
							$j('#nav-menu .btn').removeClass('active');
							$j(this).addClass('active');
							updateTheme();
						});

						// handle default theme button click
						$j('#default-theme').on('click', function() {
							$j('input[name="theme"]').prop('checked', false);
							$j(`input[name="theme"][value="${defaultTheme}"]`).prop('checked', true);

							$j('#theme-compact .btn').removeClass('active');
							$j(`#theme-compact .btn[data-value="${defaultCompact}"]`).addClass('active');

							$j('#nav-menu .btn').removeClass('active');
							$j(`#nav-menu .btn[data-value="${defaultNavMenu}"]`).addClass('active');

							updateTheme();
						});

						// handle theme radio button change
						$j('input[name="theme"]').on('change', function() {
							// disable the radio buttons
							$j('input[name="theme"]').prop('disabled', true);
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

			$j('#language').select2({ width: '100%' });

			$j('#language').on('change', function() {
				$j('#language').prop('disabled', true);
				$j('#notify')
					.html(AppGini.Translate._map['Loading ...'])
					.removeClass('alert-danger alert-success')
					.addClass('alert-warning').show();

				post2(
					'<?php echo basename(__FILE__); ?>',
					{ action: 'updateLanguage', language: $j('#language').val(), csrf_token: $j('#csrf_token').val() },
					'ignore', 'language-selector', 'ignore',
					'<?php echo basename(__FILE__); ?>?notify=<?php echo urlencode($Translation['your language was updated successfully']); ?>'
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
					var ps = passwordStrength($j('#new-password').val(), <?php echo json_encode($mi['username']); ?>);

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

		$j(() => {
			// on clicking a tab, store it in localstorage and switch to it on next page load
			$j('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
				AppGini.localStorage.setItem('membershipProfileLastTab', $j(e.target).attr('href'));
			});
			// on page load, switch to the last tab if it exists in localstorage
			const lastTab = AppGini.localStorage.getItem('membershipProfileLastTab');
			if(lastTab) {
				const $lastTab = $j(`a[href="${lastTab}"]`);
				if($lastTab.length) {
					$lastTab.tab('show');
				} else {
					AppGini.localStorage.removeItem('membershipProfileLastTab'); // remove it if it doesn't exist
				}
			}
		})
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
