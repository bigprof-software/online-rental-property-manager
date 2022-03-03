<?php
	require(__DIR__ . '/incCommon.php');
	$GLOBALS['page_title'] = $Translation['data records'];
	include(__DIR__ . '/incHeader.php');

	// process search
	$memberID = new Request('memberID', 'strtolower');
	$groupID = max(0, intval(Request::val('groupID')));
	$tableName = new Request('tableName');
	$page = max(1, intval(Request::val('page')));
	$where = [];

	// process sort
	$sortDir = (Request::val('sortDir') == 'DESC' ? 'DESC' : '');
	$sort = makeSafe(Request::val('sort'));
	if($sort != 'dateAdded' && $sort != 'dateUpdated') { // default sort is newly created first
		$sort = 'dateAdded';
		$sortDir = 'DESC';
	}

	$sortClause = $sort ? "ORDER BY {$sort} {$sortDir}" : '';

	if($memberID->sql == '{none}')
		$where[] = "NOT LENGTH(r.memberID)";
	elseif($memberID->sql != '')
		$where[] = "r.memberID LIKE '{$memberID->sql}%'";

	if($groupID) $where[] = "g.groupID='{$groupID}'";

	if($tableName->sql != '') $where[] = "r.tableName='{$tableName->sql}'";

	$whereStr = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

	$numRecords = sqlValue("SELECT COUNT(1) FROM membership_userrecords r LEFT JOIN membership_groups g ON r.groupID=g.groupID {$whereStr}");

	$noResults = false;
	if(!$numRecords) {
		echo "<div class=\"alert alert-warning\">{$Translation['no matching results found']}</div>";
		$noResults = true;
		$page = 1;
	}

	if($page > ceil($numRecords / $adminConfig['recordsPerPage']) && !$noResults) {
		redirect("admin/pageViewRecords.php?page=" . ceil($numRecords/$adminConfig['recordsPerPage']));
	}

	$start = ($page - 1) * $adminConfig['recordsPerPage'];

?>
<div class="page-header"><h1><?php echo $Translation['data records'] ; ?></h1></div>

<table class="table table-striped table-bordered table-hover">
	<thead>
		<tr>
			<th colspan="7">
				<form class="form-inline" method="get" action="pageViewRecords.php" class="form-horizontal">
					<input type="hidden" name="page" value="1">

					<div class="form-group">
						<label for="groupID" class="control-label"><?php echo $Translation["group"]; ?></label>
						<?php echo htmlSQLSelect("groupID", "select groupID, name from membership_groups order by name", $groupID); ?>
					</div>
					<div class="form-group">
						<label for="memberID" class="control-label"><?php echo $Translation["member username"] ; ?></label>
						<input type="text" class="form-control" id="memberID" name="memberID" value="<?php echo $memberID->attr; ?>">
					</div>
					<div class="form-group">
						<label for="tableName" class="control-label"><?php echo $Translation['show records'] ; ?></label>
						<?php
							$tables = array_merge(
								['' => $Translation['all tables']],
								array_map(function($t) { return $t[0]; }, getTableList(true))
							);
							$arrFields = array_keys($tables);
							$arrFieldCaptions = array_values($tables);
							echo htmlSelect('tableName', $arrFields, $arrFieldCaptions, $tableName->raw);
						?>
					</div>
					<div class="form-group">
						<label for="sort" class="control-label"><?php echo $Translation['sort records'] ; ?></label>
						<?php
							$arrFields = ['dateAdded', 'dateUpdated'];
							$arrFieldCaptions = [$Translation['date created'], $Translation['date modified']];
							echo htmlSelect('sort', $arrFields, $arrFieldCaptions, $sort);
						?>
						<span class="hspacer-md"></span>
						<?php
							$arrFields = ['DESC', ''];
							$arrFieldCaptions = [$Translation['newer first'], $Translation['older first']];
							echo htmlSelect('sortDir', $arrFields, $arrFieldCaptions, $sortDir);
						?>
					</div>
					<div class="form-group">
						<button type="submit" class="btn btn-primary"><i class="glyphicon glyphicon-search"></i> <?php echo $Translation['find'] ; ?></button>
						<button type="button" id="reset-search" class="btn btn-warning"><i class="glyphicon glyphicon-remove"></i> <?php echo $Translation['reset'] ; ?></button>
					</div>
				</form>
			</th>
		</tr>
		<tr>
			<th>&nbsp;</td>
			<th><?php echo $Translation['username'] ; ?></th>
			<th><?php echo $Translation['group'] ; ?></th>
			<th><?php echo $Translation['table'] ; ?></th>
			<th><?php echo $Translation['created'] ; ?></th>
			<th><?php echo $Translation['modified'] ; ?></th>
			<th><?php echo $Translation['data'] ; ?></th>
		</tr>
	</thead>
	<tbody>
