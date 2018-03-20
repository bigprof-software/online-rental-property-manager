<?php
	$currDir = dirname(__FILE__);
	require("{$currDir}/incCommon.php");

	$recID = 0;

	// request to save changes?
	if(isset($_REQUEST['saveChanges'])){
		// validate data
		$recID = intval($_REQUEST['recID']);
		$memberID = makeSafe(strtolower($_REQUEST['memberID']));
		###############################

		/* for ajax requests coming from the users' area, get the recID */
		if(is_ajax()){
			$tableName = $_REQUEST['t'];
			$pkValue = $_REQUEST['pkValue'];

			if(!in_array($tableName, array_keys(getTableList()))) die($Translation["invalid table"]);

			if(!$pkValue) die($Translation["invalid primary key"]);
		}

		if($recID){
			$tableName = sqlValue("select tableName from membership_userrecords where recID='{$recID}'");
			$pkValue = sqlValue("select pkValue from membership_userrecords where recID='{$recID}'");
		}

		// update ownership
		set_record_owner($tableName, $pkValue, $memberID);

		if(is_ajax()){
			echo 'OK';
			exit;
		}

		// redirect to member editing page
		redirect("admin/pageEditOwnership.php?recID={$recID}");
		exit;
	}elseif(isset($_GET['recID'])){
		// we have an edit request for a member
		$recID = intval($_GET['recID']);
	}

	if(!$recID){
		redirect("admin/pageViewRecords.php");
		exit;
	}

	$GLOBALS['page_title'] = $Translation['edit Record Ownership'];
	include("{$currDir}/incHeader.php");

	// fetch record data to fill in the form below
	$res = sql("select * from membership_userrecords where recID='{$recID}'", $eo);
	if($row = db_fetch_assoc($res)){
		// get record data
		$tableName = $row['tableName'];
		$pkValue = $row['pkValue'];
		$memberID = strtolower($row['memberID']);
		$dateAdded = @date($adminConfig['PHPDateTimeFormat'], $row['dateAdded']);
		$dateUpdated = @date($adminConfig['PHPDateTimeFormat'], $row['dateUpdated']);
		$groupID = $row['groupID'];
	}else {
		// no such record exists
		die("<div class=\"alert alert-danger\">{$Translation["record not found error"]}</div>");
	}
?>

<div class="page-header"><h1><?php echo $Translation['edit Record Ownership']; ?></h1></div>

