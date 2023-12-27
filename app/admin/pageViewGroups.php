<?php
	require(__DIR__ . '/incCommon.php');
	$GLOBALS['page_title'] = $Translation['groups'];
	include(__DIR__ . '/incHeader.php');

	if(Request::val('searchGroups')) {
		$searchSQL = makeSafe(Request::val('searchGroups'));
		$searchHTML = html_attr(Request::val('searchGroups'));
		$where = "where name like '%{$searchSQL}%' or description like '%{$searchSQL}%'";
	} else {
		$searchSQL = '';
		$searchHTML = '';
		$where = "";
	}

	$numGroups = sqlValue("select count(1) from membership_groups $where");
	if(!$numGroups && $searchSQL != '') {
		echo "<div class=\"alert alert-danger\">{$Translation['no matching results found']}</div>";
		$noResults = true;
		$page = 1;
	} else {
		$noResults = false;
	}

	$page = intval(Request::val('page'));
	if($page < 1) {
		$page = 1;
	} elseif($page > ceil($numGroups / $adminConfig['groupsPerPage']) && !$noResults) {
		redirect("admin/pageViewGroups.php?page=" . ceil($numGroups / $adminConfig['groupsPerPage']));
	}

	$start = ($page - 1) * $adminConfig['groupsPerPage'];

?>
<div class="page-header">
	<h1>
		<?php echo $Translation['groups']; ?>
		<span class="pull-right"><a href="pageEditGroup.php" class="btn btn-success btn-lg"><i class="glyphicon glyphicon-plus"></i> <?php echo $Translation['add group']; ?></a></span>
		<div class="clearfix"></div>
	</h1>
</div>

