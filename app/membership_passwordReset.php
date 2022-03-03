<?php
	include_once(__DIR__ . '/lib.php');
	if(MULTI_TENANTS) redirect(SaaS::passResetUrl(), true);

	include_once(__DIR__ . '/header.php');

	$adminConfig = config('adminConfig');

	$reset_expiry = 86400; // time validity of reset key in seconds

#_______________________________________________________________________________
# Step 4: Final step; change the password
#_______________________________________________________________________________
	if(Request::val('changePassword') && Request::val('key')) {
		$expiry_limit = time() - $reset_expiry - 900; // give an extra tolerence of 15 minutes
		$res = sql("select * from membership_users where pass_reset_key='" . makeSafe(Request::val('key')) . "' and pass_reset_expiry>$expiry_limit limit 1", $eo);

		if($row = db_fetch_assoc($res)) {
			if(Request::val('newPassword') != Request::val('confirmPassword') || !Request::val('newPassword')) {
				?>
				<div class="alert alert-danger">
					<?php echo $Translation['password no match']; ?>
				</div>
				<?php

				include_once(__DIR__ . '/footer.php');
				exit;
			}

			sql("update membership_users set passMD5='" . password_hash(Request::val('newPassword'), PASSWORD_DEFAULT) . "', pass_reset_expiry=NULL, pass_reset_key=NULL where lcase(memberID)='" . makeSafe($row['memberID'], false) . "'", $eo);
			?>
			<div class="row">
				<div class="col-md-6 col-md-offset-3">
					<div class="alert alert-info">
						<i class="glyphicon glyphicon-info-sign"></i>
						<?php echo $Translation['password reset done']; ?>
					</div>
				</div>
			</div>
			<?php
		} else {
			?>
			<div class="alert alert-danger">
				<?php echo $Translation['password reset invalid']; ?>
			</div>
			<?php
		}

		include_once(__DIR__ . '/footer.php');
		exit;
	}
#_______________________________________________________________________________
# Step 3: This is the special link that came to the member by email. This is
#         where the member enters his new password.
#_______________________________________________________________________________
	if(Request::val('key')) {
		$expiry_limit = time() - $reset_expiry;
		$res = sql("select * from membership_users where pass_reset_key='" . makeSafe(Request::val('key')) . "' and pass_reset_expiry>$expiry_limit limit 1", $eo);

		if($row = db_fetch_assoc($res)) {
			?>
			<div class="page-header"><h1><?php echo $Translation['password change']; ?></h1></div>

			<div class="row">
				<div class="col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3 col-lg-4 col-lg-offset-4">
					<form method="post" action="membership_passwordReset.php">
						<div class="form-group">
							<label for="name" class="control-label"><?php echo $Translation['username']; ?></label>
							<p class="lead"><?php echo $row['memberID']; ?></p>
						</div>
						<div class="form-group">
							<label for="newPassword" class="control-label"><?php echo $Translation['new password']; ?></label>
							<input type="password" class="form-control" autocomplete="new-password" id="newPassword" name="newPassword" placeholder="<?php echo html_attr($Translation['new password']); ?>">
						</div>
						<div class="form-group">
							<label for="confirmPassword" class="control-label"><?php echo $Translation['confirm password']; ?></label>
							<input type="password" class="form-control" autocomplete="new-password" id="confirmPassword" name="confirmPassword" placeholder="<?php echo html_attr($Translation['confirm password']); ?>">
						</div>

						<div class="row">
							<div class="col-sm-offset-3 col-sm-6">
								<button class="btn btn-primary btn-lg btn-block" value="changePassword" id="changePassword" type="submit" name="changePassword" value="1"><?php echo $Translation['ok']; ?></button>
							</div>
						</div>

						<input type="hidden" name="key" value="<?php echo html_attr(Request::val('key')); ?>">
					</form>
				</div>
			</div>
			<?php
		} else {
			?>
			<div class="alert alert-danger">
				<?php echo $Translation['password reset invalid']; ?>
			</div>
			<?php
		}

		include_once(__DIR__ . '/footer.php');
		exit;
	}