<form method="post" action="pageEditOwnership.php" class="form-horizontal">
	<input type="hidden" name="recID" value="<?php echo html_attr($recID); ?>">
	<div style="height: 1em;"></div>

	<div class="form-group">
		<label for="groupID" class="col-xs-12 col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label">
			<?php echo $Translation["owner group"]; ?>
		</label>
		<div class="col-xs-10 col-sm-7 col-md-8 col-lg-5">
			<?php
				echo bootstrapSQLSelect('groupID', "select g.groupID, g.name from membership_groups g order by name", $groupID);
			?>
		</div>
		<div class="col-xs-2 col-sm-1">
			<a class="btn btn-default" title="<?php echo html_attr($Translation['view all records by group']); ?>" href="pageViewRecords.php?groupID=<?php echo urlencode($groupID); ?>">
				<i class="glyphicon glyphicon-chevron-right"></i> 
			</a>
		</div>
	</div>

	<div class="form-group">
		<label for="memberID" class="col-xs-12 col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label">
			<?php echo $Translation["owner member"]; ?>
		</label>
		<div class="col-xs-10 col-sm-7 col-md-8 col-lg-5">
			<?php
				echo bootstrapSQLSelect('memberID', "select lcase(memberID), lcase(memberID) from membership_users where groupID='$groupID' order by memberID", $memberID);
			?>
			<span class="help-block"><?php echo $Translation["switch record ownership"]; ?></span>
		</div>
		<div class="col-xs-2 col-sm-1">
			<a class="btn btn-default" title="<?php echo html_attr($Translation['view all records by member']); ?>" href="pageViewRecords.php?memberID=<?php echo urlencode($memberID); ?>">
				<span class="glyphicon glyphicon-chevron-right"></span> 
			</a>
		</div>
	</div>

	<div class="form-group">
		<label class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label">
			<?php echo $Translation["record created on"]; ?>
		</label>
		<div class="col-sm-8 col-md-9 col-lg-6">
			<p class="form-control-static"><?php echo $dateAdded; ?></p>
		</div>
	</div>

	<div class="form-group">
		<label class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label">
			<?php echo $Translation["record modified on"]; ?>
		</label>
		<div class="col-sm-8 col-md-9 col-lg-6">
			<p class="form-control-static"><?php echo $dateUpdated; ?></p>
		</div>
	</div>

	<div class="form-group">
		<label class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label">
			<?php echo $Translation["table"]; ?>
		</label>
		<div class="col-sm-8 col-md-9 col-lg-6">
			<p class="form-control-static">
				<a href="pageViewRecords.php?tableName=<?php echo urlencode($tableName); ?>" title="<?php echo html_attr($Translation['view all records of table']); ?>">
					<?php echo $tableName; ?>
					<i class="glyphicon glyphicon-th"></i> 
				</a>
			</p>
		</div>
	</div>

	<div class="form-group ">
		<label for="member username" class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"> 
			<div><?php echo $Translation["record data"]; ?></div>
		</label>
		<div class="col-sm-8 col-md-9 col-lg-6">
			<div class="form-control-static">
				<?php
					// get pk field name
					$pkField = getPKFieldName($tableName);

					$res = sql("select * from `{$tableName}` where `{$pkField}`='" . makeSafe($pkValue, false) . "'", $eo);
					if($row = db_fetch_assoc($res)){
						?>
						<div style="margin-bottom: 1em;">
							<a href="../<?php echo $tableName; ?>_view.php?SelectedID=<?php echo urlencode($pkValue); ?>&dvprint_x=1" target="_blank" class="btn btn-default">
								<i class='glyphicon glyphicon-print'></i>
								<?php echo $Translation["print"]; ?>
							</a>
							<a href="../<?php echo $tableName; ?>_view.php?SelectedID=<?php echo urlencode($pkValue); ?>" target="_blank" class="btn btn-default">
								<i class='glyphicon glyphicon-pencil'></i>
								<?php echo $Translation["edit"]; ?>
							</a>
						</div>

						<table class="table table-striped table-bordered">
							<thead>
								<tr>
									<th style="width: 30%"><?php echo $Translation["field name"]; ?></th>
									<th><?php echo $Translation["value"]; ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
									foreach ($row as $field_name => $field_value){
										$field_link = false;
										if(@is_file("{$currDir}/../{$Translation['ImageFolder']}{$field_value}")){
										   $field_value = "<a href=\"../{$Translation['ImageFolder']}{$field_value}\" target=\"_blank\">" . html_attr($field_value) . "</a>";
										   $field_link = true;
										}
										?>
										<tr>
										   <td><?php echo $field_name; ?></td>
										   <?php if($field_link){ ?>
										       <td><?php echo $field_value; ?></td>
										   <?php }else{ ?>
										       <td><?php echo nl2br(htmlspecialchars($field_value, ENT_NOQUOTES | ENT_COMPAT | ENT_HTML401, datalist_db_encoding)); ?></td>
										   <?php } ?>
										</tr>
										<?php
									}
								?>
							</tbody>
						</table>
						<?php
					}else{
						?>
						<div class="alert alert-danger"><?php echo $Translation['record not found error']; ?></div>
						<?php
					}
				?>
			</div>
		</div>
	</div>

	<div class="row">
		<div class="col-sm-8 col-sm-offset-4 col-md-9 col-md-offset-3 col-lg-6 col-lg-offset-4">
			<button type="submit" name="saveChanges" value="1" class="hidden-xs hidden-sm btn btn-primary btn-lg">
				<i class="glyphicon glyphicon-ok"></i>
				<?php echo $Translation["save changes"]; ?>
			</button>
			<button type="submit" name="saveChanges" value="1" class="hidden-md hidden-lg btn btn-primary btn-lg btn-block">
				<i class="glyphicon glyphicon-ok"></i>
				<?php echo $Translation["save changes"]; ?>
			</button>
		</div>
	</div>
</form>

<div style="height: 1em;"></div>

<style>
	.form-control{ width: 100% !important; }
</style>

<?php
include("{$currDir}/incFooter.php");
?>
