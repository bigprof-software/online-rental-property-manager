<?php
	if(!isset($Translation)) { @header('Location: index.php'); exit; }

	include_once(__DIR__ . '/header.php');
	@include(__DIR__ . '/hooks/links-home.php');

	if(is_file(__DIR__ . '/hooks/home-custom.php')) {
		include(__DIR__ . '/hooks/home-custom.php');
		include_once(__DIR__ . '/footer.php');
		exit;
	}

	/*
		Classes of first and other blocks
		---------------------------------
		For possible classes, refer to the Bootstrap grid columns, panels and buttons documentation:
			Grid columns: https://getbootstrap.com/css/#grid
			Panels: https://getbootstrap.com/components/#panels
			Buttons: https://getbootstrap.com/css/#buttons
	*/
	$cols = HOMEPAGE_TABLES_PER_ROW;
	$isDouble = HOMEPAGE_FIRST_TABLE_DOUBLE_WIDTH;

	if ($isDouble) {
		if ($cols > 3) { $grid_first = 'col-sm-12 col-md-8 col-lg-6'; }
		elseif ($cols == 3) { $grid_first = 'col-md-12 col-lg-8'; }
		else { $grid_first = 'col-lg-12'; }
	} else {
		if ($cols > 3) { $grid_first = 'col-sm-6 col-md-4 col-lg-3'; }
		elseif ($cols == 3) { $grid_first = 'col-md-6 col-lg-4'; }
		elseif ($cols == 2) { $grid_first = 'col-lg-6'; }
		else { $grid_first = 'col-lg-12'; }
	}

	if ($cols > 3) { $grid_other = 'col-sm-6 col-md-4 col-lg-3'; }
	elseif ($cols == 3) { $grid_other = 'col-md-6 col-lg-4'; }
	elseif ($cols == 2) { $grid_other = 'col-lg-6'; }
	else { $grid_other = 'col-lg-12'; }

	$block_classes = [
		'first' => [
			'grid_column' => $grid_first,
			'panel' => 'panel-warning',
			'link' => 'btn-warning',
		],
		'other' => [
			'grid_column' => $grid_other,
			'panel' => 'panel-info',
			'link' => 'btn-info',
		],
	];
?>

<style>
	.panel-body-description {
		margin-top: 10px;
		height: <?php echo HOMEPAGE_PANEL_HEIGHT; ?>px;
		overflow: auto;
	}
	.panel-body .btn img {
		margin: 0 10px;
		max-height: 32px;
	}
	.table-quick-search iframe {
		width: 100%;
		border: none;
	}
	.table-quick-search .matches-count, .table-quick-search .openup-in-new-tab {
		font-size: .55em;
		vertical-align: middle;
	}
	.table-quick-search + .table-quick-search {
		border-top: solid 1px #999;
		margin-top: 2em;
		padding-top: 2em;
	}
</style>

<!-- search box -->
<div id="homepage-search-box-container" class="input-group input-group-lg vspacer-lg hidden">
	<input type="text" class="form-control" id="homepage-search-box" placeholder="<?php echo html_attr($Translation['quick search']); ?>">
	<span class="input-group-btn">
		<button class="btn btn-default" type="button" title="<?php echo html_attr($Translation['quick search']); ?>"><i class="glyphicon glyphicon-search"></i></button>
		<button class="btn btn-default" type="button" id="clear-homepage-search-box" title="<?php echo html_attr($Translation['clear search']); ?>"><i class="glyphicon glyphicon-remove"></i></button>
	</span>
</div>

