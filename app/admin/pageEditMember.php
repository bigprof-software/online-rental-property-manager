<?php
	require(__DIR__ . '/incCommon.php');

	$groupID = $addend = $superadmin = $email = $isApproved = $isBanned = $customs = $comments = null;

	$GLOBALS['page_title'] = $Translation['view members'];

	// get memberID of guest user
	$anonMemberID = strtolower($adminConfig['anonymousMember']);
	$anonGroup = $adminConfig['anonymousGroup'];

	/* no editing of guest user */
	if(strtolower(Request::val('memberID')) == $anonMemberID || strtolower(Request::val('oldMemberID')) == $anonMemberID) {
		redirect('admin/pageViewMembers.php');
		exit;
	}

	include(__DIR__ . '/incHeader.php');

	$memberID = '';
	// request to save changes?
	if(Request::has('saveChanges')) {
		// csrf check
		if(!csrf_token(true)) {
			echo Notification::show([
				'message' => $Translation['invalid security token'],
				'class' => 'danger',
				'dismiss_seconds' => 5000
			]);
			include(__DIR__ . '/incFooter.php');
		}

		// validate data
		$oldMemberID = makeSafe(strtolower(Request::val('oldMemberID')));
		$password = makeSafe(Request::val('password'));
		$email = isEmail(Request::val('email'));
		$groupID = intval(Request::val('groupID'));
		$isApproved = (Request::val('isApproved') == 1 ? 1 : 0);
		$isBanned = (Request::val('isBanned') == 1 ? 1 : 0);
		$customs = [];
		for($cust = 1; $cust <= 4; $cust++)
			$customs[$cust] = makeSafe(Request::val("custom{$cust}"));
		$comments = makeSafe(Request::val('comments'));

		###############################
		// new member or old?
		if(!$oldMemberID) { // new member
			// make sure member name is unique
			$memberID = is_allowed_username(Request::val('memberID'));
			if(!$memberID) {
				echo Notification::show([
					'message' => $Translation['username error'],
					'class' => 'danger',
					'dismiss_seconds' => 5000
				]);
				include(__DIR__ . '/incFooter.php');
			}

			// add member
			$customs_sql = '';
			foreach($customs as $i => $cust_value) {
				$customs_sql .= "custom{$i}='{$cust_value}', ";
			}
			sql("INSERT INTO `membership_users` set memberID='{$memberID}', passMD5='" . password_hash($password, PASSWORD_DEFAULT) . "', email='{$email}', signupDate='" . @date('Y-m-d') . "', groupID='{$groupID}', isBanned='{$isBanned}', isApproved='{$isApproved}', {$customs_sql} comments='{$comments}'", $eo);

			if($isApproved) {
				notifyMemberApproval($memberID);
			}

			// redirect to member editing page
			redirect("admin/pageEditMember.php?memberID={$memberID}&new_member=1");
			exit;
		} else { // old member
			// make sure new member username, if applicable, is valid
			$memberID = makeSafe(strtolower(Request::val('memberID')));

			// for super admin user, no username change allowed here
			$superadmin = (strtolower($adminConfig['adminUsername']) == $oldMemberID);
			if($superadmin) $memberID = $oldMemberID;

			if($oldMemberID != $memberID)
				$memberID = is_allowed_username(Request::val('memberID'));

			if(!$memberID) {
				echo Notification::show([
					'message' => $Translation['username error'],
					'class' => 'danger',
					'dismiss_seconds' => 5000
				]);
				include(__DIR__ . '/incFooter.php');
			}

			// get current approval state
			$oldIsApproved = sqlValue("select isApproved from membership_users where lcase(memberID)='{$oldMemberID}'");

			// get member group ID
			$oldGroupID = sqlValue("select groupID from membership_users where lcase(memberID)='{$oldMemberID}'");

			// update member info
			$customs_sql = '';
			$non_superadmin_sql = "passMD5=" . ($password != '' ? "'" . password_hash($password, PASSWORD_DEFAULT) . "'" : "passMD5") . ", email='{$email}', groupID='{$groupID}', isBanned='{$isBanned}', isApproved='{$isApproved}', ";
			foreach($customs as $i => $cust_value) {
				$customs_sql .= "custom{$i}='{$cust_value}', ";
			}      

			if($superadmin) {
				$admin_pass_hash = makeSafe($adminConfig['adminPassword'], false);
				$admin_email = makeSafe($adminConfig['senderEmail'], false);
				$non_superadmin_sql = "passMD5='{$admin_pass_hash}', email='{$admin_email}', isBanned='0', isApproved='1', ";
			}

			$upQry = "UPDATE `membership_users` set memberID='{$memberID}', {$non_superadmin_sql} {$customs_sql} comments='{$comments}' WHERE lcase(memberID)='{$oldMemberID}'";
			sql($upQry, $eo);

			// if memberID was changed, update membership_userrecords, membership_userpermissions
			if($oldMemberID != $memberID) {
				sql("update membership_userrecords     set memberID='{$memberID}' where lcase(memberID)='{$oldMemberID}'", $eo);
				sql("update membership_userpermissions set memberID='{$memberID}' where lcase(memberID)='{$oldMemberID}'", $eo);
			}

			// if groupID was changed, update membership_userrecords
			if($oldGroupID != $groupID && !$superadmin) {
				sql("update membership_userrecords set groupID='{$groupID}' where lcase(memberID)='{$oldMemberID}'", $eo);
			}

			// if member was approved, notify him
			if($isApproved && !$oldIsApproved) {
				notifyMemberApproval($memberID);
			}

			// redirect to member editing page
			redirect("admin/pageEditMember.php?saved=1&memberID=" . urlencode($memberID));
			exit;
		}
	} elseif(Request::val('memberID')) {
		// we have an edit request for a member
		$memberID = makeSafe(strtolower(Request::val('memberID')));
		$superadmin = (strtolower($adminConfig['adminUsername']) == $memberID);
	} elseif(Request::val('groupID')) {
		// show the form for adding a new member, and pre-select the provided group
		$groupID = intval(Request::val('groupID'));
		$group_name = strip_tags(sqlValue("select name from membership_groups where groupID='$groupID'"));
		if($group_name)
			$addend = " to '{$group_name}'";
	}

	if($memberID != '') {
		// fetch group data to fill in the form below
		$res = sql("select * from membership_users where lcase(memberID)='{$memberID}'", $eo);
		if(!($row = db_fetch_assoc($res))) {
			// no such member exists
			echo Notification::show([
				'message' => $Translation['member not found'],
				'class' => 'danger',
				'dismiss_seconds' => 5000
			]);
			include(__DIR__ . '/incFooter.php');
		}

		// get member data
		$email = $row['email'];
		$groupID = $row['groupID'];
		$isApproved = $row['isApproved'];
		$isBanned = $row['isBanned'];
		$customs = [];
		for($cust = 1; $cust <= 4; $cust++) {
			$customs[$cust] = html_attr($row["custom{$cust}"]);
		}
		$comments = html_attr($row['comments']);

		//display dismissible alert for new members and successful saves
		if(Request::has('new_member')) {
			echo Notification::show([
				'message' => str_replace('<USERNAME>', "<b><i>{$memberID}</i></b>", $Translation['member added']),
				'class' => 'success',
				'dismiss_seconds' => 20
			]);
		} elseif(Request::has('saved')) {
			echo Notification::show([
				'message' => str_replace('<USERNAME>', "<b><i>{$memberID}</i></b>", $Translation['member updated']),
				'class' => 'success',
				'dismiss_seconds' => 20
			]);
		}
	}

	$userPermissionsNote = '';
	if($memberID != '' && $groupID != sqlValue("select groupID from membership_groups where name='Admins'")) {
		$userPermissionsNote = '<span class="help-block">' . str_replace('<GROUPID>', $groupID, $Translation["user has group permissions"]) . '</span>';

		if(sqlValue("select count(1) from membership_userpermissions where memberID='$memberID'") > 0) {
			$userPermissionsNote = '<span class="help-block">' . $Translation["user has special permissions"] . '</span>';
		}

		$userPermissionsNote .= '<button type="button" class="btn btn-danger" id="special-permissions">' . html_attr($Translation['set user special permissions']) . '</button>';
	}
