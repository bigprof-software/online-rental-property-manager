<?php if(!isset($Translation)){ @header('Location: index.php'); exit; } ?>

<div class="page-header"><h1>
	<span id="table-title-img"><img align="top" src="<?php echo $this->TableIcon; ?>" /></span> <?php echo $this->TableTitle . " " . $Translation['filters']; ?>
</h1></div>

<?php
	/* SPM link for admin */
	if(getLoggedAdmin()){
		$spm_installed = false;
		$plugins = get_plugins();
		foreach($plugins as $pl){
			if($pl['title'] == 'Search Page Maker') $spm_installed = true;
		}

		if(!$spm_installed) echo Notification::show(array(
			'message' => '<i class="glyphicon glyphicon-info-sign"></i> Wish to offer your users an easier, more-tailored search experience? <a href="https://bigprof.com/appgini/applications/search-page-maker-plugin-discount" target="_blank" class="alert-link"><i class="glyphicon glyphicon-hand-right"></i> Click here to learn how Search Page Maker plugin can help</a>.',
			'dismiss_days' => 30,
			'class' => 'success',
			'id' => 'spm_notification'
		));
	}
?>

<!-- checkboxes for parent filterers -->
<?php
	foreach($this->filterers as $filterer => $caption){
		$fltrr_name = 'filterer_' . $filterer;
		$fltrr_val = $_REQUEST[$fltrr_name];
		if($fltrr_val != ''){
			?>
			<div class="row">
				<div class="col-md-offset-3 col-md-7">
					<div class="checkbox">
						<label>
							<input type="checkbox" id="<?php echo $fltrr_name; ?>" name="<?php echo $fltrr_name; ?>" value="<?php echo html_attr($fltrr_val); ?>" checked>
							<strong><?php printf($Translation['Only show records having filterer'], $caption, "<span class=\"text-info\" id=\"{$fltrr_name}_display_value\"></span>"); ?></strong>
						</label>
						<script>
							jQuery(function() {
								jQuery.ajax({
									url: 'ajax_combo.php',
									dataType: 'json',
									data: { id: '<?php echo addslashes($fltrr_val); ?>', t: '<?php echo $this->TableName; ?>', f: '<?php echo $filterer; ?>', o: 0 }
								}).done(function(resp){
									jQuery('#<?php echo $fltrr_name; ?>_display_value').html(resp.results[0].text);
								});
							});
						</script>
					</div>
				</div>
			</div>
			<?php
			break; // currently, only one filterer can be applied at a time
		}
	}
?>


<!-- filters header -->
<div class="row hidden-sm hidden-xs" style="border-bottom: solid 2px #DDD;">
	<div class="col-md-2 col-md-offset-4 vspacer-lg"><strong><?php echo $Translation['filtered field']; ?></strong></div>
	<div class="col-md-2 vspacer-lg"><strong><?php echo $Translation['comparison operator']; ?></strong></div>
	<div class="col-md-2 vspacer-lg"><strong><?php echo $Translation['comparison value']; ?></strong></div>
</div>


