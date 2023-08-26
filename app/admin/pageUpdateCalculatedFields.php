<?php
	require(__DIR__ . '/incCommon.php');
	$GLOBALS['page_title'] = $Translation['update calculated fields'];
	include(__DIR__ . '/incHeader.php');

	// get tables that contain calculated fields
	$tables = calculated_fields();

	// remove tables that have no calculated fields
	foreach($tables as $table => $fields) {
		if(!count($fields)) {
			unset($tables[$table]);
			continue;
		}

		// set the values for $tables array to the name of the PK field of each table
		$tables[$table] = getPKFieldName($table);
	}
?>

<script>
	const BATCH_SIZE = 50; // number of records to update in each request

	const TABLES = <?php echo json_encode($tables, JSON_FORCE_OBJECT); ?>;
	const CSRF_TOKEN = <?php echo json_encode(csrf_token(false, true)); ?>;

	$j(() => {
		// in case of no tables with calculated fields, show a message and exit
		if(!Object.keys(TABLES).length) {
			$j('#no-tables-found').removeClass('hidden');
			return;
		}

		$j('#start-update').removeClass('hidden');

		// set update state to an object with keys as table names and values as objects for tracking progress
		let updateState = {};
		for(let table in TABLES) {
			updateState[table] = {
				total: 0,
				done: 0,
				status: 'pending', // pending, started, success, error
			};
		}

		// loop through tables and create a progress bar for each inside #progress-container
		for(let table in TABLES) {
			$j('#progress-container').append(`
				<div class="table-progress" data-tablename="${table}">
					<div class="table-name"><b>${table}</b> <span class="label label-default"><span class="done-records">0</span> / <span class="total-records">0</span></span></div>
					<div class="progress">
						<div class="progress-bar" style="min-width: 2em; width: 0%;">0%</div>
					</div>
				</div>
			`);
		}

		const updateProgress = () => {
			// loop through updateState and update progress bars for each table
			for(let table in updateState) {
				let progress = updateState[table].done / updateState[table].total * 100;
				$j(`.table-progress[data-tablename="${table}"] .progress-bar`).css('width', `${progress}%`).text(`${progress.toFixed(0)}%`);
				$j(`.table-progress[data-tablename="${table}"] .done-records`).text(updateState[table].done);
				$j(`.table-progress[data-tablename="${table}"] .total-records`).text(updateState[table].total);

				// set the label class based on the status
				let labelClass = 'label-default'; // pending
				switch(updateState[table].status) {
					case 'started':
						labelClass = 'label-info';
						break;
					case 'success':
						labelClass = 'label-success';
						break;
					case 'error':
						labelClass = 'label-danger';
						break;
				}
				$j(`.table-progress[data-tablename="${table}"] .label`).removeClass('label-default label-info label-success label-danger').addClass(labelClass);
			}

			// disable #start-update button unless all tables are pending
			let allPending = Object.keys(updateState).every(table => updateState[table].status == 'pending');
			let allSuccess = Object.keys(updateState).every(table => updateState[table].status == 'success');

			$j('#start-update').prop('disabled', !allPending).toggleClass('disabled', !allPending);

			// toggle loop-rotate class to make the refresh icon rotate when updating
			$j('#start-update .glyphicon').toggleClass('loop-rotate', !allPending && !allSuccess);
		};

		// function to retrieve the record count of each table
		// this is done through a call to `../ajax-sql.php`
		// it returns a set of promises that resolve to the record count of each table
		const getRecordCounts = () => {
			let promises = [];
			for(let table in TABLES) {
				promises.push(new Promise((resolve, reject) => {
					$j.ajax({
						url: 'ajax-sql.php',
						method: 'POST',
						data: {
							sql: `select count(1) as \`count\` from \`${table}\``,
							csrf_token: CSRF_TOKEN,
						},
						dataType: 'json',
						success: resp => {
							if(!resp.error) {
								updateState[table].total = resp.data[0][0];
								resolve(resp.data[0][0]);
							} else {
								reject(resp.error);
							}
						},
						error: (xhr, status, error) => {
							reject(error);
						}
					});
				}));
			}

			return Promise.all(promises);
		};

		// get records count and update ui
		getRecordCounts().then(() => {
			updateProgress();
		}).catch(error => {
			console.error(error);
		});

		// function to loop through tables and update calculated fields
		// first, we look for the first table that has a started status
		// if none found, we look for the first table that has a pending status
		const nextTable = () => {
			for(let t in updateState) {
				if(updateState[t].status == 'started') {
					return t;
				}
			}

			for(let t in updateState) {
				if(updateState[t].status == 'pending') {
					return t;
				}
			}
		};

		// get the PK values of the next BATCH_SIZE records
		// by calling `ajax-sql.php` with a SELECT query that has a LIMIT clause
		const getNextPKs = (table) => {
			const pk = TABLES[table];
			return new Promise((resolve, reject) => {
				$j.ajax({
					url: 'ajax-sql.php',
					method: 'POST',
					data: {
						sql: `select \`${pk}\` from \`${table}\` limit ${updateState[table].done}, ${BATCH_SIZE}`,
						csrf_token: CSRF_TOKEN,
					},
					dataType: 'json',
					success: resp => {
						if(!resp.error) {
							resolve(resp.data.map(row => row[0]));
						} else {
							console.error({ table, error: resp.error });
							reject(resp.error);
						}
					},
					error: (xhr, status, error) => {
						console.error({ table, error });
						reject(error);
					}
				});
			});
		};

		// for the found table, we retrieve the PK values of the next BATCH_SIZE records
		// by calling `ajax-sql.php` with a SELECT query that has a LIMIT clause
		// then, we call `../ajax-update-calculated-fields.php` with the PK values
		// then we increment the done count for the table and call updateProgress()
		// then we call this function again to process the next batch
		const updateCalculatedFields = () => {
			let table = nextTable();
			if(!table) {
				// no more tables to update
				console.log('No more tables to update');
				updateProgress();
				return;
			}

			// get the PK values of the next BATCH_SIZE records
			getNextPKs(table).then(pks => {
				// call `../ajax-update-calculated-fields.php` with the PK values
				$j.ajax({
					url: '../ajax-update-calculated-fields.php',
					method: 'POST',
					data: {
						table,
						id: pks,
						csrf_token: CSRF_TOKEN,
					},
					dataType: 'json',
					success: resp => {
						if(!resp.error) {
							updateState[table].done += pks.length;
							updateState[table].status = 'started';
							if(updateState[table].done >= updateState[table].total) {
								updateState[table].status = 'success';
							}

							updateProgress();
							updateCalculatedFields();
						} else {
							console.error({ table, error: resp.error });
							updateState[table].status = 'error';
							updateProgress();
							updateCalculatedFields();
						}
					},
					error: (xhr, status, error) => {
						console.error({ table, error });
						updateState[table].status = 'error';
						updateProgress();
						updateCalculatedFields();
					}
				});
			}).catch(error => {
				console.error({ table, error });
				updateState[table].status = 'error';
				updateProgress();
				updateCalculatedFields();
			});
		};

		// start updating calculated fields when the button is clicked
		$j('#start-update').click(() => {
			updateCalculatedFields();
		});
	})

</script>

<h1 class="page-header"><?php echo $Translation['update calculated fields']; ?></h1>

<p><?php echo $Translation['description for updating calculated fields']; ?></p>

<p><?php echo $Translation['cli tool for updating calculated fields']; ?></p>
<pre>php <?php echo realpath(__DIR__ . '/../cli-update-calculated-fields.php'); ?> -h</pre>

<div id="no-tables-found" class="alert alert-danger hidden">
	<?php echo $Translation['no tables with calculated fields']; ?>
</div>

<button type="button" class="btn btn-primary btn-lg vspacer-lg hidden" id="start-update">
	<i class="glyphicon glyphicon-refresh"></i>
	<?php echo $Translation['start updating']; ?>
</button>

<div id="progress-container"></div>

<style>
	#start-update {
		width: 20em;
		margin: 2em auto;
		max-width: 100%;
		display: block;
	}
</style>

<?php include(__DIR__ . '/incFooter.php');
