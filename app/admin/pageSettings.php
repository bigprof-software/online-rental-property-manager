<?php
	$currDir = dirname(__FILE__);
	require("{$currDir}/incCommon.php");
	$GLOBALS['page_title'] = $Translation['admin settings'];
	include("{$currDir}/incHeader.php");

	if(isset($_POST['saveChanges'])){
		// csrf check
		if(!csrf_token(true)){
			?>
			<div class="alert alert-danger">
				<?php echo $Translation['invalid security token'] ; ?>
			</div>
			<?php
			include("{$currDir}/incFooter.php");
		}

		// apply undo_magic_quotes to all input
		$post = @array_map('undo_magic_quotes', $_POST);

		// validate inputs
		$errors = array();

		// if admin username changed, check if the new username already exists
		$adminUsername = makeSafe(strtolower($post['adminUsername']));
		if($adminConfig['adminUsername'] != strtolower($post['adminUsername']) && sqlValue("select count(1) from membership_users where lcase(memberID)='$adminUsername'")){
			$errors[] = $Translation['unique admin username error'] ;
		}

		// if anonymous username changed, check if the new username already exists
		$anonymousMember = makeSafe(strtolower($post['anonymousMember']));
		if($adminConfig['anonymousMember'] != strtolower($post['anonymousMember']) && sqlValue("select count(1) from membership_users where lcase(memberID)='$anonymousMember'")){
			$errors[] = $Translation['unique anonymous username error'];
		}

		// if anonymous group name changed, check if the new group name already exists
		$anonymousGroup = makeSafe($post['anonymousGroup']);
		if($adminConfig['anonymousGroup'] != $post['anonymousGroup'] && sqlValue("select count(1) from membership_groups where name='$anonymousGroup'")){
			$errors[] = $Translation['unique anonymous group name error'];
		}

		$adminPassword = $post['adminPassword'];
		if($adminPassword != '' && $adminPassword == $post['confirmPassword']){
			$adminPassword = md5($adminPassword);
		}elseif($adminPassword != '' && $adminPassword != $post['confirmPassword']){
			$errors[] = $Translation['admin password mismatch'];
		}else{
			$adminPassword = $adminConfig['adminPassword'];
		}

		if(!isEmail($post['senderEmail'])){
			$errors[] = $Translation['invalid sender email'];
		}

		if(count($errors)){
			?>
			<div class="alert alert-danger">
				<?php echo $Translation['errors occurred'] ;  ?>
				<ul><li><?php echo implode('</li><li>', $errors); ?></li></ul>
				<?php echo $Translation['go back'] ;  ?>
			</div>
			<?php
			include("{$currDir}/incFooter.php");
		}

		$new_config = array(
			'dbServer' => config('dbServer'),
			'dbUsername' => config('dbUsername'),
			'dbPassword' => config('dbPassword'),
			'dbDatabase' => config('dbDatabase'),

			'adminConfig' => array(
				'adminUsername' => strtolower($post['adminUsername']),
				'adminPassword' => $adminPassword,
				'notifyAdminNewMembers' => intval($post['notifyAdminNewMembers']),
				'defaultSignUp' => intval($post['visitorSignup']),
				'anonymousGroup' => $post['anonymousGroup'],
				'anonymousMember' => strtolower($post['anonymousMember']),
				'groupsPerPage' => (intval($post['groupsPerPage']) > 0 ? intval($post['groupsPerPage']) : $adminConfig['groupsPerPage']),
				'membersPerPage' => (intval($post['membersPerPage']) > 0 ? intval($post['membersPerPage']) : $adminConfig['membersPerPage']),
				'recordsPerPage' => (intval($post['recordsPerPage']) > 0 ? intval($post['recordsPerPage']) : $adminConfig['recordsPerPage']),
				'custom1' => $post['custom1'],
				'custom2' => $post['custom2'],
				'custom3' => $post['custom3'],
				'custom4' => $post['custom4'],
				'MySQLDateFormat' => $post['MySQLDateFormat'],
				'PHPDateFormat' => $post['PHPDateFormat'],
				'PHPDateTimeFormat' => $post['PHPDateTimeFormat'],
				'senderName' => $post['senderName'],
				'senderEmail' => $post['senderEmail'],
				'approvalSubject' => $post['approvalSubject'],
				'approvalMessage' => $post['approvalMessage'],
				'hide_twitter_feed' => ($post['hide_twitter_feed'] ? true : false),
				'maintenance_mode_message' => $post['maintenance_mode_message'],
				'mail_function' => in_array($post['mail_function'], array('smtp', 'mail')) ? $post['mail_function'] : 'mail',
				'smtp_server' => $post['smtp_server'],
				'smtp_encryption' => in_array($post['smtp_encryption'], array('ssl', 'tls')) ? $post['smtp_encryption'] : '',
				'smtp_port' => intval($post['smtp_port']) > 0 ? intval($post['smtp_port']) : 25,
				'smtp_user' => $post['smtp_user'],
				'smtp_pass' => $post['smtp_pass']
			)
		);

		// save changes
		$save_result = save_config($new_config);
		if($save_result === true){
			// update admin member
			sql( "update membership_users set memberID='$adminUsername', passMD5='$adminPassword', email='{$post['senderEmail']}', comments=concat_ws('', comments, '\\n', '".str_replace ( "<DATE>" , @date('Y-m-d') , $Translation['record updated automatically'] ) ."') where lcase(memberID)='" . makeSafe(strtolower($adminConfig['adminUsername'])) . "'" , $eo);
			$_SESSION['memberID'] = $_SESSION['adminUsername'] = strtolower($post['adminUsername']);

			// update anonymous group name if changed
			if($adminConfig['anonymousGroup'] != $post['anonymousGroup']){
				sql("update membership_groups set name='$anonymousGroup' where name='" . addslashes($adminConfig['anonymousGroup']) . "'", $eo);
			}

			// update anonymous username if changed
			if($adminConfig['anonymousMember'] != $post['anonymousMember']){
				sql("update membership_users set memberID='$anonymousMember' where memberID='" . addslashes($adminConfig['anonymousMember']) . "'", $eo);
			}

			// display status
			echo "<div class=\"alert alert-success\"><h2>{$Translation['admin settings saved']}</h2></div>";
		}else{
			// display status
			echo "<div class=\"alert alert-danger\"><h2>" . str_replace('<ERROR>', $save_result['error'], $Translation['admin settings not saved']) . "</h2></div>";
		}

		// exit
		include("{$currDir}/incFooter.php");
	}

	function settings_textbox($name, $label, $value, $hint = '', $type = 'text'){
		ob_start();
		?>
		<div class="form-group">
			<label for="<?php echo $name; ?>" class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"><?php echo $label; ?></label>
			<div class="col-sm-8 col-md-9 col-lg-6">
				<input type="<?php echo $type; ?>" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo html_attr($value); ?>" class="form-control">
				<?php if($hint){ ?>
					<span class="help-block"><?php echo $hint; ?></span>
				<?php } ?>
			</div>
		</div>
		<?php
		$out = ob_get_contents();
		ob_end_clean();

		return $out;
	}

	function settings_textarea($name, $label, $value, $height = 6, $hint = ''){
		ob_start();
		?>
		<div class="form-group">
			<label for="<?php echo $name; ?>" class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"><?php echo $label; ?></label>
			<div class="col-sm-8 col-md-9 col-lg-6">
				<textarea rows="<?php echo abs($height); ?>" name="<?php echo $name; ?>" id="<?php echo $name; ?>" class="form-control"><?php echo html_attr(str_replace(array('\r', '\n'), array("", "\n"), $value)); ?></textarea>
				<?php if($hint){ ?>
					<span class="help-block"><?php echo $hint; ?></span>
				<?php } ?>
			</div>
		</div>
		<?php
		$out = ob_get_contents();
		ob_end_clean();

		return $out;
	}

	function settings_radiogroup($name, $label, $value, $options){
		ob_start();
		?>
		<div class="form-group">
			<label class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"><?php echo $label; ?></label>
			<div class="col-sm-8 col-md-9 col-lg-6">
				<?php foreach($options as $val => $display){ ?>
					<div class="radio">
						<label>
							<input type="radio" 
								name="<?php echo $name; ?>" 
								id="<?php echo $name; ?><?php echo html_attr($val); ?>" 
								value="<?php echo html_attr($val); ?>" 
								<?php if($value == $val){ ?>checked<?php } ?>
							>
							<?php echo $display; ?>
						</label>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php
		$out = ob_get_contents();
		ob_end_clean();

		return $out;
	}

	function settings_checkbox($name, $label, $value, $set_value, $hint = ''){
		ob_start();
		?>
		<div class="form-group">
			<label class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"></label>
			<div class="col-sm-8 col-md-9 col-lg-6">
				<div class="checkbox">
					<label>
						<input type="checkbox" 
							name="<?php echo $name; ?>" 
							id="<?php echo $name; ?>" 
							value="<?php echo html_attr($value); ?>" 
							<?php if($value == $set_value){ ?>checked<?php } ?>
						>
						<?php echo $label; ?>
					</label>
				</div>
				<?php if($hint){ ?>
					<span class="help-block"><?php echo $hint; ?></span>
				<?php } ?>
			</div>
		</div>
		<?php
		$out = ob_get_contents();
		ob_end_clean();

		return $out;
	}

?>

<div class="page-header"><h1><?php echo $Translation['admin settings'] ; ?></h1></div>

<form method="post" action="pageSettings.php" class="form-horizontal">
	<?php echo csrf_token(); ?>

	<?php echo settings_textbox('adminUsername', $Translation['admin username'], $adminConfig['adminUsername']); ?>
	<?php echo settings_textbox('adminPassword', $Translation['admin password'], '', $Translation['change admin password'], 'password'); ?>
	<?php echo settings_textbox('confirmPassword', $Translation['confirm password'], '', '', 'password'); ?>

	<?php
		echo settings_radiogroup(
			'notifyAdminNewMembers', 
			$Translation['admin notifications'], 
			intval($adminConfig['notifyAdminNewMembers']), 
			array(
				0 => $Translation['no email notifications'],
				1 => $Translation['member waiting approval'],
				2 => $Translation['new sign-ups']
			)
		); 
	?>

	<?php echo settings_textbox('custom1', $Translation['members custom field 1'], $adminConfig['custom1']); ?>
	<?php echo settings_textbox('custom2', $Translation['members custom field 2'], $adminConfig['custom2']); ?>
	<?php echo settings_textbox('custom3', $Translation['members custom field 3'], $adminConfig['custom3']); ?>
	<?php echo settings_textbox('custom4', $Translation['members custom field 4'], $adminConfig['custom4']); ?>

	<?php echo settings_textbox('approvalSubject', $Translation['member approval email subject'], $adminConfig['approvalSubject'], $Translation['member approval email subject control']); ?>
	<?php echo settings_textarea('approvalMessage', $Translation['member approval email message'], $adminConfig['approvalMessage']); ?>

	<?php echo settings_textbox('MySQLDateFormat', $Translation['MySQL date'], $adminConfig['MySQLDateFormat'], $Translation['MySQL reference']); ?>
	<?php echo settings_textbox('PHPDateFormat', $Translation['PHP short date'], $adminConfig['PHPDateFormat'], $Translation['PHP manual']); ?>
	<?php echo settings_textbox('PHPDateTimeFormat', $Translation['PHP long date'], $adminConfig['PHPDateTimeFormat'], $Translation['PHP manual']); ?>

	<?php echo settings_textbox('groupsPerPage', $Translation['groups per page'], $adminConfig['groupsPerPage']); ?>
	<?php echo settings_textbox('membersPerPage', $Translation['members per page'], $adminConfig['membersPerPage']); ?>
	<?php echo settings_textbox('recordsPerPage', $Translation['records per page'], $adminConfig['recordsPerPage']); ?>

	<?php
		echo settings_radiogroup(
			'visitorSignup', 
			$Translation['default sign-up mode'], 
			intval($adminConfig['defaultSignUp']), 
			array(
				0 => $Translation['no sign-up allowed'],
				1 => $Translation['admin approve members'],
				2 => $Translation['automatically approve members']
			)
		); 
	?>

	<?php echo settings_textbox('anonymousGroup', $Translation['anonymous group'], $adminConfig['anonymousGroup']); ?>
	<?php echo settings_textbox('anonymousMember', $Translation['anonymous user name'], $adminConfig['anonymousMember']); ?>

	<?php echo settings_checkbox('hide_twitter_feed', $Translation['hide twitter feed'], '1', $adminConfig['hide_twitter_feed'], $Translation['twitter feed']); ?>
	<?php echo settings_textarea('maintenance_mode_message', $Translation['maintenance mode message'], $adminConfig['maintenance_mode_message']); ?>

	<hr>
	<div id="mail-settings" style="height: 5em;"></div>

	<?php echo settings_textbox('senderEmail', $Translation['sender email'], $adminConfig['senderEmail'], $Translation['sender name and email'] . ' ' . $Translation['email messages']); ?>
	<?php echo settings_textbox('senderName', $Translation['sender name'], $adminConfig['senderName']); ?>
	<?php
		echo settings_radiogroup(
			'mail_function', 
			$Translation['mail_function'], 
			thisOr($adminConfig['mail_function'], 'mail'), 
			array(
				'mail' => 'PHP mail()',
				'smtp' => 'SMTP'
			)
		); 
	?>
	<?php echo settings_textbox('smtp_server', $Translation['smtp_server'], $adminConfig['smtp_server']); ?>
	<?php
		echo settings_radiogroup(
			'smtp_encryption', 
			$Translation['smtp_encryption'], 
			$adminConfig['smtp_encryption'], 
			array(
				'' => $Translation['none'],
				'ssl' => 'SSL',
				'tls' => 'TLS'
			)
		); 
	?>
	<?php echo settings_textbox('smtp_port', $Translation['smtp_port'], $adminConfig['smtp_port'], $Translation['smtp_port_hint']); ?>
	<?php echo settings_textbox('smtp_user', $Translation['smtp_user'], $adminConfig['smtp_user']); ?>
	<?php echo settings_textbox('smtp_pass', $Translation['smtp_pass'], $adminConfig['smtp_pass'], '', 'password'); ?>

	<div class="form-group">
		<label class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"></label>
		<div class="col-sm-8 col-md-9 col-lg-6">
			<button type="submit" name="saveChanges" value="1" onclick="return jsValidateAdminSettings();" class="btn btn-primary btn-lg"><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation['save changes']; ?></button>
			<a href="pageSettings.php" class="btn btn-warning btn-lg hspacer-md"><i class="glyphicon glyphicon-remove"></i> <?php echo $Translation['cancel']; ?></a>
		</div>
	</div>

</form>

<div style="height: 600px;"></div>

<style>
	.form-group{
		margin-bottom: 1.5em;
	}
</style>

<script>
	$j(function(){
		// circumvent browser auto-completion of password field
		setTimeout(function(){ $j('#adminPassword').val(''); }, 500);

		// hide/show SMTP settings based on mail_function value
		var mail_function_observer = function(){
			var mail_function = 'mail';
			if($j('#mail_functionsmtp').prop('checked')) mail_function = 'smtp';

			if(mail_function == 'smtp'){
				$j('#smtp_server, #smtp_port, #smtp_user, #smtp_pass, [name=smtp_encryption]')
					.prop('readonly', false)
					.removeClass('text-muted bg-muted')
					.parents('.form-group')
					.removeClass('text-muted');
			}else{
				$j('#smtp_server, #smtp_port, #smtp_user, #smtp_pass, [name=smtp_encryption]')
					.prop('readonly', true)
					.addClass('text-muted bg-muted')
					.parents('.form-group')
					.addClass('text-muted');
			}
		};

		$j('#mail_functionsmtp, #mail_functionmail').click(mail_function_observer);
		mail_function_observer();
	});
</script>

<?php
	include("{$currDir}/incFooter.php");
?>
