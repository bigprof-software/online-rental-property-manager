<?php

	/*
	 * UI for CSVImport class
	 * 
	 * Usage:
	 * 		$ui = new CSVImportUI($transformFunctions, $filterFunctions, $uri, $reuest); // use $_REQUEST by default if no $request passed
	 * 
	 * after uploading a CSV file, user can select a table to import to
	 * only tables that the user has insert/edit permission for are listed
	 * if user has no insert/edit permissions, an error message is shown instead
	 * 
	 * - the CSV file is parsed to retrieve the 1st 10 non-empty lines, using some assumptions > ajaxCSVPreview()
	 * - CSV data is previewed in the page, where user can map columns to table fields
	 *   and adjust import settings ... after some seconds of no activity (or after clicking the Preview button),
	 *   controls are disabled and a request made to ajaxCSVPreview, sending the new settings
	 *   after a response is obtained, the new preview is displayed, and controls re-enabled.
	 *   
	 * if user is satisfied with results, he clicks 'Start import'
	 * this displays a progress bar showing import progress.
	 * also, a perma link for the import job that the user can use later to continue import if it's
	 * interrupted for any reason.
	 * 
	 * when done, an 'Import job finished' confirmation shows up, with option to go to the table
	 * containing the imported records, or to start a new import job.
	 * 
	 */

	class CSVImportUI {
		const CSV_DIR = 'import-csv'; // relative to appDir

		protected	$T = [], // translation strings
					$request = [], // REQUEST (or mocked request)
					$uri = '',
					$appDir = '',
					$csvFile = '',
					$routes = [
						'import-ui' => 'viewImportUI', // default if no params provided
						'upload-csv' => 'viewUploadCSV',
						'preview-ui' => 'viewPreviewUI',
						'ajax-csv-preview' => 'ajaxCSVPreview',
						'ajax-save-job-config' => 'ajaxSaveJobConfig',
						'job-progress-ui' => 'viewJobProgressUI', // default if a jobId param provided
						'ajax-job-progress' => 'ajaxJobProgress',
					];

		public function __construct($transformFunctions, $filterFunctions, $uri = '', $request = false) {
			global $Translation;

			if($uri === '') $uri = htmlspecialchars($_SERVER['PHP_SELF']);
			if($request === false) $request = $_REQUEST;

			$this->transformFunctions = $transformFunctions;
			$this->filterFunctions = $filterFunctions;
			$this->uri = $uri;
			$this->T = $Translation;
			$this->validateRequest($request);

			$this->exec();
		}

		private function validateRequest($request) {
			// limit request to these and remove anything else
			$accept = [
				'jobId', 'action', 'table', 'fieldSeparator', 'fieldWrapper', 'updateExistingRecords', 'fastMode',
				'csvHasHeaderRow', 'csvMap',
			];

			$tempRequest = [];
			foreach ($request as $key => $value) {
				if(!in_array($key, $accept)) continue;
				$tempRequest[$key] = $value;
			}

			// validate tempRequest
			
			// action must be one of route keys, else set to 1st key (or to 'job-progress-ui' if a jobId is provided)
			if(!in_array($tempRequest['action'], array_keys($this->routes)))
				$tempRequest['action'] = empty($tempRequest['jobId']) ? key($this->routes) : 'job-progress-ui';

			// handle tab fieldSeparator
			if(in_array(strtolower(trim($tempRequest['fieldSeparator'])), ['\t', 'tab']))
				$tempRequest['fieldSeparator'] = "\t";

			if(empty($tempRequest['fieldSeparator'])) $tempRequest['fieldSeparator'] = ',';

			// updateExistingRecords must be boolean
			$tempRequest['updateExistingRecords'] = ($tempRequest['updateExistingRecords'] == 'true');
			
			// fastMode must be boolean, and true is allowed only for super admin
			$tempRequest['fastMode'] = ($tempRequest['fastMode'] == 'true' && getLoggedAdmin());

			// csvHasHeaderRow must be boolean
			$tempRequest['csvHasHeaderRow'] = (!empty($tempRequest['csvHasHeaderRow']) && strtolower($tempRequest['csvHasHeaderRow']) != 'false');

			$this->request = $tempRequest;

			// if user has no access to specified table, this would exit with error
			$this->checkTable();
		}

		private function exec() {
			echo call_user_func_array([$this, $this->routes[$this->request['action']]], []);
		}

		/**
		 * checks if user has access to requested table. if not, quits with error.
		 * sets $this->table to specified table
		 */
		private function checkTable() {
			// is there a table specified?
			$accessibleTables = $this->accessibleTables();
			if(!count($accessibleTables)) die($this->viewNoAccess());

			$this->table = $this->request['table'];
			if(!in_array($this->table, $accessibleTables)) $this->table = $accessibleTables[0];
		}

		/**
		 * @return array of tables that the current user can insert to and/or edit
		 */
		private function accessibleTables() {
			return array_values(array_unique(array_merge(
				$this->insertableTables(), $this->editableTables()
			)));
		}

		/**
		 * @return array of tables that the current user can insert to
		 */
		private function insertableTables() {
			if(isset($this->_tablesInsert)) return $this->_tablesInsert;

			$tables = array_keys(getTableList());
			if(!$tables || !userCanImport()) return [];

			$this->_tablesInsert = array_values(array_filter($tables, function($tn) {
				return (getTablePermissions($tn)['insert'] == 1);
			}));

			return $this->_tablesInsert;
		}

		/**
		 * @return array of tables where the current user can edit records
		 */
		private function editableTables() {
			if(isset($this->_tablesEdit)) return $this->_tablesEdit;

			$tables = array_keys(getTableList());
			if(!$tables || !userCanImport()) return [];

			$this->_tablesEdit = array_values(array_filter($tables, function($tn) {
				return (getTablePermissions($tn)['edit'] > 0);
			}));

			return $this->_tablesEdit;
		}

		/**
		 * @return  string  full page HTML after translating and wrapping given $html inside header and footer
		 */
		private function html($html, $replace = []) {
			global $Translation;
			ob_start();
			@include_once(APP_DIR . '/header.php');

			// Apply translation to %%xxx%% placeholders
			echo preg_replace_callback('/%%(.*?)%%/', function($m) {
				return isset($this->T[$m[1]]) ? $this->T[$m[1]] : $m[1];
			}, $html);

			@include_once(APP_DIR . '/footer.php');

			// perform replacements if provided
			if(count($replace))
				return str_replace(array_keys($replace), array_values($replace), ob_get_clean());

			return ob_get_clean();
		}

		private function fixData($data) {
			if(@json_encode($data)) return $data;

			foreach ($data as $key => $arr) {
				if(is_array($arr)) {
					$data[$key] = $this->fixData($arr);
					continue;
				}

				$data[$key] = to_utf8($arr);
			}

			return $data;
		}

		/**
		 * @return  string  JSONified data after sending json header and ensuring data is UTF-8 encoded.
		 */
		private function json($data, $error = null, $errorStatus = null) {
			@header('Content-type: application/json');
			if($error !== null && $errorStatus !== null) @header($_SERVER['SERVER_PROTOCOL'] . ' ' . $errorStatus);

			if($error) $error = to_utf8($error);

			$ret = ['data' => $this->fixData($data), 'error' => $error];

			return json_encode($ret, JSON_PARTIAL_OUTPUT_ON_ERROR);
		}

		/**
		 * @return     string  HTML code for a full-page 'access denied' error page. sets status header to 403
		 */
		private function viewNoAccess() {
			if(is_ajax())
				return $this->json(null, $this->T['tableAccessDenied'], '403 Forbidden');

			return $this->html('<div class="alert alert-danger">%%tableAccessDenied%%</div>');
		}

		private function ajaxJobProgress() { // action=ajax-job-progress
			if(!$this->getJob())
				return $this->json(null, $this->T[$this->error], '404 Not Found');

			// set table correctly and make sure it's valid
			if(!$this->table = $this->importJob->getJobInfo()['table'])
				return $this->json(null, $this->T[$this->importJob->error], '404 Not Found');
			if(!in_array($this->table, $this->accessibleTables()))
				return $this->json(null, 'tableAccessDenied', '403 Forbidden');

			// set callback and filter functions
			$this->importJob->configure([
				'rowTransformCallback' => $this->transformFunctions[$this->table],
				'rowFilterCallback' => $this->filterFunctions[$this->table],
			], false);

			$this->importJob->importBatch();

			if($this->importJob->error)
				return $this->json(null, $this->T[$this->importJob->error], '500 Internal Server Error');
			
			$data = $this->importJob->getJobInfo();
			
			$data['logs'] = $this->importJob->logs;
			
			return $this->json($data, null);
		}

		private function viewJobProgressUI() { // action=job-progress-ui
			if(!$this->getJob())
				return $this->html("<div class=\"alert alert-danger\">%%{$this->error}%%</div>");

			ob_start(); ?>

				<div class="page-header initial-ui"><h1>%%importing CSV data%%</h1></div>

				<div class="alert alert-info fast-mode-on">
					<i class="glyphicon glyphicon-plane rotate90" style="font-size: 2.5rem; margin: auto 1rem;"></i>
					<span style="vertical-align: super;">%%fast import mode%%</span>
				</div>

				<dl class="dl-horizontal" style="max-width: 40rem; overflow-x: visible;">
					<dt>%%Table%%</dt>            <dd id="table"></dd>
					<dt>%%total records%%</dt>    <dd id="totalRecords" class="text-right"></dd>
					<dt>%%imported records%%</dt>
						<dd>
							<dl class="dl-horizontal" style="margin-left: -6rem;">
								<dt></dt>                                  <dd class="text-right" id="importedCount"></dd>
								<dt class="fast-mode-off">%%new%%</dt>     <dd class="text-right fast-mode-off" id="importedCountInsert"></dd>
								<dt class="fast-mode-off">%%updated%%</dt> <dd class="text-right fast-mode-off" id="importedCountUpdate"></dd>
							</dl>
						</dd>
					<dt class="fast-mode-off">%%skipped%%</dt> <dd id="importedCountSkip" class="text-right fast-mode-off"></dd>
					<dt>%%remaining%%</dt>                     <dd id="remaining" class="text-right"></dd>
					<dt>%%ETA%%</dt>                           <dd id="eta"></dd>
				</dl>

				<dl class="dl-horizontal">					
					<!-- UI for pausing/resuming import job -->
					<dt></dt>
					<dd>
						<div class="btn-group" id="progress-controls">
							<button type="button" class="btn btn-default btn-lg" id="progress-pauser">
								<i class="glyphicon glyphicon-pause text-danger"></i>
								<span class="text-danger">%%pause%%</span>
							</button>
							<button type="button" class="btn btn-default btn-lg active" id="progress-resumer">
								<i class="glyphicon glyphicon-play text-success"></i>
								<span class="text-success">%%resume%%</span>
							</button>
						</div>
						<div id="paused-actions" class="well well-sm hidden vspacer-lg text-center" style="max-width: 60rem;">
							<button type="button" class="btn btn-default hspacer-sm table-link">
								<i class="glyphicon glyphicon-th"></i> <span class="table-name"></span>
							</button>
							<a href="<?php echo $this->uri; ?>" class="btn btn-default hspacer-lg">
								<i class="glyphicon glyphicon-remove text-danger"></i>
								<span class="text-danger">%%cancel and back to csv upload%%</span>
							</a>
						</div>
						<div class="clearfix"></div>
					</dd>
					<!-- /UI -->
				</dl>


				<div class="progress" style="min-height: 4rem;">
					<div class="progress-bar progress-bar-striped active" style="min-width: 2em; width: 0; font-size: 2rem; padding: 1rem;" id="job-progress"></div>
				</div>

				<button type="button" class="btn btn-default btn-sm vspacer-lg" data-toggle="collapse" href=".logs"><i class="glyphicon glyphicon-eye-open"></i> %%toggle import logs%%</button>
				<div class="logs collapse"></div>

				<div class="alert alert-danger hidden" id="import-error-alert">
					%%error:%% <span id="import-error"></span>

					<div class="vspacer-lg">
						<a href="<?php echo $this->uri; ?>" class="btn btn-default btn-sm">%%cancel and back to csv upload%%</a>
					</div>
				</div>

				<div class="alert alert-success h2 text-center hidden" id="import-finished">
					<i class="glyphicon glyphicon-ok"></i> %%import finished%%
					<div>
						<button type="button" class="btn btn-link alert-link hspacer-sm import-link"><i class="glyphicon glyphicon-chevron-left"></i> %%import another csv file%%</button>
						<button type="button" class="btn btn-default hspacer-sm table-link">
							<i class="glyphicon glyphicon-th"></i> <span class="table-name"></span>
						</button>
					</div>

					<a class="alert-link h6 super-admin-only hidden" href="admin/pageAssignOwners.php">%%assign owner for n records in table%%</a>
				</div>

				<div class="well permalink-alert hidden">
					<i class="glyphicon glyphicon-hourglass pull-left h1" style="font-size: 4rem; line-height: 0; margin: 3.5rem .5rem 0;"></i> 
					%%import job taking long keep page open permalink%%
					<div class="h5" style="margin: 1rem 5rem;"><a class="permalink"><i class="glyphicon glyphicon-link"></i> %%permalink%%</a></div>
					<div class="clearfix"></div>
				</div>

				<style>
					@keyframes dimmer {
						0%   { opacity: 1.00; }
						50%  { opacity: 0.30; }
						100% { opacity: 0.99; }
						101% { opacity: 0.99; }
					}

					#progress-resumer:not(.active) > * {
						animation: dimmer linear 2s;
						animation-iteration-count: infinite;
						transform-origin: 50% 50%;
					}

					#job-progress.progress-bar-striped:not(.active) {
						animation: dimmer linear 2s;
						animation-iteration-count: infinite;
						transform-origin: 50% 50%;
					}

					.logs {
						max-height: 20rem;
						overflow-y: auto;
						margin: 1rem 0;
						padding: 1rem;
						font-family: monospace;
					}
				</style>

				<script>
					// initial job info
					var job = <?php echo json_encode($this->importJob->getJobInfo()); ?>;
					var progressStatus = 'resumed'; // other possible value: 'paused', @see #progress-controls.click

					$j(function() {
						var startTime = new Date(), startProgress = null;
						var updateUI = function() {
								$j('#table').html(job.table);
								
								/* initial UI adjustments */
								if($j('.initial-ui').length) {
									$j('.initial-ui').removeClass('initial-ui'); // to prevent multiple redraws
									
									/* update table name and icon if not already updated on finish button */
									$j('.table-name').html(job.table);
									var icon = $j('nav a[href^="' + job.table + '_view.php"] > img').attr('src');
									if(icon)
										$j('.table-name')
											.html($j('nav a[href^="' + job.table + '_view.php"]').text().trim())
											.parent().find('.glyphicon-th')
												.replaceWith('<img src="' + icon + '" style="max-height: 2em;">');

									/* update permalink */
									$j('a.permalink').attr('href', location.href.replace(/\?.*/, '') + '?jobId=' + job.jobId);

									// hiddens in fast mode
									$j('.fast-mode-off').toggleClass('hidden', job.fastMode);
									$j('.fast-mode-on').toggleClass('hidden', !job.fastMode);

									// hiddens in replace mode
									$j('.replace-off').toggleClass('hidden', job.updateExistingRecords);
									$j('.replace-on').toggleClass('hidden', !job.updateExistingRecords);

									// hiddens for non super-admins
									var isSuperAdmin = ($j('nav a[href="admin/pageHome.php"]').length > 0);
									if(!isSuperAdmin)
										$j('.super-admin-only').remove();
									else
										$j('.super-admin-only').removeClass('hidden');
								}

								$j('#totalRecords').html(job.totalRecords.toLocaleString());
								$j('#importedCount').html(job.importedCount.toLocaleString());
								$j('#importedCountInsert').html(job.importedCountInsert.toLocaleString());
								$j('#importedCountUpdate').html(job.importedCountUpdate.toLocaleString());
								$j('#importedCountSkip').html(job.importedCountSkip.toLocaleString());
								$j('#remaining').html((job.totalRecords - job.importedCount - job.importedCountSkip).toLocaleString());

								var progressPercent = Math.round(job.progress * 100) + '%';
								$j('#job-progress').css({ width: progressPercent }).html(progressPercent);
								document.title = document.title.replace(/\|.*/, '| [' + progressPercent + '] ' + $j('.page-header').text());

								if(startProgress !== null) {
									var elapsed = new Date() - startTime, // in milliseconds
										deltaProgress = job.progress - startProgress,
										/* estimated time ahead in milliseconds */
										ETA = (deltaProgress ? elapsed * (1 - startProgress) / deltaProgress - elapsed : 0);

									/* if ETA > 30 sec, show permalink */
									$j('.permalink-alert').toggleClass('hidden', ETA < 30000);

									$j('#eta').html(ETA ? moment.duration(ETA).humanize() : '-');
								}

								if(job.logs != undefined && job.logs.length) {
									$j(job.logs.reduce(function(prev, line) {
										var dangerClass = '';
										if(
											line.indexOf(' skipped') > -1 ||
											line.indexOf(' no owner') > -1 ||
											line.indexOf(' failed') > -1
										) dangerClass = ' class="text-danger"';

										return prev + '<div' + dangerClass + '>' + line + '</div>';
									}, '')).appendTo('.logs');

									// scroll to bottom
									var logger = $j('.logs').get(0);
									logger.scrollTop = Math.max(logger.scrollHeight, logger.clientHeight) - logger.clientHeight;
								}
	
								if(job.progress < 1) return;

								// import finished .. show next steps
								$j('#eta').html('-');
								$j('.permalink-alert, #progress-controls').addClass('hidden');
								$j('#job-progress').removeClass('progress-bar-striped active');

								// for super-admin, show count of records without owners
								showRecordsWithoutOwners();

								setTimeout(function() {
									$j('#import-finished').removeClass('hidden');
								}, 600);

							},

							showRecordsWithoutOwners = function() {
								if(!$j('.super-admin-only').length) return;

								var assignLink = $j('a.super-admin-only[href="admin/pageAssignOwners.php"]');

								// retrieve count of records without owners via admin/pageAssignOwners.php
								$j.ajax({
									url: 'admin/pageAssignOwners.php',
									success: function(resp) {
										var currentTableCaption = $j('.table-name').eq(0).text().trim();
										var tablesWithOrphanRecords = {};
										
										// parse response HTML to get count of orphan records in each table
										$j(resp).find('table > tbody > tr:not(:last-child) > td:first-child').each(function() {
											tablesWithOrphanRecords[$j(this).text().trim()] = $j(this).next().text().trim();
										});

										// if current table has no orphans, remove link and abort
										if(tablesWithOrphanRecords[currentTableCaption] === undefined) {
											assignLink.remove();
											return;
										}

										assignLink.html(assignLink.text().replace(/\[n\]/, tablesWithOrphanRecords[currentTableCaption]));
									},
									error: function() {
										assignLink.remove();
									}
								})
							},

							getJobProgress = function() {
								if(job.progress == 1) return;
								if(progressStatus == 'paused') return setTimeout(getJobProgress, 300);

								$j.ajax({
									type: 'POST',
									data: {
										jobId: job.jobId,
										action: 'ajax-job-progress'
									},
									success: function(resp) {
										if(!resp.data) {
											$j('#import-error-alert').removeClass('hidden');
											$j('#import-error').html(resp.error ? resp.error : '%%Connection error%%');
											return;
										}

										// update job with new data
										job = resp.data;
										startProgress === null ? startProgress = job.progress : 0;
										updateUI();

										getJobProgress();
									},
									error: function(xhr) {
										$j('#import-error-alert').removeClass('hidden');
										$j('#import-error').html(xhr.responseJSON.error ? xhr.responseJSON.error : '%%Connection error%%');
									}
								})
							},

							endOfVar;

						$j('#import-finished').on('click', 'button', function() {
							if($j(this).hasClass('import-link')) {
								location.assign(location.href.replace(/\?.*/, ''));
								return;
							}
							
							// .table-link, get table link from nav menu, or make a guess
							var link = $j('nav a[href^="' + job.table + '_view.php"]').attr('href');
							location.assign(link ? link : job.table + '_view.php');
						})

						$j('#progress-controls').on('click', '.btn:not(.active)', function() {
							var btnId = $j(this).attr('id');

							$j('#progress-resumer, #progress-pauser, #job-progress').toggleClass('active');
							$j('#paused-actions').toggleClass('hidden', btnId == 'progress-resumer');
							progressStatus = (btnId == 'progress-resumer' ? 'resumed' : 'paused');
						})

						updateUI();
						getJobProgress();
					})
				</script>

			<?php
			return $this->html(ob_get_clean());			
		}

		private function viewImportUI() { // action=import-ui
			// what's the max upload size allowed?
			$maxSize = intval(min(
				toBytes(@ini_get('upload_max_filesize')),
				toBytes(@ini_get('post_max_size'))
			));

			// try to guess some safe maxSize, we'll try our luck with 100KB,
			// which should be safe enough to work on most servers!
			if(!$maxSize) $maxSize = 102400;

			ob_start();
			?>
			<div class="page-header"><h1>
				%%import csv file%%
			</h1></div>

			<div style="height: 3vh;"></div>
			<div class="text-center">
				<img src="<?php echo PREPEND_PATH; ?>resources/images/table_go.png">
				<img src="<?php echo PREPEND_PATH; ?>resources/images/database.png">
			</div>
			<div style="height: 3vh;"></div>

			<form enctype="multipart/form-data" method="POST" action="<?php echo $this->uri; ?>">
				<input type="hidden" name="action" value="upload-csv">
				<div class="form-group">
					<label for="csvFile" class="control-label">%%choose csv upload%%</label>
					<input type="file" class="form-control" id="csvFile" name="csvFile" accept=".csv,.txt" autofocus>

					<div class="vspacer-md alert alert-danger hidden" id="max-size-error">%%file too large%%</div>
					
					<div class="vspacer-md alert alert-info hidden" id="auto-upload">
						<i class="glyphicon glyphicon-hourglass loop-rotate"></i>
						<span id="seconds-remaining">%%uploading in x seconds%%</span>
						<button type="submit" class="btn btn-primary hspacer-lg" id="start-upload"><i class="glyphicon glyphicon-upload"></i> %%start upload%%</button>
						<button type="button" class="btn btn-link alert-link"><i class="glyphicon glyphicon-remove"></i> %%Cancel%%</button>
					</div>
				</div>
			</form>

			<script>
				$j(function() {
					var autoUploadTimer,

						maxSize = <?php echo intval($maxSize); ?>,
					
						startUpload = function() {
							$j('#auto-upload').removeClass('hidden');
							$j('#start-upload').focus();
							
							autoUploadTimer = setInterval(function() {
								var remaining = parseInt($j('#seconds').text());
								$j('#seconds').html(isNaN(remaining) ? '7' : --remaining);
								if(remaining !== 0) return;

								clearInterval(autoUploadTimer);
								$j('#start-upload').click();
							}, 1000);
						},

						stopUpload = function() {
							$j('#auto-upload').addClass('hidden');
							clearInterval(autoUploadTimer);
							$j('#seconds').html('');
						},

					endOfVar;

					// display max upload size in error div
					$j('MaxSize').replaceWith('<b>' + Math.round(maxSize / 1024) + 'KB</b>');

					// add seconds counter span
					$j('#seconds-remaining').html($j('#seconds-remaining').text().replace('[SECONDS]', '<span id="seconds"></span>'));

					// automatically start uploading if file ok
					$j('#csvFile').on('change', function(e) {
						$j('#max-size-error').addClass('hidden');

						if(!e.target.files.length) return;
						var fileSize = e.target.files[0].size;
						if(fileSize > maxSize) {
							$j('#max-size-error').removeClass('hidden');
							return;
						}

						startUpload();
					})

					// if user manually clicks #start-upload, hide timer
					$j('#start-upload').on('click', function(e) {
						e.preventDefault();
						$j('#seconds-remaining').html(AppGini.Translate._map['please wait']);
						clearInterval(autoUploadTimer);
						$j(this).prop('disabled', true).parents('form').submit();
					})

					// cancel auto-upload on request
					$j('#auto-upload .alert-link').on('click', function() {
						$j('#csvFile').val('');
						stopUpload();
					})
				})
			</script>

			<?php

			return $this->html(ob_get_clean());
		}

		private function viewUploadCSV() {  // action=upload-csv
			if(!$csvFile = $this->handleCSVUpload())
				return $this->html("<div class=\"alert alert-danger\">%%{$this->error}%%</div>");

			$this->importJob = new CSVImport([ 'csvFile' => $csvFile ]);
			if(!$this->importJob->getJobId())
				return $this->html("<div class=\"alert alert-danger\">%%{$this->importJob->error}%%</div>");

			ob_start(); ?>

			<form method="POST" action="<?php echo $this->uri; ?>">
				<input type="hidden" name="jobId" value="<?php echo $this->importJob->getJobId(); ?>">
				<input type="hidden" name="action" value="preview-ui">
				<div class="alert alert-success text-center" style="margin-top: 3em;">
					%%please wait%%
					<div class="text-right" id="continue-if-slow">%%click continue if slow%%</div>
				</div>
			</form>
			<script>
				$j(function() {
					// add continue link to hint
					var continueDiv = $j('#continue-if-slow'), continueText = continueDiv.text();
					continueDiv.empty().html(continueText.replace('[a]', '<button type="submit" class="btn btn-link">').replace('[/a]', '</button>'));

					$j('form').submit();
				})
			</script>
			<style>
				#continue-if-slow .btn-link { text-decoration: underline; padding: 0; margin: 0; }
			</style>

			<?php
			return $this->html(ob_get_clean());
		}

		private function getJob() {
			if(!$this->request['jobId']) {
				$this->error = 'Invalid import job';
				return false;
			}

			$this->importJob = new CSVImport(['jobId' => $this->request['jobId']]);
			if(!$this->jobId = $this->importJob->getJobId()) {
				$this->error = $this->importJob->error;
				return false;
			}

			return true;
		}

		private function viewPreviewUI() { // action=preview-ui
			if(!$this->getJob())
				return $this->html("<div class=\"alert alert-danger\">%%{$this->error}%%<br>%%tip check csv for errors%%</div>");

			$editableTables = $this->editableTables();
			$insertableTables = $this->insertableTables();

			// get list of AppGini fields in each accessible table
			$tables = $this->accessibleTables();
			$fields = $tablePK = [];
			foreach ($tables as $tn) {
				$fields[$tn] = array_keys(get_table_fields($tn));
				$tablePK[$tn] = getPKFieldName($tn);
			}
			ob_start(); ?>

			<div class="page-header"><h1>
				%%preview and confirm CSV data%%
				<a href="<?php echo $this->uri; ?>" class="btn btn-default btn-sm hspacer-lg">
					%%cancel and back to csv upload%%
				</a>
			</h1></div>

			<form id="csv-import-form">
				<div class="row">
					<div class="[grid-classes]">
						<div class="form-group">
							<label for="table" class="control-label">%%Table%%</label>
							<div class="input-group">
								<?php echo str_replace(
									'<select ', 
									'<select class="form-control" ', 
									htmlSelect('table', $tables, $tables, $this->table)
								); ?>
								<span class="input-group-btn">
									<button class="btn btn-default" type="button" id="auto-detect-fields" title="%%auto-detect csv columns%%">
										<i class="glyphicon glyphicon-flash"></i>
									</button>
								</span>
							</div>
						</div>
					</div>

					<div class="[grid-classes-narrow]">
						<div class="form-group">
							<label for="fieldSeparator" class="control-label">%%field separator%%</label>
							<div class="form-control-static" id="fieldSeparator" contenteditable>,</div>
							<span class="help-block">%%default comma%%<br>%%to use tab%%</span>
						</div>
					</div>

					<div class="[grid-classes-narrow]">
						<div class="form-group">
							<label for="fieldWrapper" class="control-label">%%field qualifier%%</label>
							<div class="form-control-static" id="fieldWrapper" contenteditable>"</div>
							<span class="help-block">%%default double-quote%%</span>
						</div>
					</div>

					<div class="[grid-classes]">
						<div class="checkbox">
							<label>
								<input type="checkbox" id="csvHasHeaderRow" value="true">
								%%first line field names%%
							</label>
						</div>

						<div class="checkbox">
							<label>
								<input type="checkbox" id="updateExistingRecords" value="true">
								%%update table records%%
							</label>
						</div>
						<div class="text-warning vspacer-lg hidden" id="missing-pk-field-in-csv">%%no update missing pk field in csv%%</div>

						<!-- super admin only -->
						<div class="checkbox hidden">
							<label>
								<input type="checkbox" id="fastMode" value="true">
								%%fast import mode%%
							</label>
						</div>
					</div>
				</div>

				<div style="margin-left:auto; margin-right:0; width: 100%; max-width: 45em;">
					<button class="btn btn-primary btn-lg btn-block" type="button" data-toggle="collapse" data-target="#confirm-import-div" id="start-import">
						%%import CSV%%
					</button>
					<div class="collapse" id="confirm-import-div">
						<div class="well text-center">
							<div class="btn-group">
								<button type="button" class="btn btn-danger btn-lg" id="confirm-import">
									<i class="glyphicon glyphicon-ok"></i> %%confirm importing csv%%
								</button>
								<button type="button" class="btn btn-default btn-lg" data-toggle="collapse" data-target="#confirm-import-div" title="%%Cancel%%">
									&times;
								</button>
							</div>
						</div>
					</div>

					<div class="vspacer-md text-danger text-center" id="import-error"></div>
				</div>

				<div style="margin-top: 2em;">
					<button class="btn btn-sm btn-default" id="update-csv-preview" type="button" style="width: 8em;">
						<i class="glyphicon glyphicon-refresh"></i> 
					</button>
				</div>
				<div id="csv-preview" class="table-responsive vspacer-sm"></div>

				<div id="csvlint-hint" class="text-info hidden">%%tip check csv for errors%%</div>

				<div class="progress moving" id="data-loader">
					<div class="progress-bar progress-bar-default progress-bar-striped active" style="min-width: 2em; width: 0%"></div>
				</div>
			</form>

			<style>
				.form-control-static {
					font-size: 3em;
					line-height: 1em;
					font-weight: bold;
					color: #777;
					padding: .1em;
					width: 3em;
					border-radius: 3px;
					text-align: center;
				}
				.form-control-static:focus {
					box-shadow: inset 0 0 2px 1px;
					text-align: left;
				}
				#table {
					font-size: 1.33em;
					font-weight: bold;
				}
				#table option {
					font-size: initial;
					font-weight: initial;
				}

				@keyframes loader_progress {
					from { width: 0; }
					to { width: 100%; }
				}

				.progress.moving > .progress-bar {
					animation: loader_progress linear 10s;
					animation-iteration-count: infinite;
				}
			</style>

			<script>
				var lang = {
						connectionError: "%%Connection error%%",
						noDataToImport: "%%nothing to import%%",
						repeatedFields: "%%multiple columns mapped to same field%%",
						noInsertUpdateOnly: "%%no insert update only%%"
					},
					jobId = <?php echo json_encode($this->jobId); ?>,
					fields = <?php echo json_encode($fields); ?>,
					tablePK = <?php echo json_encode($tablePK); ?>,
					editableTables = <?php echo json_encode($editableTables); ?>,
					insertableTables = <?php echo json_encode($insertableTables); ?>,
					timerForPreviewUpdate,
					previewRequestInProgress = false,

					logging = false, // set to true to enable logging to console

					// select text colors
					colorNormal = '#555', colorSkip = '#A94442',

					state = {
						table: '',
						tableBestMatch: '',
						error: '',
						csvMap: [],
						newMap: true,
						data: [], // would become a 2D array
						csvHasHeaderRow: false,
						updateExistingRecords: false,
						fastMode: false
					},

					htmlSpecialChars = function(str) {
						return str.replace(/[&<>"']/g, function(m) {
							var map = {
								'&': '&amp;',
								'<': '&lt;',
								'>': '&gt;',
								'"': '&quot;',
								"'": '&#039;'
							};
							return map[m];
						});
					},

					log = function(msg) {
						if(!logging) return;
						console.log(msg);
					},

					resetMap = function() {
						log('resetMap()');
						state.csvMap = [];
						state.newMap = true;
						state.csvHasHeaderRow = false;
					},

					resetSelects = function() {
						log('resetSelects()');

						var table = state.table;
						if(!table.length || fields[table] === undefined) return;
						if(state.data[0] === undefined) return;

						var numDataCols = state.data[0].length;
						if(!numDataCols) return;

						var thead = $j('#csv-preview > table > thead');
						if(!thead.length) return;

						var selectsRow = function(tn, num) {
							log('selectsRow(' + tn + ',' + num + ')');

							var select = $j('<select></select>');
							select.css({ width: '100%', 'min-width': '4em' });

							$j('<option value="">%%skip column%%</option>').css({ color: colorSkip }).appendTo(select);
							for(var fi = 0; fi < fields[tn].length; fi++)
								$j('<option value="' + fields[tn][fi] + '">' + fields[tn][fi] + '</option>').appendTo(select);

							var th = select.wrap('<th></th>').parent();

							var row = $j('<tr class="selects-row"></tr>');
							row.data('table', tn);

							for(var c = 0; c < num; c++)
								th.clone().addClass('col-' + c).appendTo(row);

							return row;
						}

						var numCells = $j('.selects-row > th').length;

						// if selects exist and populated with fields of current table and have same num as data columns, nothing to do so return
						if($j('.selects-row').data('table') == table && numCells == numDataCols) return;

						// if no existing selects in thead, or their count doesn't match numDataCols, redraw entire thead
						if(numCells != numDataCols) {
							thead.empty();

							// add an empty tr to thead, then selects row, then another empty row
							$j('<tr><th class="active" colspan="' + numDataCols + '"></th></tr>').appendTo(thead);
							selectsRow(table, numDataCols).appendTo(thead);
							$j('<tr><th class="active" colspan="' + numDataCols + '"></th></tr>').appendTo(thead);

							return;
						}

						// if selects populated with fields of another table, remove and redraw only the selects row
						$j('.selects-row').replaceWith(selectsRow(table, numDataCols));
					},

					detectTable = function() {
						// function that tries to detect best matching table for csv data
						// this function won't affect any parts of state except state.tableBestMatch
						// so we're freezing a copy of state first to restore after detection
						log('detectTable()');
						var oldState = JSON.stringify(state);

						var matches = 0, tableBestMatch = false;
						for(var tn in fields) {
							if(!fields.hasOwnProperty(tn)) continue;

							state.table = tn;
							state.newMap = true;
							if(!detectMap()) continue;

							// how many csv columns match fields in current table?
							var currentMatches = state.csvMap.filter(function(f) { return f.length > 0; }).length;
							if(currentMatches > matches) {
								matches = currentMatches;
								tableBestMatch = tn;
							}
						}

						// restore original state, then update tableBestMatch
						state = JSON.parse(oldState);
						state.tableBestMatch = tableBestMatch;

						return tableBestMatch;
					},

					detectMap = function() {
						log('detectMap()');
						if(!state.newMap) return false;
						if(state.data[0] === undefined) return false;

						log({ csvMapBefore: state.csvMap });

						var autoDetected = false,
							lcase = function(s) { return typeof(s) == 'string' ? s.toLowerCase().replace(/[^a-z\d]/g, '') : ''; },
							tableFields = fields[state.table].map(lcase), // lower-case trimmed field names
							firstRow = state.data[0].map(lcase); // lower case trimmed csv column titles
							
						state.csvMap = [];

						for(var i = 0; i < firstRow.length; i++) {
							state.csvMap.push('');
							var index = tableFields.indexOf(firstRow[i]); // case insensitive search for column title in field names
							if(index == -1) continue; // not found, leave map item empty

							state.csvMap[i] = fields[state.table][index]; // set map item to actual field name
							autoDetected = true;
							log('Auto-detected field ' + state.csvMap[i] + ' matches column# ' + i + ' (' + state.data[0][i] + ')');
						}

						if(!autoDetected) return false;

						state.newMap = false;
						state.csvHasHeaderRow = true;

						log({ csvMapAfter: state.csvMap });

						return true;
					},

					applyStatetoUI = function() {
						log('applyStatetoUI()');

						// for non-super-admin, hide fastMode option
						$j('#fastMode').parents('.checkbox').toggleClass('hidden', !$j('nav a[href="admin/pageHome.php"]').length);

						// state.csvHasHeaderRow
						$j('#csvHasHeaderRow').prop('checked', state.csvHasHeaderRow);

						// empty previous import errors
						$j('#import-error').html('');

						// force default fieldSeparator, fieldWrapper if empty
						if(!$j('#fieldSeparator').text().length) $j('#fieldSeparator').html(',');
						if(!$j('#fieldWrapper').text().length) $j('#fieldWrapper').html('"');

						// state.updateExistingRecords
						var uCan = userCanUpdate();
						$j('#updateExistingRecords')
							.prop('disabled', !uCan)
							.parents('label').toggleClass('text-muted', !uCan);
						$j('#missing-pk-field-in-csv').toggleClass('hidden', uCan);
						if(!uCan) state.updateExistingRecords = false;

						$j('#updateExistingRecords')
							.prop('checked', state.updateExistingRecords)
							.parents('label').toggleClass('text-warning', state.updateExistingRecords);

						// state.fastMode
						$j('#fastMode')
							.prop('checked', state.fastMode)
							.parents('label').toggleClass('text-warning', state.fastMode);

						// state.error
						if(state.error) {
							$j('#csv-preview').empty();
							$j('<div class="alert alert-danger"></div>')
								.html(state.error)
								.appendTo('#csv-preview');
							$j('#start-import').prop('disabled', true);
							return;
						}

						// state.table
						// draw table structure if not already there
						renderTableStructure();
						
						// draw selects if not already there, populate selects with table fields
						resetSelects();

						// state.data
						renderCSVData();

						// enable/disable #start-import based on validity of state.csvMap
						var res = hintCSVMapping();
						if(res !== true) {
							$j('#start-import').prop('disabled', true);
							$j('#import-error').html(res);
							return;
						}

						// disable #start-import if user can neither update nor insert
						if((!userCanUpdate() || !state.updateExistingRecords) && !userCanInsert()) {
							$j('#start-import').prop('disabled', true);
							$j('#import-error').html(lang.noInsertUpdateOnly);
							return;
						}

						$j('#start-import').prop('disabled', false);
					},

					userCanUpdate = function() {
						return (
							// true if user can update records of current table
							editableTables.indexOf(state.table) > -1 &&

							/* true if the PK of the current table is one of the csvMap fields */
							state.csvMap.indexOf(tablePK[state.table]) > -1
						);
					},

					userCanInsert = function() {
						return (insertableTables.indexOf(state.table) > -1);
					},

					// show visual hints for data indicating which would be imported and which skipped
					// return true if there is one or mapped columns to import, error message otherwise
					hintCSVMapping = function() {
						log('hintCSVMapping()');
						var columnsMapped = false, fieldsInUse = [], repeatedFields = false;

						$j('.selects-row select').each(function(index, slct) {
							var val = state.csvMap[index];

							if(val && fieldsInUse.indexOf(val) > -1) {
								log('Select #' + index + ' is reusing "' + val + '"');
								$j(slct).val(val).css({ color: colorSkip });
								repeatedFields = true;

								$j('td.col-' + index).css({ opacity: 0.33 });
								return; // skip remaining checks
							}
							
							if(val) fieldsInUse.push(val);

							log('Setting select #' + index + ' to "' + val + '"');
							$j(slct)
								.val(val)
								.css({ color: (val ? colorNormal : colorSkip) });

							$j('td.col-' + index).css({ opacity: val ? 1 : 0.33 });
							if(val) columnsMapped = true;
						});

						if(!columnsMapped) return lang.noDataToImport;
						if(repeatedFields) return lang.repeatedFields;

						return true;
					},

					renderTableStructure = function() {
						log('renderTableStructure()');
						// if we don't already have a table, plot it
						if(!$j('#csv-preview > table').length) {
							$j('#csv-preview').empty();
							$j('<table class="table table-striped table-hover table-bordered"><thead></thead><tbody></tbody></table>')
								.appendTo('#csv-preview');
						}
					},

					renderCSVData = function() {
						log('renderCSVData()');
						var data = state.data;

						var tbody = $j('#csv-preview > table > tbody');
						tbody.empty();

						if(data === undefined || data.length === undefined || data === null) return;
						
						log('\trenderCSVData has data to render');

						// skip first data row if csvHasHeaderRow
						var r = state.csvHasHeaderRow ? 1 : 0;

						// now loop through the 2D array data and add correpsonding rows to table
						while(r < data.length) {
							// new record > new table row
							var row = $j('<tr></tr>');

							// all rows should have the same # of columns, as determined by data[0]
							for(var c = 0; c < data[0].length; c++)
								// new cell appended to current row, escaping special HTML
								$j('<td class="col-' + c + '">' + htmlSpecialChars(data[r][c] != undefined ? data[r][c] : '') + '</td>').appendTo(row);

							row.appendTo(tbody);
							r++;
						}
					},

					handleAjaxData = function(data, error) {
						log('handleAjaxData(' + typeof(data) + ', "' + error + '")');
						if(!data || !data.length || error) {
							state.data = [];
							state.error = error ? error : lang.connectionError;
							return;
						}

						state.data = data;
						state.error = error;
						log('\thandleAjaxData received valid data');
						detectMap();
					},

					enableForm = function(enable) {
						log('enableForm(' + JSON.stringify(enable) + ')');
						$j('#csv-import-form *')
							.prop('disabled', !enable)
							.toggleClass('text-muted', !enable);
						$j('.form-control-static').prop('contenteditable', enable);
						$j('#update-csv-preview').find('.glyphicon').toggleClass('loop-rotate', !enable);
						$j('#csvlint-hint').toggleClass('hidden', !enable);
					},

					ajaxGetCSVPreview = function(callbackOnComplete) {
						if(previewRequestInProgress) return;
						log('ajaxGetCSVPreview()');

						$j.ajax({
							/* url: defaults to current page is not specified, which is fine */
							type: 'POST',
							data: {
								jobId: jobId,
								action: 'ajax-csv-preview',
								table: state.table,
								fieldSeparator: $j('#fieldSeparator').text(),
								fieldWrapper: $j('#fieldWrapper').text(),
								updateExistingRecords: state.updateExistingRecords,
								csvMap: state.csvMap
							},
							beforeSend: function() {
								previewRequestInProgress = true;
								enableForm(false);
							},
							success: function(resp) {
								handleAjaxData(resp.data, '');
							},
							complete: function() {
								previewRequestInProgress = false;
								enableForm(true);
								applyStatetoUI();

								if(typeof(callbackOnComplete) == 'function') {
									callbackOnComplete();
								}
							},
							error: function(xhr) {
								handleAjaxData(
									[], 
									xhr.responseJSON === undefined || xhr.responseJSON.error === undefined ? lang.connectionError : xhr.responseJSON.error
								);
							}
						});
					},

					endOfVar = 1; // this has no real value except to keep adding functions above if needed, ending with ,

				// event handlers and initial code below
				$j(function() {
					$j('#auto-detect-fields').on('click', function() {
						log('#auto-detect-fields.click event');
						detectTable();
						if(state.tableBestMatch !== false)
							$j('#table').val(state.tableBestMatch).trigger('change');
					})

					// on changing table, try to detect csv map
					$j('#table').on('change', function(e) {
						log('#table.change event');
						e.stopPropagation(); // we don't want to trigger form change here ...
						state.table = $j(this).val();
						resetMap();
						detectMap()
						applyStatetoUI();
					});

					$j('#update-csv-preview').on('click', function() {
						log('#update-csv-preview.click event');
						clearInterval(timerForPreviewUpdate);
						ajaxGetCSVPreview();
					});

					$j('#updateExistingRecords').on('change', function(e) {
						log('#updateExistingRecords.change event');
						e.stopPropagation();
						state.updateExistingRecords = $j(this).prop('checked');
						applyStatetoUI();
					})

					$j('#fastMode').on('change', function(e) {
						log('#fastMode.change event');
						e.stopPropagation();
						state.fastMode = $j(this).prop('checked');
						applyStatetoUI();
					})

					$j('#fieldWrapper, #fieldSeparator').on('input', function() {
						log('#fieldWrapper.input or #fieldSeparator.input event');
						clearInterval(timerForPreviewUpdate);
						timerForPreviewUpdate = setTimeout(function() {
							ajaxGetCSVPreview();
						}, 1000);
					}).on('keypress', function(e) {
						// new line ends editing
					    if(e.which != 13) return true;

					    $j(this).trigger('blur');
					    e.preventDefault();
					    return false;
					})

					$j('#csvHasHeaderRow').on('change', function() {
						log('#csvHasHeaderRow.change event');
						state.csvHasHeaderRow = $j(this).prop('checked');
						applyStatetoUI();
					});

					// user-initiated field mapping
					$j('#csv-preview').on('change', 'thead select', function(e) {
						e.stopPropagation();
						var slct = $j(this), index = parseInt(slct.parents('th').attr('class').replace('col-', ''));
						state.csvMap[index] = slct.val();
						
						applyStatetoUI();
					});

					$j('#confirm-import').on('click', function() {
						// ajax request to send all settings to a new action 'begin-import'
						// then on success, load import progress page, passing jobId in url ...
						log('#confirm-import.click event');

						$j.ajax({
							/* url: defaults to current page is not specified, which is fine */
							type: 'POST',
							data: {
								jobId: jobId,
								action: 'ajax-save-job-config',
								table: state.table,
								fieldSeparator: $j('#fieldSeparator').text(),
								fieldWrapper: $j('#fieldWrapper').text(),
								updateExistingRecords: state.updateExistingRecords,
								fastMode: state.fastMode,
								csvMap: state.csvMap,
								csvHasHeaderRow: state.csvHasHeaderRow
							},
							beforeSend: function() {
								previewRequestInProgress = true;
								enableForm(false);
							},
							success: function(resp) {
								window.location.assign(window.location.href.replace(/#.*/, '') + '?jobId=' + jobId);
							},
							complete: function() {
								previewRequestInProgress = false;
							},
							error: function(xhr) {
								handleAjaxData(
									[], 
									xhr.responseJSON === undefined || xhr.responseJSON.error === undefined ? lang.connectionError : xhr.responseJSON.error
								);
								enableForm(true);
								$j('#confirm-import-div').collapse('hide');
								applyStatetoUI();
							}
						});
					});

					// --------------------------------------------
					// initial actions
					// --------------------------------------------
					state.table = $j('#table').val();
					applyStatetoUI();
					ajaxGetCSVPreview(function() {
						// hide initial loader progress bar
						$j('#data-loader').toggleClass('hidden moving');

						// try to detect best matching table
						if(detectTable() === false) return;
						
						$j('#table').val(state.tableBestMatch).trigger('change');
					});
				})
			</script>

			<?php
			return $this->html(ob_get_clean(), [
				'[grid-classes]' => 'col-sm-6 col-md-3 col-lg-3',
				'[grid-classes-narrow]' => 'col-xs-6 col-sm-3 col-md-3 col-lg-3',
			]);
		}

		private function ajaxSaveJobConfig() { // action=ajax-save-job-config
			if(!$this->getJob())
				return $this->json(null, $this->T[$this->error], '404 Not Found');

			// adjust CSV parameters according to sent request
			$this->importJob->configure([
				'table' => $this->table,
				'fieldSeparator' => $this->request['fieldSeparator'],
				'fieldWrapper' => $this->request['fieldWrapper'],
				'updateExistingRecords' => $this->request['updateExistingRecords'],
				'fastMode' => $this->request['fastMode'],
				'csvHasHeaderRow' => $this->request['csvHasHeaderRow'],
				'csvMap' => $this->request['csvMap'],
			]);


			if($this->importJob->error)
				return $this->json(null, $this->T[$this->importJob->error], '404 Not Found');

			return $this->json($this->importJob->getJobInfo());
		}

		private function ajaxCSVPreview() { // action=ajax-csv-preview
			if(!$this->getJob())
				return $this->json(null, $this->T[$this->error], '404 Not Found');

			// adjust CSV parameters according to sent request
			$this->importJob->configure([
				'table' => $this->table,
				'fieldSeparator' => $this->request['fieldSeparator'],
				'fieldWrapper' => $this->request['fieldWrapper'],
				'updateExistingRecords' => $this->request['updateExistingRecords'],
				// csvHasHeaderRow is handled client-side during preview
				'csvHasHeaderRow' => false,
			]);

			if($this->importJob->error)
				return $this->json(null, $this->T[$this->importJob->error], '404 Not Found');

			if(!$data = $this->importJob->previewCSV())
				return $this->json(null, $this->T[$this->importJob->error], '404 Not Found');

			return $this->json($data);
		}

		/**
		 * @return  string  uploaded file path or false on error
		 */
		private function handleCSVUpload() {
			if(!$dir = $this->prepCSVDir()) return false;

			$csvFile = PrepareUploadedFile('csvFile', 999999999, 'csv|txt', false, $dir);
			if(!$csvFile || !is_readable("{$dir}/{$csvFile}")) {
				$this->error = 'file upload error';
				return false;
			}

			return "{$dir}/{$csvFile}";
		}

		private function prepCSVDir() {
			$dir = APP_DIR . '/' . self::CSV_DIR;
			if(is_dir($dir)) return $dir;

			@mkdir($dir);
			@touch("{$dir}/index.html");
			@file_put_contents("{$dir}/.htaccess", implode("\n", [
				'<FilesMatch "\.(csv)$">',
				'    Order allow,deny',
				'</FilesMatch>',
			]));

			if(!is_dir($dir)) {
				$this->error = 'clean csv dir error';
				return false;
			}

			return $dir;
		}
	}