?>

<div class="page-header">
	<h1>
		<?php echo ($memberID ? str_replace('<MEMBERID>', '<span class="text-primary">' . $memberID . '</span>', $Translation["edit member"]) : $Translation["add new member"] . $addend); ?>
		<div class="pull-right">
			<div class="btn-group">
				<a href="pageViewMembers.php" class="btn btn-default btn-lg"><i class="glyphicon glyphicon-arrow-left"></i> <span class="hidden-xs hidden-sm"><?php echo $Translation['back to members']; ?></span></a>
				<?php if($memberID) { ?>
					<a href="pageViewRecords.php?memberID=<?php echo urlencode($memberID); ?>" class="btn btn-default btn-lg"><i class="glyphicon glyphicon-th"></i> <span class="hidden-xs hidden-sm"><?php echo $Translation['View member records']; ?></span></a>
					<a href="pageMail.php?memberID=<?php echo urlencode($memberID); ?>" class="btn btn-default btn-lg"><i class="glyphicon glyphicon-envelope"></i> <span class="hidden-xs hidden-sm"><?php echo $Translation['send message to member']; ?></span></a>
				<?php } ?>
			</div>
		</div>
		<div class="clearfix"></div>
	</h1>
</div>


<div style="height: 3em;"></div>

<?php if($superadmin) { ?>
	<div class="alert alert-warning text-center"><?php echo $Translation["admin member"]; ?></div>
<?php } ?>

