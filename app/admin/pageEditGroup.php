<?php
$currDir = dirname(__FILE__);
require("{$currDir}/incCommon.php");

// get groupID of anonymous group
$anon_safe = makeSafe($adminConfig['anonymousGroup'], false);
$anonGroupID = sqlValue("select groupID from membership_groups where name='{$anon_safe}'");

// get list of tables
$table_list = getTableList();
$perm = array();

// request to save changes?
if($_POST['saveChanges'] != ''){
	// validate data
	$name = makeSafe($_POST['name']);
	$description = makeSafe($_POST['description']);
	switch($_POST['visitorSignup']){
		case 0:
			$allowSignup = 0;
			$needsApproval = 1;
			break;
		case 2:
			$allowSignup = 1;
			$needsApproval = 0;
			break;
		default:
			$allowSignup = 1;
			$needsApproval = 1;
	}

	foreach($table_list as $tn => $tc){
		$perm["{$tn}_insert"] = checkPermissionVal("{$tn}_insert");
		$perm["{$tn}_view"] = checkPermissionVal("{$tn}_view");
		$perm["{$tn}_edit"] = checkPermissionVal("{$tn}_edit");
		$perm["{$tn}_delete"] = checkPermissionVal("{$tn}_delete");
	}

	// new group or old?
	if($_POST['groupID'] == ''){ // new group
		// make sure group name is unique
		if(sqlValue("select count(1) from membership_groups where name='{$name}'")){
			echo "<div class=\"alert alert-danger\">{$Translation["group exists error"]}</div>";
			include("{$currDir}/incFooter.php");
		}

		// add group
		sql("insert into membership_groups set name='{$name}', description='{$description}', allowSignup='{$allowSignup}', needsApproval='{$needsApproval}'", $eo);

		// get new groupID
		$groupID = db_insert_id(db_link());
	} else { // old group
		// validate groupID
		$groupID = intval($_POST['groupID']);

		/* force configured name and no signup for anonymous group */
		if($groupID == $anonGroupID){
			$name = $adminConfig['anonymousGroup'];
			$allowSignup = 0;
			$needsApproval = 0;
		}

		// make sure group name is unique
		if(sqlValue("select count(1) from membership_groups where name='{$name}' and groupID!='{$groupID}'")){
			echo "<div class=\"alert alert-danger\">{$Translation["group exists error"]}</div>";
			include("{$currDir}/incFooter.php");
		}

		// update group
		sql("update membership_groups set name='{$name}', description='{$description}', allowSignup='{$allowSignup}', needsApproval='{$needsApproval}' where groupID='{$groupID}'", $eo);

		// reset then add group permissions
		foreach($table_list as $tn => $tc){
			sql("delete from membership_grouppermissions where groupID='{$groupID}' and tableName='{$tn}'", $eo);
		}
	}

	// add group permissions
	if($groupID){
		foreach($table_list as $tn => $tc){
			$allowInsert = $perm["{$tn}_insert"];
			$allowView = $perm["{$tn}_view"];
			$allowEdit = $perm["{$tn}_edit"];
			$allowDelete = $perm["{$tn}_delete"];
			sql("insert into membership_grouppermissions set groupID='{$groupID}', tableName='{$tn}', allowInsert='{$allowInsert}', allowView='{$allowView}', allowEdit='{$allowEdit}', allowDelete='{$allowDelete}'", $eo);
		}
	}

	// redirect to group editing page
	redirect("admin/pageEditGroup.php?groupID={$groupID}");
} elseif($_GET['groupID'] != ''){
	// we have an edit request for a group
	$groupID = intval($_GET['groupID']);
}

$GLOBALS['page_title'] = $Translation['view groups'];
include("{$currDir}/incHeader.php");

