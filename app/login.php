<?php if(!isset($Translation)) { @header('Location: index.php?signIn=1'); exit; } ?>
<?php if(MULTI_TENANTS) redirect(SaaS::loginUrl(), true); ?>
<?php include_once(__DIR__ . '/header.php'); ?>

<?php if(Request::val('loginFailed')) { ?>
	<div class="alert alert-danger"><?php echo $Translation['login failed']; ?></div>
<?php } ?>

<div class="row">
	<div class="col-sm-6 col-lg-8" id="login_splash">
		<!-- customized splash content here -->
	</div>
	<div class="col-sm-6 col-lg-4">
		<div class="panel panel-success">

			<div class="panel-heading">
				<h1 class="panel-title"><strong><?php echo $Translation['sign in here']; ?></strong></h1>
			</div>

			<div class="panel-body">
				<?php if(sqlValue("SELECT COUNT(1) from `membership_groups` WHERE `allowSignup`=1")) { ?>
					<a class="btn btn-success btn-lg pull-right" href="membership_signup.php"><?php echo $Translation['sign up']; ?></a>
					<div class="clearfix"></div>
				<?php } ?>

				<form method="post" action="index.php">
					<div class="form-group">
						<label class="control-label" for="username"><?php echo $Translation['username']; ?></label>
						<input class="form-control" name="username" id="username" type="text" placeholder="<?php echo $Translation['username']; ?>" required>
					</div>
					<div class="form-group">
						<label class="control-label" for="password"><?php echo $Translation['password']; ?></label>
						<input class="form-control" name="password" id="password" type="password" placeholder="<?php echo $Translation['password']; ?>" required>
						<span class="help-block"><?php echo $Translation['forgot password']; ?></span>
					</div>
					<div class="checkbox">
						<label class="control-label" for="rememberMe">
							<input type="checkbox" name="rememberMe" id="rememberMe" value="1">
							<?php echo $Translation['remember me']; ?>
						</label>
					</div>

					<div class="row">
						<div class="col-sm-offset-3 col-sm-6">
							<button name="signIn" type="submit" id="submit" value="signIn" class="btn btn-primary btn-lg btn-block"><?php echo $Translation['sign in']; ?></button>
						</div>
					</div>
				</form>
			</div>

			<?php if(is_array(getTableList()) && count(getTableList())) { /* if anon. users can see any tables ... */ ?>
				<div class="panel-footer">
					<a href="index.php"><i class="glyphicon glyphicon-user text-muted"></i> <?php echo $Translation['continue browsing as guest']; ?></a>
				</div>
			<?php } ?>

		</div>
	</div>
</div>

<script>document.getElementById('username').focus();</script>
<?php include_once(__DIR__ . '/footer.php');
