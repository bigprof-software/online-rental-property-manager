<?php
	$currDir=dirname(__FILE__);
	include("$currDir/defaultLang.php");
	include("$currDir/language.php");
	include("$currDir/lib.php");
	include_once("$currDir/header.php");

	$adminConfig = config('adminConfig');

	$reset_expiry = 86400; // time validity of reset key in seconds

#_______________________________________________________________________________
# Step 4: Final step; change the password
#_______________________________________________________________________________
	if($_POST['changePassword'] && $_POST['key']){
		$expiry_limit = time() - $reset_expiry - 900; // give an extra tolerence of 15 minutes
		$res = sql("select * from membership_users where pass_reset_key='" . makeSafe($_POST['key']) . "' and pass_reset_expiry>$expiry_limit limit 1", $eo);

		if($row = db_fetch_assoc($res)){
			if($_POST['newPassword'] != $_POST['confirmPassword'] || !$_POST['newPassword']){
				?>
				<div class="alert alert-danger">
					<?php echo $Translation['password no match']; ?>
				</div>
				<?php

				include_once("$currDir/footer.php");
				exit;
			}

			sql("update membership_users set passMD5='" . md5($_POST['newPassword']) . "', pass_reset_expiry=NULL, pass_reset_key=NULL where lcase(memberID)='" . addslashes($row['memberID']) . "'", $eo);
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
		}else{
			?>
			<div class="alert alert-danger">
				<?php echo $Translation['password reset invalid']; ?>
			</div>
			<?php
		}

		include_once("$currDir/footer.php");
		exit;
	}
#_______________________________________________________________________________
# Step 3: This is the special link that came to the member by email. This is
#         where the member enters his new password.
#_______________________________________________________________________________
	if($_GET['key'] != ''){
		$expiry_limit = time() - $reset_expiry;
		$res = sql("select * from membership_users where pass_reset_key='" . makeSafe($_GET['key']) . "' and pass_reset_expiry>$expiry_limit limit 1", $eo);

		if($row = db_fetch_assoc($res)){
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
							<input type="password" class="form-control" id="newPassword" name="newPassword" placeholder="<?php echo html_attr($Translation['new password']); ?>">
						</div>
						<div class="form-group">
							<label for="confirmPassword" class="control-label"><?php echo $Translation['confirm password']; ?></label>
							<input type="password" class="form-control" id="confirmPassword" name="confirmPassword" placeholder="<?php echo html_attr($Translation['confirm password']); ?>">
						</div>

						<div class="row">
							<div class="col-sm-offset-3 col-sm-6">
								<button class="btn btn-primary btn-lg btn-block" value="changePassword" id="changePassword" type="submit" name="changePassword" value="1"><?php echo $Translation['ok']; ?></button>
							</div>
						</div>

						<input type="hidden" name="key" value="<?php echo $_GET['key']; ?>">
					</form>
				</div>
			</div>
			<?php
		}else{
			?>
			<div class="alert alert-danger">
				<?php echo $Translation['password reset invalid']; ?>
			</div>
			<?php
		}

		include_once("$currDir/footer.php");
		exit;
	}
#_______________________________________________________________________________
# Step 2: Send email to member containing the reset link
#_______________________________________________________________________________
	if($_POST['reset']){
		$username = makeSafe(strtolower(trim($_POST['username'])));
		$email = isEmail(trim($_POST['email']));

		if((!$username && !$email) || ($username==$adminConfig['adminUsername'])){
			redirect("membership_passwordReset.php?emptyData=1");
			exit;
		}

		?><div class="page-header"><h1><?php echo $Translation['password reset']; ?></h1></div><?php

		$where = '';
		if($username){
			$where = "lcase(memberID)='{$username}'";
		}elseif($email){
			$where = "email='{$email}'";
		}
		$res = sql("select * from membership_users where {$where} limit 1", $eo);
		if(!$row=db_fetch_assoc($res)){
			?>
			<div class="alert alert-danger">
				<?php echo $Translation['password reset invalid']; ?>
			</div>
			<?php
		}else{
			// avoid admin password change
			if($row['memberID']==$adminConfig['adminUsername']){
				?>
				<div class="alert alert-danger">
					<?php echo $Translation['password reset invalid']; ?>
				</div>
				<?php

				include_once("$currDir/footer.php");
				exit;
			}

			// generate and store password reset key, if no valid key already exists
			$no_valid_key = ($row['pass_reset_key'] == '' || ($row['pass_reset_key'] != '' && $row['pass_reset_expiry'] < (time() - $reset_expiry)));
			$key = ($no_valid_key ? md5(microtime()) : $row['pass_reset_key']);
			$expiry = ($no_valid_key ? time() + $reset_expiry : $row['pass_reset_expiry']);
			@db_query("update membership_users set pass_reset_key='$key', pass_reset_expiry='$expiry' where memberID='" . addslashes($row['memberID']) . "'");

			// determine password reset URL
			$ResetLink = application_url("membership_passwordReset.php?key=$key");

			// send reset instructions
			sendmail(array(
				'to' => $row['email'],
				'subject' => $Translation['password reset subject'],
				'message' => nl2br(str_replace('<ResetLink>', $ResetLink, $Translation['password reset message']))
			));

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
		}

		include_once("$currDir/footer.php");
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

				<?php if(is_array(getTableList()) && count(getTableList())){ /* if anon. users can see any tables ... */ ?>
					<p style="margin-top: 1.5em;"><?php echo $Translation['browse as guest']; ?></p>
				<?php } ?>
			</form>
		</div>
	</div>

	<script>
		jQuery(function(){
			jQuery('#username').focus();
			<?php  if($_GET['emptyData']){ ?>
				jQuery('#username, #email').parent().addClass('has-error');
			<?php } ?>
		});
	</script>

<?php include_once("$currDir/footer.php"); ?>
