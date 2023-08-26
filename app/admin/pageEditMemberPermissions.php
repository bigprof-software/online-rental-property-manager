<?php
	require(__DIR__ . '/incCommon.php');

	// tables list
	$tables = getTableList();

	// ensure that a memberID is provided
	if(!Request::has('memberID')) {
		// error in request. redirect to members page.
		redirect('admin/pageViewMembers.php');
	}

	$memberID = new Request('memberID', 'strtolower');

	// validate memberID exists and is not guest and is not admin
	$anonymousMember = strtolower($adminConfig['anonymousMember']);
	$anonymousGroup = $adminConfig['anonymousGroup'];
	$anonGroupID = sqlValue("select groupID from membership_groups where lcase(name)='" . strtolower(makeSafe($anonymousGroup)) . "'");
	$adminGroupID = sqlValue("select groupID from membership_groups where name='Admins'");
	$groupID = sqlValue("select groupID from membership_users where lcase(memberID)='{$memberID->sql}'");
	$group = strip_tags(sqlValue("select name from membership_groups where groupID='{$groupID}'"));
	if($groupID == $anonGroupID || $memberID->raw == $anonymousMember || !$groupID || $groupID == $adminGroupID || $memberID->raw == $adminConfig['adminUsername']) {
		// error in request. redirect to members page.
		redirect('admin/pageViewMembers.php');
	}

	// request to save changes?
	if(Request::has('saveChanges')) {
		// csrf check
		if(!csrf_token(true)) die($Translation['invalid security token']);

		// validate data
		foreach ($tables as $t => $tc) {
			eval(" 
					\${$t}_insert = checkPermissionVal('{$t}_insert');
					\${$t}_view = checkPermissionVal('{$t}_view');
					\${$t}_edit = checkPermissionVal('{$t}_edit');
					\${$t}_delete = checkPermissionVal('{$t}_delete');
				");
		}

		// reset then add member permissions
		sql("delete from membership_userpermissions where lcase(memberID)='{$memberID->sql}'", $eo);

		// add new member permissions
		$query = "insert into membership_userpermissions (memberID, tableName, allowInsert, allowView, allowEdit, allowDelete) values ";
		foreach ($tables as $t => $tc) {
			$insert = "{$t}_insert";
			$view = "{$t}_view";
			$edit = "{$t}_edit";
			$delete = "{$t}_delete";
			$query .= "('{$memberID->sql}', '{$t}', '${$insert}', '${$view}', '${$edit}', '${$delete}'),";
		}
		$query = substr($query, 0, -1);
		sql($query, $eo);

		// redirect to member permissions page
		redirect("admin/pageEditMemberPermissions.php?saved=1&memberID=" . $memberID->url);
	} elseif(Request::has('resetPermissions')) {
		sql("delete from membership_userpermissions where lcase(memberID)='{$memberID->sql}'", $eo);
		// redirect to member permissions page
		redirect("admin/pageEditMemberPermissions.php?reset=1&memberID=" . $memberID->url);
	}

	$GLOBALS['page_title'] = $Translation['user table permissions'];
	include(__DIR__ . '/incHeader.php');

	// fetch group permissions to fill in the form below in case user has no special permissions
	$res1 = sql("select * from membership_grouppermissions where groupID='{$groupID}'", $eo);
	while ($row = db_fetch_assoc($res1)) {
		$tableName = $row['tableName'];
		$vIns = $tableName . "_insert";
		$vUpd = $tableName . "_edit";
		$vDel = $tableName . "_delete";
		$vVue = $tableName . "_view";
		$$vIns = $row['allowInsert'];
		$$vUpd = $row['allowEdit'];
		$$vDel = $row['allowDelete'];
		$$vVue = $row['allowView'];
	}

	// fetch user permissions to fill in the form below, overwriting his group permissions
	$res2 = sql("select * from membership_userpermissions where lcase(memberID)='{$memberID->sql}'", $eo);
	while ($row = db_fetch_assoc($res2)) {
		$tableName = $row['tableName'];
		$vIns = $tableName . "_insert";
		$vUpd = $tableName . "_edit";
		$vDel = $tableName . "_delete";
		$vVue = $tableName . "_view";
		$$vIns = $row['allowInsert'];
		$$vUpd = $row['allowEdit'];
		$$vDel = $row['allowDelete'];
		$$vVue = $row['allowView'];
	}
?>

<!-- show notifications -->
<?php
	if(Request::has('saved')) {
		echo Notification::show([
			'message' => "<i class=\"glyphicon glyphicon-ok\"></i> {$Translation['member permissions saved']}",
			'class' => 'success',
			'dismiss_seconds' => 10
		]);
	} elseif(Request::has('reset')) {
		echo Notification::show([
			'message' => "<i class=\"glyphicon glyphicon-ok\"></i> {$Translation['member permissions reset']}",
			'class' => 'success',
			'dismiss_seconds' => 10
		]);
	}
?>

<div class="page-header">
	<h1>
		<?php
			echo str_replace(
				array('<MEMBER>', '<MEMBERID>', '<GROUPID>', '<GROUP>'),
				array($memberID->url, $memberID->html, $groupID, $group),
				$Translation['user table permissions']
			);
		?>
	</h1>
</div>

<form method="post" action="pageEditMemberPermissions.php">
	<?php echo csrf_token(); ?>

	<input type="hidden" name="memberID" value="<?php echo $memberID->attr; ?>">

	<div class="text-right" style="margin: 2em 0;">
		<?php
			if(!db_num_rows($res2)) {
				echo Notification::show([
					'message' => '<i class="glyphicon glyphicon-user"></i> ' . $Translation["no member permissions"],
					'class' => 'info',
					'dismiss_seconds' => 3600
				]);
			} else {
				?>
					<button type="submit" name="resetPermissions" value="1" class="btn btn-warning btn-lg reset-permissions">
						<i class="glyphicon glyphicon-refresh"></i> 
						<?php echo html_attr($Translation['reset member permissions']); ?>
					</button>
				<?php
			}

			// permissions arrays common to the radio groups below
			$arrPermVal = [0, 1, 2, 3];
			$arrPermText = [$Translation['no'], $Translation['owner'], $Translation['group'], $Translation['all']];
		?>
		<button type="submit" name="saveChanges" value="1" class="btn btn-primary btn-lg"><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation["save changes"]; ?></button>
	</div>

	<div class="table-responsive">
		<table class="table table-striped table-hover">
			<thead>
				<tr>
					<th width="30%"><?php echo $Translation["table"]; ?></th>
					<th width="10%" class="text-center"><?php echo $Translation["insert"]; ?></th>
					<th width="20%"><?php echo $Translation["view"]; ?></th>
					<th width="20%"><?php echo $Translation["edit"]; ?></th>
					<th width="20%"><?php echo $Translation["delete"]; ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
					foreach ($tables as $t => $tc) {
						$insert = "{$t}_insert";
						$view = "{$t}_view";
						$edit = "{$t}_edit";
						$delete = "{$t}_delete";
						?>
						<!-- <?php echo $tc[0]; ?> table -->
						<tr>
							<th valign="top"><?php echo $tc[0]; ?></th>
							<td valign="top" class="text-center">
								<input type="checkbox" name="<?php echo $t; ?>_insert" value="1" <?php echo ($$insert ? "checked" : ""); ?>>
							</td>
							<td>
								<?php echo htmlRadioGroup("{$t}_view", $arrPermVal, $arrPermText, $$view); ?>
							</td>
							<td>
								<?php echo htmlRadioGroup("{$t}_edit", $arrPermVal, $arrPermText, $$edit); ?>
							</td>
							<td>
								<?php echo htmlRadioGroup("{$t}_delete", $arrPermVal, $arrPermText, $$delete); ?>
							</td>
						</tr>
						<?php
					}
				?>
			</tbody>
			<tfoot class="hidden-xs"><tr><th colspan="5"></th></tr></tfoot>
		</table>
	</div>

	<div class="text-right">
		<button type="submit" name="saveChanges" value="1" class="hidden-xs hidden-sm btn btn-primary btn-lg"><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation["save changes"]; ?></button>
		<button type="submit" name="saveChanges" value="1" class="hidden-md hidden-lg btn btn-primary btn-lg btn-block"><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation["save changes"]; ?></button>
	</div>
</form>

<div style="height: 3em;"></div>

<style>
	div.text-primary label{ font-weight: bold; }
</style>

<script>
	$j(function () {
		var highlight_selections = function () {
			$j('input[type=radio]').parent().parent().removeClass('bg-warning text-primary text-bold');
			$j('input[type=radio]:checked').parent().parent().addClass('bg-warning text-primary text-bold');
		}

		$j('button.reset-permissions').click(function() {
			return confirm(<?php echo json_encode($Translation["remove special permissions"]); ?>);
		})

		$j('input[type=radio]').change(highlight_selections);
		highlight_selections();
	});
</script>

<?php include(__DIR__ . '/incFooter.php');
