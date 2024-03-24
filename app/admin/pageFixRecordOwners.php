<?php
	require(__DIR__ . '/incCommon.php');
	define('TIME_LIMIT', 1.0); // seconds
	define('BATCH_READ_SIZE', 500); // number of records to read in each batch

	// GET request: show app page
	if($_SERVER['REQUEST_METHOD'] == 'GET') showAppPage();

	// POST request containing `tn`: continue (or begin) batch process of fixing record owners
	if(Request::val('tn')) batchProcess();

	function showAppPage() {
		global $Translation;

		$tables = getTableList();
		// remove tables that don't have a record owner set
		foreach($tables as $tn => $tc) {
			if(!tableRecordOwner($tn)) unset($tables[$tn]);
		}

		// clear session variables from previous runs
		foreach($tables as $tn => $tc) {
			unset($_SESSION['fixRecordOwners_' . $tn]);
		}

		$GLOBALS['page_title'] = $Translation['fix record owners'];
		include(__DIR__ . '/incHeader.php');
		?>

		<div class="page-header"><h1>
			<span class="glyphicon glyphicon-user"></span>
			<?php echo $Translation['fix record owners']; ?>
		</h1></div>

		<p class="lead"><?php echo $Translation['fix record owners description']; ?></p>

		<?php if(!count($tables)) { ?>
			<div class="alert alert-warning"><?php echo $Translation['no tables to fix record owners']; ?></div>
		<?php } else { ?>

			<?php echo csrf_token(); ?>
			<div class="text-right bspacer-lg">
				<button type="button" class="btn btn-primary btn-lg" id="start">
					<span class="glyphicon glyphicon-play"></span> <?php echo $Translation['start']; ?>
				</button>
			</div>

			<div class="panel panel-default">
				<div class="panel-body bg-info">
					<label class="text-info"><?php echo $Translation['number of runs']; ?></label>
					<div class="progress">
						<div class="progress-bar progress-bar-striped progress-bar-runs" style="width: 0%; min-width: 3em;">0 / <?php echo count($tables); ?></div>
					</div>
				</div>
				<ul class="list-group">
					<?php
					// list each table and a progress bar set to 0% initially
					foreach($tables as $tn => $tc) { ?>
						<li class="list-group-item">
							<label><img src="../<?php echo $tc[2]; ?>"> <?php echo $tc[0]; ?></label>
							<div class="progress">
								<div class="progress-bar progress-bar-striped progress-bar-table" style="width: 0%; min-width: 2em;" data-tn="<?php echo $tn; ?>">0%</div>
							</div>
							<div class="text-muted"><?php echo $Translation['records updated:']; ?><span class="total-records" id="total-records-<?php echo $tn; ?>">0</span></div>
						</li>
					<?php } ?>
				</ul>
			</div>

			<?php echo batchRunnerJSCode(); ?>

		<?php } ?> 

		<style>
			#start {
				width: 100%;
				max-width: 20em;
			}
		</style>

		<?php
		include(__DIR__ . '/incFooter.php');
	}

	function batchRunnerJSCode() {
		global $Translation;

		ob_start(); ?>

		<script>$j(() => {
			$j('#start').on('click', () => {
				$j('#start')
					.prop('disabled', true)
					.html('<span class="glyphicon glyphicon-refresh loop-rotate"></span> ' + <?php echo json_encode($Translation['fixing record owners']); ?>)
					.addClass('disabled');

				// get list of tables to fix by retrieving the `data-tn` attribute of each progress bar
				const tables = $j('.progress-bar-table').map(function() { return $j(this).data('tn'); }).get();

				// promises array, an array of runs, each run is an array of batch promises
				const promises = [[]];

				const batch = (tn, run) => {
					const progressBar = $j(`.progress-bar[data-tn="${tn}"]`);
					progressBar.addClass('active');

					// send POST request, add promise to array
					promises[run] = promises[run] || [];
					promises[run].push($j.ajax({
						url: 'pageFixRecordOwners.php',
						method: 'POST',
						data: { tn, run, csrf_token: $j('#csrf_token').val() },
						dataType: 'json',
						success: resp => {
							const data = resp.data;

							// update table progress bar
							progressBar.css('width', data.progress + '%').html(data.progress + '%');

							// add affected records to total
							$j(`#total-records-${tn}`).html(parseInt($j(`#total-records-${tn}`).html()) + data.affectedRecords);

							if(data.progress == 100) {
								// TODO: if there are more runs ... ???
								progressBar.removeClass('progress-bar-striped active');
								progressBar.html(<?php echo json_encode($Translation['done']); ?>);
								return;
							}

							// if there are more records to process, continue
							batch(tn, run);
						},
						error: (jqxhr) => {
							let error;
							try {
								error = JSON.parse(jqxhr.responseText).message;
							} catch(e) {
								error = jqxhr.responseText;
							}
							progressBar.removeClass('progress-bar-striped');
							progressBar.html(<?php echo json_encode($Translation['error:']); ?> + ' ' + error);
						}
					}));
				};

				const nextRun = (run = 0) => {
					if(run >= tables.length) return;

					const runsProgressBar = $j('.progress-bar-runs');

					runsProgressBar.addClass('active');
					tables.forEach(tn => {
						// reset table progress bar
						$j(`.progress-bar-table[data-tn="${tn}"]`)
							.css('width', '0%')
							.addClass('progress-bar-striped active')
							.html('0%');

						// reset total records
						$j(`#total-records-${tn}`).html('0');

						batch(tn, run); 
					});

					// keep watching until all table progress bars are done, then start next run
					const interval = setInterval(() => {
						// if there are still active progress bars, keep watching
						if($j('.progress-bar-table.progress-bar-striped').length > 0) return; 

						// all progress bars are done, clear interval to stop watching
						clearInterval(interval);

						// update runs progress bar
						const runsProgress = Math.round((run + 1) / tables.length * 100);
						runsProgressBar.css('width', runsProgress + '%').html((run + 1) + ' / ' + tables.length);

						// if there are no more runs, we're finished fixing record owners
						if(runsProgress == 100) {
							runsProgressBar.removeClass('progress-bar-striped');

							// update start button
							$j('#start')
								.html('<span class="glyphicon glyphicon-ok"></span> ' + <?php echo json_encode($Translation['done']); ?>)
								.removeClass('disabled btn-primary')
								.addClass('btn-success');

							return;
						}

						// otherwise, start next run
						nextRun(run + 1);
					}, 100);
				};

				nextRun();
			});
		})</script>

		<?php return ob_get_clean();
	}

	function withinTimeLimit() {
		static $start = null;
		if($start === null) $start = microtime(true);
		$elapsed = round(microtime(true) - $start, 3);

		return $elapsed < TIME_LIMIT;
	}

	function parentTable($tn, $lookupField) {
		$pts = get_parent_tables($tn); // [pt1 => [lookup1, lookup2, ...], pt2 => [...], ...]
		foreach($pts as $pt => $lookups) {
			if(in_array($lookupField, $lookups)) return $pt;
		}
		return false;
	}

	function cleanupSessionData($tn) {
		unset($_SESSION['fixRecordOwners_' . $tn]);
	}

	function storeRecordsInfoToSession($tn, $lookupField) {
		$processId = 'fixRecordOwners_' . $tn;
		$_SESSION[$processId] = $_SESSION[$processId] ?? [];
		$store =& $_SESSION[$processId];

		if(!empty($store['lookupOwnersDone']) && !empty($store['recordsDone'])) return; // already done

		// parent table name
		$ptn = parentTable($tn, $lookupField);

		$eo = ['silentErrors' => true];

		// init
		$store += [
			'lookupOwners' => [],
			'records' => [],
			'lookupOwnersDone' => false,
			'recordsDone' => false,
			'totalRecords' => INF, // assume infinite number of records initially
		];

		// get all distinct lookup values as keys and their corresponding record owners as values, [lookupValue => recordOwner]
		while(withinTimeLimit() && !$store['lookupOwnersDone']) {
			$start = count($store['lookupOwners']);
			$res = sql(" 
				SELECT `pkValue` AS `lookupValue`, `memberID` AS `recordOwner`
				FROM `membership_userrecords` 
				WHERE `tableName` = '{$ptn}'
				ORDER BY `pkValue`
				LIMIT {$start}, " . BATCH_READ_SIZE, $eo
			);

			// no more records to read?
			if(!db_num_rows($res)) {
				$store['lookupOwnersDone'] = true;
				break;
			}

			while($row = db_fetch_assoc($res)) {
				$store['lookupOwners'][$row['lookupValue']] = $row['recordOwner'];
			}
		}

		// get all records of the table [id => lookupValue]
		$pk = getPKFieldName($tn);
		while(withinTimeLimit() && !$store['recordsDone']) {
			$start = count($store['records']);
			$res = sql(" 
				SELECT `{$pk}` AS `id`, `{$lookupField}` AS `lookupValue` 
				FROM `{$tn}` 
				WHERE `{$lookupField}` IS NOT NULL
				ORDER BY `{$lookupField}`
				LIMIT {$start}, " . BATCH_READ_SIZE, $eo
			);

			// no more records to read?
			if(!db_num_rows($res)) {
				$store['recordsDone'] = true;
				$store['totalRecords'] = count($store['records']);
				break;
			}

			while($row = db_fetch_assoc($res)) {
				$store['records'][$row['id']] = $row['lookupValue'];
			}
		}
	}

	function updateRecordOwners($tn) {
		$store =& $_SESSION['fixRecordOwners_' . $tn];
		$affectedRecords = 0;

		while(withinTimeLimit() && count($store['records'])) {
			// get a single record [id => lookupValue] off the array
			$record = array_slice($store['records'], 0, 1, true); // [id => lookupValue]
			$id = array_keys($record)[0];
			$owner = $store['lookupOwners'][$record[$id]];

			$insertSet = prepare_sql_set([
				'tableName' => $tn,
				'pkValue' => $id,
				'memberID' => $owner,
				'groupID' => get_group_id($owner),
				'dateAdded' => time(),
			]);
			$updateSet = prepare_sql_set([
				'memberID' => $owner,
				'groupID' => get_group_id($owner),
			]);

			// insert or update record owner
			$eo = ['silentErrors' => true];
			sql("INSERT INTO `membership_userrecords` SET $insertSet ON DUPLICATE KEY UPDATE $updateSet", $eo);

			// get affected rows
			$affectedRecords += db_affected_rows();

			// remove record from array
			unset($store['records'][$id]);
		}

		return $affectedRecords;
	}

	function batchProcess() {
		global $Translation;

		$tn = Request::val('tn');
		$run = intval(Request::val('run', 0));
		if(!csrf_token(true)) json_response($Translation['invalid security token'], true);

		$lookupField = tableRecordOwner($tn);
		if(!$lookupField) json_response($Translation['record owner not configured for this table'], true);

		storeRecordsInfoToSession($tn, $lookupField);
		$affectedRecords = updateRecordOwners($tn);

		$store =& $_SESSION['fixRecordOwners_' . $tn];

		if($store['totalRecords'] === INF) {
			$progress = 0;
		} elseif(count($store['records']) === 0) {
			$progress = 100;
			cleanupSessionData($tn);
		} else {
			$progress = min(100, round((1 - count($store['records']) / $store['totalRecords']) * 100));
		}

		json_response([
			'tn' => $tn,
			'progress' => $progress,
			'totalRecords' => $store['totalRecords'],
			'affectedRecords' => $affectedRecords,
			'run' => $run,
		]);
	}
