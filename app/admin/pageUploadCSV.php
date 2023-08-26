<?php
	require(__DIR__ . '/incCommon.php');

	$GLOBALS['DEBUG_MODE'] = false;
	$csv = new CSV($_REQUEST);

	class CSV{
		private $curr_dir,
				$curr_page,
				$lang, /* translation text */
				$request, /* assoc array that stores $_REQUEST */
				$error_back_link,
				$max_batch_size, /* max # of non-empty lines to insert per batch */
				$max_data_length, /* max length of csv data in read per batch in bytes */
				$initial_ts; /* initial timestamp */

		public function __construct($request = []) {
			global $Translation;

			$this->curr_dir = __DIR__;
			$this->curr_page = basename(__FILE__);
			$this->max_batch_size = 500;
			$this->max_data_length = 0.5 * 1024 * 1024;
			$this->initial_ts = microtime(true);
			$this->lang = $Translation;

			/* back link to use in errors */
			$this->error_back_link = '' .
				'<div class="text-center vspacer-lg"><a href="' . $this->curr_page . '" class="btn btn-danger btn-lg">' .
					'<i class="glyphicon glyphicon-chevron-left"></i> ' .
					$this->lang['back and retry'] .
				'</a></div>';

			/* process request to retrieve $this->request, and then execute the requested action */
			$this->process_request($request);          
			call_user_func_array([$this, $this->request['action']], []);
		}

		protected function debug($msg, $html = true) {
			if($GLOBALS['DEBUG_MODE'] && $html) return "<pre>DEBUG: {$msg}</pre>";
			if($GLOBALS['DEBUG_MODE']) return " [DEBUG: {$msg}] ";
			return '';
		}

		protected function elapsed() {
			return number_format(microtime(true) - $this->initial_ts, 3);
		}

		protected function process_request($request) {
			/* action must be a valid controller, else set to default (show_load_form) */
			$controller = isset($request['action']) ? $request['action'] : false;
			if(!in_array($controller, $this->controllers())) $request['action'] = 'show_load_form';

			$this->request = $request;
		}

		/**
		 *  discover the public functions in this class that can act as controllers
		 *  
		 *  @return array of public function names
		 */
		protected function controllers() {
			$csv = new ReflectionClass($this);
			$methods = $csv->getMethods(ReflectionMethod::IS_PUBLIC);

			$controllers = [];
			foreach($methods as $mthd) {
				$controllers[] = $mthd->name;
			}

			return $controllers;
		}

		/**
		 * function to show form for uploading a CSV file or choosing one from the 'csv' folder
		 */
		public function show_load_form() {
			/* get list of available CSV files */
			$csv_files = $this->csv_files("{$this->curr_dir}/csv");

			/* prepare tables drop-down */
			$tables = getTableList();
			$tables_dropdown = htmlSelect('table', array_keys($tables), array_map(function($t) { return $t[0]; }, array_values($tables)), '');
			$tables_dropdown = str_replace('<select ', '<select class="form-control input-lg" ', $tables_dropdown);
			$tables_dropdown = preg_replace('/(<select .*?>)/i', "\$1<option value=\"\">{$this->lang['select a table']}</option>", $tables_dropdown);

			echo $this->header();
			?>
				<form method="post" action="<?php echo $this->curr_page; ?>" enctype="multipart/form-data">
					<input type="hidden" name="action" value="upload">
					<?php echo csrf_token(); ?>
					<div class="page-header"><h1><?php echo $this->lang['import CSV to database']; ?></h1></div>

					<div class="alert alert-warning text-center"><?php echo str_replace(
						['[a]', '[/a]'], 
						['<a class="btn btn-alert btn-warning" href="../import-csv.php">', '</a>'], 
						$this->lang['admin csv warning']
					); ?></div>

					<h4><?php echo $this->lang['import CSV to database page']; ?></h4>

					<div class="panel panel-success">
						<div class="panel-heading">
							<h3 class="panel-title"><i class="glyphicon glyphicon-th hspacer-md"></i> <?php echo "<b>{$this->lang['step 1']}</b> {$this->lang['table']}"; ?></h3>
						</div>
						<div class="panel-body">
							<div class="form-group">
								<?php echo $tables_dropdown; ?>
								<span class="help-block"><?php echo $this->lang['populate table from CSV']; ?></span>
							</div>
						</div>
					</div>

					<div class="panel panel-success">
						<div class="panel-heading">
							<h3 class="panel-title"><i class="glyphicon glyphicon-upload hspacer-md"></i><?php echo "<b>{$this->lang['step 2']}</b> {$this->lang['upload or choose csv file']}"; ?></h3>
						</div>
						<div class="panel-body">
							<label for="upload_csv" class="btn btn-primary btn-lg">
								<i class="glyphicon glyphicon-upload"></i> &nbsp;<?php echo $this->lang['choose csv upload']; ?>
							</label>

							<span class="hspacer-lg"></span>
							<span id="csv_file_name"><?php echo $this->lang['no file chosen yet']; ?></span>
							<input type="file" name="upload_csv" id="upload_csv" accept=".csv, text/csv">
							<button type="submit" class="btn btn-success btn-lg hspacer-lg hidden" id="start_upload"><i class="glyphicon glyphicon-upload"></i> <?php echo $this->lang['start upload']; ?></button>
							<div class="help-block"><?php echo $Translation['tip check csv for errors']; ?></div>

							<?php if(count($csv_files)) { ?>
								<hr>
								<div class="panel panel-primary">
									<div class="panel-heading">
										<h3 class="panel-title"><i class="glyphicon glyphicon-folder-open hspacer-md"></i> Open an existing CSV file</h3>
									</div>
									<div class="panel-body hidden">
										<div class="row" id="existing-csv-files">
										   <?php foreach($csv_files as $csv_file) { ?>
											  <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6 csv-file">
												  <button type="button" class="btn btn-link invisible delete-csv" data-csv="<?php echo html_attr($csv_file); ?>" title="<?php echo html_attr($this->lang['delete']); ?>"><i class="glyphicon glyphicon-trash text-danger"></i></button>
												  <a href="<?php echo $this->curr_page; ?>?csv=<?php echo urlencode($csv_file); ?>&action=show_preview&table=">
													  <i class="glyphicon glyphicon-file text-success"></i> <?php echo $csv_file; ?>
												  </a>
											  </div>
										   <?php } ?>
										</div>
									</div>
								</div>
							<?php } ?>
						</div>
					</div>
				</form>

				<script>
					$j(function() {
						/* function to highlight table drop-down as required */
						var highlight_dropdown = function() {
							$j('#table').focus().parent().addClass('has-error');
							return false;
						};

						/* function to unhighlight table drop-down */
						var unhighlight_dropdown = function() {
							$j('#table').parent().removeClass('has-error');
						};

						/* function to update csv file links */
						var update_csv_links = function() {
							var table = $j('#table').val();
							var csrf_token = $j('#csrf_token').val();

							if(table.length) unhighlight_dropdown();

							$j('.csv-file a').each(function() {
								var href = $j(this).attr('href');
								href = href.replace(/(action=show_preview).*$/, '$1');
								href += '&csrf_token=' + csrf_token;
								href += '&table=' + table;
								$j(this).attr('href', href);
							});                            
						}

						/* validate and display name of selected-for-upload CSV file */
						$j('#upload_csv').change(function() {
							var csv_file = $j(this).val();
							if(!csv_file.match(/\.csv$/i)) {
								$j('#csv_file_name').html(
									'<span class="text-danger bg-danger">' + 
										'<i class="glyphicon glyphicon-remove"></i> ' +
										'<?php echo $this->lang['invalid csv file selected']; ?>' + 
									'</span>');
								$j(this).val('');
								$j('#start_upload').addClass('hidden');
								return false;
							}
							$j('#csv_file_name').html(
								'<span class="bg-success text-success">' + 
									'<i class="glyphicon glyphicon-ok"></i> ' + csv_file +
								'</span>'
							);
							$j('#start_upload').removeClass('hidden');
						});

						/* toggle panel-body and panel-footer on clicking panel-title */
						$j('.panel-heading').click(function() {
							var panel = $j(this).parent();
							panel.find('.panel-body').toggleClass('hidden');
							panel.find('.panel-footer').toggleClass('hidden');
						});

						/* hover effect for existing csv files */
						var highlighter = 'success';
						$j('.row').on('mousemove', '.csv-file', function() {
							$j('.csv-file').removeClass('text-' + highlighter + ' bg-' + highlighter);
							$j('.delete-csv').addClass('invisible');
							$j(this).addClass('text-' + highlighter + ' bg-' + highlighter);
							$j(this).find('button').removeClass('invisible');
						}).mouseout(function() {
							$j('.csv-file').removeClass('text-' + highlighter + ' bg-' + highlighter);
							$j('.delete-csv').addClass('invisible');
						});

						/* on changing table, update csv file links */
						$j('#table').change(function() {
							update_csv_links();
						});

						/* on submitting csv, make sure a table is selected */
						$j('#start_upload').click(function() {
							var table = $j('#table').val();
							if(!table.length) {
								return highlight_dropdown();
							}
						});
						$j('#existing-csv-files').on('click', '.csv-file a', function() {
							var table = $j('#table').val();
							if(!table.length) {
								return highlight_dropdown();
							}
						});

						$j('#existing-csv-files').on('click', '.delete-csv', function() {
							var del_btn = $j(this);
							var csv = del_btn.data('csv');
							var msg = <?php echo json_encode($this->lang['sure delete csv']); ?>;
							msg = msg.replace(/\[CSVFILE\]/, '"' + csv + '"');

							var fail_delete = function(elm) {
								elm.popover({
									placement: 'auto bottom',
									title: <?php echo json_encode($this->lang['errors occurred']); ?>,
									content: '<span class="text-danger"><?php echo html_attr($this->lang['couldnt delete csv file']); ?></span>',
									trigger: 'manual',
									container: 'body',
									html: true
								}).popover('show').on('shown.bs.popover', function() {
									setTimeout(function() {
										elm.popover('hide').popover('destroy');
									}, 3000);
								});
							};

							if(confirm(msg)) {
								var url = '<?php echo $this->curr_page; ?>?action=delete_csv&csv=' + encodeURIComponent(csv);
								$j.ajax(url)
									.done(function(data) {
										if(data.deleted) {
										   del_btn.parent().remove();
										} else {
										   fail_delete(del_btn.parent());
										}
									})
									.fail(function() {
										fail_delete(del_btn.parent());
									});
							}
						});
					})
				</script>

				<style>
					.csv-file{
						overflow: hidden;
						white-space: nowrap;
						font-size: 1.2em;
						padding-top: 0.4em;
					}
					label[for=upload_csv]{
						cursor: pointer;
					}
					#upload_csv{
						opacity: 0;
						position: absolute;
						z-index: -1;
					}
					.panel-heading{
						cursor: pointer;
					}
					.panel-title{
						font-size: 1.4em;
					}
				</style>
			<?php
			echo $this->footer();
		}

		public function delete_csv() {
			$deleted = false;
			@header('Content-type: application/json');

			$csv_folder = "{$this->curr_dir}/csv/";
			$csv = $this->get_csv();
			if($csv && @unlink($csv_folder . $csv)) $deleted = true;           

			echo json_encode(['deleted' => $deleted]);
		}

		private function csv_files($dir) {
			$csv_files = [];

			if(!is_dir($dir)) @mkdir($dir);

			$d = dir($dir);
			while(false !== ($entry = $d->read())) {
				if(preg_match('/\.csv$/i', $entry)) $csv_files[] = urldecode($entry);
			}
			$d->close();
			return $csv_files;
		}

		/**
		  * function to handle csv file upload request by validating and saving into the csv folder
		  */
		public function upload() {
			if(!csrf_token(true)) {
				echo $this->header();
				echo errorMsg("{$this->lang['csrf token expired or invalid']}<br>{$csv_file}{$this->error_back_link}" . $this->debug(__LINE__));
				echo $this->footer();

				return;
			}

			$csv_file = getUploadedFile('upload_csv', PHP_INT_MAX, 'csv', true);
			$table = $this->get_table();
			if(!$table) return;

			if(!$csv_file || !is_readable($csv_file)) {
				echo $this->header();
				echo errorMsg("{$this->lang['csv file upload error']}<br>{$csv_file}{$this->error_back_link}" . $this->debug(__LINE__));
				echo $this->footer();

				return;
			}

			echo $this->header();
			?>
			<div class="alert alert-success vspacer-lg"><h2>
				<i class="glyphicon glyphicon-ok"></i> 
				<?php echo $this->lang['please wait and do not close']; ?>
			</h2></div>
			<script>
				$j(function() {
					window.location = '<?php echo $this->curr_page; ?>?action=show_preview&csv=<?php echo urlencode(basename($csv_file)); ?>&table=<?php echo urlencode($table); ?>';
				});
			</script>
			<?php
			echo $this->footer();
		}

		/**
		 *  @brief retrieve and validate the csv file specified in the request parameter 'csv'
		 *  
		 *  @param [in] $options optional assoc array of options ('htmlpage' => bool, displaying errors as html page)
		 *  @return csv filename if valid, false otherwise.
		 */
		protected function get_csv($options = []) {
			$csv_ok = true;

			$csv = $this->request['csv'];
			if(!$csv) $csv_ok = false;

			if($csv_ok) {
				$csv = basename($csv);
				if(!is_readable("{$this->curr_dir}/csv/{$csv}")) $csv_ok = false;
			}

			if(!$csv_ok) {
				if(isset($options['htmlpage'])) {
					echo $this->header();
					echo errorMsg($this->lang['csv file upload error'] . $this->error_back_link . $this->debug(__LINE__));
					echo $this->footer();
				}
				return false;
			}

			return $csv;
		}

		/**
		 *  @brief Retrieve and validate name of table used for importing data
		 *  
		 *  @param [in] $silent (optional) boolean indicating no output to client if true, useful in ajax requests for example.
		 *  @return table name, or false on error.
		 */
		protected function get_table($silent = false) {
			$table_ok = true;

			$table = $this->request['table'];
			if(!$table) $table_ok = false;

			if($table_ok) {
				$tables = getTableList();
				if(!array_key_exists($table, $tables)) $table_ok = false;
			}

			if(!$table_ok) {
				if($silent) return false;

				echo $this->header();
				echo errorMsg(str_replace('<TABLENAME>', html_attr($table), $this->lang['table name title']) . ': ' . $this->lang['does not exist'] . $this->error_back_link . $this->debug(__LINE__));
				echo $this->footer();
				return false;
			}

			return $table;
		}

		protected function table_fields($table) {
			$field_details = $fields = [];

			$res = sql("show fields from `{$table}`", $eo);
			while($row = db_fetch_assoc($res)) {
				$fields[] = $row['Field'];
				$field_details[] = $row;
			}

			return $fields;
		}

		/**
		  * show js-driven preview of 1st 10 lines, with live csv options and column mapping options
		  */
		public function show_preview() {

			/* retrieve and validate table to import to */
			$table = $this->get_table();
			if(!$table) return;

			/* retrieve fields of table */
			$fields = $this->table_fields($table);

			/* retrieve and open requested csv file */
			$csv = $this->get_csv(['htmlpage' => true]);
			if(!$csv) return;
			$csv_fp = fopen("{$this->curr_dir}/csv/{$csv}", 'r');
			if(!$csv_fp) return;

			/* get the first 50 lines of the csv */
			$lines = [];
			$line_num = 0;
			while(($line = fgets($csv_fp)) && $line_num < 50) {
				if(!$line_num) $line = trim($this->no_bom($line), '"');
				$lines[] = trim($line);
				$line_num++;
			}

			$lines_json = @json_encode($lines);
			if($lines_json === false && function_exists('json_last_error')) {
				if(json_last_error() == JSON_ERROR_UTF8) {
					$lines = $this->utf8ize($lines);
					$lines_json = @json_encode($lines);
				}
			}

			echo $this->header();

			if($lines_json === false) {
				$lines_json = '[]';
				echo "\n<!-- \n\t" .
					$this->debug(implode("\n\t", $lines), false) .
					"\n -->\n";
				if(function_exists('json_last_error')) {
					echo "\n<!-- \n\t" . $this->debug('json error: ' . json_last_error()) . "\n -->\n";
				}
			}

			?>

			<script src="../resources/csv/jquery.csv.min.js"></script>

			<div class="page-header"><h1><?php echo $this->lang['preview and confirm CSV data']; ?></h1></div>

			<h3 class="text-right">
				<?php echo $this->lang['CSV file']; ?>:
				<span class="text-info"><?php echo html_attr($csv); ?></span>
				<a href="<?php echo $this->curr_page; ?>" class="btn btn-warning" title="<?php echo html_attr($this->lang['cancel']); ?>"><i class="glyphicon glyphicon-remove"></i></a>
			</h3>

			<div class="row">
				<div class="col-lg-offset-5 col-lg-7">
					<div class="panel panel-success">
						<div class="panel-heading">
							<h3 class="panel-title text-center"><i class="glyphicon glyphicon-cog hspacer-md"></i> <?php echo $this->lang['change CSV settings']; ?></h3>
						</div>
						<div class="panel-body hidden">
							<div id="csv-errors" class="alert alert-danger hidden">
								<i class="glyphicon glyphicon-exclamation-sign"></i>
								<?php echo $this->lang['error reading csv data']; ?>
							</div>

							<form class="form-horizontal" id="csv-settings">
								<?php echo csrf_token(); ?>
								<div class="form-group">
									<div class="col-sm-8 col-md-6 col-lg-7 col-sm-offset-4 col-md-offset-3 col-lg-offset-4">
										<label for="has_titles" class="control-label">
										   <input type="checkbox" id="has_titles" name="has_titles" value="1" checked>
										   <?php echo $this->lang['first line field names']; ?>
										</label>
									</div>
								</div>
								<div class="form-group">
									<label for="ignore_lines" class="control-label col-sm-4 col-md-3 col-lg-4"><?php echo $this->lang['ignore lines number']; ?></label>
									<div class="col-sm-8 col-md-6 col-lg-8">
										<div class="input-group">
										   <input type="text" class="form-control" id="ignore_lines" name="ignore_lines" value="0">
										   <span class="input-group-btn">
											   <button class="btn btn-default" type="button" id="increment-ignored-lines"><i class="glyphicon glyphicon-plus"></i></button>
											   <button class="btn btn-default" type="button" id="decrement-ignored-lines"><i class="glyphicon glyphicon-minus"></i></button>
										   </span>
										</div>
										<span class="help-block"><?php echo $this->lang['skip lines number']; ?></span>
									</div>
								</div>
								<div class="form-group">
									<label for="field_separator" class="control-label col-sm-4 col-md-3 col-lg-4"><?php echo $this->lang['field separator']; ?></label>
									<div class="col-sm-8 col-md-6 col-lg-8">
										<input type="text" class="form-control" id="field_separator" name="field_separator" value=",">
										<span class="help-block"><?php echo $this->lang['default comma']; ?></span>
									</div>
								</div>
								<div class="form-group">
									<label for="field_delimiter" class="control-label col-sm-4 col-md-3 col-lg-4"><?php echo $this->lang['field delimiter']; ?></label>
									<div class="col-sm-8 col-md-6 col-lg-8">
										<input type="text" class="form-control" id="field_delimiter" name="field_delimiter" value="&quot;">
										<span class="help-block"><?php echo $this->lang['default double-quote']; ?></span>
									</div>
								</div>
								<div class="form-group">
									<div class="col-sm-offset-4 col-sm-8 col-md-offset-3 col-md-9 col-lg-offset-4 col-lg-8">
										<label class="">
										   <input type="checkbox" name="update_pk" id="update_pk" value="1">
										   <?php echo $this->lang['update table records'] ; ?>
										</label>
										<span class="help-block"><?php echo $this->lang['ignore CSV table records']; ?></span>
									</div>
								</div>
								<div class="form-group">
									<div class="col-sm-offset-4 col-sm-8 col-md-offset-3 col-md-9 col-lg-offset-4 col-lg-8">
										<label class="">
										   <input type="checkbox" name="backup_table" id="backup_table" value="1" checked>
										   <?php echo $this->lang['back up the table'] ; ?>
										</label>
									</div>
								</div>

								<div class="row">
									<div class="col-sm-4 col-sm-offset-4">
										<button class="btn btn-warning btn-lg btn-block" type="button" id="reset-settings"><i class="glyphicon glyphicon-repeat"></i> <?php echo $this->lang['reset']; ?></button>
									</div>
									<div class="col-sm-4">
										<button class="btn btn-info btn-lg btn-block" type="button" id="apply-settings"><i class="glyphicon glyphicon-ok"></i> <?php echo $this->lang['ok']; ?></button>
									</div>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>

			<div style="height: 5em;"></div>

			<div class="table-responsive">
				<table class="table table-striped table-hover table-bordered" id="csv-preview-table">
					<thead><tr id="csv-fields"></tr><tr id="db-fields" class="bg-warning"></tr></thead>
					<tbody>
					</tbody>
				</table>
			</div>
			<div class="alert alert-danger hidden" id="no-csv-data-error">
				<i class="glyphicon glyphicon-exclamation-sign"></i>
				<?php echo $this->lang['error reading csv data']; ?>
			</div>

			<div class="row">
				<div class="col-sm-offset-7 col-sm-5 col-md-offset-8 col-md-4 col-lg-offset-9 col-lg-3">
					<b class="text-danger hidden" id="mappings-warning"><i class="glyphicon glyphicon-info-sign"></i> <?php echo $this->lang['no columns selected']; ?></b>
					<button type="button" class="hidden btn btn-primary btn-lg btn-block" id="start-import"><i class="glyphicon glyphicon-ok"></i> <?php echo $this->lang['import CSV']; ?></button>
				</div>
			</div>

			<script>
				$j(function() {
					var csv_data = <?php echo $lines_json; ?>;
					var fields = <?php echo json_encode($fields); ?>;

					var default_config = {
						field_separator: ',',
						field_delimiter: '"',
						ignore_lines: 0, // non-empty lines to ignore (after title line, if enabled)
						has_titles: true
					};
					var config = $j.extend({}, default_config);

					/* function to apply stored csv config to preview csv table */
					var update_preview = function() {
						if(!csv_data.length) {
							$j('#no-csv-data-error').removeClass('hidden');
							return;
						}
						var csv_row = [], data_rows = 0, title_displayed= false;
						var options = {
							separator: config.field_separator,
							delimiter: config.field_delimiter
						}

						/* clear table */
						$j('#no-csv-data-error').addClass('hidden');
						$j('#csv-fields,#db-fields,#csv-preview-table tbody').empty();

						/* clear and hide errors */
						$j('#csv-errors').addClass('hidden');

						for(var i = 0; i < csv_data.length; i++) {
							if((data_rows - config.ignore_lines) >= 10) break;

							try{
								csv_row = $j.csv.toArray(csv_data[i], options);
							}catch(e) {
								try{
									/* BOM handling seems to strip the first " ni some cases */
									csv_row = $j.csv.toArray('\"' + csv_data[i], options);
								}catch(e) {
									$j('#csv-errors').removeClass('hidden');
									continue;
								}
							}

							/* disregard empty lines */
							if((csv_row.length == 1 && csv_row[0] == '') || !csv_row.length) continue;

							if(config.has_titles && !title_displayed) {
								add_table_header(csv_row);
								title_displayed = true;
							} else if(!config.has_titles && !title_displayed) {
								var generic_titles = [];
								for(var j = 0; j < csv_row.length; j++) {
									generic_titles.push(<?php echo json_encode($this->lang['field']); ?> + ' ' + (j + 1));
								}
								add_table_header(generic_titles)
								title_displayed = true;

								data_rows++;
								if(data_rows > config.ignore_lines) add_table_row(csv_row);
							} else {
								data_rows++;
								if(data_rows > config.ignore_lines) add_table_row(csv_row);
							}
						}
					}

					var add_table_row = function(row) {
						if(!row.length) return;
						var rand_id = 'tr-' + Math.floor(Math.random() * 100000);
						var td = '';
						var num_columns = $j('tr:first th').length;

						$j('#csv-preview-table tbody').append('<tr id="' + rand_id + '"></tr>');
						for(var i = 0; i < num_columns; i++) {
							td = '<td>';
							if(row[i] == undefined) row[i] = '';
							if(row[i].length > 34) row[i] = row[i].substr(0, 30) + ' ...';
							td += row[i] + '</td>';
							$j('#' + rand_id).append(td);
						}
					}

					var add_table_header = function(row) {
						for(var i = 0; i < row.length; i++) {
							$j('#csv-fields').append('<th>' + row[i] + '</th>');
							$j('#db-fields').append('<th id="belongs-' + i + '"></th>');
							render_belongs('#belongs-' + i, row[i]);
						}
					}

					var import_button = function(hide_it) {
						if(!hide_it) {
							$j('#start-import').addClass('hidden');
							$j('#mappings-warning').removeClass('hidden');
							return;
						}

						$j('#start-import').removeClass('hidden');
						$j('#mappings-warning').addClass('hidden');
					}

					var render_belongs = function(id, title) {
						var selected = '';
						var csv_field_num = id.replace(/#belongs-/, '');

						var dropdown = '<select class="form-control" id="db-field-for-' + csv_field_num + '">';
						dropdown += '<option value="ignore-field">&lt;<?php echo html_attr($this->lang['skip column']); ?>&gt;</option>';
						for(var i = 0; i < fields.length; i++) {
							selected = '';
							if(strip_name(fields[i]) == strip_name(title)) {
								selected = ' selected';
							}
							dropdown += '<option value="' + fields[i] + '"' + selected + '>' + fields[i] + '</option>';
						}
						dropdown += '</select>';

						$j(id).append(
							'<label for="db-field-for-' + csv_field_num + '" class="control-label">' +
								<?php echo json_encode($this->lang['belongs to']); ?> +
							'</label>' +
							dropdown
						);
					}

					/* function to strip strings  */
					var strip_name = function(name) {
						return name.toLowerCase().replace(/[\W_]+/g,'');
					}

					/* sync csv settings from stored values to screen */
					var sync_screen_settings = function() {
						$j('#field_separator').val(config.field_separator);
						$j('#field_delimiter').val(config.field_delimiter);
						$j('#ignore_lines').val(config.ignore_lines);
						$j('#has_titles').prop('checked', config.has_titles);
					}

					/* sync csv settings from screen to stored values */
					var sync_stored_settings = function() {
						config.field_separator = $j('#field_separator').val();
						config.field_delimiter = $j('#field_delimiter').val();
						config.ignore_lines = parseInt($j('#ignore_lines').val());
						if(config.ignore_lines < 0) config.ignore_lines = 0;
						config.has_titles = $j('#has_titles').prop('checked');
					}

					/* parse GET parameters of the url and return the one requested */
					var get = function(get_var) {
						var result = "",
							tmp = [];
						var items = location.search.substr(1).split("&");
						for (var index = 0; index < items.length; index++) {
							tmp = items[index].split("=");
							if (tmp[0] === get_var) result = decodeURIComponent(tmp[1]);
						}
						return result;
					}

					update_preview();

					/* monitor column mappings and toggle import button accordingly */
					setInterval(function() {
						/* at least one field is selected for mapping? */
						var mappings_exist = false;
						$j('#db-fields select').each(function() {
							if($j(this).val() != 'ignore-field') {
								mappings_exist = true;
								return false; // break loop
							}
						});

						/* apply visual clues for mappings, checking for duplicates */
						var mappings = {};
						var no_duplicate_mapping = true;
						$j('#db-fields select').each(function() {
							if($j(this).val() == 'ignore-field') {
								$j(this).parent().removeClass('bg-success bg-danger has-error');
								return true; // continue loop
							}

							if(mappings[$j(this).val()] != undefined) {
								no_duplicate_mapping = false;
								$j(this).parent().addClass('has-error bg-danger');
								return false; // break loop
							}
							mappings[$j(this).val()] = true;
							$j(this).parent().removeClass('has-error bg-danger').addClass('bg-success');
						});

						import_button(mappings_exist && no_duplicate_mapping);
					}, 1000);

					/* toggle panel-body and panel-footer on clicking panel-title */
					$j('.panel-heading').click(function() {
						var panel = $j(this).parent();
						panel.find('.panel-body').toggleClass('hidden');
						panel.find('.panel-footer').toggleClass('hidden');
					});

					/* close settings panel on clicking outside it */
					$j('html, #apply-settings').click(function() {
						$j('.panel-success .panel-body, .panel-success .panel-footer').addClass('hidden');
					});
					$j('.panel-success').click(function(e) {
						e.stopPropagation(); // don't bubble the click event to prevent closing the panel
					});

					/* reset csv settings to defaults */
					$j('#reset-settings').click(function() {
						config = $j.extend({}, default_config);
						sync_screen_settings();
						update_preview();
					});

					/* apply changes in csv settings to preview */
					$j('#csv-settings').change(function() {
						sync_stored_settings();
						update_preview();
					});

					/* increase/decrease value in ignore_lines box */
					$j('#increment-ignored-lines,#decrement-ignored-lines').click(function() {
						var ignore_lines = parseInt($j('#ignore_lines').val());
						if($j(this).attr('id') == 'increment-ignored-lines') {
							ignore_lines++;
						} else {
							ignore_lines--;
						}
						if(ignore_lines < 0) ignore_lines = 0;
						$j('#ignore_lines').val(ignore_lines).change();
					});

					/* prepare csv settings for submission on clicking the import button */
					$j('#start-import').click(function() {
						if(!$j('#csv-errors').hasClass('hidden')) return;

						/* fetch csv settings */
						var params = {
							action: 'show_import_progress',
							csv: get('csv'),
							table: get('table'),
							backup_table: $j('#backup_table').prop('checked') ? 1 : 0,
							update_pk: $j('#update_pk').prop('checked') ? 1 : 0,
							has_titles: $j('#has_titles').prop('checked') ? 1 : 0,
							ignore_lines: Math.max(parseInt($j('#ignore_lines').val()), 0),
							field_separator: $j('#field_separator').val(),
							field_delimiter: $j('#field_delimiter').val(),
							csrf_token: $j('#csrf_token').val()
						};

						/* fetch csv field mappings */
						var num_fields= $j('#db-fields th').length;
						for(var i = 0; i < num_fields; i++) {
							params['mappings[' + i + ']'] = $j('#db-field-for-' + i).val();
						}

						/* prepare submission url */
						var url = '<?php echo $this->curr_page; ?>?' + $j.param(params);

						/* disable submit for 60 seconds (timeout in case submission fails) */
						$j(this).prop('disabled', true);
						setTimeout(function() {
							$j(this).prop('disabled', false);
						}, 60000);

						window.location = url;
					});
				});
			</script>

			<style>
				.panel-heading{
					cursor: pointer;
				}
				.panel-success{
					width: 90%;
					position: absolute;
					opacity: .95;
					right: 1.2em;
					z-index: 999;
				}
			</style>

			<?php
			echo $this->footer();
		}

/* ------------------------------------------------------ */

		/**
		  * start/continue importing a csv file into the db (ajax-friendly)
		  */
		public function import() {
			@header('Content-type: application/json');
			$res = [
				'imported' => 0,
				'failed' => 0,
				'remaining' => 0,
				'logs' => [],
			];

			$csv_status = $this->start();
			if(isset($csv_status['error'])) {
				$res['logs'][] = $csv_status['error'];
				echo json_encode($res);
				return;
			}

			$start = $csv_status['start'];

			$lines = $this->csv_lines();
			if($start >= $lines) { // no more rows to import
				$res['logs'][] = $this->lang['mission accomplished'];
				echo json_encode($res);
				$this->start(0);
				return;
			}

			$settings = $this->get_csv_settings();
			if($settings === false) {
				$res['logs'][] = $this->debug(__LINE__, false) . $this->lang['csv file upload error'];
				echo json_encode($res);
				return;
			}
			$data_lines = $lines - ($settings['has_titles'] ? 1 : 0);

			$bkp_res = $this->backup_table($start, $settings);
			if(isset($bkp_res['error'])) {
				$res['logs'][] = $this->debug(__LINE__, false) . $bkp_res['error'];
				echo json_encode($res);
				return;
			} elseif(isset($bkp_res['status'])) {
				$res['logs'][] = $bkp_res['status'];
			}

			$csv_data = $this->get_csv_data($start, $settings);
			if(!count($csv_data)) {
				$res['logs'][] = $this->lang['mission accomplished'];
				echo json_encode($res);
				$this->start(0);
				return;
			}

			$res['logs'][] = str_replace(
				array('<RECORDNUMBER>', '<RECORDS>'),
				array(number_format($start + 1), number_format($data_lines)),
				$this->lang['start at estimated record']
			);

			$res['imported'] = count($csv_data);
			$new_start = $start + $res['imported'];
			$res['remaining'] = $lines - $new_start;

			$query_info = $eo = [];
			$insert = $this->get_query($csv_data, $settings, $query_info);
			if($insert === false) {
				$res['logs'][] = $this->debug(__LINE__, false) . $this->lang['csv file upload error'];
				echo json_encode($res);
				return;
			}

			if(!sql($insert, $eo)) {
				$res['logs'][] = $this->debug(__LINE__, false) . db_error();
				echo json_encode($res);
				return;
			}

			$res['logs'][count($res['logs']) - 1] .= " {$this->lang['ok']}";

			if($new_start >= $lines) {
				$this->start(0); /* reset csv status after finishing */
			} else {
				$this->start($new_start); /* update csv status file to new start */
			}

			echo json_encode($res);
		}

		protected function request_or($var, $default) {
			return (isset($this->request[$var]) ? $this->request[$var] : $default);
		}

		/**
		 *  @brief Retrieve and validate CSV settings from REQUEST
		 *  
		 *  @return false on error, or associative array (table, backup_table, update_pk, has_titles, ignore_lines, field_separator, field_delimiter, mappings[])
		 */
		protected function get_csv_settings() {
			static $settings = [];
			if(!empty($settings)) return $settings; // cache to avoid reprocessing

			$settings = [
				'backup_table' => (bool) $this->request_or('backup_table', true),
				'update_pk' => (bool) $this->request_or('update_pk', false),
				'has_titles' => (bool) $this->request_or('has_titles', false),
				'ignore_lines' => max(0, (int) $this->request_or('ignore_lines', 0)),
				'field_separator' => $this->request_or('field_separator', ','),
				'field_delimiter' => $this->request_or('field_delimiter', '"'),
				'mappings' => $this->request_or('mappings', []),
			];

			if(!$settings['field_delimiter']) $settings['field_delimiter'] = '"';
			if(!$settings['field_separator']) $settings['field_separator'] = ',';

			$settings['table'] = $this->get_table(true);
			if($settings['table'] === false) return false;

			if(!is_array($settings['mappings']) || !count($settings['mappings'])) return false;

			/* validate and trim field names */
			$last_field_key = count($settings['mappings']) - 1;
			$mappings = [];
			for($i = 0; $i <= $last_field_key; $i++) {
				$fn = $settings['mappings'][$i];
				$fn = trim($fn);
				if(!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $fn) && $fn != 'ignore-field') return false;

				/* make sure field is not already mapped to another column */
				if(isset($mappings[$fn])) return false;

				$settings['mappings'][$i] = $fn;
				if($fn == 'ignore-field') {
					unset($settings['mappings'][$i]);
				} else {
					$mappings[$fn] = true;
				}
			}

			if(!count($settings['mappings'])) return false;

			return $settings;
		}

		/**
		 *  @brief Counts non-empty lines in the current csv file. Count is cached for performance.
		 *  
		 *  @return number of non-empty data lines in csv
		 *  
		 *  @details This function counts all non-empty, including the title column
		 */
		protected function csv_lines() {
			$csv = $this->get_csv();
			if(!$csv) return 0;

			/*
				store lines count server-side:
				for each csv file being imported, create a count file in the csv folder named {csv-file-name.csv.count}
				the file stores the # of non-empty lines.
			*/
			$count_file = "{$this->curr_dir}/csv/{$csv}.count";
			$csv_file = "{$this->curr_dir}/csv/{$csv}";

			/* if csv modified after counting its lines, force recount */
			if(is_file($count_file) && filemtime($count_file) < filemtime($csv_file)) {
				@unlink($count_file);
				return $this->csv_lines();
			}

			$lines = @file_get_contents($count_file);
			if($lines !== false) return intval($lines);

			/* this is a new import process */
			$lines = 0;
			$fp = @fopen($csv_file, 'r');
			if($fp === false) return 0;

			/* start counting non-empty lines of csv */
			while($line = fgets($fp)) {
				if(strlen(trim($line))) $lines++;
			}
			fclose($fp);

			@file_put_contents($count_file, $lines);
			return $lines;
		}

		/**
		 *  @brief if this is the beginning of the import process, perform table backup if requested
		 *  
		 *  @param $start current line in csv
		 *  @param $settings assoc arry of csv settings
		 *  @return assoc array, 'status' key and value if successful, 'error' key and value on error
		 */
		protected function backup_table($start, $settings) {
			if($start > 0) return []; // no need to backup as we've passed the first batch
			if(!$settings['backup_table']) return []; // no backup requested

			$table = $this->get_table(true);
			if($table === false) return ['error' => $this->lang['no table name provided'] . $this->debug(__LINE__, false)];

			$stable = makeSafe($table);
			if(!sqlValue("select count(1) from `{$stable}`")) // nothing to backup!
				return ['status' => str_replace('<TABLE>', $table, $this->lang['table backup not done'])];

			$btn = $stable . '_backup_' . @date('YmdHis');
			$eo = [];
			sql("DROP TABLE IF EXISTS `{$btn}`", $eo);
			if(!sql("CREATE TABLE IF NOT EXISTS `{$btn}` LIKE `{$stable}`", $eo))
				return ['error' => str_replace('<TABLE>', $table, $this->lang['error backing up table'] . $this->debug(__LINE__, false))];
			if(!sql("insert `{$btn}` select * from `{$stable}`", $eo))
				return ['error' => str_replace('<TABLE>', $table, $this->lang['error backing up table'] . $this->debug(__LINE__, false))];

			return [
				'status' => str_replace(
					array('<TABLE>', '<TABLENAME>'),
					array($table, $btn),
					$this->lang['table backed up']
				)
			];
		}

		protected function no_bom($str) {
			return preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $str);
		}

		/**
		 *  @brief opens current csv file and reads several lines from it, starting at non-empty line $start
		 *  
		 *  @param $start non-empty line # to start reading from
		 *  @param $settings csv settings array as retrieved from CSV::get_csv_settings
		 *  @return numeric 2D array of csv data (row1array, row2array, ...)
		 */
		protected function get_csv_data($start, $settings) {
			if($settings === false) return [];

			$csv = $this->get_csv();
			$csv_file = "{$this->curr_dir}/csv/{$csv}";
			$first_line = true;

			$fp = @fopen($csv_file, 'r');
			if(false === $fp) return [];

			/* skip $start non-empty lines */
			$skip = $start;

			/* apply ignore_lines */
			if($start < $settings['ignore_lines']) $skip = $settings['ignore_lines'];

			/* get key of last mapping field -- used later here to apply ignored fields */
			end($settings['mappings']);
			$last_field_key = key($settings['mappings']);

			/* skip title line */
			$skip += ($settings['has_titles'] ? 1 : 0);

			for($i = 0; $i < $skip; $i++) {
				/* keep reading till a non-empty line or EOF */
				do{
					$line = @implode('', @fgetcsv($fp));
					if($first_line) {
						$line = $this->no_bom($line); /* remove BOM from 1st line */
						$first_line = false;
					}
				}while(trim($line) === '' && $line !== false);

				if(false === $line) { fclose($fp); return []; } /* EOF before $start */
			}

			/* keep reading data from csv file till data size limit or EOF is reached */
			$csv_data = []; $raw_data = '';
			do{
				$data = fgetcsv($fp, pow(2, 15), $settings['field_separator'], $settings['field_delimiter']);
				if($data === false) { fclose($fp); return $csv_data; } /* EOF */

				if($first_line) {
					$data[0] = $this->no_bom($data[0]); /* remove BOM if 1st line */
					$data[0] = trim($data[0], $settings['field_delimiter']); /* fix fgetcsv behavior with BOM */
					$first_line = false;
				}
				if(count($data) == 1 && !$data[0]) continue; /* empty line */

				/* handle ignored fields */
				$last_key = max($last_field_key, count($data) - 1);
				for($i = 0; $i <= $last_key; $i++) {
					if(!isset($data[$i])) $data[$i] = '';
					if(!isset($settings['mappings'][$i])) unset($data[$i]);
				}

				$raw_data .= implode('', $data);
				$csv_data[] = $data;
			}while(strlen($raw_data) < $this->max_data_length && count($csv_data) < $this->max_batch_size);

			fclose($fp);
			return $csv_data;
		}

		/**
		 *  @brief Prepare the insert/replace query
		 *  
		 *  @param [in] $csv_data 2D numeric array of data to insert/replace
		 *  @param [in] $settings import settings assoc. array
		 *  @param [in,out] $query_info assoc array for exchanging query info and options
		 *  @return query string on success, false on error
		 */
		protected function get_query(&$csv_data, $settings, &$query_info) {
			/* make sure table name is provided */
			$table = $this->get_table(true);
			if($table === false) {
				$query_info['error'] = $this->lang['no table name provided'] . $this->debug(__LINE__, false);
				return false;
			}
			$stable = makeSafe($table);

			/* make sure mappings are provided */
			if(!isset($settings['mappings']) || !is_array($settings['mappings']) || !count($settings['mappings'])) {
				$query_info['error'] = $this->lang['error reading csv data'] . $this->debug(__LINE__, false);
				return false;
			}

			/* replace or insert? */
			$query = "INSERT IGNORE INTO ";
			if(isset($settings['update_pk']) && $settings['update_pk'] === true) {
				$query = "REPLACE ";
			}
			$query .= "`{$stable}` ";

			/* use mappings to determine field names */
			$query .= '(`' . implode('`,`', $settings['mappings']) . '`) VALUES ';

			/* build query data */
			$insert_data = [];
			foreach($csv_data as $rec) {
				/* sanitize data for SQL */
				foreach($rec as $i => $item) {
					$rec[$i] = "'" . makeSafe($item, false) . "'";
					if($item === '') $rec[$i] = 'NULL';
				}

				$insert_data[] = '(' . implode(',', $rec) . ')';
			}
			$query .= implode(",\n", $insert_data);

			return $query;
		}

		/**
		 *  get/set the next start of current csv file
		 *  
		 *  @param $start optional, new start value to save into status file
		 *  @return ['error' => error message] or ['start' => start line]
		 */
		protected function start($new_start = false) {
			$csv = $this->get_csv();
			if(!$csv) {
				/* invalid csv file specified */
				return ['error' => $this->debug(__LINE__, false) . $this->lang['csv file upload error']];
			}

			/*
				store progress server-side:
				for each csv file being imported, create a status file in the csv folder named {csv-file-name.csv.status}
				the file stores the last imported line#.
			*/
			$status_file = "{$this->curr_dir}/csv/{$csv}.status";
			if(!is_file($status_file)) {
				/* this is a new import process */
				/* create a status file and store $new_start into it */
				@file_put_contents($status_file, $new_start);
			}

			if($new_start !== false && intval($new_start) >= 0) {
				@file_put_contents($status_file, intval($new_start));
				return ['start' => intval($new_start)];
			}

			$start = @file_get_contents($status_file);
			if(false === $start) {
				/* can't read file */
				return ['error' => $this->debug(__LINE__, false) . $this->lang['csv file upload error']];
			}

			return ['start' => intval($start)];
		}

		/**
		  * show page to control and monitor csv import process
		  * (launch import job via ajax and keep relaunching and showing progress till done)
		  */
		public function show_import_progress() {
			echo $this->header();
			if(!csrf_token(true)) {
				echo errorMsg("{$this->lang['csrf token expired or invalid']}<br>{$this->error_back_link}" . $this->debug(__LINE__));
				echo $this->footer();
				return;
			}
			?>
			<div class="page-header"><h1><?php echo $this->lang['importing CSV data']; ?></h1></div>
			<div class="progress">
				<div id="import-progress" class="progress-bar progress-bar-striped active progress-bar-info" style="width: 0">
					<span>0%</span>
				</div>
			</div>

			<pre id="import-log"></pre>

			<div id="next-action" class="hidden row">
				<div class="col-lg-offset-8 col-lg-4 col-md-offset-6 col-md-6 col-sm-offset-2 col-sm-8">
					<a href="pageAssignOwners.php" class="btn btn-success btn-lg btn-block"><i class="glyphicon glyphicon-user"></i> <?php echo "{$this->lang['next']}: {$this->lang['assign a records owner']}"; ?></a>
				</div>
			</div>

			<div id="aborted" class="hidden alert-danger"><?php echo $this->lang['csv file upload error']; ?></div>

			<script>
				$j(function() {
					var add_log = function(log_message) {
						if(!log_message) return;

						var import_log = $j("#import-log");
						import_log.append(log_message + '\n');
						import_log.scrollTop(import_log.prop("scrollHeight"));
					}

					/* function to update progress */
					var update_progress = function(percent, log_message, status_class) {
						if(isNaN(percent)) percent = 0;

						/* limit to integer values between 0 and 100 */
						var p = Math.max(0, Math.min(parseInt(percent), 100));
						$j('#import-progress').css({ width: p + '%' });
						$j('#import-progress span').html(p + '%');

						if(status_class != undefined) {
							$j('#import-progress')
								.removeClass('progress-bar-success progress-bar-danger progress-bar-warning progress-bar-info progress-bar-primary')
								.addClass('progress-bar-' + status_class);
						}

						if(status_class == 'danger' || p == 100) {
							$j('#import-progress').removeClass('active');
						}

						if(log_message != undefined) add_log(log_message);
					}

					/**
					 *  function to trigger importing of a batch
					 *  
					 *  @param progress { total, failed, imported, retries }
					 *  @param callbacks { completed, aborted }
					 *  
					 *  @return Return_Description
					 */
					var import_batch = function(progress, callbacks) {
						// if first param is functions
						if(progress !== undefined) {
							if($j.isFunction(progress.completed) || $j.isFunction(progress.aborted)) {
								// we assume it's the callbacks
								callbacks = progress;
								progress = undefined;
							}
						}

						progress = progress || { total: 0, failed: 0, imported: 0, retries: 0 };

						var url = window.location.pathname + window.location.search.replace(/show_import_progress/, 'import');

						$j.ajax({
							url: url
						}).done(function(data) {
							/* data: { imported, failed, remaining, logs[] } */
							if(undefined == data.imported) data.imported = 0;
							if(undefined == data.failed) data.failed = 0;
							if(undefined == data.remaining) data.remaining = 0;
							if(undefined == data.logs) data.logs = [];

							if(!progress.total) progress.total = data.imported + data.failed + data.remaining;

							/* finished importing? */
							if(progress.total <= 0 || data.remaining <= 0) {
								update_progress(100, '<b class="text-success"><?php echo html_attr($this->lang['finished status']); ?></b>', 'success');
								if(callbacks !== undefined && $j.isFunction(callbacks.completed)) {
									callbacks.completed();
								}
								return;
							}
							progress.failed += data.failed;
							progress.imported += data.imported;
							progress.retries = 0;

							update_progress((progress.failed + progress.imported) / progress.total * 100, data.logs.join('\n'));
							import_batch(progress, callbacks);
						}).fail(function() {
							/* if ajax failed, retry up to 10 times, with 10 seconds in-between then fail */
							if(progress.retries < 10) {
								progress.retries++;
								update_progress((progress.failed + progress.imported) / progress.total * 100, <?php echo json_encode(str_replace('<SECONDS>', '10', $this->lang['connection failed retrying'])); ?>, 'warning');
								setTimeout(function() { import_batch(progress, callbacks); }, 3000);
								return;
							} else {
								/* fail and abort importing process */
								update_progress((progress.failed + progress.imported) / progress.total * 100, <?php echo json_encode($this->lang['connection failed timeout']); ?>, 'danger');
								if(callbacks !== undefined && $j.isFunction(callbacks.aborted)) {
									callbacks.aborted();
								}
							}                          
						});
					}

					/* adjust import-log height based on window height */
					$j(window).resize(function() {
						$j('#import-log').height($j(window).height() * 0.4);
					}).resize();

					add_log('<b class="text-warning"><?php echo html_attr($this->lang['please wait and do not close']); ?></b>');
					import_batch({
						completed: function() {
							$j('#next-action').removeClass('hidden');
						},
						aborted: function() {
							$j('#aborted').removeClass('hidden');
						}
					});
				})
			</script>

			<style>
				#import-log{
					overflow: auto;
				}
			</style>
			<?php
			echo $this->footer();
		}

		protected function header() {
			$Translation = $this->lang;
			ob_start();
			$GLOBALS['page_title'] = $Translation['importing CSV data'];
			include("{$this->curr_dir}/incHeader.php");

			return ob_get_clean();
		}

		protected function footer() {
			$Translation = $this->lang;
			ob_start();
			include("{$this->curr_dir}/incFooter.php");

			return ob_get_clean();
		}

		/**
		 *  @brief UTF8-encodes a string/array
		 *  @see https://stackoverflow.com/a/26760943/1945185
		 *  
		 *  @param [in] $mixed string or array of strings to be UTF8-encoded
		 *  @return UTF8-encoded array/string
		 */
		protected function utf8ize($mixed) {
			if(!is_array($mixed)) return to_utf8($mixed);

			foreach($mixed as $key => $value) {
				$mixed[$key] = $this->utf8ize($value);
			}
			return $mixed;
		}
	}
