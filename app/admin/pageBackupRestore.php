<?php
	$admin_dir = dirname(__FILE__);
	require("{$admin_dir}/incCommon.php");

	$GLOBALS['DEBUG_MODE'] = false;
	$csv = new Backup($_REQUEST);

	class Backup{
		private $curr_dir,
				$curr_page,
				$lang, /* translation text */
				$request, /* assoc array that stores $_REQUEST */
				$error_back_link,
				$backup_log,
				$initial_ts; /* initial timestamp */

		public function __construct($request = array()){
			global $Translation;

			$this->curr_dir = dirname(__FILE__);
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
			if(!$this->create_backup_folder() && is_xhr()){
				@header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error");
				return;
			}

			/* process request to retrieve $this->request, and then execute the requested action */
			$this->process_request($request);
			$out = call_user_func_array(array($this, $this->request['action']), array());
			if($out === true || $out === false){
				echo $this->backup_log;
				if(!$out) @header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error");
				return;
			}
			echo $out;
		}

		protected function debug($msg, $html = true){
			if($GLOBALS['DEBUG_MODE'] && $html) return "<pre>DEBUG: {$msg}</pre>";
			if($GLOBALS['DEBUG_MODE']) return " [DEBUG: {$msg}] ";
			return '';
		}

		protected function elapsed(){
			return number_format(microtime(true) - $this->initial_ts, 3);
		}

		protected function process_request($request){
			/* action must be a valid controller, else set to default (main) */
			$controller = isset($request['action']) ? $request['action'] : false;
			if(!in_array($controller, $this->controllers())) $request['action'] = 'main';

			$this->request = $request;
		}

		/**
		 *  discover the public functions in this class that can act as controllers
		 *  
		 *  @return array of public function names
		 */
		protected function controllers(){
			$csv = new ReflectionClass($this);
			$methods = $csv->getMethods(ReflectionMethod::IS_PUBLIC);

			$controllers = array();
			foreach($methods as $mthd){
				$controllers[] = $mthd->name;
			}

			return $controllers;
		}

		protected function request_or($var, $default){
			return (isset($this->request[$var]) ? $this->request[$var] : $default);
		}

		protected function header(){
			$Translation = $this->lang;
			ob_start();
			$GLOBALS['page_title'] = $Translation['database backups'];
			include("{$this->curr_dir}/incHeader.php");
			$out = ob_get_contents();
			ob_end_clean();

			return $out;
		}

		protected function footer(){
			$Translation = $this->lang;
			ob_start();
			include("{$this->curr_dir}/incFooter.php");
			$out = ob_get_contents();
			ob_end_clean();

			return $out;
		}

		/**
		 *  @brief UTF8-encodes a string/array
		 *  @see https://stackoverflow.com/a/26760943/1945185
		 *  
		 *  @param [in] $mixed string or array of strings to be UTF8-encoded
		 *  @return UTF8-encoded array/string
		 */
		protected function utf8ize($mixed) {
			if(is_array($mixed)){
				foreach($mixed as $key => $value){
					$mixed[$key] = $this->utf8ize($value);
				}
			}elseif(is_string($mixed)){
				return utf8_encode($mixed);
			}
			return $mixed;
		}

		/**
		 *  @brief Retrieves and validates user-specified md5_hash and checks if it matches a backup file
		 *  
		 *  @return False on error, backup file full path on success.
		 */
		protected function get_specified_backup_file(){
			$md5_hash = $this->request['md5_hash'];
			if(!preg_match('/^[a-f0-9]{32}$/i', $md5_hash)) return false;

			$bfile = "{$this->curr_dir}/backups/{$md5_hash}.sql";
			if(!is_file($bfile)) return false;

			return $bfile;
		}

		/**
		 * function to show main page
		 */
		public function main(){
			ob_start();

			echo $this->header();

			$can_backup = $this->create_backup_folder();

			?>
			<div class="page-header"><h1><?php echo $this->lang['database backups']; ?></h1></div>

			<div id="in-page-notifications"></div>
			<script>
				/* move notifications below page title */
				$j(function(){
					$j('.notifcation-placeholder').appendTo('#in-page-notifications');
				})
			</script>

			<?php
				echo Notification::show(array(
					'message' => '<i class="glyphicon glyphicon-info-sign"></i> ' . $this->lang['about backups'],
					'class' => 'info',
					'dismiss_days' => 30,
					'id' => 'info-about-backups'
				));

				if(!$can_backup){
					echo Notification::show(array(
						'message' => $this->lang['cant create backup folder'],
						'class' => 'danger',
						'dismiss_seconds' => 900
					));
				}
			?>

			<?php if($can_backup){ ?>
				<button type="button" class="vspacer-lg btn btn-primary btn-lg" id="create-backup"><i class="glyphicon glyphicon-plus"></i> <?php echo $this->lang['create backup file']; ?></button>
				<pre id="backup-log" class="hidden"></pre>

				<h2><?php echo $this->lang['available backups']; ?></h2>
				<div id="backup-files-list"></div>

				<style>
					.backup-file{
						border-bottom: solid 1px #aaa;
						padding: .75em;
						margin: 0;
					}
				</style>
				<script>
					$j(function(){
						/* language strings */
						var create_backup = '<?php echo html_attr($this->lang['create backup file']); ?>';
						var please_wait = '<?php echo html_attr($this->lang['please wait']); ?>';
						var finished = '<?php echo html_attr($this->lang['done!']); ?>';
						var error = '<?php echo html_attr($this->lang['error']); ?>';
						var no_matches = '<?php echo html_attr($this->lang['no backups found']); ?>';
						var restore_backup = '<?php echo html_attr($this->lang['restore backup']); ?>';
						var delete_backup = '<?php echo html_attr($this->lang['delete backup']); ?>';
						var confirm_backup = '<?php echo html_attr($this->lang['confirm backup']); ?>';
						var confirm_restore = '<?php echo html_attr($this->lang['confirm restore']); ?>';
						var confirm_delete = '<?php echo html_attr($this->lang['confirm delete backup']); ?>';
						var backup_restored = '<?php echo html_attr($this->lang['backup restored']); ?>';
						var backup_deleted = '<?php echo html_attr($this->lang['backup deleted']); ?>';
						var delete_error = '<?php echo html_attr($this->lang['backup delete error']); ?>';
						var restore_error = '<?php echo html_attr($this->lang['restore error']); ?>';

						var page = '<?php echo $this->curr_page; ?>';
						var backup_files_list = $j('#backup-files-list');

						var clear_list = function(){
							backup_files_list.html('<div class="alert alert-warning">' + no_matches + '</div>');
						}

						var display_backups = function(){
							$j.ajax({
								url: page,
								data: { action: 'get_backup_files' },
								success: function(resp){
									try{
										var list = JSON.parse(resp);
										if(list.constructor !== Array) throw 'not a list of files';

										backup_files_list.html('');
										for(var i = 0; i < list.length; i++){
											backup_files_list.append(
												'<h4 class="hspacer-lg backup-file">' + 
													'<div class="btn-group hspacer-lg">' +
														'<button type="button" class="btn btn-default restore" data-md5_hash="' + list[i].md5_hash + '"><i class="glyphicon glyphicon-download-alt"></i> ' + restore_backup + '</button>' +
														'<button type="button" class="btn btn-default delete" data-md5_hash="' + list[i].md5_hash + '"><i class="glyphicon glyphicon-trash"></i> ' + delete_backup + '</button>' +
													'</div>' +
													list[i].datetime +
													' (' + list[i].size + ' KB) ' +
												'</h4>'
											);
										}
									}catch(e){
										clear_list();
									}
								},
								error: clear_list
							});
						};

						backup_files_list
							.on('click', '.restore', function(){
								/* confirm restore */
								if(!confirm(confirm_restore)) return;

								$j.ajax({
									url: page,
									data: { action: 'restore', md5_hash: $j(this).data('md5_hash') },
									success: function(){
										show_notification({
											message: backup_restored,
											class: 'success',
											dismiss_seconds: 30
										});
									},
									error: function(){
										show_notification({
											message: restore_error,
											class: 'danger',
											dismiss_seconds: 30
										});
									},
									complete: display_backups
								});
							})
							.on('click', '.delete', function(){
								/* confirm delete backup */
								if(!confirm(confirm_delete)) return;

								$j.ajax({
									url: page,
									data: { action: 'delete', md5_hash: $j(this).data('md5_hash') },
									success: function(){
										show_notification({
											message: backup_deleted,
											class: 'success',
											dismiss_seconds: 30
										});
									},
									error: function(){
										show_notification({
											message: delete_error,
											class: 'danger',
											dismiss_seconds: 30
										});
									},
									complete: display_backups
								});
							})
							.on('mouseover', 'h4', function(){
								$j(this).addClass('bg-warning');
							})
							.on('mouseout', 'h4', function(){
								$j(this).removeClass('bg-warning');
							});

						$j('#create-backup').click(function(){
							if(!confirm(confirm_backup)) return;

							$j('#backup-log').html('').addClass('hidden');

							var btn = $j(this);
							btn.addClass('btn-warning').prop('disabled', true).html('<i class="glyphicon glyphicon-hourglass"></i> ' + please_wait);
							$j.ajax({
								url: page,
								data: { action: 'create_backup' },
								success: function(){
									btn.removeClass('btn-warning btn-primary').addClass('btn-success').html('<i class="glyphicon glyphicon-ok"></i> ' + finished);
								},
								error: function(){
									btn.removeClass('btn-warning btn-primary').addClass('btn-danger').html('<i class="glyphicon glyphicon-remove"></i> ' + error);
								},
								complete: function(jx){
									if(jx.responseText.length > 0) $j('#backup-log').html(jx.responseText).removeClass('hidden');
									display_backups();
									setTimeout(function(){
										btn.removeClass('btn-danger btn-warning').addClass('btn-primary').prop('disabled', false).html('<i class="glyphicon glyphicon-plus"></i> ' + create_backup);
									}, 10000);
								}
							});
						});

						/* keep removing dismissed notifications from DOM */
						setInterval(function(){
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
		 *  @brief Retrieve a list of available backup files, with dates and times
		 *  
		 *  @return Array of backup files [[md5_hash => '', datetime => 'y-m-d H:i:s', size => '659888'], ..]
		 *  
		 *  @details Backup files are those found in the folder 'backups' named as an md5 hash with .sql extension.
		 */
		public function get_backup_files(){
			$bdir = $this->curr_dir . '/backups';
			$d = dir($bdir);
			if(!$d) return false;
			$admin_cfg = config('adminConfig');
			$dtf = $admin_cfg['PHPDateTimeFormat'];

			$list = array();

			while(false !== ($entry = $d->read())){
				if(!preg_match('/^[a-f0-9]{32}\.sql$/i', $entry)) continue;
				$fts = @filemtime("{$bdir}/{$entry}");
				$list[$fts] = array(
					'md5_hash' => substr($entry, 0, 32),
					'datetime' => date($dtf, $fts),
					'size' => number_format(@filesize("{$bdir}/{$entry}") / 1024)
				);
			}

			$d->close();
			if(!count($list)) return false;

			krsort($list);
			return json_encode(array_values($list));
		}

		/**
		 *  @brief create a new backup file
		 *  
		 *  @return Boolean indicating success or failure
		 *  
		 *  @details Uses mysqldump (if available) to create a new backup file
		 */
		public function create_backup(){
			$config = array('dbServer' => '', 'dbUsername' => '', 'dbPassword' => '', 'dbDatabase' => '');
			foreach($config as $k => $v) $config[$k] = escapeshellarg(config($k));

			$dump_file = $this->curr_dir . '/backups/' . md5(microtime()) . '.sql';
			$out = array(); $ret = 0;
			maintenance_mode(true);
			@exec("(mysqldump -u{$config['dbUsername']} -p{$config['dbPassword']} -h{$config['dbServer']} {$config['dbDatabase']} > {$dump_file}) 2>&1", $out, $ret);
			$this->backup_log = implode("\n", $out);
			maintenance_mode(false);

			if($ret) return false;

			return true;
		}

		/**
		 *  @brief Restores a given backup file
		 *  
		 *  @return Boolean indicating success or failure
		 *  
		 *  @details Overwrites existing data in the database, including users and groups.
		 */
		public function restore(){
			$bfile = $this->get_specified_backup_file();
			if(!$bfile) return false;

			$config = array('dbServer' => '', 'dbUsername' => '', 'dbPassword' => '', 'dbDatabase' => '');
			foreach($config as $k => $v) $config[$k] = config($k);

			$out = $ret = null;
			maintenance_mode(true);
			$cmd = "mysql -u{$config['dbUsername']} -p{$config['dbPassword']} -h{$config['dbServer']} {$config['dbDatabase']} < {$bfile}";
			@exec($cmd, $out, $ret);
			maintenance_mode(false);

			if($ret){ echo $cmd; return false; }

			return true;
		}

		/**
		 *  @brief Deletes a given backup file
		 *  
		 *  @return Boolean indicating success or failure
		 */
		public function delete(){
			$bfile = $this->get_specified_backup_file();
			if(!$bfile) return false;

			return @unlink($bfile);
		}

		protected function create_backup_folder(){
			$bdir = $this->curr_dir . '/backups';
			if(!is_dir($bdir))
				if(!@mkdir($bdir)) return false;

			/* create .htaccess file preventing direct download of backup files */
			if(!is_file("{$bdir}/.htaccess"))
				@file_put_contents("{$bdir}/.htaccess", 
					"<FilesMatch \"\\.(sql)\$\">\n" .
					"   Order allow,deny\n" .
					"</FilesMatch>" 
				);

			/* create index.html empty file to prevent directory browsing */
			if(!is_file("{$bdir}/index.html")) @touch("{$bdir}/index.html");

			return true;
		}
	}
