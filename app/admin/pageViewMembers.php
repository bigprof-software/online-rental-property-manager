<?php
	require(__DIR__ . '/incCommon.php');
	$GLOBALS['page_title'] = $Translation['members'];
	include(__DIR__ . '/incHeader.php');

	// fields of memebers view
	$df = makeSafe($adminConfig['MySQLDateFormat'], false);

	$mviewFields = [
		'LCASE(m.memberID)',
		'g.name',
		"DATE_FORMAT(m.signupDate, '$df')",
		'm.email',
		'm.custom1', 'm.custom2', 'm.custom3', 'm.custom4',
		'm.isBanned',
		'm.isApproved',
	];

	// process sorting
	$sort = 2; // default sorting by signupDate
	$sortDir = 'DESC'; // default sorting direction

	if(Request::has('sort')) {
		// make sure requested sort is a valid index of mviewFields. fallback to default sort index
		$rSort = intval(Request::val('sort'));
		if(!isset($mviewFields[$rSort])) $rSort = $sort;

		// make sure requested sortDir is valid. fallback to default sortDir
		$rSortDir = Request::val('sortDir');
		if(!$rSortDir || !in_array(strtoupper($rSortDir), ['DESC', 'ASC']))
			$rSortDir = $sortDir;

		$sort = $rSort;
		$sortDir = $rSortDir;
	}

	// process search
	if(Request::val('searchMembers')) {
		$searchSQL = makeSafe(Request::val('searchMembers'));
		$searchHTML = html_attr(Request::val('searchMembers'));
		$searchURL = urlencode(Request::val('searchMembers'));
		$searchField = intval(Request::val('searchField'));
		$searchFieldName = array_search($searchField, [
			'm.memberID' => 1,
			'g.name' => 2,
			'm.email' => 3,
			'm.custom1' => 4,
			'm.custom2' => 5,
			'm.custom3' => 6,
			'm.custom4' => 7,
			'm.comments' => 8
		]);
		if(!$searchFieldName) { // = search all fields
			$where = "where (m.memberID like '%{$searchSQL}%' or g.name like '%{$searchSQL}%' or m.email like '%{$searchSQL}%' or m.custom1 like '%{$searchSQL}%' or m.custom2 like '%{$searchSQL}%' or m.custom3 like '%{$searchSQL}%' or m.custom4 like '%{$searchSQL}%' or m.comments like '%{$searchSQL}%')";
		} else { // = search a specific field
			$where = "where ({$searchFieldName} like '%{$searchSQL}%')";
		}
	} else {
		$searchSQL = '';
		$searchHTML = '';
		$searchField = 0;
		$searchFieldName = '';
		$where = '';
	}

	// process groupID filter
	$groupID = intval(Request::val('groupID'));
	if($groupID) {
		if($where != '') {
			$where .= " and (g.groupID='{$groupID}')";
		} else {
			$where = "where (g.groupID='{$groupID}')";
		}
	}

	// process status filter
	$status = intval(Request::val('status')); // 1=waiting approval, 2=active, 3=banned, 0=any
	if($status) {
		switch($status) {
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
		if($where != '' && $statusCond != '') {
			$where .= " and {$statusCond}";
		} else {
			$where = "where {$statusCond}";
		}
	}

	$numMembers = sqlValue("select count(1) from membership_users m left join membership_groups g on m.groupID=g.groupID {$where}");
	if(!$numMembers) {
		echo "<div class=\"alert alert-warning\">{$Translation['no matching results found']}</div>";
		$noResults = true;
		$page = 1;
	} else {
		$noResults = false;
	}

	$page = max(1, intval(Request::val('page')));
	if($page > ceil($numMembers / $adminConfig['membersPerPage']) && !$noResults) {
		redirect("admin/pageViewMembers.php?page=" . ceil($numMembers/$adminConfig['membersPerPage']));
	}

	$start = ($page - 1) * $adminConfig['membersPerPage'];

	$urlCsrfToken = 'csrf_token=' . urlencode(csrf_token(false, true));

?>
<div class="page-header">
	<h1>
		<?php echo $Translation['members']; ?>
		<div class="pull-right">
			<a href="pageEditMember.php" class="btn btn-success btn-lg"><i class="glyphicon glyphicon-plus"></i> <?php echo $Translation['add new member']; ?></a>
		</div>
	</h1>
</div>

<form id="search-form" class="form-inline" method="get" action="pageViewMembers.php">

<input type="hidden" name="sort" value="<?php echo $sort; ?>">
<input type="hidden" name="sortDir" value="<?php echo $sortDir; ?>">

<table class="table table-striped table-bordered table-hover">
	<thead>
		<tr>
			<th colspan="10" align="center">
				<input type="hidden" name="page" value="1">

				<div class="form-group">
					<?php 
						$originalValues =  array ('<SEARCH>','<HTMLSELECT>');
						$searchValue = '<input class="form-control" type="text" name="searchMembers" value="' . $searchHTML . '">';
						$arrFields = [0, 1, 2, 3, 4, 5, 6, 7, 8];
						$arrFieldCaptions = [$Translation['all fields'], $Translation['username'], $Translation['group'], $Translation['email'], $adminConfig['custom1'], $adminConfig['custom2'], $adminConfig['custom3'], $adminConfig['custom4'], $Translation['comments']];
						$htmlSelect = htmlSelect('searchField', $arrFields, $arrFieldCaptions, $searchField);
						$replaceValues = [$searchValue, $htmlSelect];
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
						$arrFields = [0, 1, 2, 3];
						$arrFieldCaptions = [$Translation['any'], $Translation['waiting approval'], $Translation['active'], $Translation['Banned']];
						echo htmlSelect('status', $arrFields, $arrFieldCaptions, $status);
					?>
				</div>

				<div class="form-group">
					<button class="btn btn-primary" type="submit"><i class="glyphicon glyphicon-search"></i> <?php echo $Translation['find']; ?></button>
					<a class="btn btn-warning" href="pageViewMembers.php"><i class="glyphicon glyphicon-remove"></i> <?php echo $Translation['reset']; ?></a>
				</div>
			</th>
		</tr>

		<tr>
			<?php
				$colTitles = [
					$Translation['username'],
					$Translation['group'],
					$Translation['sign up date'],
					$Translation['email'],
					$adminConfig['custom1'],
					$adminConfig['custom2'],
					$adminConfig['custom3'],
					$adminConfig['custom4'],
					$Translation['Status'],
				];
				foreach ($colTitles as $i => $title) {
					$sortIcon = '';
					$nextSortDir = 'asc';
					if($sort == $i) {
						$sortIcon = '<i class="hspacer-sm glyphicon glyphicon-sort-by-attributes' . ($sortDir == 'ASC' ? '' : '-alt') .'"></i>';
						if($sortDir == 'ASC') $nextSortDir = 'desc';
					}
					printf(
						'<th><span class="sort-link" data-sort="%s" data-sortdir="%s">%s</span>%s</th>', 
						$i, $nextSortDir, htmlspecialchars($title), $sortIcon
					);
				}
			?>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
<?php

	$mpp = intval($adminConfig['membersPerPage']);
	$mviewFieldsStr = implode(', ', $mviewFields);
	// handle date sorting
	if(strpos($mviewFields[$sort], 'm.signupDate') !== false)
		$sort = 'm.signupDate';
	else
		$sort++; // because sort index in MySQL is 1-based

	$query = " 
		SELECT
			$mviewFieldsStr
		FROM
			membership_users m LEFT JOIN
			membership_groups g ON m.groupID=g.groupID
		$where
		ORDER BY $sort $sortDir
		LIMIT $start, $mpp
	";
	$res = sql($query, $eo);
	while($row = db_fetch_row($res)) {
		$tr_class = '';
		if($adminConfig['adminUsername'] == $row[0]) $tr_class = 'warning text-bold';
		if($adminConfig['anonymousMember'] == $row[0]) $tr_class = 'text-muted';
		?>
		<tr class="<?php echo $tr_class; ?>">
			<?php if($adminConfig['anonymousMember'] == $row[0]) { ?>
				<td class="text-left"><?php echo htmlspecialchars($row[0]); ?></td>
			<?php } else { ?>
				<td class="text-left"><a href="pageEditMember.php?memberID=<?php echo urlencode($row[0]); ?>"><?php echo htmlspecialchars($row[0]); ?></a></td>
			<?php } ?>
			<td class="text-left"><?php echo htmlspecialchars($row[1]); ?></td>
			<td class="text-left"><?php echo htmlspecialchars($row[2]); ?></td>
			<td class="text-left">
				<?php if($adminConfig['anonymousMember'] != $row[0]) { ?>
					<a
						title="<?php echo html_attr($Translation["send message to member"]); ?>" 
						href="pageMail.php?memberID=<?php echo urlencode($row[0]); ?>"><?php echo $row[3]; ?></a>
				<?php } ?>
			</td>
			<td class="text-left"><?php echo htmlspecialchars($row[4]); ?></td>
			<td class="text-left"><?php echo htmlspecialchars($row[5]); ?></td>
			<td class="text-left"><?php echo htmlspecialchars($row[6]); ?></td>
			<td class="text-left"><?php echo htmlspecialchars($row[7]); ?></td>
			<td class="text-left">
				<?php echo (($row[8] && $row[9]) ? $Translation['Banned'] : ($row[9] ? $Translation['active'] : $Translation['waiting approval'] )); ?>
			</td>
			<td class="text-center">
				<!-- edit -->
				<?php if($adminConfig['anonymousMember'] == $row[0]) { ?>
					<i class="glyphicon glyphicon-pencil text-muted"></i>
				<?php } else { ?>
					<a href="pageEditMember.php?memberID=<?php echo urlencode($row[0]); ?>"><i class="glyphicon glyphicon-pencil" title="<?php echo $Translation['Edit member']; ?>"></i></a>
				<?php } ?>
				<span class="hspacer-sm"></span>

				<!-- delete, ban, unban -->
				<?php if($adminConfig['anonymousMember'] == $row[0] || $adminConfig['adminUsername'] == $row[0]) { ?>
					<i class="glyphicon glyphicon-trash text-muted"></i>
					<span class="hspacer-sm"></span>
					<i class="glyphicon glyphicon-ban-circle text-muted"></i>
				<?php } else { ?>
					<a href="pageDeleteMember.php?memberID=<?php echo urlencode($row[0]); ?>&<?php echo $urlCsrfToken; ?>" onClick="return confirm('<?php echo addslashes(str_replace('<USERNAME>', $row[0], $Translation['sure delete user'])); ?>');"><i class="glyphicon glyphicon-trash text-danger" title="<?php echo $Translation['delete member']; ?>"></i></a>
					<span class="hspacer-sm"></span>
					<?php
						if(!$row[9]) { // if member is not approved, display approve link
							?><a href="pageChangeMemberStatus.php?memberID=<?php echo urlencode($row[0]); ?>&approve=1&<?php echo $urlCsrfToken; ?>"><i class="glyphicon glyphicon-ok text-success" title="<?php echo $Translation["unban this member"]; ?>" title="<?php echo $Translation["approve this member"]; ?>"></i></a><?php
						} else {
							if($row[8]) { // if member is banned, display unban link
								?><a href="pageChangeMemberStatus.php?memberID=<?php echo urlencode($row[0]); ?>&unban=1&<?php echo $urlCsrfToken; ?>"><i class="glyphicon glyphicon-ok text-success" title="<?php echo $Translation["unban this member"]; ?>"></i></a><?php
							} else { // if member is not banned, display ban link
								?><a href="pageChangeMemberStatus.php?memberID=<?php echo urlencode($row[0]); ?>&ban=1&<?php echo $urlCsrfToken; ?>"><i class="glyphicon glyphicon-ban-circle text-danger" title="<?php echo $Translation["ban this member"]; ?>"></i></a><?php
							}
						}
					?>
				<?php } ?>
				<span class="hspacer-sm"></span>

				<!-- view records -->
				<a href="pageViewRecords.php?memberID=<?php echo urlencode($row[0]); ?>"><i class="glyphicon glyphicon-th" title="<?php echo $Translation["View member records"]; ?>"></i></a>
			</td>
		</tr>
		<?php
	}
?>
	</tbody>

	<tfoot>
		<tr>
			<th colspan="10">
				<table width="100%" cellspacing="0">
					<tr>
						<?php
							// pagination
							$prevPage = $page > 1 ? $page - 1 : false;
							$nextPage = $page < ceil($numMembers / $adminConfig['membersPerPage']) ? $page + 1 : false;
						?>
						<td class="text-left" width="33%">
							<?php if($prevPage) { ?>
								<button type="submit" class="btn btn-default" name="page" value="<?php echo $prevPage; ?>"><?php echo $Translation['previous']; ?></button>
							<?php } ?>
						</td>
						<td class="text-center" width="33%">
							<?php 
								$originalValues =  array ('<MEMBERNUM1>','<MEMBERNUM2>','<MEMBERS>' );
								$replaceValues = array ( $start+1 , $start+db_num_rows($res) , $numMembers );
								echo str_replace ( $originalValues , $replaceValues , $Translation['displaying members'] );
							?>
						</td>
						<td class="text-right">
							<?php if($nextPage) { ?>
								<button type="submit" class="btn btn-default" name="page" value="<?php echo $nextPage; ?>"><?php echo $Translation['next']; ?></button>
							<?php } ?>
						</td>
					</tr>
				</table>
			</th>
		</tr>
		<tr>
			<th colspan="10">
				<b><?php echo $Translation['key']; ?></b>
				<div class="row">
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-pencil text-info"></i> <?php echo $Translation['edit member details']; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-trash text-danger"></i> <?php echo $Translation['delete member']; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-ok text-success"></i> <?php echo $Translation['activate member']; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-ban-circle text-danger"></i> <?php echo $Translation['ban member']; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-th text-info"></i> <?php echo $Translation['view entered member records']; ?></div>
					<div class="col-sm-6 col-md-4 col-lg-3"><i class="glyphicon glyphicon-envelope text-info"></i> <?php echo $Translation['send email to member']; ?></div>
				</div>
			</th>
		</tr>
	</tfoot>
</table>
</form>

<style>
	.form-inline .form-group{ margin: .5em 1em; }
	.sort-link { cursor: pointer; }
</style>

<script>
	$j(function() {
		$j('.form-inline select').addClass('form-control');

		// handle sorting
		$j('.sort-link').on('click', function() {
			$j('input[name=sort]').val($j(this).data('sort'));
			$j('input[name=sortDir]').val($j(this).data('sortdir'));
			$j('#search-form').submit();
		})
	})
</script>

<?php include(__DIR__ . '/incFooter.php');