#_______________________________________________________________________________
# Step 2: Send email to member containing the reset link
#_______________________________________________________________________________
	if(Request::val('reset')) {
		$username = makeSafe(strtolower(trim(Request::val('username'))));
		$email = isEmail(trim(Request::val('email')));

		if(!$username && !$email) redirect('membership_passwordReset.php?emptyData=1');

		?><div class="page-header"><h1><?php echo $Translation['password reset']; ?></h1></div><?php

		$where = '';
		if($username) {
			$where = "LCASE(`memberID`)='{$username}'";
		} elseif($email) {
			$where = "`email`='{$email}'";
		}

		$eo = ['silentErrors' => true];
		$res = sql("SELECT * FROM `membership_users` WHERE {$where} LIMIT 1", $eo);
		$row = db_fetch_assoc($res);

		// avoid admin password change
		if($row && $row['memberID'] != $adminConfig['adminUsername']) {
			// generate and store password reset key, if no valid key already exists
			$no_valid_key = ($row['pass_reset_key'] == '' || ($row['pass_reset_key'] != '' && $row['pass_reset_expiry'] < (time() - $reset_expiry)));
			$key = ($no_valid_key ? substr(md5(microtime() . rand(0, 100000)), -17) : $row['pass_reset_key']);
			$expiry = ($no_valid_key ? time() + $reset_expiry : $row['pass_reset_expiry']);
			@db_query("UPDATE `membership_users` SET `pass_reset_key`='{$key}', `pass_reset_expiry`='{$expiry}' WHERE `memberID`='" . makeSafe($row['memberID']) . "'");

			// determine password reset URL
			$ResetLink = application_url("membership_passwordReset.php?key={$key}");

			// send reset instructions
			sendmail([
				'to' => $row['email'],
				'subject' => $Translation['password reset subject'],
				'message' => nl2br(str_replace('<ResetLink>', $ResetLink, $Translation['password reset message'])),
			]);
		}

		// display confirmation
		?>
		<div class="row">
			<div class="col-md-6 col-md-offset-3">
				<div class="alert alert-info">
					<i class="glyphicon glyphicon-info-sign" style="font-size: xx-large; float: left; margin: 0 10px;"></i>
					<?php echo $Translation['password reset ready']; ?>
				</div>
			</div>
		</div>
		<?php

		include_once(__DIR__ . '/footer.php');
		exit;
	}

#_______________________________________________________________________________
# Step 1: get the username or email of the member who wants to reset his password
#_______________________________________________________________________________

	?>
	<div class="page-header"><h1><?php echo $Translation['password reset']; ?></h1></div>

	<div class="row">
		<div class="col-sm-8 col-sm-offset-2 col-md-6 col-md-offset-3 col-lg-4 col-lg-offset-4">
			<form method="post" action="membership_passwordReset.php">
				<div class="alert alert-info"><?php echo $Translation['password reset details']; ?></div>

				<div class="form-group">
					<label for="username" class="control-label"><?php echo $Translation['username']; ?></label>
					<input type="text" class="form-control" id="username" name="username" placeholder="<?php echo html_attr($Translation['username']); ?>">
				</div>

				<div class="form-group">
					<label for="email" class="control-label"><?php echo '<i>'.$Translation['or'].':</i> '.$Translation['email']; ?></label>
					<input type="email" class="form-control" id="email" name="email" placeholder="<?php echo html_attr($Translation['email']); ?>">
				</div>

				<div class="row">
					<div class="col-sm-offset-3 col-sm-6">
						<button class="btn btn-primary btn-lg btn-block" value="<?php echo html_attr($Translation['ok']); ?>" id="reset" type="submit" name="reset"><?php echo $Translation['ok']; ?></button>
					</div>
				</div>

				<?php if(is_array(getTableList()) && count(getTableList())) { /* if anon. users can see any tables ... */ ?>
					<p style="margin-top: 1.5em;"><a href="index.php"><i class="glyphicon glyphicon-user text-muted"></i> <?php echo $Translation['continue browsing as guest']; ?></a></p>
				<?php } ?>
			</form>
		</div>
	</div>

	<script>
		jQuery(function() {
			jQuery('#username').focus();
			<?php  if(Request::val('emptyData')) { ?>
				jQuery('#username, #email').parent().addClass('has-error');
			<?php } ?>
		});
	</script>

<?php include_once(__DIR__ . '/footer.php');
