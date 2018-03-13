<?php
	$currDir = dirname(__FILE__);
	require("{$currDir}/incCommon.php");
	$GLOBALS['page_title'] = $Translation['members'];
	include("{$currDir}/incHeader.php");

	// process search
	if($_GET['searchMembers'] != ""){
		$searchSQL = makeSafe($_GET['searchMembers']);
		$searchHTML = html_attr($_GET['searchMembers']);
		$searchURL = urlencode($_GET['searchMembers']);
		$searchField = intval($_GET['searchField']);
		$searchFieldName = array_search($searchField, array(
			'm.memberID' => 1,
			'g.name' => 2,
			'm.email' => 3,
			'm.custom1' => 4,
			'm.custom2' => 5,
			'm.custom3' => 6,
			'm.custom4' => 7,
			'm.comments' => 8
		));
		if(!$searchFieldName){ // = search all fields
			$where = "where (m.memberID like '%{$searchSQL}%' or g.name like '%{$searchSQL}%' or m.email like '%{$searchSQL}%' or m.custom1 like '%{$searchSQL}%' or m.custom2 like '%{$searchSQL}%' or m.custom3 like '%{$searchSQL}%' or m.custom4 like '%{$searchSQL}%' or m.comments like '%{$searchSQL}%')";
		}else{ // = search a specific field
			$where = "where ({$searchFieldName} like '%{$searchSQL}%')";
		}
	}else{
		$searchSQL = '';
		$searchHTML = '';
		$searchField = 0;
		$searchFieldName = '';
		$where = '';
	}

	// process groupID filter
	$groupID = intval($_GET['groupID']);
	if($groupID){
		if($where != ''){
			$where .= " and (g.groupID='{$groupID}')";
		}else{
			$where = "where (g.groupID='{$groupID}')";
		}
	}

	// process status filter
	$status = intval($_GET['status']); // 1=waiting approval, 2=active, 3=banned, 0=any
	if($status){
		switch($status){
			case 1:
				$statusCond = "(m.isApproved=0)";
				break;
			case 2:
				$statusCond = "(m.isApproved=1 and m.isBanned=0)";
				break;
			case 3:
				$statusCond = "(m.isApproved=1 and m.isBanned=1)";
				break;
			default:
				$statusCond = "";
		}
		if($where != '' && $statusCond != ''){
			$where .= " and {$statusCond}";
		}else{
			$where = "where {$statusCond}";
		}
	}

	$numMembers = sqlValue("select count(1) from membership_users m left join membership_groups g on m.groupID=g.groupID {$where}");
	if(!$numMembers){
		echo "<div class=\"alert alert-warning\">{$Translation['no matching results found']}</div>";
		$noResults = true;
		$page = 1;
	}else{
		$noResults = false;
	}

	$page = max(1, intval($_GET['page']));
	if($page > ceil($numMembers / $adminConfig['membersPerPage']) && !$noResults){
		redirect("admin/pageViewMembers.php?page=" . ceil($numMembers/$adminConfig['membersPerPage']));
	}

	$start = ($page - 1) * $adminConfig['membersPerPage'];

?>
<div class="page-header">
	<h1>
		<?php echo $Translation['members'] ; ?>
		<div class="pull-right">
			<a href="pageEditMember.php" class="btn btn-success btn-lg"><i class="glyphicon glyphicon-plus"></i> <?php echo $Translation['add new member']; ?></a>
		</div>
	</h1>
</div>