if($groupID != ''){
	// fetch group data to fill in the form below
	$res = sql("select * from membership_groups where groupID='{$groupID}'", $eo);
	if($row = db_fetch_assoc($res)){
		// get group data
		$name = $row['name'];
		$description = $row['description'];
		$visitorSignup = ($row['allowSignup'] == 1 && $row['needsApproval'] == 1 ? 1 : ($row['allowSignup'] == 1 ? 2 : 0));

		// get group permissions for each table
		$res = sql("select * from membership_grouppermissions where groupID='{$groupID}'", $eo);
		while($row = db_fetch_assoc($res)){
			$tn = $row['tableName'];
			$perm["{$tn}_insert"] = $row['allowInsert'];
			$perm["{$tn}_view"] = $row['allowView'];
			$perm["{$tn}_edit"] = $row['allowEdit'];
			$perm["{$tn}_delete"] = $row['allowDelete'];
		}
	} else {
		// no such group exists
		echo "<div class=\"alert alert-danger\">{$Translation["group not found error"]}</div>";
		$groupID = 0;
	}
}
?>

<div class="page-header">
	<h1>
		<?php echo($groupID ? str_replace('<GROUPNAME>', '<span class="text-info">' . html_attr($name) . '</span>', $Translation['edit group']) : $Translation['add new group']); ?>
		<div class="pull-right">
			<div class="btn-group">
				<a href="pageViewGroups.php" class="btn btn-default btn-lg"><i class="glyphicon glyphicon-arrow-left"></i> <span class="hidden-xs hidden-sm"><?php echo $Translation['back to groups']; ?></span></a>
				<?php if($groupID){ ?>
					<a href="pageViewMembers.php?groupID=<?php echo $groupID; ?>" class="btn btn-default btn-lg"><i class="glyphicon glyphicon-user"></i> <span class="hidden-xs hidden-sm"><?php echo $Translation['view group members']; ?></span></a>
					<a href="pageEditMember.php?groupID=<?php echo $groupID; ?>" class="btn btn-default btn-lg"><i class="glyphicon glyphicon-plus"></i> <span class="hidden-xs hidden-sm"><?php echo $Translation['add member to group']; ?></span></a>
					<a href="pageViewRecords.php?groupID=<?php echo $groupID; ?>" class="btn btn-default btn-lg"><i class="glyphicon glyphicon-th"></i> <span class="hidden-xs hidden-sm"><?php echo $Translation['view group records']; ?></span></a>
				<?php } ?>
			</div>
		</div>
		<div class="clearfix"></div>
	</h1>
</div>

<?php if($anonGroupID == $groupID){ ?>
	<div class="alert alert-warning"><?php echo $Translation["anonymous group attention"]; ?></div>
<?php } ?> 


<div class="form-group">
	<label class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"></label>
	<div class="col-sm-8 col-md-9 col-lg-6">
		<div class="checkbox">
			<label>
				<input type="checkbox" id="showToolTips" value="1" checked>
				<?php echo $Translation["show tool tips"]; ?>
			</label>
		</div>
	</div>
</div>