<?php

	$res = sql(
		"SELECT
			r.recID, r.memberID, g.name, r.tableName, r.dateAdded, r.dateUpdated, r.pkValue 
		FROM
			membership_userrecords r LEFT JOIN membership_groups g ON r.groupID=g.groupID
		$whereStr $sortClause LIMIT $start, " . intval($adminConfig['recordsPerPage']),
	$eo);
	while($row = db_fetch_row($res)) {
		?>
		<tr>
			<td class="text-center">
				<a href="pageEditOwnership.php?recID=<?php echo $row[0]; ?>" title="<?php echo $Translation['change record ownership'] ; ?>"><i class="glyphicon glyphicon-user"></i></a>
				<a href="pageDeleteRecord.php?recID=<?php echo $row[0]; ?>&csrf_token=<?php echo urlencode(csrf_token(false, true)); ?>" onClick="return confirm('<?php echo addslashes($Translation['sure delete record']); ?>');" title="<?php echo $Translation['delete record']; ?>"><i class="glyphicon glyphicon-trash text-danger"></i></a>
			</td>
			<td><?php echo $row[1]; ?></td>
			<td><?php echo $row[2]; ?></td>
			<td><?php echo $row[3]; ?></td>
			<td class="<?php echo ($sort == 'dateAdded' ? 'warning' : '');?>"><?php echo @date($adminConfig['PHPDateTimeFormat'], $row[4]); ?></td>
			<td class="<?php echo ($sort == 'dateUpdated' ? 'warning' : '');?>"><?php echo @date($adminConfig['PHPDateTimeFormat'], $row[5]); ?></td>
			<td>
				<a href="#" class="view-record" data-record_id="<?php echo $row[0]; ?>"><i class="glyphicon glyphicon-search hspacer-md"></i></a>
				<?php echo substr(getCSVData($row[3], $row[6]), 0, 80) . " ... "; ?>
			</td>
		</tr>
		<?php
	}
	?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="7" style="padding: .3em;">
				<table width="100%" cellspacing="0">
					<tr>
						<th class="text-left flip" style="width: 25%;">
							<?php if($start) { ?>
								<a href="pageViewRecords.php?groupID=<?php echo $groupID; ?>&memberID=<?php echo $memberID->url; ?>&tableName=<?php echo $tableName->url; ?>&page=<?php echo ($page > 1 ? $page - 1 : 1); ?>&sort=<?php echo $sort; ?>&sortDir=<?php echo $sortDir; ?>" class="btn btn-default"><?php echo $Translation['previous'] ; ?></a>
							<?php } ?>
						</th>
						<th class="text-center">
							<?php
								$record1 = $start + 1;
								$record2 = $start + db_num_rows($res);
								$originalValues = ['<RECORDNUM1>', '<RECORDNUM2>', '<RECORDS>'];
								$replaceValues = [$record1, $record2, $numRecords];
								echo str_replace($originalValues, $replaceValues, $Translation['displaying records']);
							?>
						</th>
						<th class="text-right flip" style="width: 25%;">
							<?php if($record2 < $numRecords) { ?>
								<a href="pageViewRecords.php?groupID=<?php echo $groupID; ?>&memberID=<?php echo $memberID->url; ?>&tableName=<?php echo $tableName->url; ?>&page=<?php echo ($page<ceil($numRecords/$adminConfig['recordsPerPage']) ? $page+1 : ceil($numRecords/$adminConfig['recordsPerPage'])); ?>&sort=<?php echo $sort; ?>&sortDir=<?php echo $sortDir; ?>" class="btn btn-default"><?php echo $Translation['next'] ; ?></a>
							<?php } ?>
						</th>
					</tr>
				</table>
			</td>
		</tr>
	</tfoot>
</table>

<div class="modal fade" tabindex="-1" id="view-record-modal">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<button type="button" class="close hspacer-md vspacer-md" data-dismiss="modal">&times;</button>
			<div class="modal-body" style="-webkit-overflow-scrolling:touch !important; overflow-y: auto;">
				<iframe width="100%" height="100%" sandbox="allow-modals allow-forms allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox allow-downloads" src="" id="view-record-iframe"></iframe>
			</div>
		</div>
	</div>
</div>

<style>
	.form-inline .form-group{ margin: .5em 1em; }
</style>

<script>
	$j(function() {
		$j('.view-record').click(function() {
			var recID = $j(this).data('record_id');
			$j('#view-record-iframe').attr('src', 'pagePrintRecord.php?recID=' + recID);
			$j('#view-record-modal').modal('show');
			$j('#view-record-modal .modal-body').height($j(window).height() * 0.7);

			return false;
		});

		$j('#reset-search').click(function() {
			window.location = 'pageViewRecords.php';
		});

		$j('#tableName, #groupID, #sort, #sortDir').addClass('form-control');
	})
</script>

<?php
	include(__DIR__ . '/incFooter.php');
