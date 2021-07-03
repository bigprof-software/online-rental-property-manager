<?php
	require(__DIR__ . "/incCommon.php");

	// Retrieve request (type, page)
		$queryTypes = [
			'slow' => 'COALESCE(`duration`, 0) > 0',
			'error' => 'CHAR_LENGTH(COALESCE(`error`, \'\')) > 0',
		];

		$type = $_REQUEST['type'];
		if(!in_array($type, array_keys($queryTypes))) $type = 'slow';

		$page = intval($_REQUEST['page']);
		if($page < 1) $page = 1;

	// Starting record from $page (page is 1-based, while firstRecord is 0-based)
		$recordsPerPage = config('adminConfig')['recordsPerPage'];
		// $firstRecord is calculated below after retrieving $lastPage

	// set up log table if necessary
		if(!createQueryLogTable())
			dieErrorPage($Translation['Query log table does not exist']);

	// only keep queries that are at most 2 months old
		$eo = ['silentErrors' => true];
		sql("DELETE FROM `appgini_query_log` WHERE `datetime` < DATE_SUB(NOW(), INTERVAL 2 MONTH)", $eo);

	// build the WHERE clause
		$where = "WHERE {$queryTypes[$type]}";
		/* future work: allow filtering by user, date period, ... etc, and allow sorting */

	// how many records and pages in total do we have?
		$totalRecords = sqlValue("SELECT COUNT(1) FROM `appgini_query_log` $where");
		$lastPage = ceil($totalRecords / $recordsPerPage);

	// adjust $page and $firstRecord if needed
		$page = max(1, min($page, $lastPage));
		$firstRecord = ($page - 1) * $recordsPerPage;

	// get app uri to strip from stored uri
		$appURI = config('appURI');

	// retrieve requested logs
		$records = [];
		$res = sql("SELECT * FROM `appgini_query_log`
			$where
			ORDER BY `datetime` DESC
			LIMIT $firstRecord, $recordsPerPage", $eo);
		while($row = db_fetch_assoc($res)) {
			if($appURI) $row['uri'] = ltrim(str_replace($appURI, '', $row['uri']), '/');
			$records[] = $row;
		}

	include(__DIR__ . "/incHeader.php");
?>

<!-- Page content -->
	<div class="page-header"><h1><?php echo $Translation['Query logs']; ?></h1></div>

	<div class="btn-group">
		<button type="button" class="btn btn-warning btn-lg query-type slow"><?php echo $Translation['slow queries']; ?></button>
		<button type="button" class="btn btn-danger btn-lg query-type error"><?php echo $Translation['error queries']; ?></button>
	</div>

	<hr>

	<div class="alert alert-success hidden" id="noMatches">
		<i class="glyphicon glyphicon-ok"></i> 
		<?php echo $Translation['no matching results found']; ?>
	</div>

	<div class="table-responsive">       
		<table class="table table-striped table-hover table-bordered" id="queryLogs">
			<thead>
				<tr>
					<th><?php echo $Translation['date/time']; ?> <i class="glyphicon glyphicon-sort-by-attributes-alt"></i> </th>
					<th><?php echo $Translation['username']; ?></th>
					<th><?php echo $Translation['page address']; ?></th>
					<th><?php echo $Translation['query']; ?></th>
				</tr>
			</thead>

			<tbody>
			<?php foreach($records as $rec) { ?>
				<tr>
					<td><?php echo app_datetime($rec['datetime'], 'dt'); ?></td>
					<td><?php echo $rec['memberID']; ?></td>
					<td><?php echo $rec['uri']; ?></td>
					<td>
						<pre><?php echo htmlspecialchars($rec['statement']); ?></pre>
						<div class="error text-danger text-bold"><?php echo htmlspecialchars($rec['error']); ?></div>
						<div class="duration text-warning text-bold">
							<?php echo $Translation['duration (sec)']; ?>:
							<?php echo $rec['duration']; ?>
						</div>
					</td>
				</tr>
			<?php } ?>
			</tbody>

			<tfoot>
				<tr class="records-pagination">
					<th colspan="4">
						<div class="row">
							<div class="col-xs-4 text-left">
								<button type="button" class="btn btn-default btn-previous"><i class="glyphicon glyphicon-chevron-left"></i> <?php echo $Translation['previous']; ?></button>
							</div>
							<div class="col-xs-4 text-center">
								<?php echo str_replace(
									['#', '<x>', '<y>'],
									[$totalRecords, $page, $lastPage],
									$Translation['total # queries'] . ' ' . $Translation['page x of y']
								); ?>
							</div>
							<div class="col-xs-4 text-right">
								<button type="button" class="btn btn-default btn-next"><?php echo $Translation['next']; ?> <i class="glyphicon glyphicon-chevron-right"></i></button>
							</div>
						</div>                 
					</th>
				</tr>
			</tfoot>
		</table>
	</div>

	<style>
		#queryLogs tr > td:first-child { min-width: 10em; }
		#queryLogs pre {
			white-space: pre-wrap;
			max-height: 8em;
			overflow-y: auto;
		}
	</style>

	<script>
		$j(function() {
			var type = '<?php echo $type; ?>';
			var page = <?php echo $page; ?>, lastPage = <?php echo $lastPage; ?>;

			// set active query-type button based on type
				$j('.query-type.' + type).addClass('active text-bold');

			// hide table and show no matches if 0 records
				var noMatches = !$j('#queryLogs > tbody > tr').length;
				$j('#queryLogs').toggleClass('hidden', noMatches);
				$j('#noMatches').toggleClass('hidden', !noMatches);

			// hide non relevent parts based on type
				$j('#queryLogs .duration').toggleClass('hidden', type == 'error');
				$j('#queryLogs .error').toggleClass('hidden', type == 'slow');

			// handle clicking query-type buttons
				$j('.query-type:not(.active)').click(function() {
					location.href = 'pageQueryLogs.php?type=' + (type == 'slow' ? 'error' : 'slow');
				})

			// toogle next/previous links
				var prevPage = page > 1 ? page - 1 : 1;
				var nextPage = page < lastPage ? page + 1 : lastPage;
				$j('.btn-previous').toggleClass('hidden', page == 1);
				$j('.btn-next').toggleClass('hidden', page == lastPage);

			// clone pagination on top of table
				$j('tr.records-pagination').clone().prependTo('#queryLogs thead');

			// handle clicking prev link
				$j('.btn-previous').on('click', function() {
					location.href = 'pageQueryLogs.php?type=' + type + '&page=' + prevPage;
				})

			// handle clicking next link
				$j('.btn-next').on('click', function() {
					location.href = 'pageQueryLogs.php?type=' + type + '&page=' + nextPage;
				})
		})
	</script>

<?php include(__DIR__ . "/incFooter.php");