<!-- filter groups -->
<?php
	for($i = 1; $i <= (3 * $FiltersPerGroup); $i++){ // Number of filters allowed
		$fields = '';
		$operators = '';

		if(($i % $FiltersPerGroup == 1) && $i != 1){
			$seland = new Combo;
			$seland->ListItem = array($Translation["or"], $Translation["and"]);
			$seland->ListData = array("or", "and");
			$seland->SelectName = "FilterAnd[$i]";
			$seland->SelectedData = $FilterAnd[$i];
			$seland->Render();
			?>
			<!-- how to combine next group with previous one: and/or? -->
			<div class="row FilterSet<?php echo ($i - 1); ?>" style="border-bottom: dotted 1px #DDD;">
				<div class="col-md-5 vspacer-md"></div>
				<div class="col-md-2 vspacer-md">
					<?php echo $seland->HTML; ?>
				</div>
			</div>
		<?php } ?>

		<!-- filter rule -->
		<div class="row FilterSet<?php echo $i; ?>" style="border-bottom: dotted 1px #DDD;">
			<div class="col-md-2 vspacer-md"></div>
			<div class="col-md-1 text-right hidden-sm hidden-xs vspacer-md"><strong><?php echo $Translation["filter"] . sprintf(" %02d", $i); ?></strong></div>
			<div class="col-md-1 hidden-md hidden-lg vspacer-md"><strong><?php echo $Translation["filter"] . sprintf(" %02d", $i); ?></strong></div>
			<div class="col-md-1 vspacer-md">
				<?php
					// And, Or select
					if($i % $FiltersPerGroup != 1){
						$seland = new Combo;
						$seland->ListItem = array($Translation["and"], $Translation["or"]);
						$seland->ListData = array("and", "or");
						$seland->SelectName = "FilterAnd[$i]";
						$seland->SelectedData = $FilterAnd[$i];
						$seland->Render();
						echo $seland->HTML;
					}
				?>
			</div>
			<div class="col-md-2 vspacer-md">
				<?php
					// Fields list
					$selfields = new Combo;
					$selfields->SelectName = "FilterField[$i]";
					$selfields->SelectedData = $FilterField[$i];
					$selfields->ListItem = array_values($this->QueryFieldsFilters);
					$selfields->ListData = array_keys($this->QueryFieldsIndexed);
					$selfields->Render();
					echo $selfields->HTML;
				?>
			</div>
			<div class="col-md-2 vspacer-md">
				<?php
					// Operators list
					$selop = new Combo;
					$selop->ListItem = array($Translation["equal to"], $Translation["not equal to"], $Translation["greater than"], $Translation["greater than or equal to"], $Translation["less than"], $Translation["less than or equal to"] , $Translation["like"] , $Translation["not like"], $Translation["is empty"], $Translation["is not empty"]);
					$selop->ListData = array_keys($GLOBALS['filter_operators']);
					$selop->SelectName = "FilterOperator[$i]";
					$selop->SelectedData = $FilterOperator[$i];
					$selop->Render();
					echo $selop->HTML;
				?>
			</div>
			<div class="col-md-2 vspacer-md">
				<?php /* Comparison expression */ ?>
				<input name="FilterValue[<?php echo $i; ?>]" value="<?php echo html_attr($FilterValue[$i]); ?>" class="form-control">
			</div>
			<div class="col-md-2 vspacer-md">
				<button type="button" class="btn btn-default clear_filter" id="filter_<?php echo $i; ?>"><i class="glyphicon glyphicon-trash text-danger"></i></button>
			</div>
		</div>
		<?php
	}
?>

<script>
	$j(function(){
		$j('.clear_filter').click(function(){
			var filt_num = $j(this).attr('id').replace(/^filter_/, '');
			$j('#FilterAnd_' + filt_num + '_').select2('val', '');
			$j('#FilterField_' + filt_num + '_').select2('val', '');
			$j('#FilterOperator_' + filt_num + '_').select2('val', '');
			$j('[name="FilterValue[' + filt_num + ']"]').val('');
		});
	})
</script>

<!-- sorting header  -->   
<div class="row" style="border-bottom: solid 2px #DDD;">
	<div class="col-md-offset-2 col-md-8 vspacer-lg"><strong><?php echo $Translation['order by']; ?></strong></div>
</div>

<!-- sorting rules -->
<?php
	// Fields list
	$sortFields = new Combo;
	$sortFields->ListItem = $this->ColCaption;
	$sortFields->ListData = $this->ColNumber;

	// sort direction
	$sortDirs = new Combo;
	$sortDirs->ListItem = array($Translation['ascending'], $Translation['descending']);
	$sortDirs->ListData = array('asc', 'desc');
	$num_rules = min(maxSortBy, count($this->ColCaption));

	for($i = 0; $i < $num_rules; $i++){
		$sfi = $sd = '';
		if(isset($orderBy[$i])) foreach($orderBy[$i] as $sfi => $sd);

		$sortFields->SelectName = "OrderByField$i";
		$sortFields->SelectID = "OrderByField$i";
		$sortFields->SelectedData = $sfi;
		$sortFields->SelectedText = '';
		$sortFields->Render();

		$sortDirs->SelectName = "OrderDir$i";
		$sortDirs->SelectID = "OrderDir$i";
		$sortDirs->SelectedData = $sd;
		$sortDirs->SelectedText = '';
		$sortDirs->Render();

		$border_style = ($i == $num_rules - 1 ? 'solid 2px #DDD' : 'dotted 1px #DDD');
		?>
		<!-- sorting rule -->
		<div class="row" style="border-bottom: <?php echo $border_style; ?>;">
			<div class="col-xs-2 vspacer-md hidden-md hidden-lg"><strong><?php echo ($i ? $Translation['then by'] : $Translation['order by']); ?></strong></div>
			<div class="col-md-2 col-md-offset-2 vspacer-md hidden-xs hidden-sm text-right"><strong><?php echo ($i ? $Translation['then by'] : $Translation['order by']); ?></strong></div>
			<div class="col-xs-6 col-md-4 vspacer-md"><?php echo $sortFields->HTML; ?></div>
			<div class="col-xs-4 col-md-2 vspacer-md"><?php echo $sortDirs->HTML; ?></div>
		</div>
		<?php
	}

	// ownership options
	$mi = getMemberInfo();
	$adminConfig = config('adminConfig');
	$isAnonymous = ($mi['group'] == $adminConfig['anonymousGroup']);

	if(!$isAnonymous){
		?>
		<!-- ownership header  --> 
		<div class="row filterByOwnership" style="border-bottom: solid 2px #DDD;">
			<div class="col-md-offset-2 col-md-8 vspacer-lg"><strong><?php echo $Translation['Records to display']; ?></strong></div>
		</div>

		<!-- ownership options -->
		<div class="row" style="border-bottom: dotted 2px #DDD;">
			<div class="col-md-8 col-md-offset-2">
				<div class="radio filterByOwnership">
					<label>
						<input type="radio" name="DisplayRecords" id="DisplayRecordsUser" value="user"/>
						<?php echo $Translation['Only your own records']; ?>
					</label>
				</div>
				<div class="radio filterByOwnership">
					<label>
						<input type="radio" name="DisplayRecords" id="DisplayRecordsGroup" value="group"/>
						<?php echo $Translation['All records owned by your group']; ?>
					</label>
				</div>
				<div class="radio filterByOwnership">
					<label>
						<input type="radio" name="DisplayRecords" id="DisplayRecordsAll" value="all"/>
						<?php echo $Translation['All records']; ?>
					</label>
				</div>
			</div>
		</div>
		<?php
	}