<?php
	// get member info
	$mi = getMemberInfo();

	// get configured name of guest user
	$admin_config = config('adminConfig');
	$guest_username = $admin_config['anonymousMember'];

	// arrays of user tables (accessible to current user)
	// tn => ['caption' => ..., 'icon' => ...]
	$userTables = [];

	/* accessible tables */
	$arrTables = get_tables_info();
	if(is_array($arrTables) && count($arrTables)) {
		foreach($arrTables as $tn => $ti) {
			$userTables[$tn] = ['caption' => $ti['Caption'], 'icon' => $ti['tableIcon']];
		}

		/* how many table groups do we have? */
		$groups = get_table_groups();
		$multiple_groups = (count($groups) > 1 ? true : false);

		/* construct $tg: table list grouped by table group */
		$tg = [];
		if(count($groups)) {
			foreach($groups as $grp => $tables) {
				foreach($tables as $tn) {
					$tg[$tn] = $grp;
				}
			}
		}

		$i = 0; $current_group = '';

		?><div class="homepage-links"><?php
		foreach($tg as $tn => $tgroup) {
			$tc = $arrTables[$tn];
			/* is the current table filter-first? */
			$tChkFF = array_search($tn, tablesToFilterBeforeTV());
			/* hide current table in homepage? */
			$tChkHL = array_search($tn, tablesHiddenInHomepage());
			/* allow homepage 'add new' for current table? */
			$tChkAHAN = array_search($tn, tablesWithAddNewInHomepage());

			/* homepageShowCount for current table? */
			$count_badge = '';
			if($tc['homepageShowCount'] && ($tChkHL === false || $tChkHL === null)) {
				$sql_from = get_sql_from($tn, false, true);
				$count_records = ($sql_from ? sqlValue("select count(1) from " . $sql_from) : 0);
				$count_badge = '<span class="badge hspacer-lg text-bold">' . number_format($count_records) . '</span>';
			}

			$t_perm = getTablePermissions($tn);
			$can_insert = $t_perm['insert'];

			$searchFirst = (($tChkFF !== false && $tChkFF !== null) ? '?Filter_x=1' : '');
			?>
				<?php if(!$i && !$multiple_groups) { /* no grouping, begin row */ ?>

					<div class="row table_links">
				<?php } ?>
				<?php if($multiple_groups && $current_group != $tgroup) { /* grouping, begin group & row */ ?>
					<?php if($current_group != '') { /* not first group, so we should first end previous group */ ?>

							</div><!-- /.table_links -->
							<div class="row custom_links">
								<?php
									/* custom home links for current group, as defined in "hooks/links-home.php" */
									echo get_home_links($homeLinks, $block_classes['other'], $current_group);
								?>
							</div>
						</div><!-- /.collapse -->
					<?php } ?>
					<?php $current_group = $tgroup; ?>

					<a class="btn btn-primary btn-block btn-lg collapser collapsed vspacer-lg" data-toggle="collapse" href="#group-<?php echo md5($tgroup); ?>"><?php echo $tgroup; ?> <i class="glyphicon glyphicon-chevron-right"></i></a>
					<div class="collapse" id="group-<?php echo md5($tgroup); ?>">
						<div class="row table_links">
				<?php } ?>

				<?php if($tChkHL === false || $tChkHL === null) { /* if table is not set as hidden in homepage */ ?>
					<div id="<?php echo $tn; ?>-tile" class="<?php echo (!$i ? $block_classes['first']['grid_column'] : $block_classes['other']['grid_column']); ?>">
						<div class="panel <?php echo (!$i ? $block_classes['first']['panel'] : $block_classes['other']['panel']); ?>">
							<div class="panel-body">
								<?php if($can_insert && $tChkAHAN !== false && $tChkAHAN !== null) { ?>

									<div class="btn-group" style="width: 100%;">
										<a style="width: calc(100% - 3.5em);" class="btn btn-lg <?php echo (!$i ? $block_classes['first']['link'] : $block_classes['other']['link']); ?>" title="<?php echo preg_replace("/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", html_attr(strip_tags($tc['Description']))); ?>" href="<?php echo $tn; ?>_view.php<?php echo $searchFirst; ?>"><?php echo ($tc['tableIcon'] ? '<img src="' . $tc['tableIcon'] . '">' : '');?><strong class="table-caption"><?php echo $tc['Caption']; ?></strong><?php echo $count_badge; ?></a>
										<a id="<?php echo $tn; ?>_add_new" style="width: 3.5em; padding-right: 0.1rem; padding-left: 0.1rem;" class="btn btn-add-new btn-lg <?php echo (!$i ? $block_classes['first']['link'] : $block_classes['other']['link']); ?>" title="<?php echo html_attr($Translation['Add New']); ?>" href="<?php echo $tn; ?>_view.php?addNew_x=1"><i style="vertical-align: bottom;" class="glyphicon glyphicon-plus"></i></a>
									</div>
								<?php } else { ?>

									<a class="btn btn-block btn-lg <?php echo (!$i ? $block_classes['first']['link'] : $block_classes['other']['link']); ?>" title="<?php echo preg_replace("/&amp;(#[0-9]+|[a-z]+);/i", "&$1;", html_attr(strip_tags($tc['Description']))); ?>" href="<?php echo $tn; ?>_view.php<?php echo $searchFirst; ?>"><?php echo ($tc['tableIcon'] ? '<img src="' . $tc['tableIcon'] . '">' : '');?><strong class="table-caption"><?php echo $tc['Caption']; ?></strong><?php echo $count_badge; ?></a>
								<?php } ?>

								<div class="panel-body-description"><?php echo $tc['Description']; ?></div>
							</div>
						</div>
					</div>
				<?php } ?>

				<?php if($i == (count($arrTables) - 1) && !$multiple_groups) { /* no grouping, end row */ ?>

					</div> <!-- /.table_links -->

					<div class="row custom_links" id="custom_links">
						<?php
							/* custom home links, as defined in "hooks/links-home.php" */
							echo get_home_links($homeLinks, $block_classes['other'], '*');
						?>
					</div>

				<?php } ?>
				<?php if($i == (count($arrTables) - 1) && $multiple_groups) { /* grouping, end last group & row */ ?>

							</div> <!-- /.table_links -->
							<div class="row custom_links" id="custom_links">
								<?php
									/* custom home links for last table group, as defined in "hooks/links-home.php" */
									echo get_home_links($homeLinks, $block_classes['other'], $tgroup);

									/* custom home links having no table groups, as defined in "hooks/links-home.php" */
									echo get_home_links($homeLinks, $block_classes['other']);
								?>
							</div>
						</div><!-- /.collapse -->
				<?php } ?>
			<?php
			$i++;
		}
		?></div><?php // .homepage-links

	} elseif($mi['username'] && $mi['username'] != $guest_username) {
		// non-guest user but no tables to access
		die(error_message($Translation['no table access'], application_url('index.php?signIn=1'), false));
	} else {
		?><script>window.location='index.php?signIn=1';</script><?php
		exit;
	}
?>

<?php if(HOMEPAGE_QUICK_SEARCH_TABLES): ?>
	<div class="well text-center hidden" id="homepage-no-matches-found">
		<?php echo $Translation['homepage no matches found try quick search']; ?>
		<button class="btn btn-default hspacer-sm" type="button" onclick="$j('#homepage-search-box-container .btn:first-child').click();">
			<i class="glyphicon glyphicon-search"></i> <?php echo $Translation['quick search']; ?>
		</button>
	</div>

	<div class="well text-center hidden" id="quick-search-no-matches-found">
		<?php echo $Translation['no matches found in any table']; ?>
	</div>

	<?php foreach($userTables as $tn => $info): ?>
		<div class="table-quick-search hidden" data-table-name="<?php echo html_attr($tn); ?>" id="<?php echo html_attr($tn); ?>-quick-search">
			<h3>
				<img src="<?php echo html_attr($info['icon']); ?>" alt="<?php echo html_attr($info['caption']); ?>" style="max-height: 32px; vertical-align: middle;">
				<?php echo html_attr($info['caption']); ?>
				<span class="label label-default matches-count">0</span>
				<a href="#" target="_blank" class="openup-in-new-tab"><i class="glyphicon glyphicon-new-window"></i></a>
			</h3>
			<iframe src=""></iframe>
		</div>
	<?php endforeach; ?>
<?php endif; ?>

<script>
	$j(function() {
		<?php if(HOMEPAGE_QUICK_SEARCH_TABLES): ?>
			const userTables = <?php echo json_encode($userTables); ?>;
			const quickSearchBtn = $j('#homepage-search-box-container .btn:first-child');

			quickSearchBtn.click(() => {
				$j('#homepage-no-matches-found, #quick-search-no-matches-found').addClass('hidden');

				const searchStr = $j('#homepage-search-box').val().trim();
				if(searchStr === '') return;

				// show load icon on quick search button until all iframes are loaded
				let loadedIframes = 0;
				const totalIframes = Object.keys(userTables).length;
				quickSearchBtn
					.removeClass('btn-default').addClass('btn-warning')
					.children('.glyphicon').removeClass('glyphicon-search').addClass('glyphicon-refresh spin');

				// for each user table, set corresponding quick search iframe src to:
				//    {tn}_view.php?SearchString={search}&Embedded=1
				// and set a one-time load handler to show iframe if content satisfies $j('.table_view tbody tr').length > 0
				// and to adjust iframe height to fit content
				// else hide iframe
				Object.keys(userTables).forEach(tn => {
					const qsDiv = $j(`#${tn}-quick-search`);
					const iframe = qsDiv.find('iframe');
					iframe.off('load').on('load', () => {
						loadedIframes++;
						if(loadedIframes === totalIframes) {
							// all iframes loaded, remove load icon from quick search button
							quickSearchBtn
								.removeClass('btn-warning').addClass('btn-default')
								.children('.glyphicon').removeClass('glyphicon-refresh spin').addClass('glyphicon-search');

							// if no quick search results, show 'no matches found' div
							const anyVisible = $j('.table-quick-search:not(.hidden)').length > 0;
							$j('#quick-search-no-matches-found').toggleClass('hidden', anyVisible);
						}

						const iframeDoc = iframe[0].contentDocument || iframe[0].contentWindow.document;
						const rowCount = $j(iframeDoc).find('.table_view tbody tr:not(.sum)').length;
						if(rowCount > 0) {
							// show div
							qsDiv.removeClass('hidden');

							// set 'open in new tab' link href
							qsDiv.find('.openup-in-new-tab').attr('href', `${tn}_view.php?SearchString=${encodeURIComponent(searchStr)}`);

							// obtain matches count from `.record-count` element if exists, else use rowCount
							let matchesCount = rowCount;
							const recordCountEl = $j(iframeDoc).find('.record-count');
							if(recordCountEl.length) {
								matchesCount = recordCountEl.text().trim();
							}

							// set matches count
							qsDiv.find('.matches-count').text(matchesCount);

							// adjust iframe height
							const contentHeight = $j(iframeDoc).find('body').outerHeight();
							iframe.height(contentHeight + 20); // +20 for some extra space

							// set all links inside tbody to open in new tab including those with .modal-link class
							// and append SearchString to their href
							$j(iframeDoc).find('.table_view tbody a')
								.removeAttr('onclick')
								.attr('target', '_blank')
								.attr('href', function() {
									// if .modal-link, return href after removing Embedded param
									if($j(this).hasClass('modal-link')) return this.href.replace(/([?&])Embedded=1(&|$)/, '$1$2');

									// else, append SearchString param to href
									return this.href + (this.href.indexOf('?') > -1 ? '&' : '?') + 'SearchString=' + encodeURIComponent(searchStr);
								})
								.removeClass('modal-link');
						} else {
							// hide div
							qsDiv.addClass('hidden');
						}
					});
					iframe.attr('src', `${tn}_view.php?SearchString=${encodeURIComponent(searchStr)}&Embedded=1`);
				});
			});
		<?php endif; ?>

		var table_descriptions_exist = false;
		$j('div[id$="-tile"] .panel-body-description').each(function() {
			if($j.trim($j(this).html()).length) table_descriptions_exist = true;
		});

		if(!table_descriptions_exist) {
			$j('div[id$="-tile"] .panel-body-description').css({height: 'auto'});
		}

		// adjust button heights to be equal and at least 32px
		AppGini.repeatUntil({
			action: () => $j('.panel-body .btn').height(32),
			condition: () => Math.min(...$j('.panel-body .btn').map((_, el) => $j(el).height()).get().filter(height => height >= 0)) >= 32,
			frequency: 100,
		})

		$j('.btn-add-new').click(function() {
			var tn = $j(this).attr('id').replace(/_add_new$/, '');
			modal_window({
				url: tn + '_view.php?addNew_x=1&Embedded=1',
				size: 'full',
				title: $j(this).prev().children('.table-caption').text() + ": <?php echo html_attr($Translation['Add New']); ?>"
			});
			return false;
		});

		/* adjust arrow directions on opening/closing groups, and initially open first group */
		$j('.collapser').click(function() {
			$j(this).children('.glyphicon').toggleClass('glyphicon-chevron-right glyphicon-chevron-down');
		});

		/* hide empty table groups */
		$j('.collapser').each(function() {
			var target = $j(this).attr('href');
			if(!$j(target + " .row div").length) $j(this).hide();
		});
		$j('.collapser:visible').eq(0).click();

		// homepage search functionality
		// to disable search, set `AppGini.disableHomePageSearch` to true in a script block in hooks/footer-extras.php
		//
		// by default, only titles are searched
		// to also search descriptions, set `AppGini.homePageSearchDescriptions` to true in a script block in hooks/footer-extras.php

		// if we have at least 2 collapsers visible, or 4+ .btn in .panel-body, enable search
		if($j('.collapser:visible').length > 1 || $j('.panel-body .btn:not(.btn-add-new)').length > 3) {
			// if AppGini.disableHomePageSearch is set to true, don't enable search
			const disabled = AppGini?.disableHomePageSearch ?? false;
			if(!disabled) enableSearch();
		}

		function enableSearch() {
			// show search box
			$j('#homepage-search-box-container').removeClass('hidden');

			// search box functionality: on typing, hide all blocks except those containing the search string
			// if search string is empty, show all blocks
			$j('#homepage-search-box').keyup(function() {
				$j('#homepage-no-matches-found, #quick-search-no-matches-found').addClass('hidden');

				const search = $j(this).val().toLowerCase().trim();
				if(search == '') {
					$j('.hidden-by-search').removeClass('hidden-by-search hidden');
					$j('.table-quick-search').addClass('hidden');
					return;
				}

				// loop through all panels, hide unmatching ones, show matching ones
				$j('.homepage-links .panel').each(function() {
					const panel = $j(this);
					const searchable = (AppGini?.homePageSearchDescriptions ? panel : panel.find('.btn:not(.btn-add-new)'));
					panel.parent().toggleClass('hidden-by-search hidden', $j.trim(searchable.text()).toLowerCase().indexOf(search) == -1);
				});

				// loop through all `.collapser` elements and show if:
					// - their text matches the search string, or
					// - at least one of their target's `.panel` elements is not hidden-by-search
					// - and hide otherwise
				$j('.collapser').each(function() {
					const collapser = $j(this);
					const target = $j(collapser.attr('href'));
					const collapserMatches = collapser.text().toLowerCase().indexOf(search) > -1;
					let show = collapserMatches;

					target.find('.panel').each(function() {
						show = show || !$j(this).parent().hasClass('hidden-by-search');
					});
					collapser.toggleClass('hidden-by-search hidden', !show);

					// uncollapse if at least one of the panels is not hidden
					if(show && collapser.hasClass('collapsed')) collapser.click();

					// if collapser itself matches the search, show all panels
					if(collapserMatches) target.find('.panel').parent().removeClass('hidden-by-search hidden');
				});

				<?php if(HOMEPAGE_QUICK_SEARCH_TABLES): ?>
					// if no homepage results, show 'no matches found' div
					const anyVisible = $j('.homepage-links .panel').parent(':not(.hidden-by-search)').length > 0;
					$j('#homepage-no-matches-found').toggleClass('hidden', anyVisible);
				<?php endif; ?>
			});

			// focus search box if not in xs screen size
			if(!screen_size('xs')) $j('#homepage-search-box').focus();

			// focus search box on pressing '/' or '?' keys
			$j(document).keyup(function(e) {
				if(e.keyCode == 191 && !$j('#homepage-search-box').is(':focus')) {
					$j('#homepage-search-box').focus();
					e.preventDefault();
				}
			});

			// clear search box on clicking 'x' button
			$j('#clear-homepage-search-box').click(function() {
				$j('#homepage-search-box').val('').keyup().focus();
				// hide all quick search results
				$j('.table-quick-search').addClass('hidden');
			});

			// clear search box on clicking ESC key while search box is focused
			$j(document).keyup(function(e) {
				if(e.keyCode == 27 && $j('#homepage-search-box').is(':focus')) {
					$j('#homepage-search-box').val('').keyup().focus();
					// hide all quick search results
					$j('.table-quick-search').addClass('hidden');
				}
			});
		}
	});
</script>

<?php include_once(__DIR__ . '/footer.php');