<table class="table table-striped table-bordered table-hover">
	<thead>
		<tr>
			<th colspan="9" align="center">
				<form class="form-inline" method="get" action="pageViewMembers.php">
					<input type="hidden" name="page" value="1">

					<div class="form-group">
						<?php 
							$originalValues =  array ('<SEARCH>','<HTMLSELECT>');
							$searchValue = '<input class="form-control" type="text" name="searchMembers" value="' . $searchHTML . '">';
							$arrFields = array(0, 1, 2, 3, 4, 5, 6, 7, 8);
							$arrFieldCaptions = array($Translation['all fields'], $Translation['username'], $Translation["group"], $Translation["email"], $adminConfig['custom1'], $adminConfig['custom2'], $adminConfig['custom3'], $adminConfig['custom4'], $Translation["comments"]);
							$htmlSelect = htmlSelect('searchField', $arrFields, $arrFieldCaptions, $searchField);
							$replaceValues = array($searchValue, $htmlSelect);
							echo str_replace($originalValues, $replaceValues, $Translation['search members']);
						?>
					</div>

					<div class="form-group">
						<label for="groupID" class="control-label"><?php echo $Translation["group"]; ?></label>
						<?php echo htmlSQLSelect("groupID", "select groupID, name from membership_groups order by name", $groupID); ?>
					</div>

					<div class="form-group">
						<label for="" class="control-label"><?php echo $Translation["Status"]; ?></label>
						<?php
							$arrFields = array(0, 1, 2, 3);
							$arrFieldCaptions = array($Translation['any'], $Translation['waiting approval'], $Translation['active'], $Translation['Banned']);
							echo htmlSelect("status", $arrFields, $arrFieldCaptions, $status);
						?>
					</div>

					<div class="form-group">
						<button class="btn btn-primary" type="submit"><i class="glyphicon glyphicon-search"></i> <?php echo $Translation['find'] ; ?></button>
						<a class="btn btn-warning" href="pageViewMembers.php"><i class="glyphicon glyphicon-remove"></i> <?php echo $Translation['reset'] ; ?></a>
					</div>
				</form>
			</th>
		</tr>

		<tr>
			<th><?php echo $Translation['username'] ; ?></th>
			<th><?php echo $Translation["group"] ; ?></th>
			<th><?php echo $Translation['sign up date'] ; ?></th>
			<th><?php echo $adminConfig['custom1']; ?></th>
			<th><?php echo $adminConfig['custom2']; ?></th>
			<th><?php echo $adminConfig['custom3']; ?></th>
			<th><?php echo $adminConfig['custom4']; ?></th>
			<th><?php echo $Translation['Status'] ; ?></th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
<?php

	$res=sql("select lcase(m.memberID), g.name, DATE_FORMAT(m.signupDate, '" . makeSafe($adminConfig['MySQLDateFormat'], false) . "'), m.custom1, m.custom2, m.custom3, m.custom4, m.isBanned, m.isApproved from membership_users m left join membership_groups g on m.groupID=g.groupID $where order by m.signupDate limit $start, " . intval($adminConfig['membersPerPage']), $eo);
	while($row = db_fetch_row($res)){
		$tr_class = '';
		if($adminConfig['adminUsername'] == $row[0]) $tr_class = 'warning text-bold';
		if($adminConfig['anonymousMember'] == $row[0]) $tr_class = 'text-muted';
		?>
		<tr class="<?php echo $tr_class; ?>">
			<?php if($adminConfig['anonymousMember'] == $row[0]){ ?>
				<td class="text-left"><?php echo thisOr($row[0]); ?></td>
			<?php }else{ ?>
				<td class="text-left"><a href="pageEditMember.php?memberID=<?php echo $row[0]; ?>"><?php echo thisOr($row[0]); ?></a></td>
			<?php } ?>
			<td class="text-left"><?php echo thisOr($row[1]); ?></td>
			<td class="text-left"><?php echo thisOr($row[2]); ?></td>
			<td class="text-left"><?php echo thisOr($row[3]); ?></td>
			<td class="text-left"><?php echo thisOr($row[4]); ?></td>
			<td class="text-left"><?php echo thisOr($row[5]); ?></td>
			<td class="text-left"><?php echo thisOr($row[6]); ?></td>
			<td class="text-left">
				<?php echo (($row[7] && $row[8]) ? $Translation['Banned'] : ($row[8] ? $Translation['active'] : $Translation['waiting approval'] )); ?>
			</td>
			<td class="text-center">
				<?php if($adminConfig['anonymousMember'] == $row[0]){ ?>
					<i class="glyphicon glyphicon-pencil text-muted"></i>
				<?php }else{ ?>
					<a href="pageEditMember.php?memberID=<?php echo $row[0]; ?>"><i class="glyphicon glyphicon-pencil" title="<?php echo $Translation['Edit member'] ; ?>"></i></a>
				<?php } ?>

				<?php if($adminConfig['anonymousMember'] == $row[0] || $adminConfig['adminUsername'] == $row[0]){ ?>
					<i class="glyphicon glyphicon-trash text-muted"></i>
					<i class="glyphicon glyphicon-ban-circle text-muted"></i>
				<?php }else{ ?>
					<a href="pageDeleteMember.php?memberID=<?php echo $row[0]; ?>" onClick="return confirm('<?php echo str_replace ( '<USERNAME>' , $row[0] , $Translation['sure delete user'] ); ?>');"><i class="glyphicon glyphicon-trash text-danger" title="<?php echo $Translation['delete member'] ; ?>"></i></a>
					<?php
						if(!$row[8]){ // if member is not approved, display approve link
							?><a href="pageChangeMemberStatus.php?memberID=<?php echo $row[0]; ?>&approve=1"><i class="glyphicon glyphicon-ok text-success" title="<?php echo $Translation["unban this member"] ; ?>" title="<?php echo $Translation["approve this member"] ; ?>"></i></a><?php
						}else{
							if($row[7]){ // if member is banned, display unban link
								?><a href="pageChangeMemberStatus.php?memberID=<?php echo $row[0]; ?>&unban=1"><i class="glyphicon glyphicon-ok text-success" title="<?php echo $Translation["unban this member"] ; ?>"></i></a><?php
							}else{ // if member is not banned, display ban link
								?><a href="pageChangeMemberStatus.php?memberID=<?php echo $row[0]; ?>&ban=1"><i class="glyphicon glyphicon-ban-circle text-danger" title="<?php echo $Translation["ban this member"] ; ?>"></i></a><?php
							}
						}
					?>
				<?php } ?>

				<a href="pageViewRecords.php?memberID=<?php echo $row[0]; ?>"><i class="glyphicon glyphicon-th" title="<?php echo $Translation["View member records"] ; ?>"></i></a>

				<?php if($adminConfig['anonymousMember'] == $row[0]){ ?>
					<i class="glyphicon glyphicon-envelope text-muted"></i>
				<?php }else{ ?>
					<a href="pageMail.php?memberID=<?php echo $row[0]; ?>"><i class="glyphicon glyphicon-envelope" title="<?php echo $Translation["send message to member"] ; ?>"></i></a>
				<?php } ?>
			</td>
		</tr>
		<?php
	}
