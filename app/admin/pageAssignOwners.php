<?php
	@set_time_limit(0);
	require(__DIR__ . '/incCommon.php');
	$GLOBALS['page_title'] = $Translation['data ownership assign'];
	include(__DIR__ . '/incHeader.php');

	// get a list of tables
	$arrTables = getTableList();
	$arrTablesNoOwners = [];
	$insert = '';
	$status = '';

	// get a list of tables with records that have no owners
	foreach($arrTables as $tn => $tc) {
		$countOwned = sqlValue("select count(1) from membership_userrecords where tableName='{$tn}'");
		$countAll = sqlValue("select count(1) from `{$tn}`");

		if($countAll > $countOwned) {
			$arrTablesNoOwners[$tn] = ($countAll - $countOwned);
		}
	}

	// process ownership request
	if(count($_POST)) {
		// csrf check
		if(!csrf_token(true)) die($Translation['invalid security token']);

		ignore_user_abort();
		foreach($arrTablesNoOwners as $tn => $tc) {
			$groupID = intval(Request::val("ownerGroup_$tn"));
			$memberID = makeSafe(strtolower(Request::val("ownerMember_$tn")));
			$pkf = getPKFieldName($tn);

			if($groupID) {
				$insertBegin = "insert ignore into membership_userrecords (tableName, pkValue, groupID, memberID, dateAdded, dateUpdated) values ";
				$ts = time();
				$assigned = 0;
				$tempStatus = '';

				$res = sql("select `$tn`.`$pkf` from `$tn`", $eo);
				while($row = db_fetch_row($res)) {
					$pkValue = makeSafe($row[0], false);
					$insert .= "('$tn', '$pkValue', '$groupID', ".($memberID ? "'$memberID'" : "NULL").", $ts, $ts),";
					if(strlen($insert) > 50000) {
						sql($insertBegin . substr($insert, 0, -1), $eo);
						$assigned += @db_affected_rows(db_link());
						$insert = '';
					}
				}
				if($insert != '') {
					sql($insertBegin . substr($insert, 0, -1), $eo);
					$assigned += @db_affected_rows(db_link());
					$insert = '';
				}

				if ($memberID) {
					$tempStatus = $Translation["assigned table records to group and member"];
					$tempStatus = str_replace ( "<MEMBERID>" , $memberID , $tempStatus );
				} else {
					$tempStatus = $Translation["assigned table records to group"];   
				}

				$originalValues =  array ('<NUMBER>','<TABLE>' , '<GROUP>' );
				$number = number_format($assigned);
				$group = sqlValue("select name from membership_groups where groupID='$groupID'");
				$replaceValues = array ( $number , $tn , $group );
				$tempStatus = str_replace ( $originalValues , $replaceValues , $tempStatus );

				$status.= $tempStatus. ".<br>";
			} 
		}

		// refresh the list of tables with records that have no owners
		$arrTablesNoOwners = [];
		foreach($arrTables as $tn => $tc) {
			$countOwned = sqlValue("select count(1) from membership_userrecords where tableName='$tn'");
			$countAll = sqlValue("select count(1) from `$tn`");

			if($countAll > $countOwned)
				$arrTablesNoOwners[$tn] = ($countAll - $countOwned);
		}

	}

?>

<div class="page-header"><h1><?php echo $Translation['data ownership assign']; ?></h1></div>

<?php

	// if all records of all tables have owners, no need to continue
	if(empty($arrTablesNoOwners)) {
		echo "<div class=\"alert alert-success\"><i class=\"glyphicon glyphicon-ok\"></i> {$Translation['records ownership done']}</div>";
		include(__DIR__ . '/incFooter.php');
		exit;
	}

	// show status of previous assignments
	if($status != '')
		echo "<div class=\"alert alert-info\">$status</div>";

	// compose groups drop-down
	$htmlGroups = "<option value=\"0\">--- {$Translation['select group']} ---</option>";
	$res = sql("SELECT `groupID`, `name` FROM `membership_groups` ORDER BY `name`", $eo);
	while($row = db_fetch_row($res))
		$htmlGroups .= "<option value=\"$row[0]\">$row[1]</option>";
	$htmlGroups .= '</select>';
?>

<script>
	var members = [];
	<?php
		$members = [];
		$res = sql("select groupID, lcase(memberID) from membership_users order by groupID, memberID", $eo);
		while($row = db_fetch_row($res))
			$members[$row[0]] .= "'" . $row[1] . "',";

		foreach($members as $groupID => $groupMembers)
			echo "\n\tmembers[$groupID] = [" . substr($groupMembers, 0, -1) . "];";
	?>

	function populateMembers(memberSelect, groupSelect) {
		var m = document.getElementsByName(memberSelect)[0];
		var g = document.getElementsByName(groupSelect)[0];

		if(m.options.length > 0) {
			var mc = m.options.length;
			for(var i = 0; i < mc; i++)
				m.options[0] = null;
		}

		var gval = g.options[g.selectedIndex].value;

		if(gval == 0 || members[gval] == undefined)
			return 0;

		for(var j = 0; j < members[gval].length; j++)
			m.options[j] = new Option(members[gval][j], members[gval][j], false, false);

		return 0;
	}
	</script>

<form method="post" action="pageAssignOwners.php">
	<?php echo csrf_token(); ?>

	<p>
		<?php echo  $Translation['data ownership'] ; ?>
	</p>

	<div class="table-responsive"><table class="table">
		<thead><tr>
			<th><?php echo  $Translation["table"] ; ?></th>
			<th><?php echo  $Translation["records with no owners"] ; ?></th>
			<th><?php echo  $Translation["new owner group"] ; ?></th>
			<th><?php echo  $Translation["new owner member"] ; ?></th>
		</tr></thead>

		<tbody>
<?php
	foreach($arrTablesNoOwners as $tn=>$countNoOwners) {
		?>
		<tr>
			<td><?php echo $arrTables[$tn][0]; ?></td>
			<td align="right"><?php echo number_format($countNoOwners); ?>&nbsp;</td>
			<td><select onchange="populateMembers('ownerMember_<?php echo $tn; ?>', 'ownerGroup_<?php echo $tn; ?>');" name="ownerGroup_<?php echo $tn; ?>"><?php echo $htmlGroups; ?></td>
			<td><select style="width: 120px;" name="ownerMember_<?php echo $tn; ?>"></select></td>
			</tr>
		<?php
	}
?>
		<tr><td colspan="4" class="text-center">
			<input type="button" value="<?php echo $Translation['cancel'] ; ?>" onclick="window.location='pageHome.php';">
			<input type="button" name="assignOwners" value="<?php echo $Translation["assign new owners"] ; ?>" onclick="this.value='<?php echo $Translation["please wait"] ; ?>'; this.onclick='return FALSE;'; this.disabled=true; document.getElementsByTagName('form')[0].submit();">
			</td></tr>
		</tbody>
		</table></div>

		<p><?php echo  $Translation['if no owner member assigned'] ; ?></p>
	</form>

<?php include(__DIR__ . '/incFooter.php');