<form method="post" action="pageEditMember.php" class="form-horizontal">
	<?php echo csrf_token(); ?>
	<input type="hidden" name="oldMemberID" value="<?php echo ($memberID ? html_attr($memberID) : ""); ?>">

	<?php if(!$superadmin) { /* non-admin user fields */ ?>
		<div class="form-group ">
			<label for="memberID" class="label-classes control-label"><?php echo $Translation["member username"]; ?></label>
			<div class="input-classes">
				<input type="text" class="form-control" name="memberID" id="memberID" value="<?php echo html_attr($memberID); ?>" autofocus>
				<div class="input-group hidden" id="memberID-input-group">
					<span class="input-group-btn">
						<button class="btn btn-default" type="button"><i class="glyphicon glyphicon-pencil"></i></button>
					</span>
				</div>
				<span id="username-available" class="help-block hidden"><i class="glyphicon glyphicon-ok"></i> <?php echo str_ireplace(["'", '"', '<memberid>'], '', $Translation['user available']); ?></span>
				<span id="username-not-available" class="help-block hidden"><i class="glyphicon glyphicon-remove"></i> <?php echo str_ireplace(["'", '"', '<memberid>'], '', $Translation['username invalid']); ?></span>
			</div>
		</div>

		<div class="row password-hider vspacer-lg hidden">
			<div class="label-classes"></div>
			<div class="input-classes">
				<button type="button" class="btn btn-default"><i class="glyphicon glyphicon-pencil"></i> <?php echo $Translation['Update password']; ?></button>
			</div>
		</div>

		<div class="form-group">
			<label for="password" class="label-classes control-label"><?php echo $Translation["password"]; ?></label>
			<div class="input-classes">
				<input  class="form-control" type="password" name="password" id="password" value="" autocomplete="new-password">
				<?php echo ($memberID ? "<span class='help-block'>" . $Translation["change password"] : "" . "</span>"); ?>
			</div>
		</div>

		<div class="form-group">
			<label for="confirmPassword" class="label-classes control-label"><?php echo $Translation["confirm password"]; ?> </label>
			<div class="input-classes">
				<input class="form-control" type="password" name="confirmPassword" id="confirmPassword" value="" autocomplete="new-password">
			</div>
		</div>

		<div class="form-group">
			<label for="email" class="label-classes control-label"><?php echo $Translation["email"]; ?> </label>
			<div class="input-classes">
				<input class="form-control" type="email" id="email" name="email" value="<?php echo $email; ?>" required>
			</div>
		</div>

		<div class="form-group">
			<label for="group" class="label-classes control-label"><?php echo $Translation["group"]; ?></label>
			<div class="input-classes">
				<?php
					$safe_anonGroup = makeSafe($anonGroup, false);
					echo bootstrapSQLSelect('groupID', "select groupID, name from membership_groups where name!='{$safe_anonGroup}' order by name", $groupID);
					echo $userPermissionsNote;
				?>
			</div>
		</div>

		<div class="form-group">
			<label class="label-classes control-label"></label>
			<div class="input-classes">
				<div class="checkbox">
					<label>
						<input  type="checkbox" name="isApproved" value="1" <?php echo ($isApproved ? "checked" : ($memberID ? "" : "checked")); ?>>
						<?php echo $Translation["approved"]; ?>
					</label>
				</div>
			</div>
		</div>

		<div class="form-group">
			<label class="label-classes control-label"></label>
			<div class="input-classes">
				<div class="checkbox">
					<label>
						<input type="checkbox" name="isBanned" value="1" <?php echo ($isBanned ? 'checked' : ''); ?>>
						<?php echo $Translation['banned']; ?>
					</label>
				</div>
			</div>
		</div>
	<?php } /* end of non-admin user fields */ ?>

	<?php for($cust = 1; $cust <= 4; $cust++) { ?>
		<?php if($adminConfig["custom{$cust}"] != '') { ?>
			<div class="form-group">
				<label for="custom<?php echo $cust; ?>" class="label-classes control-label"><?php echo htmlspecialchars($adminConfig["custom{$cust}"]); ?></label>
				<div class="input-classes">
					<input class="form-control" type="text" name="custom<?php echo $cust; ?>" id="custom<?php echo $cust; ?>" value="<?php echo $customs[$cust]; ?>" >
				</div>
			</div>
		<?php } ?>
	<?php } ?>

	<div class="form-group">
		<label for="comments" class="label-classes control-label"><?php echo $Translation["comments"]; ?> </label>
		<div class="input-classes">
			<textarea id="comments" name="comments" rows="10" class="form-control"><?php echo $comments; ?></textarea>
		</div>
	</div>

	<div class="form-group">
		<label class="label-classes control-label"></label>
		<div class="input-classes">
			<button type="button" id="saveChanges" class="btn btn-primary btn-lg"><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation["save changes"]; ?></button>
			<?php if($memberID != '') { /* for existing members, cancel reloads the member */ ?>
				<a href="pageEditMember.php?memberID=<?php echo urlencode($memberID); ?>" class="btn btn-warning btn-lg hspacer-md"><i class="glyphicon glyphicon-remove"></i> <?php echo $Translation['cancel']; ?></a>
				<a href="pageViewMembers.php" class="btn btn-default btn-lg hspacer-md"><i class="glyphicon glyphicon-arrow-left"></i> <?php echo $Translation['back to members']; ?></a>
			<?php } else { /* for new members, cancel goes to list of members */ ?>
				<a href="pageViewMembers.php" class="btn btn-warning btn-lg hspacer-md"><i class="glyphicon glyphicon-remove"></i> <?php echo $Translation['cancel']; ?></a>
			<?php } ?>
		</div>
	</div>