?>
	</tbody>

	<tfoot>
		<tr>
			<th colspan="9">
				<table width="100%" cellspacing="0">
					<tr>
						<td class="text-left" width="33%">
							<a class="btn btn-default" href="pageViewMembers.php?searchMembers=<?php echo $searchURL; ?>&groupID=<?php echo $groupID; ?>&status=<?php echo $status; ?>&searchField=<?php echo $searchField; ?>&page=<?php echo ($page>1 ? $page-1 : 1); ?>"><?php echo $Translation['previous'] ; ?></a>
						</td>
						<td class="text-center" width="33%">
							<?php 
								$originalValues =  array ('<MEMBERNUM1>','<MEMBERNUM2>','<MEMBERS>' );
								$replaceValues = array ( $start+1 , $start+db_num_rows($res) , $numMembers );
								echo str_replace ( $originalValues , $replaceValues , $Translation['displaying members'] );
							?>
						</td>
						<td class="text-right">
							<a class="btn btn-default" href="pageViewMembers.php?searchMembers=<?php echo $searchURL; ?>&groupID=<?php echo $groupID; ?>&status=<?php echo $status; ?>&searchField=<?php echo $searchField; ?>&page=<?php echo ($page<ceil($numMembers/$adminConfig['membersPerPage']) ? $page+1 : ceil($numMembers/$adminConfig['membersPerPage'])); ?>"><?php echo $Translation['next'] ; ?></a>
						</td>
					</tr>
				</table>
			</th>
		</tr>
		<tr>
			<th colspan="9">
				<b><?php echo $Translation['key'] ; ?></b>
				<div class="row">
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-pencil text-info"></i> <?php echo $Translation['edit member details'] ; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-trash text-danger"></i> <?php echo $Translation['delete member'] ; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-ok text-success"></i> <?php echo $Translation['activate member'] ; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-ban-circle text-danger"></i> <?php echo $Translation['ban member'] ; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-th text-info"></i> <?php echo $Translation['view entered member records'] ; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-envelope text-info"></i> <?php echo $Translation['send email to member'] ; ?></div>
				</div>
			</th>
		</tr>
	</tfoot>
</table>

<style>
	.form-inline .form-group{ margin: .5em 1em; }
</style>

<script>
	$j(function(){
		$j('.form-inline select').addClass('form-control');
	})
</script>


<?php
	include("{$currDir}/incFooter.php");