?>

<!-- filter actions -->
<div class="row">
	<div class="col-md-2 col-md-offset-2 vspacer-lg">
		<input type="hidden" name="apply_sorting" value="1">
		<button type="submit" id="applyFilters" class="btn btn-success btn-block btn-lg"><i class="glyphicon glyphicon-ok"></i> <?php echo $Translation['apply filters']; ?></button>
	</div>
	<?php if($this->AllowSavingFilters){ ?>
		<div class="col-md-3 vspacer-lg">
			<button type="submit" class="btn btn-default btn-block btn-lg" id="SaveFilter" name="SaveFilter_x" value="1"><i class="glyphicon glyphicon-align-left"></i> <?php echo $Translation['save filters']; ?></button>
		</div>
	<?php } ?>
	<div class="col-md-2 vspacer-lg">
		<button onclick="jQuery('form')[0].reset();" type="submit" id="cancelFilters" class="btn btn-warning btn-block btn-lg"><i class="glyphicon glyphicon-remove"></i> <?php echo $Translation['Cancel']; ?></button>
	</div>
</div>


<script>
	var FiltersPerGroup = <?php echo $FiltersPerGroup; ?>;

	function filterGroupDisplay(groupIndex, hide, animate){
		for(i = ((groupIndex - 1) * FiltersPerGroup + 1); i <= (groupIndex * FiltersPerGroup); i++){
			if(animate){
				if(hide) jQuery('div.FilterSet' + i).fadeOut();
				if(!hide) jQuery('div.FilterSet' + i).fadeIn(function(){
					jQuery('#FilterField_' + ((groupIndex - 1) * FiltersPerGroup + 1) + '_').focus();
				});
			}else{
				if(hide) jQuery('div.FilterSet' + i).hide();
				if(!hide) jQuery('div.FilterSet' + i).show(function(){
					jQuery('#FilterField_' + ((groupIndex - 1) * FiltersPerGroup + 1) + '_').focus();
				});
			}
		}
	}

	jQuery(function(){
		for(i = (FiltersPerGroup + 1); i <= (3 * FiltersPerGroup); i++){
			jQuery('div.FilterSet' + i).hide();
		}
		jQuery('#FilterAnd_' + (FiltersPerGroup + 1) + '_').change(function(){
			filterGroupDisplay(2, (jQuery(this).val() ? false : true), true);
		});
		jQuery('#FilterAnd_' + (2 * FiltersPerGroup + 1) + '_').change(function(){
			filterGroupDisplay(3, (jQuery(this).val() ? false : true), true);
		});

		if(jQuery('#FilterAnd_' + (    FiltersPerGroup + 1) + '_').val()){ filterGroupDisplay(2); }
		if(jQuery('#FilterAnd_' + (2 * FiltersPerGroup + 1) + '_').val()){ filterGroupDisplay(3); }

		var DisplayRecords = '<?php echo html_attr($_REQUEST['DisplayRecords']); ?>';

		switch(DisplayRecords){
			case 'user':
				jQuery('#DisplayRecordsUser').prop('checked', true);
				break;
			case 'group':
				jQuery('#DisplayRecordsGroup').prop('checked', true);
				break;
			default:
				jQuery('#DisplayRecordsAll').prop('checked', true);
		}
	});
</script>