</form>

<style>
	#username-available, #username-not-available{ cursor: pointer; }
</style>


<script>
	$j(function() {
		var new_member = !$j('[name=oldMemberID]').val().length;

		var uaro; // user availability request object
		var check_user = function() {
			// abort previous request, if any
			if(uaro != undefined) uaro.abort();

			if(!$j('#memberID').length) return true; // username field hidden

			var currentUser = $j('[name=oldMemberID]').val();
			var memberID = $j('#memberID').val();

			/* no username change, so no need to check it */
			if(currentUser.length && currentUser == memberID) {
				$j('#username-available, #username-not-available')
					.addClass('hidden')
					.parents('.form-group').removeClass('has-error has-success');
				return;
			}

			/* username is empty so highlight the error and return without further checks */
			if(!memberID.length) {
				$j('#username-not-available')
					.removeClass('hidden')
					.parents('.form-group').addClass('has-error');
				return;
			}

			uaro = $j.ajax(
				'../checkMemberID.php', {
					type: 'GET',
					data: {
						memberID: memberID,
						currentUser: currentUser
					},
					beforeSend: function() {
						$j('#username-available, #username-not-available')
							.addClass('hidden')
							.parents('.form-group').removeClass('has-error has-success');
					},
					success: function(resp) {
						if(resp.indexOf('username-available') > -1) {
							$j('#username-available')
								.removeClass('hidden')
								.parents('.form-group').addClass('has-success');
						} else {
							$j('#username-not-available')
								.removeClass('hidden')
								.parents('.form-group').addClass('has-error');
						}
					}
				}
			);
		}

		var validate_password = function() {
			if(!$j('#password').length) return true; // password field hidden

			/* reset error highlights */
			$j('#password, #confirmPassword').parents('.form-group').removeClass('has-error');
			$j('#password-mismatch-alert').remove();

			var p1 = $j('#password').val();
			var p2 = $j('#confirmPassword').val();

			if((p1 != '' && p1 != p2) || (p1 == '' && new_member)) {
				show_notification({
					message: <?php echo json_encode($Translation['password mismatch']); ?>,
					'class': 'danger',
					dismiss_seconds: 10,
					id: 'password-mismatch-alert'
				});
				$j('#password, #confirmPassword').parents('.form-group').addClass('has-error');
				$j('#password').focus();
				return false;
			}

			return true;
		}

		var validate_email = function() {
			if(!$j('#email').length) return true; // email field hidden

			/* reset error highlights */
			$j('#email').parents('.form-group').removeClass('has-error');
			$j('#invalid-email-alert').remove();

			/* source: https://stackoverflow.com/a/46181/1945185 */
			var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			if(!re.test($j('#email').val())) {
				show_notification({
					message: <?php echo json_encode($Translation['email invalid']); ?>,
					'class': 'danger',
					dismiss_seconds: 20,
					id: 'invalid-email-alert'
				});
				$j('#email').focus().parents('.form-group').addClass('has-error');

				return false;
			}

			return true;
		}

		var validate_group = function() {
			if(!$j('#groupID').length) return true; // group field hidden

			/* reset error highlights */
			$j('#groupID').parents('.form-group').removeClass('has-error');
			$j('#invalid-group-alert').remove();

			if(!$j('#groupID').val()) {
				show_notification({
					message: <?php echo json_encode($Translation['group invalid']); ?>,
					'class': 'danger',
					dismiss_seconds: 20,
					id: 'invalid-group-alert'
				});
				$j('#groupID').focus().parents('.form-group').addClass('has-error');

				return false;
			}

			return true;
		}

		var disable_existing_member_edits_initially = function() {
			// only apply for existng members
			if(new_member) return;

			// move focus to email field
			$j('#email').focus();

			// disable username field and add edit button
			$j('#memberID').prop('disabled', true).prependTo('#memberID-input-group');
			$j('#memberID-input-group')
				.removeClass('hidden')
				.find('.input-group-btn').on('click', function() {
					$j('#memberID').prop('disabled', false).focus();
				});

			// hide password fields and show 'change password' button
			$j('#password, #confirmPassword').parents('.form-group').addClass('hidden');
			$j('.password-hider')
				.removeClass('hidden')
				.find('button').on('click', function() {
					$j('#password, #confirmPassword').parents('.form-group').removeClass('hidden');
					$j('.password-hider').addClass('hidden');
					$j('#password').focus();
				})
		}

		$j('#username-available, #username-not-available').click(function() { $j('#memberID').focus(); });

		$j('#memberID').on('keyup blur', check_user);

		/* disable submit button during ajax requests */
		$j(document)
			.ajaxStart(function() {
				$j('#saveChanges').prop('disabled', true);
			}).ajaxStop(function() {
				$j('#saveChanges').prop('disabled', false);
			});

		/* validate form before submitting */
		$j('#saveChanges').click(function() {
			/* don't submit form if any ajax requests are still active */
			if($j.active) return false;

			$j('#general-error-alert').remove();

			if(!validate_password()) return false;
			if(!validate_email()) return false;
			if(!validate_group()) return false;
			check_user();

			if($j('.form-group.has-error').length) {
				/* show general error if no other error alerts displayed */
				if(!$j('.notifcation-placeholder .alert:not(.invisible)').length) {
					show_notification({
						message: <?php echo json_encode($Translation['fix errors before submitting']); ?>,
						'class': 'danger',
						dismiss_seconds: 20,
						id: 'general-error-alert'
					});
					$j('.has-error').children('input').focus();
				}
				return false;
			}

			// enable disabled memberID before submit
			$j('#memberID').prop('disabled', false);

			$j('form').append('<input type="hidden" name="saveChanges" value="1">').submit();
			return true;
		});

		/* special permissions button */
		$j('#special-permissions').click(function() {
			if(confirm(<?php echo json_encode($Translation['sure continue']); ?>)) {
				window.location = 'pageEditMemberPermissions.php?memberID=' + encodeURIComponent($j('[name=oldMemberID]').val());
			}
		});

		// apply label classes, centrally
		$j('.label-classes').addClass('col-sm-4 col-md-3 col-lg-2 col-lg-offset-2');
		$j('.input-classes').addClass('col-sm-8 col-md-9 col-lg-6');

		disable_existing_member_edits_initially();
	})
</script>

<?php
	include(__DIR__ . '/incFooter.php');
