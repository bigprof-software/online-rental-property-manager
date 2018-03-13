<?php
	$currDir=dirname(__FILE__);
	include("$currDir/defaultLang.php");
	include("$currDir/language.php");
	include("$currDir/lib.php");

	$adminConfig = config('adminConfig');

	/* no access for guests */
	$mi = getMemberInfo();
	if(!$mi['username'] || $mi['group'] == $adminConfig['anonymousGroup']){
		@header('Location: index.php'); exit;
	}

	/* save profile */
	if($_POST['action'] == 'saveProfile'){
		if(!csrf_token(true)){
			echo $Translation['error:'];
			exit;
		}

		/* process inputs */
		$email=isEmail($_POST['email']);
		$custom1=makeSafe($_POST['custom1']);
		$custom2=makeSafe($_POST['custom2']);
		$custom3=makeSafe($_POST['custom3']);
		$custom4=makeSafe($_POST['custom4']);

		/* validate email */
		if(!$email){
			echo "{$Translation['error:']} {$Translation['email invalid']}";
			echo "<script>$$('label[for=\"email\"]')[0].pulsate({ pulses: 10, duration: 4 }); $('email').activate();</script>";
			exit;
		}

		/* update profile */
		$updateDT = date($adminConfig['PHPDateTimeFormat']);
		sql("UPDATE `membership_users` set email='$email', custom1='$custom1', custom2='$custom2', custom3='$custom3', custom4='$custom4', comments=CONCAT_WS('\\n', comments, 'member updated his profile on $updateDT from IP address {$mi[IP]}') WHERE memberID='{$mi['username']}'", $eo);

		// hook: member_activity
		if(function_exists('member_activity')){
			$args=array();
			member_activity($mi, 'profile', $args);
		}

		exit;
	}

	/* change password */
	if($_POST['action'] == 'changePassword' && $mi['username'] != $adminConfig['adminUsername']){
		if(!csrf_token(true)){
			echo $Translation['error:'];
			exit;
		}

		/* process inputs */
		$oldPassword=$_POST['oldPassword'];
		$newPassword=$_POST['newPassword'];

		/* validate password */
		if(md5($oldPassword) != sqlValue("SELECT `passMD5` FROM `membership_users` WHERE memberID='{$mi['username']}'")){
			echo "{$Translation['error:']} {$Translation['Wrong password']}";
			echo "<script>$$('label[for=\"old-password\"]')[0].pulsate({ pulses: 10, duration: 4 }); $('old-password').activate();</script>";
			exit;
		}
		if(strlen($newPassword) < 4){
			echo "{$Translation['error:']} {$Translation['password invalid']}";
			echo "<script>$$('label[for=\"new-password\"]')[0].pulsate({ pulses: 10, duration: 4 }); $('new-password').activate();</script>";
			exit;      
		}

		/* update password */
		$updateDT = date($adminConfig['PHPDateTimeFormat']);
		sql("UPDATE `membership_users` set `passMD5`='".md5($newPassword)."', `comments`=CONCAT_WS('\\n', comments, 'member changed his password on $updateDT from IP address {$mi[IP]}') WHERE memberID='{$mi['username']}'", $eo);

		// hook: member_activity
		if(function_exists('member_activity')){
			$args=array();
			member_activity($mi, 'password', $args);
		}

		exit;
	}

	/* get profile info */
	/* 
		$mi already contains the profile info, as documented at: 
		https://bigprof.com/appgini/help/working-with-generated-web-database-application/hooks/memberInfo

		custom field names are stored in $adminConfig['custom1'] to $adminConfig['custom4']
	*/
	$permissions = array();
	$userTables = getTableList();
	if(is_array($userTables))  foreach($userTables as $tn => $tc){
		$permissions[$tn] = getTablePermissions($tn);
	}

	/* the profile page view */
	include_once("$currDir/header.php"); ?>

	<div class="page-header">
		<h1><?php echo sprintf($Translation['Hello user'], $mi['username']); ?></h1>
	</div>
	<div id="notify" class="alert alert-success" style="display: none;"></div>
	<div id="loader" style="display: none;"><i class="glyphicon glyphicon-refresh"></i> <?php echo $Translation['Loading ...']; ?></div>

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
							<input type="email" id="email" name="email" value="<?php echo $mi['email']; ?>" class="form-control">
						</div>

						<?php for($i=1; $i<5; $i++){ ?>
							<div class="form-group">
								<label for="custom<?php echo $i; ?>"><?php echo $adminConfig['custom'.$i]; ?></label>
								<input type="text" id="custom<?php echo $i; ?>" name="custom<?php echo $i; ?>" value="<?php echo $mi['custom'][$i-1]; ?>" class="form-control">
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
								<?php foreach($permissions as $tn => $perm){ ?>
									<tr>
										<td><img src="<?php echo $userTables[$tn][2]; ?>"> <a href="<?php echo $tn; ?>_view.php"><?php echo $userTables[$tn][0]; ?></a></td>
										<td class="text-center"><img src="admin/images/<?php echo permIcon($perm[2]); ?>" /></td>
										<td class="text-center"><img src="admin/images/<?php echo ($perm[1] ? 'approve' : 'stop'); ?>_icon.gif" /></td>
										<td class="text-center"><img src="admin/images/<?php echo permIcon($perm[3]); ?>" /></td>
										<td class="text-center"><img src="admin/images/<?php echo permIcon($perm[4]); ?>" /></td>
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
						<div class="form-control-static"><?php echo $mi['group']; ?></div>
					</div>
				</div>
			</div>

			<?php if($mi['username'] != $adminConfig['adminUsername']){ ?>
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
									<input type="password" id="new-password" autocomplete="off" class="form-control">
									<p id="password-strength" class="help-block"></p>
								</div>

								<div class="form-group">
									<label for="confirm-password"><?php echo $Translation['confirm password']; ?></label>
									<input type="password" id="confirm-password" autocomplete="off" class="form-control">
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

		</div>

	</div>


	<script>
		$j(function() {
			<?php
				/* Is there a notification to display? */
				$notify = '';
				if(isset($_GET['notify'])) $notify = addslashes(strip_tags($_GET['notify']));
			?>
			<?php if($notify){ ?> notify('<?php echo $notify; ?>'); <?php } ?>

			$('update-profile').observe('click', function(){
				post2(
					'<?php echo basename(__FILE__); ?>',
					{ action: 'saveProfile', email: $F('email'), custom1: $F('custom1'), custom2: $F('custom2'), custom3: $F('custom3'), custom4: $F('custom4'), csrf_token: $F('csrf_token') },
					'notify', 'profile', 'loader', 
					'<?php echo basename(__FILE__); ?>?notify=<?php echo urlencode($Translation['Your profile was updated successfully']); ?>'
				);
			});

			<?php if($mi['username'] != $adminConfig['adminUsername']){ ?>
				$('update-password').observe('click', function(){
					/* make sure passwords match */
					if($F('new-password') != $F('confirm-password')){
						$('notify').addClassName('Error');
						notify('<?php echo "{$Translation['error:']} ".addslashes($Translation['password no match']); ?>');
						$$('label[for="confirm-password"]')[0].pulsate({ pulses: 10, duration: 4 });
						$('confirm-password').activate();
						return false;
					}

					post2(
						'<?php echo basename(__FILE__); ?>',
						{ action: 'changePassword', oldPassword: $F('old-password'), newPassword: $F('new-password'), csrf_token: $F('csrf_token') },
						'notify', 'password-change-form', 'loader', 
						'<?php echo basename(__FILE__); ?>?notify=<?php echo urlencode($Translation['Your password was changed successfully']); ?>'
					);
				});

				/* password strength feedback */
				$('new-password').observe('keyup', function(){
					ps = passwordStrength($F('new-password'), '<?php echo addslashes($mi['username']); ?>');

					if(ps == 'strong')
						$('password-strength').update('<?php echo $Translation['Password strength: strong']; ?>').setStyle({color: 'Green'});
					else if(ps == 'good')
						$('password-strength').update('<?php echo $Translation['Password strength: good']; ?>').setStyle({color: 'Gold'});
					else
						$('password-strength').update('<?php echo $Translation['Password strength: weak']; ?>').setStyle({color: 'Red'});
				});

				/* inline feedback of confirm password */
				$('confirm-password').observe('keyup', function(){
					if($F('confirm-password') != $F('new-password') || !$F('confirm-password').length){
						$('confirm-status').update('<img align="top" src="Exit.gif"/>');
					}else{
						$('confirm-status').update('<img align="top" src="update.gif"/>');
					}
				});
			<?php } ?>
		});

		function notify(msg){
			$j('#notify').html(msg).fadeIn();
			window.setTimeout(function(){ $j('#notify').fadeOut(); }, 15000);
		}
	</script>

	<?php
		/* return icon file name based on given permission value */
		function permIcon($perm){
			switch($perm){
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

	<?php include_once("$currDir/footer.php"); ?>
