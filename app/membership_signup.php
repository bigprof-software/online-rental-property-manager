<?php
	include_once(__DIR__ . '/lib.php');
	if(MULTI_TENANTS) redirect(SaaS::signupUrl(), true);

	include_once(__DIR__ . '/header.php');

	$adminConfig = config('adminConfig');
	$app_name = APP_TITLE;

	if(!$cg = sqlValue("select count(1) from membership_groups where allowSignup=1")) {
		$noSignup = true;
		echo error_message($Translation['sign up disabled']);
		exit;
	}

	if(Request::val('signUp')) {
		// receive data
		$memberID = is_allowed_username(Request::val('newUsername'));
		$email = isEmail(Request::val('email'));
		$password = Request::val('password');
		$confirmPassword = Request::val('confirmPassword');
		$groupID = intval(Request::val('groupID'));
		$custom1 = makeSafe(Request::val('custom1'));
		$custom2 = makeSafe(Request::val('custom2'));
		$custom3 = makeSafe(Request::val('custom3'));
		$custom4 = makeSafe(Request::val('custom4'));

		// validate data
		if(!$memberID) {
			echo error_message($Translation['username invalid']);
			exit;
		}
		if(strlen($password) < 4 || trim($password) != $password) {
			echo error_message($Translation['password invalid']);
			exit;
		}
		if($password != $confirmPassword) {
			echo error_message($Translation['password no match']);
			exit;
		}
		if(!$email) {
			echo error_message($Translation['email invalid']);
			exit;
		}
		if(!sqlValue("select count(1) from membership_groups where groupID='$groupID' and allowSignup=1")) {
			echo error_message($Translation['group invalid']);
			exit;
		}

		// save member data
		$needsApproval = sqlValue("select needsApproval from membership_groups where groupID='$groupID'");
		sql("INSERT INTO `membership_users` set memberID='{$memberID}', passMD5='" . password_hash($password, PASSWORD_DEFAULT) . "', email='{$email}', signupDate='" . @date('Y-m-d') . "', groupID='{$groupID}', isBanned='0', isApproved='" . ($needsApproval == 1 ? '0' : '1') . "', custom1='{$custom1}', custom2='{$custom2}', custom3='{$custom3}', custom4='{$custom4}', comments='member signed up through the registration form.'", $eo);

		// admin mail notification
		/* ---- application name as provided in AppGini is used here ---- */
		$message = nl2br(
			"A new member has signed up for {$app_name}.\n\n" .
			"Member name: {$memberID}\n" .
			"Member group: " . sqlValue("select name from membership_groups where groupID='{$groupID}'") . "\n" .
			"Member email: {$email}\n" .
			"IP address: {$_SERVER['REMOTE_ADDR']}\n" .
			"Custom fields:\n" .
			($adminConfig['custom1'] ? "{$adminConfig['custom1']}: {$custom1}\n" : '') .
			($adminConfig['custom2'] ? "{$adminConfig['custom2']}: {$custom2}\n" : '') .
			($adminConfig['custom3'] ? "{$adminConfig['custom3']}: {$custom3}\n" : '') .
			($adminConfig['custom4'] ? "{$adminConfig['custom4']}: {$custom4}\n" : '')
		);

		if($adminConfig['notifyAdminNewMembers'] == 2 && !$needsApproval)
			sendmail([
				'to' => $adminConfig['senderEmail'],
				'subject' => "[{$app_name}] New member signup",
				'message' => $message,
			]);
		elseif($adminConfig['notifyAdminNewMembers'] >= 1 && $needsApproval)
			sendmail([
				'to' => $adminConfig['senderEmail'],
				'subject' => "[{$app_name}] New member awaiting approval",
				'message' => $message,
			]);

		// hook: member_activity
		if(function_exists('member_activity')) {
			$args = [];
			member_activity(getMemberInfo($memberID), ($needsApproval ? 'pending' : 'automatic'), $args);
		}

		// redirect to thanks page
		$redirect = ($needsApproval ? '' : '?redir=1');
		redirect("membership_thankyou.php$redirect");

		exit;
	}

	// drop-down of groups allowing self-signup
	$groupsDropDown = preg_replace('/<option.*?value="".*?><\/option>/i', '', htmlSQLSelect('groupID', "select groupID, concat(name, if(needsApproval=1, ' *', ' ')) from membership_groups where allowSignup=1 order by name", ($cg == 1 ? sqlValue("select groupID from membership_groups where allowSignup=1 order by name limit 1") : 0 )));
	$groupsDropDown = str_replace('<select ', '<select class="form-control" ', $groupsDropDown);