<table class="table table-striped table-bordered table-hover">
	<thead>
		<tr>
			<th colspan="4" class="text-center">
				<form class="form-inline" method="get" action="pageViewGroups.php">
					<input type="hidden" name="page" value="1">

					<div class="form-group">
						<label for="searchGroups"><?php echo $Translation['search groups'] ; ?></label>
						<input class="form-control" type="text" id="searchGroups" name="searchGroups" value="<?php echo $searchHTML; ?>" size="20">
					</div>
					<div class="form-group">
						<button type="submit" class="btn btn-primary"><i class="glyphicon glyphicon-search"></i> <?php echo $Translation['find']; ?></button>
						<button type="button" class="btn btn-warning" onClick="window.location='pageViewGroups.php';"><i class="glyphicon glyphicon-remove"></i> <?php echo $Translation['reset']; ?></button>
					</div>
				</form>
			</th>
		</tr>
		<tr>
			<th><?php echo $Translation["group"]  ; ?></th>
			<th><?php echo $Translation["description"] ; ?></th>
			<th><?php echo $Translation['members count'] ; ?></th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
		<?php

			$res = sql("select groupID, name, description from membership_groups $where limit $start, ".$adminConfig['groupsPerPage'], $eo);
			while( $row = db_fetch_row($res)) {
				$groupMembersCount = sqlValue("select count(1) from membership_users where groupID='$row[0]'");
				$isAnonGroup = ($row[1] == $adminConfig['anonymousGroup']);
				?>
				<tr>
					<td><a href="pageEditGroup.php?groupID=<?php echo $row[0]; ?>"><?php echo htmlspecialchars($row[1]); ?></a></td>
					<td><?php echo htmlspecialchars(trim($row[2] ?? '')); ?></td>
					<td class="text-right"><?php echo $groupMembersCount; ?></td>
					<td class="text-center">
						<!-- edit -->
						<a href="pageEditGroup.php?groupID=<?php echo $row[0]; ?>" title="<?php echo $Translation['Edit group']; ?>"><i class="glyphicon glyphicon-pencil"></i></a>
						<span class="hspacer-sm"></span>

						<!-- delete -->
						<?php if(!$groupMembersCount) { ?>
							<a href="pageDeleteGroup.php?groupID=<?php echo $row[0]; ?>&csrf_token=<?php echo urlencode(csrf_token(false, true)); ?>" 
								title="<?php echo $Translation['delete group'] ; ?>" 
								onClick="return confirm('<?php echo addslashes($Translation['confirm delete group']); ?>');">
								<i class="glyphicon glyphicon-trash text-danger"></i>
							</a>
						<?php } else { ?>
							<i class="glyphicon glyphicon-trash text-muted"></i>
						<?php } ?>
						<span class="hspacer-sm"></span>

						<!-- add member -->
						<?php if(!$isAnonGroup) { ?>
							<a href="pageEditMember.php?groupID=<?php echo $row[0]; ?>" title="<?php echo $Translation["add new member"]; ?>"><i class="glyphicon glyphicon-plus text-success"></i></a>
						<?php } else { ?>
							<i class="glyphicon glyphicon-plus text-muted"></i>
						<?php } ?>
						<span class="hspacer-sm"></span>

						<!-- view records -->
						<a href="pageViewRecords.php?groupID=<?php echo $row[0]; ?>" title="<?php echo $Translation['view group records'] ; ?>"><i class="glyphicon glyphicon-th"></i></a>
						<span class="hspacer-sm"></span>

						<!-- view members -->
						<?php if($groupMembersCount) { ?>
							<a href="pageViewMembers.php?groupID=<?php echo $row[0]; ?>" title="<?php echo $Translation['view group members'] ; ?>"><i class="glyphicon glyphicon-user"></i></a>
						<?php } else { ?>
							<i class="glyphicon glyphicon-user text-muted"></i>
						<?php } ?>
						<span class="hspacer-sm"></span>

						<!-- send message -->
						<?php if($groupMembersCount && !$isAnonGroup) { ?>
							<a href="pageMail.php?groupID=<?php echo $row[0]; ?>" title="<?php echo $Translation['send message to group']; ?>"><i class="glyphicon glyphicon-envelope"></i></a>
						<?php } else { ?>
							<i class="glyphicon glyphicon-envelope text-muted"></i>
						<?php } ?>
					</td>
				</tr>
				<?php
			}
		?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="4">
				<table width="100%" cellspacing="0">
					<tr>
						<?php
							// pagination
							$prevPage = $page > 1 ? $page - 1 : false;
							$nextPage = $page < ceil($numMembers / $adminConfig['membersPerPage']) ? $page + 1 : false;
						?>
						<th class="text-left" width="33%">
							<?php if($prevPage) { ?>
								<a class="btn btn-default" href="pageViewGroups.php?searchGroups=<?php echo $searchHTML; ?>&page=<?php echo ($page > 1 ? $page - 1 : 1); ?>"><?php echo $Translation['previous']; ?></a>
							<?php } ?>
						</th>
						<th class="text-center" width="33%">
							<?php 
								$originalValues = ['<GROUPNUM1>', '<GROUPNUM2>', '<GROUPS>'];
								$replaceValues = [$start + 1, $start + db_num_rows($res), $numGroups];
								echo str_replace($originalValues, $replaceValues, $Translation['displaying groups']);
							?>
						</th>
						<th class="text-right">
							<?php if($nextPage) { ?>
								<a class="btn btn-default" href="pageViewGroups.php?searchGroups=<?php echo $searchHTML; ?>&page=<?php echo ($page < ceil($numGroups / $adminConfig['groupsPerPage']) ? $page + 1 : ceil($numGroups / $adminConfig['groupsPerPage'])); ?>"><?php echo $Translation['next'] ; ?></a>
							<?php } ?>
						</th>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<th colspan="4">
				<b><?php echo $Translation['key']; ?></b>
				<div class="row">
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-pencil text-info"></i> <?php echo $Translation['edit group details'] ; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-trash text-danger"></i> <?php echo $Translation['delete group'] ; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-plus text-success"></i> <?php echo $Translation['add member to group'] ; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-th text-info"></i> <?php echo $Translation['view data records'] ; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-user text-info"></i> <?php echo $Translation['list group members'] ; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-envelope text-info"></i> <?php echo $Translation['send email to all members'] ; ?></div>
				</div>
			</th>
		</tr>
	</tfoot>
</table>

<style>
	.form-inline .form-group{ margin: 0.5em 1em; }
</style>

<?php include(__DIR__ . '/incFooter.php');
