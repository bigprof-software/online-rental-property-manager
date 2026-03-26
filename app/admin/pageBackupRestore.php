<?php
	require(__DIR__ . '/incCommon.php');

	$csv = new Backup($_REQUEST);

	class Backup{
		private $curr_dir,
				$cmd = '',
				$curr_page = '',
				$lang = [], /* translation text */
				$request = [], /* assoc array that stores $_REQUEST */
				$error_back_link = '',
				$backup_log = '',
				$initial_ts = 0; /* initial timestamp */

		public function __construct($request = []) {
			global $Translation;

			$this->curr_dir = __DIR__;
			$this->curr_page = basename(__FILE__);
			$this->initial_ts = microtime(true);
			$this->lang = $Translation;

			/* back link to use in errors */
			$this->error_back_link = '' .
				'<div class="text-center vspacer-lg"><a href="' . $this->curr_page . '" class="btn btn-danger btn-lg">' .
					'<i class="glyphicon glyphicon-chevron-left"></i> ' .
					$this->lang['back and retry'] .
				'</a></div>';

			/* create backup folder if needed */
			if(!$this->create_backup_folder() && is_xhr()) {
				@header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error");
				return;
			}

			/* process request to retrieve $this->request, and then execute the requested action */
			$this->process_request($request);
			$out = call_user_func_array([$this, $this->request['action']], []);
			if($out === true || $out === false) {
				echo $this->cmd . "\n\n" . $this->backup_log;
				if(!$out) @header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error");
				return;
			}
			echo $out;
		}

		protected function debug($msg, $html = true) {
			if(DEBUG_MODE && $html) return "<pre>DEBUG: {$msg}</pre>";
			if(DEBUG_MODE) return " [DEBUG: {$msg}] ";
			return '';
		}

		protected function elapsed() {
			return number_format(microtime(true) - $this->initial_ts, 3);
		}

		protected function process_request($request) {
			/* action must be a valid controller, and CSRF token valid, else set to default (main) */
			$controller = isset($request['action']) && csrf_token(true) ? $request['action'] : false;
			if(!in_array($controller, $this->controllers())) $request['action'] = 'main';

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

		protected function request_or($var, $default) {
			return (isset($this->request[$var]) ? $this->request[$var] : $default);
		}

		protected function header() {
			$Translation = $this->lang;
			ob_start();
			$GLOBALS['page_title'] = $Translation['database backups'];
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
		 *  UTF8-encodes a string/array
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

		/**
		 *  Retrieves and validates user-specified md5_hash and checks if it matches a backup file
		 *
		 *  @return False on error, backup file full path on success.
		 */
		protected function get_specified_backup_file() {
			$md5_hash = $this->request['md5_hash'];
			if(!preg_match('/^[a-f0-9]{17,32}$/i', $md5_hash)) return false;

			$bfile = "{$this->curr_dir}/backups/{$md5_hash}.sql";
			if(!is_file($bfile)) return false;

			return $bfile;
		}

		/**
		 * function to show main page
		 */
		public function main() {
			ob_start();

			echo $this->header();

			$can_backup = $this->create_backup_folder();

			?>
			<div class="page-header"><h1><?php echo $this->lang['database backups']; ?></h1></div>

			<div id="in-page-notifications"></div>
			<script>
				/* move notifications below page title */
				$j(function() {
					$j('.notifcation-placeholder').appendTo('#in-page-notifications');
				})
			</script>

			<?php
				echo csrf_token();

				echo Notification::show([
					'message' => '<i class="glyphicon glyphicon-info-sign"></i> ' . $this->lang['about backups'],
					'class' => 'info',
					'dismiss_days' => 30,
					'id' => 'info-about-backups'
				]);

				if(!$can_backup) {
					echo Notification::show([
						'message' => $this->lang['cant create backup folder'],
						'class' => 'danger',
						'dismiss_seconds' => 900
					]);
				}
			?>

			<?php if($can_backup) { ?>
				<div class="well">
					<i class="glyphicon glyphicon-info-sign"></i> <?php echo $this->lang['backup restore commands hint']; ?>
				</div>

				<button type="button" class="vspacer-lg btn btn-primary btn-lg" id="create-backup"><i class="glyphicon glyphicon-plus"></i> <?php echo $this->lang['create backup file']; ?></button>
				<a href="#" id="toggle-backup-options" class="hspacer-lg"><i class="glyphicon glyphicon-cog"></i> <?php echo $this->lang['choose what to backup']; ?></a>

				<div id="backup-options" class="well vspacer-lg hidden">
					<h4><?php echo $this->lang['backup table groups']; ?></h4>
					<div class="checkbox">
						<label class="text-bold"><input type="checkbox" name="backup_group[]" value="data" checked> <?php echo $this->lang['backup group data tables']; ?></label>
					</div>
					<div class="checkbox">
						<label class="text-bold"><input type="checkbox" name="backup_group[]" value="membership" checked> <?php echo $this->lang['backup group membership']; ?></label>
					</div>
					<div class="checkbox">
						<label class="text-bold"><input type="checkbox" name="backup_group[]" value="records_ownership" checked> <?php echo $this->lang['backup group records ownership']; ?></label>
					</div>
					<div class="checkbox">
						<label class="text-muted"><input type="checkbox" name="backup_group[]" value="query_logs"> <?php echo $this->lang['backup group query logs']; ?></label>
					</div>
					<div class="checkbox">
						<label class="text-muted"><input type="checkbox" name="backup_group[]" value="temp"> <?php echo $this->lang['backup group temp tables']; ?></label>
					</div>
				</div>

				<pre id="backup-log" class="hidden text-left" dir="ltr"></pre>

				<h2><?php echo $this->lang['available backups']; ?></h2>
				<div id="backup-files-list"></div>

				<style>
					.backup-file {
						border-bottom: solid 1px #aaa;
						padding: .75em;
						margin: 0;
					}
					.group-tag {
						font-size: .65em;
						vertical-align: middle;
						font-weight: normal;
					}
				</style>
				<script>
					$j(function() {
						/* language strings */
						const create_backup = AppGini.Translate._map['create backup file'],
							please_wait = AppGini.Translate._map['please wait'],
							finished = AppGini.Translate._map['done!'],
							error_title = AppGini.Translate._map['error'],
							no_matches = AppGini.Translate._map['no backups found'],
							restore_backup = AppGini.Translate._map['restore backup'],
							delete_backup = AppGini.Translate._map['delete backup'],
							download_backup = AppGini.Translate._map['download'],
							confirm_backup = AppGini.Translate._map['confirm backup'],
							confirm_restore = AppGini.Translate._map['confirm restore'],
							confirm_delete = AppGini.Translate._map['confirm delete backup'],
							backup_restored = AppGini.Translate._map['backup restored'],
							backup_deleted = AppGini.Translate._map['backup deleted'],
							delete_error = AppGini.Translate._map['backup delete error'],
							restore_error = AppGini.Translate._map['restore error'],
							select_at_least_one = AppGini.Translate._map['select at least one group'],
							group_data = AppGini.Translate._map['backup group data tables'],
							group_membership = AppGini.Translate._map['backup group membership'],
							group_records_ownership = AppGini.Translate._map['backup group records ownership'],
							group_query_logs = AppGini.Translate._map['backup group query logs'],
							group_temp = AppGini.Translate._map['backup group temp tables'];

						var group_labels = {
							'data': group_data,
							'membership': group_membership,
							'records_ownership': group_records_ownership,
							'query_logs': group_query_logs,
							'temp': group_temp
						};

						var page = '<?php echo $this->curr_page; ?>';
						var backup_files_list = $j('#backup-files-list');

						/* Toggle backup options */
						$j('#toggle-backup-options').click(function(e) {
							e.preventDefault();
							$j('#backup-options').toggleClass('hidden');
						});

						/* Check if at least one group is selected */
						var check_backup_groups = function() {
							var checked = $j('input[name="backup_group[]"]:checked').length;
							$j('#create-backup').prop('disabled', checked === 0);
							return checked > 0;
						};

						/* Listen to checkbox changes */
						$j('input[name="backup_group[]"]').on('change', check_backup_groups);

						var clear_list = function() {
							backup_files_list.html('<div class="alert alert-warning">' + no_matches + '</div>');
						}

						var display_backups = function() {
							$j.ajax({
								url: page,
								data: { action: 'get_backup_files', csrf_token: $j('#csrf_token').val() },
								success: function(resp) {
									try{
										var list = JSON.parse(resp);
										if(list.constructor !== Array) throw 'not a list of files';

										backup_files_list.html('');
										for(var i = 0; i < list.length; i++) {
											/* Build group labels */
											var group_tags = '';
											if(list[i].groups && list[i].groups.length > 0) {
												for(var j = 0; j < list[i].groups.length; j++) {
													var group = list[i].groups[j];
													var label_text = group_labels[group] || group;
													group_tags += '<span class="label label-info hspacer-sm group-tag">' + label_text + '</span>';
												}
											}

											backup_files_list.append(
												'<h4 class="hspacer-lg backup-file">' +
													'<div class="btn-group hspacer-lg">' +
														'<button type="button" class="btn btn-default download" data-md5_hash="' + list[i].md5_hash + '"><i class="glyphicon glyphicon-download-alt"></i> ' + download_backup + '</button>' +
														'<button type="button" class="btn btn-default restore" data-md5_hash="' + list[i].md5_hash + '"><i class="glyphicon glyphicon-upload"></i> ' + restore_backup + '</button>' +
														'<button type="button" class="btn btn-default delete" data-md5_hash="' + list[i].md5_hash + '"><i class="glyphicon glyphicon-trash"></i> ' + delete_backup + '</button>' +
													'</div>' +
													`<span dir="ltr">${list[i].datetime} (${list[i].size} KB)</span>` +
													(group_tags ? ' ' + group_tags : '') +
												'</h4>'
											);
										}
									}catch(e) {
										clear_list();
									}
								},
								error: clear_list
							});
						};

						backup_files_list
							.on('click', '.restore', function() {
								/* confirm restore */
								if(!confirm(confirm_restore)) return;

								$j.ajax({
									url: page,
									data: { action: 'restore', md5_hash: $j(this).data('md5_hash'), csrf_token: $j('#csrf_token').val() },
									success: function() {
										show_notification({
											message: backup_restored,
											class: 'success',
											dismiss_seconds: 30
										});
									},
									error: function(xhr, status, error) {
										show_notification({
											message: `<b>${restore_error}</b><pre>${xhr.responseText || error}</pre>`,
											class: 'danger',
											dismiss_seconds: 120
										});
									},
									complete: display_backups
								});
							})
							.on('click', '.download', function() {
								/* download backup file */
								var md5_hash = $j(this).data('md5_hash');
								window.location.href = 'utilDownloadBackup.php?md5_hash=' + md5_hash + '&csrf_token=' + $j('#csrf_token').val();
							})
							.on('click', '.delete', function() {
								/* confirm delete backup */
								if(!confirm(confirm_delete)) return;

								$j.ajax({
									url: page,
									data: { action: 'delete', md5_hash: $j(this).data('md5_hash'), csrf_token: $j('#csrf_token').val() },
									success: function() {
										show_notification({
											message: backup_deleted,
											class: 'success',
											dismiss_seconds: 30
										});
									},
									error: function(xhr, status, error) {
										show_notification({
											message: `<b>${delete_error}</b><pre>${xhr.responseText || error}</pre>`,
											class: 'danger',
											dismiss_seconds: 120
										});
									},
									complete: display_backups
								});
							})
							.on('mouseover', 'h4', function() {
								$j(this).addClass('bg-warning');
							})
							.on('mouseout', 'h4', function() {
								$j(this).removeClass('bg-warning');
							});

						$j('#create-backup').click(function() {
							if(!check_backup_groups()) {
								alert(select_at_least_one);
								return;
							}

							if(!confirm(confirm_backup)) return;

							$j('#backup-log').html('').addClass('hidden');

							/* Get selected table groups */
							var backup_groups = [];
							$j('input[name="backup_group[]"]:checked').each(function() {
								backup_groups.push($j(this).val());
							});

							var btn = $j(this);
							btn.addClass('btn-warning').prop('disabled', true).html('<i class="glyphicon glyphicon-hourglass"></i> ' + please_wait);
							$j.ajax({
								url: page,
								data: {
									action: 'create_backup',
									csrf_token: $j('#csrf_token').val(),
									backup_groups: backup_groups.join(',')
								},
								success: function() {
									btn.removeClass('btn-warning btn-primary').addClass('btn-success').html('<i class="glyphicon glyphicon-ok"></i> ' + finished);
									/* Collapse backup options after successful backup */
									$j('#backup-options').addClass('hidden');
								},
								error: function(xhr, status, error) {
									btn
										.removeClass('btn-warning btn-primary')
										.addClass('btn-danger')
										.html('<i class="glyphicon glyphicon-remove"></i> ' + error_title);
								},
								complete: function(jx) {
									if(jx.responseText.length > 0) $j('#backup-log').html(jx.responseText).removeClass('hidden');
									display_backups();
									setTimeout(function() {
										btn.removeClass('btn-danger btn-warning').addClass('btn-primary').prop('disabled', false).html('<i class="glyphicon glyphicon-plus"></i> ' + create_backup);
									}, 10000);
								}
							});
						});

						/* keep removing dismissed notifications from DOM */
						setInterval(function() {
							$j('.notifcation-placeholder .invisible').remove();
						}, 1000);

						display_backups();
					})
				</script>
			<?php } ?>

			<?php
			echo $this->footer();

			$html = ob_get_clean();

			return $html;
		}

		/**
		 *  Retrieve a list of available backup files, with dates and times
		 *
		 *  @return Array of backup files [[md5_hash => '', datetime => 'y-m-d H:i:s', size => '659888', 'groups' => []], ..]
		 *
		 *  @details Backup files are those found in the folder 'backups' named as an md5 hash with .sql extension.
		 */
		public function get_backup_files() {
			$bdir = $this->curr_dir . '/backups';
			$d = dir($bdir);
			if(!$d) return false;
			$admin_cfg = config('adminConfig');
			$dtf = $admin_cfg['PHPDateTimeFormat'];

			$list = [];

			while(false !== ($entry = $d->read())) {
				if(!preg_match('/^[a-f0-9]{17,32}\.sql$/i', $entry)) continue;
				$fts = @filemtime("{$bdir}/{$entry}");

				/* Parse backup file to extract included groups */
				$included_groups = [];
				$file_path = "{$bdir}/{$entry}";
				$fp = @fopen($file_path, 'r');
				if($fp) {
					/* Read first few lines to find the comment */
					for($i = 0; $i < 10; $i++) {
						$line = fgets($fp);
						if($line === false) break;
						if(preg_match('/^-- Included groups: (.+)$/m', $line, $matches)) {
							$included_groups = array_filter(explode(',', trim($matches[1])));
							break;
						}
					}
					fclose($fp);
				}

				$list[$fts] = [
					'md5_hash' => substr($entry, 0, -4),
					'datetime' => date($dtf, $fts),
					'size' => number_format(@filesize("{$bdir}/{$entry}") / 1024),
					'groups' => $included_groups
				];
			}

			$d->close();
			if(!count($list)) return false;

			krsort($list);
			return json_encode(array_values($list));
		}

		/**
		 *  create a new backup file
		 *
		 *  @return Boolean indicating success or failure
		 *
		 *  @details Uses mysqldump (if available) to create a new backup file
		 */
		public function create_backup() {
			$config = ['dbServer' => '', 'dbUsername' => '', 'dbPassword' => '', 'dbDatabase' => '', 'dbPort' => ''];
			foreach($config as $k => $v) $config[$k] = escapeshellarg(config($k));

			$admin_cfg = config('adminConfig');
			$backup_cmd = isset($admin_cfg['dbBackupCommand']) ? $admin_cfg['dbBackupCommand'] : DB_BACKUP_COMMAND;

			/* Get selected backup groups */
			$backup_groups_str = $this->request_or('backup_groups', 'data,membership,records_ownership');
			$backup_groups = explode(',', $backup_groups_str);

			/* Define table groups */
			$data_tables = array_keys(getTableList());
			$membership_tables = ['membership_grouppermissions', 'membership_groups', 'membership_userpermissions', 'membership_users'];
			$records_ownership_tables = ['membership_userrecords', 'appgini_saved_filters'];
			$query_log_tables = ['appgini_query_log'];
			$temp_tables = ['appgini_csv_import_jobs', 'membership_cache', 'membership_usersessions'];

			/* Determine which tables to exclude */
			$exclude_tables = [];
			if(!in_array('data', $backup_groups)) {
				$exclude_tables = array_merge($exclude_tables, $data_tables);
			}
			if(!in_array('membership', $backup_groups)) {
				$exclude_tables = array_merge($exclude_tables, $membership_tables);
			}
			if(!in_array('records_ownership', $backup_groups)) {
				$exclude_tables = array_merge($exclude_tables, $records_ownership_tables);
			}
			if(!in_array('query_logs', $backup_groups)) {
				$exclude_tables = array_merge($exclude_tables, $query_log_tables);
			}
			if(!in_array('temp', $backup_groups)) {
				$exclude_tables = array_merge($exclude_tables, $temp_tables);
			}

			/* Build --ignore-table parameters */
			$ignore_params = '';
			$db_name = trim($config['dbDatabase'], "'");
			foreach($exclude_tables as $table) {
				$ignore_params .= " --ignore-table={$db_name}.{$table}";
			}

			$dump_file_path = normalize_path($this->curr_dir) . '/backups/' . substr(md5(microtime() . rand(0, 100000)), -17) . '.sql';
			$dump_file = escapeshellarg($dump_file_path);
			$pass_param = ($config['dbPassword'] ? " -p{$config['dbPassword']}" : '');
			$port_param = ($config['dbPort'] && $config['dbPort'] != ini_get('mysqli.default_port') ? " -P {$config['dbPort']}" : '');
			$this->cmd = "({$backup_cmd} -u{$config['dbUsername']}{$pass_param}{$port_param} -h{$config['dbServer']}{$ignore_params} {$config['dbDatabase']} -r {$dump_file}) 2>&1";

			maintenance_mode(true);
			$out = []; $ret = 0;
			@exec($this->cmd, $out, $ret);
			// redact password to avoid revealing it on error
			if($pass_param !== '') $this->cmd = str_replace($pass_param, ' -p**** ', $this->cmd);
			maintenance_mode(false);

			$this->backup_log = implode("\n", $out);
			if($ret) return false;

			/* Prepend comment indicating included groups (if cat command is available) */
			$included_groups = array_intersect(['data', 'membership', 'records_ownership', 'query_logs', 'temp'], $backup_groups);
			$excluded_groups = array_diff(['data', 'membership', 'records_ownership', 'query_logs', 'temp'], $backup_groups);
			$comment = "-- AppGini Backup\n";
			$comment .= "-- Included groups: " . implode(',', $included_groups) . "\n";
			$comment .= "-- Excluded groups: " . implode(',', $excluded_groups) . "\n\n";

			/* Check if cat command is available (Unix/Linux/Mac) */
			exec('which cat 2>/dev/null', $which_out, $which_ret);
			if($which_ret === 0) {
				// cat is available, prepend comment using fast system command
				$temp_file = $dump_file_path . '.tmp';
				@file_put_contents($temp_file, $comment);

				// Use cat to append backup to comment (much faster than PHP streaming)
				$cat_cmd = 'cat ' . escapeshellarg($dump_file_path) . ' >> ' . escapeshellarg($temp_file);
				exec($cat_cmd, $out, $ret);

				if($ret === 0) {
					// Replace original with temp file
					@unlink($dump_file_path);
					@rename($temp_file, $dump_file_path);
				} else {
					// Clean up on failure
					@unlink($temp_file);
				}
			}
			// If cat is not available (e.g., Windows), skip adding comment to avoid memory/performance issues

			return true;
		}

		/**
		 *  Restores a given backup file
		 *
		 *  @return Boolean indicating success or failure
		 *
		 *  @details Overwrites existing data in the database, including users and groups.
		 */
		public function restore() {
			$bfile = $this->get_specified_backup_file();
			if(!$bfile) return false;

			$config = ['dbServer' => '', 'dbUsername' => '', 'dbPassword' => '', 'dbDatabase' => '', 'dbPort' => ''];
			foreach($config as $k => $v) $config[$k] = escapeshellarg(config($k));

			$admin_cfg = config('adminConfig');
			$restore_cmd = isset($admin_cfg['dbRestoreCommand']) ? $admin_cfg['dbRestoreCommand'] : DB_RESTORE_COMMAND;

			$out = []; $ret = null;
			maintenance_mode(true);
			$pass_param = ($config['dbPassword'] ? " -p{$config['dbPassword']}" : '');
			$port_param = ($config['dbPort'] && $config['dbPort'] != ini_get('mysqli.default_port') ? " -P {$config['dbPort']}" : '');
			$cmd = "{$restore_cmd} -u{$config['dbUsername']}{$pass_param}{$port_param} -h{$config['dbServer']} {$config['dbDatabase']} < {$bfile} 2>&1";
			@exec($cmd, $out, $ret);
			// redact password to avoid revealing it on error
			if($pass_param !== '') $cmd = str_replace($pass_param, ' -p**** ', $cmd);
			maintenance_mode(false);

			if($ret) { echo $cmd . "\n" . implode("\n", $out); return false; }

			return true;
		}

		/**
		 *  Deletes a given backup file
		 *
		 *  @return Boolean indicating success or failure
		 */
		public function delete() {
			$bfile = $this->get_specified_backup_file();
			if(!$bfile) return false;

			return @unlink($bfile);
		}

		protected function create_backup_folder() {
			$bdir = $this->curr_dir . '/backups';
			if(!is_dir($bdir))
				if(!@mkdir($bdir)) return false;

			/* create .htaccess file preventing direct download of backup files */
			if(!is_file("{$bdir}/.htaccess"))
				@file_put_contents("{$bdir}/.htaccess",
					"<FilesMatch \"\\.(sql)\$\">\n" .
					"   Order allow,deny\n" .
					"   Deny from all\n" .
					"</FilesMatch>"
				);

			/* create index.html empty file to prevent directory browsing */
			if(!is_file("{$bdir}/index.html")) @touch("{$bdir}/index.html");

			return true;
		}
	}