<form method="post" action="pageEditGroup.php" class="form-horizontal">
	<input type="hidden" name="groupID" value="<?php echo $groupID; ?>">

	<div class="form-group ">
		<label for="group name" class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"><?php echo $Translation["group name"]; ?></label>
		<div class="col-sm-8 col-md-9 col-lg-6 ">
			<input class="form-control" type="text" name="name" <?php echo ($anonGroupID == $groupID ? "readonly" : ""); ?> value="<?php echo html_attr($name); ?>">
			<span class="help-block">
				<?php
					if($anonGroupID == $groupID){
						echo $Translation["readonly group name"];
					}else{
						echo str_replace('<ANONYMOUSGROUP>', $adminConfig['anonymousGroup'], $Translation["anonymous group name"]);
					}
				?>
			</span>
		</div>
	</div>

	<div class="form-group ">
		<label for="description" class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"><?php echo $Translation["description"]; ?></label>
		<div class="col-sm-8 col-md-9 col-lg-6 ">
			<textarea class="form-control" name="description" rows="5"><?php echo html_attr($description); ?></textarea>
		</div>
	</div>

	<?php if($anonGroupID != $groupID){ ?>
		<div class="form-group ">
			<label for="allow visitors sign up" class="col-sm-4 col-md-3 col-lg-2 col-lg-offset-2 control-label"><?php echo $Translation["allow visitors sign up"]; ?></label>
			<div class="col-sm-8 col-md-9 col-lg-6 ">
				<?php
					echo htmlRadioGroup(
						"visitorSignup",
						array(0, 1, 2),
						array(
							$Translation["admin add users"],
							$Translation["admin approve users"],
							$Translation["automatically approve users"]
						), 
						($groupID ? $visitorSignup : $adminConfig['defaultSignUp'])
					);
				?>
			</div>
		</div>

		<div class="row">
			<div class=" col-lg-3 col-lg-offset-9 col-sm-4 col-sm-offset-8" >
				<button type="submit" name="saveChanges" value="1" class="btn btn-primary btn-lg pull-right btn-block"><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation["save changes"]; ?></button>
			</div>
		</div>

		<div style="height: 3em;"></div>
	<?php } ?>

	<?php
		// permissions arrays common to the radio groups below
		$arrPermVal = array(0, 1, 2, 3);
		$arrPermText = array($Translation["no"], $Translation["owner"], $Translation["group"], $Translation["all"]);
	?>

	<div class="table-responsive">
		<table class="table table-striped table-bordered table-hover">
			<caption><h2><?php echo $Translation["group table permissions"]; ?></h2></caption>
			<thead>
				<tr>
					<th><div><?php echo $Translation["table"]; ?></div></th>
					<th><div><?php echo $Translation["insert"]; ?></div></th>
					<th><div><?php echo $Translation["view"]; ?></div></th>
					<th><div><?php echo $Translation["edit"]; ?></div></th>
					<th><div><?php echo $Translation["delete"]; ?></div></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($table_list as $tn => $tc){ ?>
					<!-- <?php echo $tn; ?> table -->
					<tr>
						<th><?php echo $tc; ?></th>
						<td>
							<input onMouseOver="stm(<?php echo $tn; ?>_addTip, toolTipStyle);" onMouseOut="htm();" type="checkbox" name="<?php echo $tn; ?>_insert" value="1" <?php echo ($perm["{$tn}_insert"] ? "checked class=\"text-primary\"" : ""); ?>>
						</td>
						<td>
							<?php echo htmlRadioGroup("{$tn}_view", $arrPermVal, $arrPermText, $perm["{$tn}_view"], 'text-primary'); ?>
						</td>
						<td>
							<?php echo htmlRadioGroup("{$tn}_edit", $arrPermVal, $arrPermText, $perm["{$tn}_edit"], 'text-primary'); ?>
						</td>
						<td>
							<?php echo htmlRadioGroup("{$tn}_delete", $arrPermVal, $arrPermText, $perm["{$tn}_delete"], 'text-primary'); ?>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>

	<div class="row">
		<div class=" col-lg-3 col-lg-offset-9 col-sm-4 col-sm-offset-8 " >
			<button type="submit" name="saveChanges" value="1" class="btn btn-primary btn-lg btn-block "><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation["save changes"]; ?></button>
		</div>
	</div>
</form>

<div style="height: 10em;"></div>

<script>
	$j(function(){
		var highlight_selections = function(){
			$j('input[type=radio]:checked').parent().parent().addClass('text-primary');
			$j('input[type=radio]:not(:checked)').parent().parent().removeClass('text-primary');
		}

		$j('input[type=radio]').change(function(){
			highlight_selections();
		});

		highlight_selections();

		/* tool tips for radios */
		$j('input[type=radio]').parent().mouseover(function(){
			var radio = $j(this).children('input[type=radio]');
			stm(window[radio.attr('name') + radio.attr('value') + 'Tip'], toolTipStyle);
		});
		$j('input[type=radio]').parent().mouseout(function(){
			htm();
		});
	});
</script>

<?php
include("{$currDir}/incFooter.php");
?>
