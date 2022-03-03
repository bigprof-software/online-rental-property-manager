<?php
	require(__DIR__ . '/incCommon.php');

	// Retrieve request (type, page)
		$queryTypes = [
			'slow' => 'COALESCE(`duration`, 0) > 0',
			'error' => "CHAR_LENGTH(COALESCE(`error`, '')) > 0",
		];

		$type = Request::oneOf('type', array_keys($queryTypes), 'slow');
		$clear = Request::oneOf('clear', array_keys($queryTypes), false);

		$page = intval(Request::val('page'));
		if($page < 1) $page = 1;

	// Starting record from $page (page is 1-based, while firstRecord is 0-based)
		$recordsPerPage = config('adminConfig')['recordsPerPage'];
		// $firstRecord is calculated below after retrieving $lastPage

	// set up log table if necessary
		if(!createQueryLogTable())
			dieErrorPage($Translation['Query log table does not exist']);

	// clear logs of specified type if requested
		$eo = ['silentErrors' => true];
		if($clear) {
			sql("DELETE FROM `appgini_query_log` WHERE {$queryTypes[$clear]}", $eo);
			redirect('admin/pageQueryLogs.php?cleared=1&type=' . $clear);
		} else
			// only keep queries that are at most 2 months old
			sql("DELETE FROM `appgini_query_log` WHERE `datetime` < DATE_SUB(NOW(), INTERVAL 2 MONTH)", $eo);

	/* TODO future work: allow filtering by user, date period, ... etc, and allow sorting */

	// get count of each type
		$counts = [];
		foreach ($queryTypes as $qt => $where)
			$counts[$qt] = sqlValue("SELECT COUNT(1) FROM `appgini_query_log` WHERE $where");

	// how many pages in total do we have?
		$lastPage = ceil($counts[$type] / $recordsPerPage);

	// adjust $page and $firstRecord if needed
		$page = max(1, min($page, $lastPage));
		$firstRecord = ($page - 1) * $recordsPerPage;

	// get app uri to strip from stored uri
		$appURI = config('appURI');

	// retrieve requested logs
		$records = [];
		$res = sql("SELECT * FROM `appgini_query_log`
			WHERE {$queryTypes[$type]}
			ORDER BY `datetime` DESC
			LIMIT $firstRecord, $recordsPerPage", $eo);
		while($row = db_fetch_assoc($res)) {
			if($appURI) $row['uri'] = ltrim(str_replace($appURI, '', $row['uri']), '/');
			$records[] = $row;
		}

	include(__DIR__ . '/incHeader.php');
?>

<!-- Page content -->
	<div class="alert alert-info alert-dismissible cleared-notification hidden">
		<button type="button" class="close" data-dismiss="alert">&times;</button>
		<?php echo str_replace('<TYPE>', ucfirst($type), $Translation['query logs cleared']); ?>
	</div>

	<div class="page-header"><h1><?php echo $Translation['Query logs']; ?></h1></div>

	<p><?php echo $Translation['query logs older than 2 month auto cleared']; ?></p>

	<ul class="nav nav-tabs" style="margin-bottom: 2em;">
		<li class="query-type slow"><a href="#" class="bg-warning">
			<span class="text-warning">
				<?php echo $Translation['slow queries']; ?>
				<span class="badge"><?php echo $counts['slow']; ?></span>      
			</span>
		</a></li>
		<li class="query-type error"><a href="#" class="bg-danger">
			<span class="text-danger">
				<?php echo $Translation['error queries']; ?>
				<span class="badge"><?php echo $counts['error']; ?></span>      
			</span>
		</a></li>
	</ul>

	<div class="alert alert-success hidden" id="noMatches">
		<i class="glyphicon glyphicon-ok"></i> 
		<?php echo $Translation['no matching results found']; ?>
	</div>

	<!-- buttons to clear logs -->
	<div class="text-center bspacer-lg">
		<a href="pageQueryLogs.php?clear=slow&type=slow" class="btn btn-default clear-logs clear-slow hidden">
			<span class="text-warning">
				<i class="glyphicon glyphicon-trash"></i>
				<?php echo $Translation['clear slow queries']; ?>
			</span>
		</a>
		<a href="pageQueryLogs.php?clear=error&type=error" class="btn btn-default clear-logs clear-error hidden">
			<span class="text-danger">
				<i class="glyphicon glyphicon-trash"></i>
				<?php echo $Translation['clear error queries']; ?>
			</span>
		</a>
	</div>

	<div class="table-responsive">       
		<table class="table table-striped table-hover table-bordered" id="queryLogs">
			<thead>
				<tr>
					<th><?php echo $Translation['date/time']; ?> <i class="glyphicon glyphicon-sort-by-attributes-alt"></i> </th>
					<th><?php echo $Translation['username']; ?></th>
					<th><?php echo $Translation['page address']; ?></th>
					<th style="min-width: 40vw;"><?php echo $Translation['query']; ?></th>
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
									[$counts[$type], $page, $lastPage],
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

			// show cleared message if 'cleared=1'
				$j('.cleared-notification').toggleClass('hidden', location.search.indexOf('cleared=1') == -1);

			// set active query-type button based on type
				$j(`.query-type.${type}`).addClass('active text-bold');

			// show clear button for specified type
				$j(`.clear-logs.clear-${type}`).removeClass('hidden');

			// hide table and show no matches if 0 records
				var noMatches = !$j('#queryLogs > tbody > tr').length;
				$j(`#queryLogs, .clear-logs.clear-${type}`).toggleClass('hidden', noMatches);
				$j('#noMatches').toggleClass('hidden', !noMatches);

			// hide non relevent parts based on type
				$j('#queryLogs .duration').toggleClass('hidden', type == 'error');
				$j('#queryLogs .error').toggleClass('hidden', type == 'slow');

			// handle clicking query-type buttons
				$j('.query-type:not(.active)').click(function(e) {
					e.preventDefault();
					location.href = 'pageQueryLogs.php?type=' + (type == 'slow' ? 'error' : 'slow');
				})

			// confirm clearing logs
				$j('.clear-logs').on('click', (e) => {
					if(!confirm(<?php echo json_encode($Translation['confirm clear logs']); ?>))
						e.preventDefault();
				})

			// toggle next/previous links
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

<?php include(__DIR__ . '/incFooter.php');