?>

<?php if(!$noSignup) { ?>
	<div class="row">
		<div class="hidden-xs col-sm-4 col-md-6 col-lg-8" id="signup_splash">
			<!-- customized splash content here -->
		</div>

		<div class="col-sm-8 col-md-6 col-lg-4">
			<div class="panel panel-success">

				<div class="panel-heading">
					<h1 class="panel-title"><strong><?php echo $Translation['sign up here']; ?></strong></h1>
				</div>

				<div class="panel-body">
					<form method="post" action="membership_signup.php">
						<div class="form-group">
							<label for="username" class="control-label"><?php echo $Translation['username']; ?></label>
							<input class="form-control input-lg" type="text" required="" placeholder="<?php echo $Translation['username']; ?>" id="username" name="newUsername">
							<span id="usernameAvailable" class="help-block hidden pull-left"><i class="glyphicon glyphicon-ok"></i> <?php echo str_ireplace(["'", '"', '<memberid>'], '', $Translation['user available']); ?></span>
							<span id="usernameNotAvailable" class="help-block hidden pull-left"><i class="glyphicon glyphicon-remove"></i> <?php echo str_ireplace(["'", '"', '<memberid>'], '', $Translation['username invalid']); ?></span>
							<div class="clearfix"></div>
						</div>

						<div class="row">
							<div class="col-sm-6">
								<div class="form-group">
									<label for="password" class="control-label"><?php echo $Translation['password']; ?></label>
									<input class="form-control" type="password" autocomplete="new-password" required="" placeholder="<?php echo $Translation['password']; ?>" id="password" name="password">
								</div>
							</div>
							<div class="col-sm-6">
								<div class="form-group">
									<label for="confirmPassword" class="control-label"><?php echo $Translation['confirm password']; ?></label>
									<input class="form-control" type="password" autocomplete="new-password" required="" placeholder="<?php echo $Translation['confirm password']; ?>" id="confirmPassword" name="confirmPassword">
								</div>
							</div>
						</div>

						<div class="form-group">
							<label for="email" class="control-label"><?php echo $Translation['email']; ?></label>
							<input class="form-control" type="text" required="" placeholder="<?php echo $Translation['email']; ?>" id="email" name="email">
						</div>

						<div class="form-group">
							<label for="group" class="control-label"><?php echo $Translation['group']; ?></label>
							<?php echo $groupsDropDown; ?>
							<span class="help-block"><?php echo $Translation['groups *']; ?></span>
						</div>

						<?php
							if(!$adminConfig['hide_custom_user_fields_during_signup']) {
								for($cf = 1; $cf <= 4; $cf++) {
									if($adminConfig['custom'.$cf] != '') {
										?>
										<div class="row form-group">
										   <div class="col-sm-3"><label class="control-label" for="custom<?php echo $cf; ?>"><?php echo htmlspecialchars($adminConfig['custom'.$cf]); ?></label></div>
										   <div class="col-sm-9"><input class="form-control" type="text" placeholder="<?php echo html_attr($adminConfig['custom'.$cf]); ?>" id="custom<?php echo $cf; ?>" name="custom<?php echo $cf; ?>"></div>
										</div>
										<?php
									}
								}
							}
						?>

						<div class="row">
							<div class="col-sm-offset-3 col-sm-6">
								<button class="btn btn-primary btn-lg btn-block" value="signUp" id="submit" type="submit" name="signUp"><?php echo $Translation['sign up']; ?></button>
							</div>
						</div>

					</form>
				</div> <!-- /div class="panel-body" -->
			</div> <!-- /div class="panel ..." -->
		</div> <!-- /div class="col..." -->
	</div> <!-- /div class="row" -->

	<script>
		$j(function() {
			$j('#username').focus();

			$j('#usernameAvailable, #usernameNotAvailable').click(function() { $j('#username').focus(); });
			$j('#username').on('keyup blur', checkUser);

			/* password strength feedback */
			$j('#password').on('keyup blur', function() {
				var ps = passwordStrength($j('#password').val(), $j('#username').val());

				if(ps == 'strong') {
					$j('#password').parents('.form-group').removeClass('has-error has-warning').addClass('has-success');
					$j('#password').attr('title', <?php echo json_encode($Translation['Password strength: strong']); ?>);
				} else if(ps == 'good') {
					$j('#password').parents('.form-group').removeClass('has-success has-error').addClass('has-warning');
					$j('#password').attr('title', <?php echo json_encode($Translation['Password strength: good']); ?>);
				} else {
					$j('#password').parents('.form-group').removeClass('has-success has-warning').addClass('has-error');
					$j('#password').attr('title', <?php echo json_encode($Translation['Password strength: weak']); ?>);
				}
			});

			/* inline feedback of confirm password */
			$j('#confirmPassword').on('keyup blur', function() {
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

			/* validate form before submitting */
			$j('#submit').click(function(e) { if(!jsValidateSignup()) e.preventDefault(); })
		});

		var uaro; // user availability request object
		function checkUser() {
			// abort previous request, if any
			if(uaro != undefined) uaro.abort();

			reset_username_status();

			uaro = $j.ajax({
					url: 'checkMemberID.php',
					type: 'GET',
					data: { 'memberID': $j('#username').val() },
					success: function(resp) {
						if(resp.indexOf('username-available') > -1) {
							reset_username_status('success');
						} else {
							reset_username_status('error');
						}
					}
			});
		}

		function reset_username_status(status) {
			$j('#usernameNotAvailable, #usernameAvailable')
				.addClass('hidden')
				.parents('.form-group')
				.removeClass('has-error has-success');

			if(status == undefined) return;
			if(status == 'success') {
				$j('#usernameAvailable')
					.removeClass('hidden')
					.parents('.form-group')
					.addClass('has-success');
			}
			if(status == 'error') {
				$j('#usernameNotAvailable')
					.removeClass('hidden')
					.parents('.form-group')
					.addClass('has-error');
			}
		}

		/* validate data before submitting */
		function jsValidateSignup() {
			var p1 = $j('#password').val();
			var p2 = $j('#confirmPassword').val();
			var email = $j('#email').val();

			/* user exists? */
			if(!$j('#username').parents('.form-group').hasClass('has-success')) {
				modal_window({ message: '<div class="alert alert-danger"><?php echo html_attr($Translation['username invalid']); ?></div>', title: "<?php echo html_attr($Translation['error:']); ?>", close: function() { $j('#username').focus(); } });
				return false;
			}

			/* passwords not matching? */
			if(p1 != p2) {
				modal_window({ message: '<div class="alert alert-danger"><?php echo html_attr($Translation['password no match']); ?></div>', title: "<?php echo html_attr($Translation['error:']); ?>", close: function() { $j('#confirmPassword').focus(); } });
				return false;
			}

			if(!validateEmail(email)) {
				modal_window({ message: '<div class="alert alert-danger"><?php echo html_attr($Translation['email invalid']); ?></div>', title: "<?php echo html_attr($Translation['error:']); ?>", close: function() { $j('#email').focus(); } });
				return false;
			}

			return true;
		}
	</script>

	<style>
		#usernameAvailable,#usernameNotAvailable{ cursor: pointer; }
	</style>

<?php } ?>

<?php include_once(__DIR__ . '/footer.php');
