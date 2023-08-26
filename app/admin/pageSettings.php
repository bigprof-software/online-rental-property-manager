<?php
	require(__DIR__ . '/incCommon.php');

	// only super admin can access this page!
	if(!Authentication::getSuperAdmin()) redirect('admin');

	$GLOBALS['page_title'] = $Translation['admin settings'];
	include(__DIR__ . '/incHeader.php');

	if(Request::has('saveChanges')) {
		// csrf check
		if(!csrf_token(true)) {
			?>
			<div class="alert alert-danger">
				<?php echo $Translation['invalid security token'] ; ?>
			</div>
			<?php
			include(__DIR__ . '/incFooter.php');
		}

		// validate inputs
		$errors = [];
		$post = $_POST;

		// if admin username changed, check if the new username already exists
		$oldAdmin = $adminConfig['adminUsername'];
		$adminUsername = makeSafe(strtolower($post['adminUsername']));
		if($oldAdmin != strtolower($post['adminUsername']) && sqlValue("select count(1) from membership_users where lcase(memberID)='$adminUsername'")) {
			$errors[] = $Translation['unique admin username error'] ;
		}

		// if anonymous username changed, check if the new username already exists
		$anonymousMember = makeSafe(strtolower($post['anonymousMember']));
		if($adminConfig['anonymousMember'] != strtolower($post['anonymousMember']) && sqlValue("select count(1) from membership_users where lcase(memberID)='$anonymousMember'")) {
			$errors[] = $Translation['unique anonymous username error'];
		}

		// if anonymous group name changed, check if the new group name already exists
		$anonymousGroup = makeSafe($post['anonymousGroup']);
		if($adminConfig['anonymousGroup'] != $post['anonymousGroup'] && sqlValue("select count(1) from membership_groups where name='$anonymousGroup'")) {
			$errors[] = $Translation['unique anonymous group name error'];
		}

		$adminPassword = $post['adminPassword'];
		if($adminPassword != '' && $adminPassword == $post['confirmPassword']) {
			$adminPassword = password_hash($adminPassword, PASSWORD_DEFAULT);
		} elseif($adminPassword != '' && $adminPassword != $post['confirmPassword']) {
			$errors[] = $Translation['admin password mismatch'];
		} else {
			$adminPassword = $adminConfig['adminPassword'];
		}

		if(!isEmail($post['senderEmail'])) {
			$errors[] = $Translation['invalid sender email'];
		}

		// make sure upload path is not absolute and is not ending with a slash
		// then try creating it if it doesn't exist
		// and raise error if it still doesn't exist
		$post['baseUploadPath'] = trim($post['baseUploadPath'], '/\\');
		$baseUploadPath = __DIR__ . '/../' . $post['baseUploadPath'];
		if(!is_dir($baseUploadPath)) @mkdir($baseUploadPath);
		if(!is_dir($baseUploadPath)) {
			$errors[] = $Translation['invalid upload path'];
		}

		if(count($errors)) {
			?>
			<div class="alert alert-danger">
				<?php echo $Translation['errors occurred'] ;  ?>
				<ul><li><?php echo implode('</li><li>', $errors); ?></li></ul>
				<?php echo $Translation['go back'] ;  ?>
			</div>
			<?php
			include(__DIR__ . '/incFooter.php');
		}

		$new_config = [
			'dbServer' => config('dbServer'),
			'dbUsername' => config('dbUsername'),
			'dbPassword' => config('dbPassword'),
			'dbDatabase' => config('dbDatabase'),
			'dbPort' => config('dbPort'),
			'appURI' => formatUri(preg_replace('/admin$/', '', dirname($_SERVER['SCRIPT_NAME']))),
			'host' => config('host'),

			'adminConfig' => [
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
				'mail_function' => in_array($post['mail_function'], ['smtp', 'mail']) ? $post['mail_function'] : 'mail',
				'smtp_server' => $post['smtp_server'],
				'smtp_encryption' => in_array($post['smtp_encryption'], ['ssl', 'tls']) ? $post['smtp_encryption'] : '',
				'smtp_port' => intval($post['smtp_port']) > 0 ? intval($post['smtp_port']) : 25,
				'smtp_user' => $post['smtp_user'],
				'smtp_pass' => $post['smtp_pass'],
				'googleAPIKey' => $post['googleAPIKey'],
				'baseUploadPath' => ($post['baseUploadPath'] ? $post['baseUploadPath'] : 'images'),
			]
		];

		// save changes
		$save_result = save_config($new_config);
		if($save_result === true) {
			// update admin member
			$newComment = str_replace(
				'<DATE>', 
				@date('Y-m-d'), 
				makeSafe($Translation['record updated automatically'])
			);

			$safeOldAdmin = makeSafe($oldAdmin);
			$safeNewAdmin = Authentication::safeMemberID($post['adminUsername']);
			$oldComment = sqlValue("SELECT `comments` FROM `membership_users` WHERE LCASE(`memberID`)='$safeOldAdmin'");

			$err = '';
			update('membership_users', [
				'memberID' => $safeNewAdmin,
				'passMD5' => $adminPassword,
				'email' => $post['senderEmail'],
				'comments' => "{$oldComment}\n{$newComment}",
			], ['memberID' => $safeOldAdmin], $err);

			if(!strlen($err) && $safeNewAdmin != $safeOldAdmin)
				Authentication::signInAsAdmin();

			// update record ownership if admin username changed
			// it's OK to run this query if admin username hasn't been changed
			// as MySQL won't actually make any updates in this case
			// and the performance cost would be almost zero
			sql("UPDATE `membership_userrecords` SET `memberID`='{$safeNewAdmin}' WHERE `memberID`='{$safeOldAdmin}'", $eo);

			// update anonymous group name if changed
			if($adminConfig['anonymousGroup'] != $post['anonymousGroup']) {
				sql("UPDATE `membership_groups` SET `name`='{$anonymousGroup}' WHERE `name`='" . makeSafe($adminConfig['anonymousGroup']) . "'", $eo);
			}

			// update anonymous username if changed
			$safeOldGuest = Authentication::safeMemberID($adminConfig['anonymousMember']);
			$safeNewGuest = Authentication::safeMemberID($post['anonymousMember']);
			if($safeNewGuest != $safeOldGuest) {
				sql("UPDATE `membership_users` SET `memberID`='{$safeNewGuest}' WHERE `memberID`='{$safeOldGuest}'", $eo);
				sql("UPDATE `membership_userrecords` SET `memberID`='{$safeNewGuest}' WHERE `memberID`='{$safeOldGuest}'", $eo);
			}

			// display status
			echo "<div class=\"alert alert-success\"><h2>{$Translation['admin settings saved']}</h2></div>";
		} else {
			// display status
			echo "<div class=\"alert alert-danger\"><h2>" . str_replace('<ERROR>', $save_result['error'], $Translation['admin settings not saved']) . "</h2></div>";
		}

		// exit
		include(__DIR__ . '/incFooter.php');
	}

	function setNewPasswordAttribute($html) {
		return str_replace(' type="password" ', ' type="password" autocomplete="new-password" ', $html);
	}

	function settings_textbox($name, $label, $value, $hint = '', $type = 'text') {
		ob_start();
		?>
		<div class="form-group">
			<label for="<?php echo $name; ?>" class="col-sm-4 col-md-3 col-lg-offset-1 control-label"><?php echo str_ireplace('<br>', ' ', $label); ?></label>
			<div class="col-sm-8 col-md-9 col-lg-6">
				<input type="<?php echo $type; ?>" name="<?php echo $name; ?>" id="<?php echo $name; ?>" value="<?php echo html_attr($value); ?>" class="form-control">
				<?php if($hint) { ?>
					<span class="help-block"><?php echo $hint; ?></span>
				<?php } ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	function settings_textarea($name, $label, $value, $height = 6, $hint = '') {
		ob_start();
		?>
		<div class="form-group">
			<label for="<?php echo $name; ?>" class="col-sm-4 col-md-3 col-lg-offset-1 control-label"><?php echo str_ireplace('<br>', ' ', $label); ?></label>
			<div class="col-sm-8 col-md-9 col-lg-6">
				<textarea rows="<?php echo abs($height); ?>" name="<?php echo $name; ?>" id="<?php echo $name; ?>" class="form-control"><?php echo html_attr(str_replace(['\r', '\n'], ['', "\n"], $value)); ?></textarea>
				<?php if($hint) { ?>
					<span class="help-block"><?php echo $hint; ?></span>
				<?php } ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	function settings_radiogroup($name, $label, $value, $options) {
		ob_start();
		?>
		<div class="form-group">
			<label class="col-sm-4 col-md-3 col-lg-offset-1 control-label"><?php echo str_ireplace('<br>', ' ', $label); ?></label>
			<div class="col-sm-8 col-md-9 col-lg-6">
				<?php foreach($options as $val => $display) { ?>
					<div class="radio-inline">
						<label>
							<input type="radio" 
								name="<?php echo $name; ?>" 
								id="<?php echo $name; ?><?php echo html_attr($val); ?>" 
								value="<?php echo html_attr($val); ?>" 
								<?php if($value == $val) { ?>checked<?php } ?>
							>
							<?php echo $display; ?>
						</label>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	function settings_checkbox($name, $label, $value, $set_value, $hint = '') {
		ob_start();
		?>
		<div class="form-group">
			<label class="col-sm-4 col-md-3 col-lg-offset-1 control-label"></label>
			<div class="col-sm-8 col-md-9 col-lg-6">
				<div class="checkbox">
					<label>
						<input type="checkbox" 
							name="<?php echo $name; ?>" 
							id="<?php echo $name; ?>" 
							value="<?php echo html_attr($value); ?>" 
							<?php if($value == $set_value) { ?>checked<?php } ?>
						>
						<?php echo str_ireplace('<br>', ' ', $label); ?>
					</label>
				</div>
				<?php if($hint) { ?>
					<span class="help-block"><?php echo $hint; ?></span>
				<?php } ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

?>

<div class="page-header"><h1><?php echo $Translation['admin settings'] ; ?></h1></div>

<form method="post" action="pageSettings.php" class="form-horizontal">
	<?php echo csrf_token(); ?>

	<div class="form-group" style="margin-top: 2em; margin-bottom: 2em;">
		<div class="col-sm-6">
			<div class="input-group">
				<input type="text" class="form-control" id="search-settings" autofocus>
				<span class="input-group-btn">
					<button class="btn btn-default" type="button" id="find-setting"><i class="glyphicon glyphicon-search"></i></button>
				</span>
			</div>
		</div>
		<div class="col-sm-6 text-right">
			<div class="visible-xs-block" style="height: 1em;"></div>
			<button type="submit" name="saveChanges" value="1" onclick="return jsValidateAdminSettings();" class="btn btn-primary btn-lg hspacer-md"><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation['save changes']; ?></button>

			<a href="pageSettings.php" class="btn btn-warning btn-lg"><i class="glyphicon glyphicon-remove"></i> <?php echo $Translation['cancel']; ?></a>
		</div>
	</div>

	<!-- Nav tabs -->
	<ul class="nav nav-tabs">
		<li class="active"><a href="#display-settings" data-toggle="tab"><i class="glyphicon glyphicon-eye-open"></i> <?php echo $Translation['Appearance']; ?></a></li>
		<li><a href="#signup-settings" data-toggle="tab"><i class="glyphicon glyphicon-user"></i>  <?php echo $Translation['signup']; ?></a></li>
		<li><a href="#mail-settings" data-toggle="tab"><i class="glyphicon glyphicon-envelope"></i> <?php echo $Translation['Mail']; ?></a></li>
		<li><a href="#users-settings" data-toggle="tab"><i class="glyphicon glyphicon-user"></i> <?php echo $Translation['Preconfigured users and groups']; ?></a></li>
		<li><a href="#app-settings" data-toggle="tab"><i class="glyphicon glyphicon-cog"></i> <?php echo $Translation['Application']; ?></a></li>
	</ul>

	<!-- Tab panes -->
	<div class="tab-content">
		<div class="tab-pane active" id="display-settings">
			<div style="height: 3em;"></div>
			<?php echo settings_textbox('groupsPerPage', $Translation['groups per page'], $adminConfig['groupsPerPage']); ?>
			<?php echo settings_textbox('membersPerPage', $Translation['members per page'], $adminConfig['membersPerPage']); ?>
			<?php echo settings_textbox('recordsPerPage', $Translation['records per page'], $adminConfig['recordsPerPage']); ?>
			<?php echo settings_checkbox('hide_twitter_feed', $Translation['hide twitter feed'], '1', $adminConfig['hide_twitter_feed'], $Translation['twitter feed']); ?>
			<?php echo settings_textarea('maintenance_mode_message', $Translation['maintenance mode message'], $adminConfig['maintenance_mode_message']); ?>
		</div>

		<div class="tab-pane" id="signup-settings">
			<div style="height: 3em;"></div>
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

			<hr>

			<?php echo settings_textbox('custom1', $Translation['members custom field 1'], $adminConfig['custom1']); ?>
			<?php echo settings_textbox('custom2', $Translation['members custom field 2'], $adminConfig['custom2']); ?>
			<?php echo settings_textbox('custom3', $Translation['members custom field 3'], $adminConfig['custom3']); ?>
			<?php echo settings_textbox('custom4', $Translation['members custom field 4'], $adminConfig['custom4']); ?>

			<hr>

			<?php echo settings_textbox('approvalSubject', $Translation['member approval email subject'], $adminConfig['approvalSubject'], $Translation['member approval email subject control']); ?>
			<?php echo settings_textarea('approvalMessage', $Translation['member approval email message'], $adminConfig['approvalMessage']); ?>
		</div>

		<div class="tab-pane" id="mail-settings">
			<div style="height: 3em;"></div>
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
		</div>

		<div class="tab-pane" id="users-settings">
			<div style="height: 3em;"></div>
			<?php echo settings_textbox('adminUsername', $Translation['admin username'], $adminConfig['adminUsername']); ?>
			<?php echo setNewPasswordAttribute(settings_textbox('adminPassword', $Translation['admin password'], '', $Translation['change admin password'], 'password')); ?>
			<?php echo setNewPasswordAttribute(settings_textbox('confirmPassword', $Translation['confirm password'], '', '', 'password')); ?>
			<hr>
			<?php echo settings_textbox('anonymousGroup', $Translation['anonymous group'], $adminConfig['anonymousGroup']); ?>
			<?php echo settings_textbox('anonymousMember', $Translation['anonymous user name'], $adminConfig['anonymousMember']); ?>
		</div>

		<div class="tab-pane" id="app-settings">
			<div style="height: 3em;"></div>
			<?php echo settings_textbox('MySQLDateFormat', $Translation['MySQL date'], $adminConfig['MySQLDateFormat'], $Translation['MySQL reference']); ?>
			<?php echo settings_textbox('PHPDateFormat', $Translation['PHP short date'], $adminConfig['PHPDateFormat'], $Translation['PHP manual']); ?>
			<?php echo settings_textbox('PHPDateTimeFormat', $Translation['PHP long date'], $adminConfig['PHPDateTimeFormat'], $Translation['PHP manual']); ?>
			<?php echo settings_textbox('googleAPIKey', $Translation['google API key'], $adminConfig['googleAPIKey'], "<a target=\"_blank\" href=\"https://bigprof.com/appgini/google-maps-api-key\">{$Translation['google API key instructions']}</a>"); ?>

			<?php echo settings_textbox(
				'baseUploadPath', 
				$Translation['base upload path'], 
				$adminConfig['baseUploadPath'], 
				$Translation['base upload path instructions'] .
				'<div class="text-danger hidden text-bold" id="baseUploadPath-change-warning">' .
					$Translation['base upload path change warning'] .
				'</div>'
			); ?>
		</div>
	</div>
</form>

<div style="height: 3em;"></div>

<style>
	.form-group{
		margin-bottom: 1.5em;
	}
	.nav-tabs li > a { display: block !important; }
</style>

<script>
	$j(function() {
		// circumvent browser auto-completion of password field
		setTimeout(function() { $j('#adminPassword').val(''); }, 500);

		// hide/show SMTP settings based on mail_function value
		var mail_function_observer = function() {
			var mail_function = 'mail';
			if($j('#mail_functionsmtp').prop('checked')) mail_function = 'smtp';

			if(mail_function == 'smtp') {
				$j('#smtp_server, #smtp_port, #smtp_user, #smtp_pass, [name=smtp_encryption]')
					.prop('readonly', false)
					.removeClass('text-muted bg-muted')
					.parents('.form-group')
					.removeClass('text-muted');
			} else {
				$j('#smtp_server, #smtp_port, #smtp_user, #smtp_pass, [name=smtp_encryption]')
					.prop('readonly', true)
					.addClass('text-muted bg-muted')
					.parents('.form-group')
					.addClass('text-muted');
			}
		};

		$j('#mail_functionsmtp, #mail_functionmail').click(mail_function_observer);
		mail_function_observer();

		// if baseUploadPath changed, warn about existing uploads
		$j('#baseUploadPath').on('change keyup', function() {
			$j('#baseUploadPath-change-warning').removeClass('hidden');
		})

		_tabsContent = {};
		var findSetting = function(searchStr) {
			// obtain all labels to prepare for enabling search of settings
			if(_tabsContent['app-settings'] == undefined) {
				$j('.tab-pane label').each(function() {
					var tabId = $j(this).parents('.tab-pane').attr('id');
					if(_tabsContent[tabId] === undefined) _tabsContent[tabId] = [];
					_tabsContent[tabId].push($j(this).text().trim().replace(/\s+/, ' ').toLowerCase());
				});
			}

			var highlightClasses = 'bg-warning text-danger';

			// at least 2 characters to start search, else remove highlights and return
			searchStr = searchStr.trim().toLowerCase();
			if(searchStr.length < 2) return $j('.tab-pane label').removeClass(highlightClasses).slice(0, 0);

			// function to check if given tabId has matches
			var tabHasMatches = function(tabId) {
				return _tabsContent[tabId].filter(function(l) { return l.indexOf(searchStr) > -1; }).length > 0;
			};

			// do we want to 'find next'? (if active tab has highlights that match search str)
			var activeTabId = $j('.nav-tabs li.active a').attr('href').replace('#', '');
			var findNext = tabHasMatches(activeTabId) && $j('#' + activeTabId + ' label').hasClass(highlightClasses);

			for(var tabId in _tabsContent) {
				if(!_tabsContent.hasOwnProperty(tabId)) continue;
				if(findNext && tabId == activeTabId) continue;

				// if search string not found in current tab, continue to next tab
				if(!tabHasMatches(tabId)) continue;

				$j('.tab-pane label').removeClass(highlightClasses);

				// activate matching tab
				$j('a[href="#' + tabId + '"]').tab('show');

				// highlight matching label
				_tabsContent[tabId].map(function(l, i) {
					if(l.indexOf(searchStr) == -1) return;
					$j('#' + tabId + ' label').eq(i).addClass(highlightClasses);
				});

				return;
			}

			// no matches found and not 'find next'? remove highglights
			if(!findNext) $j('.tab-pane label').removeClass(highlightClasses);
		}

		// find matching setting(s) while user searches for them
		$j('#search-settings').on('keyup', function() {
			findSetting($j(this).val());
		});

		// find (next) matching setting when user clicks search button
		$j('#find-setting').on('click', function() {
			findSetting($j('#search-settings').val());
		});

		// if search-settings param provided in url, use it to find the corresponding setting
		var lookingFor = location.search.match(/search-settings=(\w*)/);
		if(lookingFor && lookingFor.length > 1) {
			$j('#search-settings').val(lookingFor[1]).trigger('keyup');
		}
	});
</script>

<?php include(__DIR__ . '/incFooter.php');
