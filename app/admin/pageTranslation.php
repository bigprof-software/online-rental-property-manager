<?php
	require(__DIR__ . '/incCommon.php');

	// strings from defaultLang that we don't want to include in language.php
	define('IGNORE_KEYS', ['*', 'ImageFolder']);

	$saveRes = null;
	if(Request::val('newTranslation'))
		$saveRes = saveLangFile(Request::val('newTranslation'));

	echo renderTranslation($saveRes);

	/*************************************************************/

	// safely wrap string between single quotes
	function wrapSQ($str) {
		return "'" . addcslashes($str, "'\\\0") . "'";
	}

	function saveLangFile($newTrans) {
		global $Translation;

		if(!is_array($newTrans) || empty($newTrans['language'])) return [
			'class' => 'danger',
			'message' => 'Invalid input. Please retry!',
			'dismiss_seconds' => 60
		];

		if(!csrf_token(true)) return [
			'class' => 'danger',
			'message' => $Translation['invalid security token'],
			'dismiss_seconds' => 60
		];

		// back up language.php if not already backed up
		$backupFile = __DIR__ . '/../language.bak.php';
		$langFile = __DIR__ . '/../language.php';
		if(!is_file($backupFile))
			@copy($langFile, $backupFile);
		$backedUp = is_file($backupFile);

		// build translation front matter code
		$code   = ['<' . '?php'];
		$code[] = '// File automatically built via ' . application_url('admin/pageTranslation.php');
		$code[] = '';
		$code[] = '$Translation = [';
		$code[] = "\t'language' => " . wrapSQ($newTrans['language']) . ',';

		// loop through all translation strings and add to new code
		$log = []; $i = 1;
		foreach($Translation as $key => $val) {
			// keys to ignore
			if(in_array($key, IGNORE_KEYS) || $key == 'language') continue;

			$i++; // serial number of string
			if(empty($newTrans[$key])) {
				$log[] = "Translated string #$i not provided. Using the original string";
				$code[] = "\t" . wrapSQ($key) . ' => ' . wrapSQ($val) . ',';
				continue;
			}

			$code[] = "\t" . wrapSQ($key) . ' => ' . wrapSQ($newTrans[$key]) . ',';
		}

		// if backup was not made, don't save to original file
		$saveFile = ($backedUp ? $langFile : __DIR__ . '/../language.new.php');

		// end code and save
		$code[] = '];';
		if(!@file_put_contents($saveFile, implode("\n", $code))) {
			$codeStr = htmlspecialchars(implode("\n", $code));
			return [
				'class' => 'danger',
				'message' => "<b>Couldn't save new language file.</b><br>" .
							 "Please check folder permissions/ownership. You can manually add the translation to" .
							 "the file <code>language.php</code> by copying the code below into it." .
							 "<textarea class=\"form-control tspacer-lg\" rows=\"10\">$codeStr</textarea>",
				'dismiss_seconds' => 1000
			];
		}

		if(!empty($log)) return [
			'class' => 'warning',
			'message' => "<b>The translated strings were saved to <code>$saveFile</code>. However, the following issues occured</b><ul>" .
							'<li>' . implode('</li><li>', $log) . '</li></ul>',
			'dismiss_seconds' => 1000,
		];

		if(!$backedUp) return [
			'class' => 'warning',
			'message' => "Your changes were saved to <code>$saveFile</code> because a backup of language.php couldn't be created, probably due to misconfigured folder permissions. You should manually rename the file to language.php.",
			'dismiss_seconds' => 120
		];

		return [
			'class' => 'success',
			'message' => "Your changes were saved successfully to language.php. The old version of the file was saved as <b>language.bak.php</b>",
			'dismiss_seconds' => 20
		];
	}

	function getLanguage($file) {
		$langCode = file_get_contents(__DIR__ . "/../{$file}.php");
		$langCode = preg_replace(['/<'.'\?php\b/', '/\$Translation\w*\s*=\s*/'], ['', 'return '], $langCode);
		try {
			$lang = eval($langCode);
		} catch(Exception $e) {
			return []; 
		}

		unset($lang['ImageFolder']);

		return $lang;
	}

	function langStringFixed($str) {
		return preg_replace(
			['/(&lt;.*?&gt;)/', '/(%s)/', '/(&amp;#.*;)/', '/(\[.*\])/'],
			'<code>$1</code>',
			htmlspecialchars($str)
		);
	}

	function renderTranslation($saveRes) {
		global $Translation;

		$english = ['language' => 'Please specify the language name'] + getLanguage('defaultLang');

		$language = getLanguage('language');

		ob_start();

		$GLOBALS['page_title'] = $Translation['translation tool'];
		include(__DIR__ . '/incHeader.php');
		?>


		<div class="page-header"><h1><?php echo $Translation['translation tool']; ?></h1></div>
		<?php if(!empty($saveRes)) echo Notification::show($saveRes); ?>

		<p class="lead">
			<i class="text-info glyphicon glyphicon-globe" style="font-size: 5em; float: left; margin-right: 12px;"></i> 
			The purpose of this tool is to make it easier to translate the interface of this
			AppGini app by displaying all the English strings to the left, and the corresponding
			(translated) ones to the right in the table below. To translate a string, click on it
			and start typing the desired translation. You can save changes by clicking the save
			button at the bottom. And you can return to this page any time to continue translating
			the remaining strings.
		</p>

		<div class="alert alert-info">
			<b>TIP:</b>
			If you haven't already done so, please check the
			<a href="https://bigprof.com/appgini/download-language-files" target="_blank" class="alert-link">language files page</a>
			to see if there is an already existing translation to your language. Even if it's incomplete,
			you'll save a lot of time completing the missing strings compared to starting from scratch.
		</div>

		<form method="post" action="pageTranslation.php" class="table-responsive">
			<?php echo csrf_token(); ?>

			<table class="table table-bordered table-hover translation-table ltr">
				<thead>
					<tr>
						<th width="20" class="text-center">#</th>
						<th width="20%">English string<br>
							Don't translate special words/symbols styled like <code>&lt;this&gt;</code> or <code>[that]</code> ... etc.</th>
						<th>
							Translation in <code>language.php</code> (<?php echo ucfirst($language['language']); ?>)
						</th>
						<th width="20"></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach($english as $key => $englishStr) {
						if(in_array($key, IGNORE_KEYS)) continue;

						$trClass = ''; $status = 'time'; $title = 'Not yet translated'; 
						if($englishStr != $language[$key] && !empty($language[$key])) {
							$trClass = 'success';
							$status = 'ok';
							$title = 'Done';
						}
						?>
						<tr class="<?php echo $trClass; ?>">
							<td class="text-right"><?php $i = $i ?? 1; echo $i++; ?></td>
							<td><?php echo langStringFixed($englishStr); ?></td>
							<td data-key="<?php echo html_attr($key); ?>" contenteditable onfocus="document.execCommand('selectAll', false, null);"><?php echo htmlspecialchars($language[$key] ?? ''); ?></td>
							<td class="status" title="<?php echo $title; ?>"><i class="glyphicon glyphicon-<?php echo $status; ?>"></i></td>
						</tr>
						<?php
					} ?>
				</tbody>
			</table>

		</form>

		<div style="height: 100px;"></div>

		<nav class="navbar navbar-inverse navbar-fixed-bottom">
			<div class="container">
				<div class="progress tspacer-lg hidden" style="width: 50%; float: left;">
					<div class="progress-bar progress-bar-danger" style="min-width: 2em; width: 0;" id="translation-progress">0%</div>
				</div>
				<div class="btn-group navbar-btn pull-right">
					<button type="button" class="btn btn-default btn-save-changes disabled" disabled><i class="glyphicon glyphicon-ok text-success"></i> <span class="text-success"><?php echo $Translation['save changes']; ?></span></button>
					<button type="button" class="btn btn-default btn-cancel disabled" disabled><i class="glyphicon glyphicon-remove text-danger"></i> <span class="text-danger"><?php echo $Translation['cancel']; ?></span></button>
				</div>
				<div class="checkbox-inline pull-right rspacer-lg tspacer-lg text-danger">
					<label title="Right to left"><input type="checkbox" id="rtl-on"> RTL</label>
				</div>
			</div>
		</nav>

		<style>
			td:not([contenteditable]) {
				cursor: pointer;
			}
			td[contenteditable]:focus {
				outline: solid 2px #00d;
				outline-offset: -2px;
				background-color: white;
				font-family: monospace;
			}
		</style>

		<script>
			$j(function() {
				var updateProgress = () => {
					let numStrings = $j('.translation-table > tbody > tr').length,
						numDone = $j('.translation-table > tbody > tr.success').length,
						progress = Math.round(numDone / numStrings * 100);

					$j('.progress').toggleClass('hidden', progress < 1);

					$j('#translation-progress')
						.toggleClass('progress-bar-danger', progress < 10)
						.toggleClass('progress-bar-warning', progress >= 10 && progress <= 50)
						.toggleClass('progress-bar-success', progress > 50)
						.css({ width: progress + '%' })
						.text(progress + '%');
				}

				$j('.translation-table')
					// focus on editable string when clicking the corresponding english string
					.on('click', 'td:not([contenteditable])', function() {
						$j(this).next('td[contenteditable]').focus();
					})
					// on changing a language string, apply .success if different form original
					.on('blur', 'td[contenteditable]', function() {
						let td = $j(this), isSame = (td.text() == td.prev('td:not([contenteditable])').text());
						if(!td.text().length) isSame = true;
						td.parent('tr')
							.toggleClass('success', !isSame);
						td.next('.status')
							.attr('title', isSame ? 'Not yet translated' : 'Done')
							.children('.glyphicon')
								.toggleClass('glyphicon-time', isSame)
								.toggleClass('glyphicon-ok', !isSame);
						updateProgress();

						if(!isSame) $j('.btn-save-changes, .btn-cancel')
							.prop('disabled', false)
							.removeClass('disabled');
					})

				$j('.btn-save-changes').on('click', () => {
					$j('.translation-table td[contenteditable]').each(function() {
						let key = $j(this).data('key'),
							val = $j(this).text();

						$j('<input type="hidden">')
							.attr('name', `newTranslation[${key}]`)
							.val(val)
							.appendTo('form');
					})
					$j('form').submit();
				})

				$j('.btn-cancel').on('click', () => {
					if(!confirm('Are you sure you want to discard your changes?')) return;

					location.href = 'pageTranslation.php';
				})

				// set editable translation orientation based on #rtl-on
				$j('#rtl-on').on('change', function() {
					$j('td[contenteditable]').css({ direction: $j(this).prop('checked') ? 'rtl' : 'ltr' });
				})

				// if document is already rtl, check the rtl box
				$j('#rtl-on').prop('checked', $j('.theme-rtl').length > 0).trigger('change');

				updateProgress();
			})
		</script>

		<?php
		include(__DIR__ . '/incFooter.php');

		return ob_get_clean();
	}
