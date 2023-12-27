<?php
	require(__DIR__ . '/incCommon.php');

	/*
		Requests that can be handled by this file:
		1. normal GET request => display the HTML page
		2. Ajax POST request containing `email` and `orderNum` => perform order login, process response HTML to extract download links, parse plugin names from links, and send list of plugin names as JSON response
		3. Ajax POST request containing `installPlugin` which is a plugin name => install the plugin and send JSON response indicating success or failure with reason
		4. Ajax POST request containing `installPlugin` which is a plugin name and `confirmReinstall` => install the plugin, overwriting pre-installed files, and send JSON response indicating success or failure with reason
	*/

	// if this is a GET request, display the HTML page
	if($_SERVER['REQUEST_METHOD'] == 'GET')
		showPage(); // exits after displaying the page

	// validate csrf token
	if(!csrf_token(true))
		if(is_ajax())
			die(json_response($Translation['csrf token expired or invalid'], true));
		else
			die($Translation['csrf token expired or invalid']);

	// if we have an email and order number, handle order login logic
	if(Request::val('email') && Request::val('orderNum'))
		die(handleOrderLogin());

	// if we have a plugin name, handle plugin installation logic
	if(Request::val('installPlugin'))
		die(handlePluginInstall());

	// if we get here, we have an invalid request
	die('Invalid request');

	// function to display the HTML page
	function showPage() {
		global $Translation;
		$missing = missingDependencies();

		include(__DIR__ . '/incHeader.php');
		?>
		<div class="page-header"><h1>
			<small class="glyphicon glyphicon-download-alt"></small>
			<?php echo $Translation['Install a plugin']; ?>
		</h1></div>

		<?php if($missing) { ?>
			<div class="alert alert-danger">
				<p>
					<?php echo $Translation['missing dependencies']; ?>
				</p>
				<ul>
					<?php foreach($missing as $dep) { ?>
						<li><?php echo $dep; ?></li>
					<?php } ?>
				</ul>
			</div>
			<?php include(__DIR__ . '/incFooter.php');
		} ?>

		<!-- collapseable accordion with 3 panels representing 3 steps -->
		<div class="panel-group" id="accordion">

			<!-- step 1: order login form -->
			<div class="panel panel-default" id="step1">
				<div class="panel-heading">
					<h4 class="panel-title">
						<span class="glyphicon glyphicon-chevron-down"></span>
						<span class="glyphicon glyphicon-chevron-right"></span>
						<?php echo $Translation['step 1']; ?>
						<?php echo $Translation['plugin check instructions']; ?>
					</h4>
				</div>
				<div class="panel-body hidden">
					<!-- order login form -->
					<form id="order-login-form">
						<div class="alert alert-danger hidden" id="order-login-error"></div>

						<div class="form-group">
							<label for="email" class="control-label"><?php echo $Translation['email']; ?></label>
							<input type="email" class="form-control" id="email" name="email" required>
						</div>
						<div class="form-group">
							<label for="orderNum" class="control-label"><?php echo $Translation['order number']; ?></label>
							<input type="text" class="form-control" id="orderNum" name="orderNum" required>
						</div>
						<button type="button" class="btn btn-lg btn-primary">
							<span class="glyphicon glyphicon-refresh loop-rotate hidden"></span>
							<span class="glyphicon glyphicon-play-circle"></span>
							<?php echo $Translation['view available plugins']; ?>
						</button>
					</form>
				</div>
			</div>

			<!-- step 2: plugin selection -->
			<div class="panel panel-default" id="step2">
				<div class="panel-heading">
					<h4 class="panel-title">
						<span class="glyphicon glyphicon-chevron-down"></span>
						<span class="glyphicon glyphicon-chevron-right"></span>
						<?php echo $Translation['step 2']; ?>
						<?php echo $Translation['select plugins to install']; ?>
					</h4>
				</div>
				<div class="panel-body hidden"></div>
			</div>

			<!-- step 3: plugins installation progress -->
			<div class="panel panel-default" id="step3">
				<div class="panel-heading">
					<h4 class="panel-title">
						<span class="glyphicon glyphicon-chevron-down"></span>
						<span class="glyphicon glyphicon-chevron-right"></span>
						<?php echo $Translation['step 3']; ?>
						<?php echo $Translation['plugins install progress']; ?>
					</h4>
				</div>
				<div class="panel-body hidden"></div>
			</div>

		</div>

		<?php echo pageJS(); ?>

		<style>
			.panel-group { max-width: 70em; }
			.panel-title { padding: 1em; }
			.panel.expanded .panel-title > .glyphicon-chevron-down { display: inline; }
			.panel.expanded .panel-title > .glyphicon-chevron-right { display: none; }
			.panel:not(.expanded) .panel-title > .glyphicon-chevron-down { display: none; }
			.panel:not(.expanded) .panel-title > .glyphicon-chevron-right { display: inline; }

			.progress { height: 3em; }
			.progress-bar { line-height: 3em; }
			.progress-bar.text-left { padding-left: 1em; text-align: left !important; }
		</style>
		<?php
		include(__DIR__ . '/incFooter.php');
	}

	function pageJS() {
		global $Translation;

		ob_start(); ?>
		<script>
			$j(() => {
				const numSteps = $j('#accordion .panel').length;
				const csrf_token = '<?php echo csrf_token(false, true); ?>';

				const expandStep = (step) => {
					$j(`#step${step}`).addClass('expanded').children('.panel-body').removeClass('hidden');

					for(let i = 1; i <= numSteps; i++) {
						if(i === step) continue;
						$j(`#step${i}`).removeClass('expanded').children('.panel-body').addClass('hidden');
					}
				};

				const populatePlugins = (plugins) => {
					// populate step 2 with plugin names (plugin.name) and add plugin.url as data attribute
					$j('#step2 .panel-body').html(plugins.map(plugin => `
						<div class="checkbox">
							<label>
								<input type="checkbox" name="plugins" value="${plugin.slug}" data-url="${plugin.url}">
								${plugin.name}
								${plugin.installed ? `<span class="label label-warning">${'<?php echo $Translation['already installed']; ?>'.replace('%s', plugin.installDate)}</span>` : ''}
							</label>
						</div>
					`).join(''));

					// append 'Install selected plugins' button to step 2
					$j('#step2 .panel-body').append(`
						<button type="button" class="btn btn-lg btn-primary">
							<span class="glyphicon glyphicon-refresh loop-rotate hidden"></span>
							<span class="glyphicon glyphicon-play-circle"></span>
							<?php echo $Translation['install selected plugins']; ?>
						</button>
					`);

					expandStep(2);
				}

				// if email and order number are stored in localStorage, populate form with them
				$j('#email').val(localStorage.getItem('AppGini.plugins.email'));
				$j('#orderNum').val(localStorage.getItem('AppGini.plugins.orderNum'));

				// expand step 1 by default
				expandStep(1);

				// POST order login form on clicking the button of step 1
				$j(document).on('click', '#step1 button', () => {
					// get email and order number
					let email = $j('#email').val();
					let orderNum = $j('#orderNum').val();

					let hasError = false;

					// remove error status from email and order number fields
					$j('#email').parent().removeClass('has-error');
					$j('#orderNum').parent().removeClass('has-error');
					$j('#order-login-error').addClass('hidden');

					// if order number is not alpha-numeric and hyphens only, 5-20 characters, apply error status to order number field and return
					if(!/^[a-z0-9\-]{5,20}$/i.test(orderNum)) {
						$j('#orderNum').parent().addClass('has-error');
						$j('#order-login-error').html(`<?php echo $Translation['invalid order number']; ?>`).removeClass('hidden');
						$j('#orderNum')[0].focus();
						hasError = true;
					}

					// if email invalid, apply error status to email field and return
					if($j('#email')[0].checkValidity() === false) {
						$j('#email').parent().addClass('has-error');
						$j('#order-login-error').html(`<?php echo $Translation['invalid email']; ?>`).removeClass('hidden');
						$j('#email')[0].focus();
						hasError = true;
					}

					// if there's an error, return
					if(hasError) return;

					// show loading icon
					$j('#step1 button .glyphicon-play-circle').addClass('hidden');
					$j('#step1 button .glyphicon-refresh').removeClass('hidden');

					// POST the email and order number to this page
					$j.ajax({
						url: window.location.href,
						method: 'POST',
						data: { email, orderNum, csrf_token },
						dataType: 'json',
						success: resp => {
							// if response is an error, show error message and return
							if(resp.status === 'error') {
								$j('#order-login-error').html(resp.message).removeClass('hidden');
								return;
							}

							// if response is not an array, show error message and return
							if(!Array.isArray(resp.data)) {
								$j('#order-login-error').html(`<?php echo $Translation['invalid order login']; ?>`).removeClass('hidden');
								return;
							}

							// if response is an empty array, show error message and return
							if(!resp.data.length) {
								$j('#order-login-error').html(`<?php echo $Translation['no plugins available']; ?>`).removeClass('hidden');
								return;
							}

							// hide error message
							$j('#order-login-error').addClass('hidden');

							// store email and order number in localStorage for future use
							localStorage.setItem('AppGini.plugins.email', email);
							localStorage.setItem('AppGini.plugins.orderNum', orderNum);

							populatePlugins(resp.data);
						},
						error: (xhr, status, error) => {
							// parse response to see if it contains an error message
							let resp = JSON.parse(xhr.responseText);

							// if unable to parse, show default error message
							if(!resp || !resp.message) {
								$j('#order-login-error').html(`<?php echo $Translation['invalid order login']; ?>`).removeClass('hidden');
								return;
							}

							// show error message
							$j('#order-login-error').html(resp.message).removeClass('hidden');
						},
						complete: () => {
							// hide loading icon
							$j('#accordion .panel:nth-child(1) button .glyphicon-play-circle').removeClass('hidden');
							$j('#accordion .panel:nth-child(1) button .glyphicon-refresh').addClass('hidden');
						}
					});
				});

				// Send request to install selected plugins
				$j(document).on('click', '#step2 button', () => {
					// get selected plugins
					const plugins = $j('#step2 input[type="checkbox"]:checked');

					// if no plugins selected, return
					if(!plugins.length) return;

					expandStep(3);                    

					// POST each plugin separately
					plugins.each((i, plugin) => {
						plugin = $j(plugin);

						// get plugin slug and url
						const pluginSlug = plugin.val();
						const pluginUrl = plugin.data('url');
						const pluginName = plugin.parent().text().trim();

						// render an animated striped progress bar for this plugin (warning color)
						// after plugin is installed, stop animation and change color to green
						// if plugin fails to install, change color to red and show error message
						$j('#step3 .panel-body').append(`
							<div class="progress" id="${pluginSlug}-progress">
								<div class="progress-bar progress-bar-striped progress-bar-warning active" style="width: 100%;">
									<span class="glyphicon glyphicon-ok hidden"></span>
									<span class="glyphicon glyphicon-remove hidden"></span>
									<span class="text-bold">${pluginName}</span>
								</div>
							</div>
						`);

						const pluginError = (msg) => {
							$j(`#${pluginSlug}-progress .progress-bar`).removeClass('progress-bar-striped progress-bar-warning active').addClass('progress-bar-danger text-left');
							$j(`#${pluginSlug}-progress .progress-bar .glyphicon-remove`).removeClass('hidden');
							$j(`#${pluginSlug}-progress .progress-bar`).append(`<span class="text-italic hspacer-lg">${msg}</span>`);
						};

						// POST the plugin slug and url to this page
						$j.ajax({
							url: window.location.href,
							method: 'POST',
							data: { installPlugin: pluginSlug, pluginUrl, csrf_token },
							dataType: 'json',
							success: resp => {
								// if response is an error, show error message and return
								if(resp.status === 'error') {
									pluginError(resp.message);
									return;
								}

								// show success in progress bar
								$j(`#${pluginSlug}-progress .progress-bar`).removeClass('progress-bar-striped progress-bar-warning active').addClass('progress-bar-success text-left');
								$j(`#${pluginSlug}-progress .progress-bar .glyphicon-ok`).removeClass('hidden');
								$j(`#${pluginSlug}-progress .progress-bar`).append(`<span class="text-italic hspacer-lg">${<?php echo json_encode($Translation['plugin installed successfully']); ?>}</span>`);

								// set progress bar as a link to the plugin's page (open in new tab)
								$j(`#${pluginSlug}-progress .progress-bar`).wrap(`<a href="../plugins/${pluginSlug}" target="_blank"></a>`);
							},
							error: (xhr, status, error) => {
								// parse response to see if it contains an error message
								let resp = JSON.parse(xhr.responseText);

								// if unable to parse, show default
								if(!resp || !resp.message) {
									pluginError(`<?php echo $Translation['plugin install failed']; ?>`);
									return;
								}

								// show error message
								pluginError(resp.message);
							},
							complete: () => {
							}
						});
					});
				});
			})
		</script>
		<?php
		return ob_get_clean();
	}

	function missingDependencies() {
		$pluginsFolder = __DIR__ . '/../plugins';
		// create plugin folder if it doesn't exist
		if(!is_dir($pluginsFolder))
			@mkdir($pluginsFolder, 0777, true);

		$dependencies = [
			// ZipArchive is required for extracting zip files
			'ZipArchive not enabled' => class_exists('ZipArchive'),
			// cURL is required for making HTTP requests
			'cURL extension not enabled' => function_exists('curl_init'),
			// 'plugins' folder is writable
			'Plugins folder not writable' => is_writable($pluginsFolder),
		];

		// if all dependencies are met, return false
		if(!in_array(false, $dependencies, true))
			return false;

		// return missing dependencies
		return array_keys(array_filter($dependencies, function($v) { return !$v; }));
	}

	// function to handle order login logic
	function handleOrderLogin() {
		global $Translation;

		// get the email and order number
		$email = Request::val('email');
		$order = Request::val('orderNum');

		// validate the email
		if(!isEmail($email))
			die(json_response($Translation['invalid email'], true));

		// validate the order number (alpha-numeric and hyphens only, 5-20 characters)
		if(!preg_match('/^[a-z0-9\-]{5,20}$/i', $order))
			die(json_response($Translation['invalid order number'], true));

		// POST the email and order number to the order login page
		// first, set referer header to https://bigprof.com/appgini/download-plugins
		$headers = ['Referer' => 'https://bigprof.com/appgini/download-plugins'];
		$resp = httpRequest('https://bigprof.com/appgini/download-plugins', compact('email', 'order'), $headers, 'POST');
		if(empty($resp['body']))
			echo json_response($Translation['invalid order login'], true);

		// use regex to find .download-link lines, get next <a> tag (could be on same or next line), get href attribute value
		$regex = '/<div class="download-link">.*?<\/div>/s';
		preg_match_all($regex, $resp['body'], $matches);
		$links = [];
		foreach ($matches[0] as $match) {
			$regex = '/<a.*?href="([a-z0-9_\/\.]*)"/s';
			preg_match($regex, $match, $subMatches);
			if(!empty($subMatches[1]))
				$links[] = $subMatches[1];
		}

		// if no links found, return error
		if(!count($links))
			die(json_response($Translation['no plugins available'], true));

		// parse plugin names from links
		$plugins = [];
		foreach($links as $link) {
			// get plugin name from link
			$slug = basename($link, '.zip');
			$pluginName = ucfirst($slug);
			$pluginName = str_replace(['-', '_'], ' ', $pluginName);

			// plugin already installed?
			$infoFile = __DIR__ . "/../plugins/{$slug}/plugin-info.json";
			$installed = is_file($infoFile);
			$installDate = $installed ? date('Y-m-d', filemtime($infoFile)) : '';

			// if plugin name is not empty, add it to the list of plugins
			if($pluginName) $plugins[] = ['name' => $pluginName, 'url' => $link, 'installed' => $installed, 'installDate' => $installDate, 'slug' => $slug];
		}

		// if no plugins found, return error
		if(!count($plugins))
			die(json_response($Translation['no plugins available'], true));

		// return list of plugins
		die(json_response($plugins));
	}

	// function to handle plugin installation logic
	function handlePluginInstall() {
		global $Translation;

		// get the plugin name and url
		$pluginSlug = Request::val('installPlugin');
		$pluginUrl = 'https://bigprof.com' . Request::val('pluginUrl');

		// validate the plugin name (alpha-numeric and hyphens only, 5-20 characters)
		if(!preg_match('/^[a-z0-9\-_]{5,20}$/i', $pluginSlug))
			die(json_response($Translation['plugin install failed'] . ' LINE# ' . __LINE__, true));

		// validate the plugin url
		if(!filter_var($pluginUrl, FILTER_VALIDATE_URL))
			die(json_response($Translation['plugin install failed'] . ' LINE# ' . __LINE__, true));

		// create a tmp folder if it doesn't exist
		if(!is_dir(APP_DIR . '/tmp'))
			@mkdir(APP_DIR . '/tmp', 0777);

		// add .htaccess file to tmp folder to prevent directory access
		if(!is_file(APP_DIR . '/tmp/.htaccess'))
			file_put_contents(APP_DIR . '/tmp/.htaccess', 'deny from all');

		// download the plugin zip file
		if(!copy($pluginUrl, APP_DIR . "/tmp/{$pluginSlug}.zip"))
			die(json_response($Translation['plugin install failed'] . ' LINE# ' . __LINE__, true));

		$errorLog = [];
		$res = unzip(APP_DIR . "/tmp/{$pluginSlug}.zip", APP_DIR, $errorLog);

		// delete the zip file
		@unlink(APP_DIR . "/tmp/{$pluginSlug}.zip");

		// if unzip failed, return error
		// TODO: include errorLog
		if(!$res)
			die(json_response($Translation['plugin install failed'] . ' LINE# ' . __LINE__, true));

		// return success
		die(json_response($Translation['plugin installed successfully']));
	}

	function unzip($zipFilePath, $outputPath, &$errorLog) {
		$errorLog = [];

		// Check if zip extraction is supported by PHP
		if(!class_exists('ZipArchive')) {
			$errorLog[] = 'ZipArchive class not found';
			return false;
		}

		// Check if the output path is writable
		if(!is_writable($outputPath)) {
			$errorLog[] = 'Output path not writable';
			return false;
		}

		// Create a new instance of ZipArchive
		$zip = new ZipArchive;

		// Open the zip file
		if(!$zip->open($zipFilePath) === true) {
			$errorLog[] = 'Unable to open zip file';
			return false;
		}

		// Extract each file from the archive, and if newer than existing file (or no existing file), overwrite it
		for($i = 0; $i < $zip->numFiles; $i++) {
			$filename = $zip->getNameIndex($i);
			$extractedFile = rtrim($outputPath, '/') . '/' . $filename;

			// Check if extracted file is newer than existing file (or no existing file)
			if(is_file($extractedFile) && filemtime($extractedFile) >= $zip->statIndex($i)['mtime']) {
				$errorLog[] = "File {$filename} already exists, skipping ...";
				continue;
			}

			// Create the directories if necessary
			$dir = dirname($extractedFile);
			if(!is_dir($dir))
				@mkdir($dir, 0777, true);

			// Extract the file if dir exists
			if(!is_dir($dir)) {
				$dirError = "Unable to create subdirectory {$dir}";
				// if errorLog doesn't already contain this error, add it
				if(!in_array($dirError, $errorLog))
					$errorLog[] = $dirError;
				continue;
			}

			if(!$zip->extractTo($outputPath, [$filename]))
				$errorLog[] = "Unable to extract file {$filename}";
		}

		// Close the zip file
		$zip->close();

		return true;
	}
