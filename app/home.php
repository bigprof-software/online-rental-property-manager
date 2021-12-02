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
	$block_classes = [
		'first' => [
			'grid_column' => 'col-lg-6',
			'panel' => 'panel-warning',
			'link' => 'btn-warning',
		],
		'other' => [
			'grid_column' => 'col-lg-6',
			'panel' => 'panel-info',
			'link' => 'btn-info',
		],
	];
?>

<style>
	.panel-body-description{
		margin-top: 10px;
		height: 40px;
		overflow: auto;
	}
	.panel-body .btn img{
		margin: 0 10px;
		max-height: 32px;
	}
</style>


<?php
	// get member info
	$mi = getMemberInfo();

	// get configured name of guest user
	$admin_config = config('adminConfig');
	$guest_username = $admin_config['anonymousMember'];

	/* accessible tables */
	$arrTables = get_tables_info();
	if(is_array($arrTables) && count($arrTables)) {
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
		foreach($tg as $tn => $tgroup) {
			$tc = $arrTables[$tn];
			/* is the current table filter-first? */
			$tChkFF = array_search($tn, []);
			/* hide current table in homepage? */
			$tChkHL = array_search($tn, ['residence_and_rental_history','employment_and_income_history','references','property_photos','unit_photos']);
			/* allow homepage 'add new' for current table? */
			$tChkAHAN = array_search($tn, ['applicants_and_tenants','applications_leases','rental_owners','properties','units']);

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

					<a class="btn btn-primary btn-block btn-lg collapser vspacer-lg" data-toggle="collapse" href="#group-<?php echo md5($tgroup); ?>"><?php echo $tgroup; ?> <i class="glyphicon glyphicon-chevron-right"></i></a>
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
	} elseif($mi['username'] && $mi['username'] != $guest_username) {
		// non-guest user but no tables to access
		die(error_message($Translation['no table access'], false));
	} else {
		?><script>window.location='index.php?signIn=1';</script><?php
	}
?>

<script>
	$j(function() {
		var table_descriptions_exist = false;
		$j('div[id$="-tile"] .panel-body-description').each(function() {
			if($j.trim($j(this).html()).length) table_descriptions_exist = true;
		});

		if(!table_descriptions_exist) {
			$j('div[id$="-tile"] .panel-body-description').css({height: 'auto'});
		}

		$j('.panel-body .btn').height(32);

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
	});
</script>

<?php include_once(__DIR__ . '/footer.php');