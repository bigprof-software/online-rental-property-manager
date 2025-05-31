<?php if(!isset($Translation)) { @header('Location: index.php'); exit; } ?>

<table class="table table-striped table-bordered hidden" id="css-query-table">
	<tr><td>odd</td></tr>
	<tr><td>even</td></tr>
</table>

<div class="row">
	<div class="col-md-8" id="filters-section">
		<div class="page-header"><h1>
			<span id="table-title-img"><img src="<?php echo $this->TableIcon; ?>"></span> <?php echo sprintf($Translation['find records that match'], "<mark>{$this->TableTitle}</mark>"); ?>
			<small class="checkbox match-all-groups-checkbox hidden">
				<label>
					<input type="checkbox" id="match-all-groups" style="margin-top: 1px;" 
						<?php echo $FilterAnd[1] != 'or' ? 'checked' : ''; ?>>
					<?php echo $Translation['match all groups']; ?>
				</label>
			</small>
		</h1></div>

		<?php
			$filterGroups = datalist_filters_count / FILTERS_PER_GROUP; // Number of filter groups

			compactFilters($FilterAnd, $FilterField, $FilterOperator, $FilterValue);

			$dataList = $this;
			$comboFields = function($i) use ($dataList, $FilterField) {
				$combo = new Combo;
				$combo->ApplySelect2 = false;
				$combo->Class = 'filter-field';
				$combo->ListItem = array_values($dataList->QueryFieldsFilters);
				$combo->ListData = array_keys($dataList->QueryFieldsIndexed);
				$combo->SelectName = "FilterField[$i]";
				$combo->SelectedData = $FilterField[$i];
				$combo->Render();

				return $combo->HTML;
			};

			$comboOperators = function($i) use ($Translation, $FilterOperator) {
				$combo = new Combo;
				$combo->ApplySelect2 = false;
				$combo->Class = 'filter-operator';
				$combo->ListItem = [$Translation['equal to'], $Translation['not equal to'], $Translation['greater than'], $Translation['greater than or equal to'], $Translation['less than'], $Translation['less than or equal to'] , $Translation['like'] , $Translation['not like'], $Translation['is empty'], $Translation['is not empty']];
				$combo->ListData = array_keys(FILTER_OPERATORS);
				$combo->SelectName = "FilterOperator[$i]";
				$combo->SelectedData = $FilterOperator[$i];
				$combo->Render();

				return $combo->HTML;
			};

			$comboOrderBy = function($i) use ($Translation, $orderBy, $dataList) {
				$orderByField = ''; $orderByDir = 'asc';
				if(isset($orderBy[$i]) && is_array($orderBy[$i])) {
					// set the order by field to the key of the first element
					$orderByField = array_keys($orderBy[$i])[0];
					$orderByDir = $orderBy[$i][$orderByField];
				}

				$combo = new Combo;
				$combo->ApplySelect2 = false;
				$combo->Class = 'filter-order-by';
				$combo->ListItem = $dataList->ColCaption;
				$combo->ListData = $dataList->ColNumber;
				$combo->SelectName = "OrderByField$i";
				$combo->SelectID = "OrderByField-$i";
				$combo->SelectedData = $orderByField;
				$combo->SelectedText = '';
				$combo->Render();

				$ascActive = $descActive = '';
				if(isset($orderBy[$i]) && $orderByDir == 'asc') {
					$ascActive = 'active';
				} elseif(isset($orderBy[$i]) && $orderByDir == 'desc') {
					$descActive = 'active';
				} else {
					$orderByDir = '';
				}

				ob_start(); ?>
				<?php if($i > 0) { ?><div class="filter-order-by-label"><?php echo $Translation['then by']; ?></div><?php } ?>

				<div style="display: flex; align-items: center;" class="filter-order-by-container" data-index="<?php echo $i; ?>">
					<?php echo $combo->HTML; ?>
					<div style="flex: 0 0 auto; margin-left: 10px;">
						<div class="btn-group order-by-buttons">
							<button type="button" class="btn btn-default btn-lg <?php echo $ascActive; ?>" id="OrderByAsc-<?php echo $i; ?>" title="<?php echo html_attr($Translation['ascending']); ?>">
								<span class="glyphicon glyphicon-sort-by-attributes"></span>
							</button>
							<button type="button" class="btn btn-default btn-lg <?php echo $descActive; ?>" id="OrderByDesc-<?php echo $i; ?>" title="<?php echo html_attr($Translation['descending']); ?>">
								<span class="glyphicon glyphicon-sort-by-attributes-alt"></span>
							</button>
						</div>
						<input type="hidden" name="OrderDir<?php echo $i; ?>" value="<?php echo $orderByDir; ?>">
					</div>
				</div>
				<?php

				return ob_get_clean();
			};
		?>

		<?php
			/* SPM link for admin */
			if(getLoggedAdmin()) {
				$spm_installed = false;
				$plugins = get_plugins();
				foreach($plugins as $pl) {
					if($pl['title'] == 'Search Page Maker') $spm_installed = true;
				}

				if(!$spm_installed) echo Notification::show([
					'message' => '<i class="glyphicon glyphicon-info-sign"></i> Wish to offer your users an easier, more-tailored search experience? <a href="https://bigprof.com/appgini/applications/search-page-maker-plugin-discount" target="_blank" class="alert-link"><i class="glyphicon glyphicon-hand-right"></i> Click here to learn how Search Page Maker plugin can help</a>.',
					'dismiss_days' => 30,
					'class' => 'success',
					'id' => 'spm_notification',
				]);
			}
		?>

		<!-- checkboxes for parent filterers -->
		<?php
			foreach($this->filterers as $filterer => $caption) {
				$fltrr_name = 'filterer_' . $filterer;
				$fltrr_val = Request::val($fltrr_name);
				if(strlen($fltrr_val)) {
					?>
						<div class="checkbox">
							<label class="h4" style="line-height: initial;">
								<input type="checkbox" id="<?php echo $fltrr_name; ?>" name="<?php echo $fltrr_name; ?>" value="<?php echo html_attr($fltrr_val); ?>" checked>
								<?php printf($Translation['Only show records having filterer'], $caption, "<mark id=\"{$fltrr_name}_display_value\"></mark>"); ?>
							</label>
						</div>
						<script>
							$j(function() {
								$j.ajax({
									url: 'ajax_combo.php',
									dataType: 'json',
									data: <?php echo json_encode(['id' => to_utf8($fltrr_val), 't' => $this->TableName, 'f' => $filterer, 'o' => 0]); ?>
								}).done(function(resp) {
									$j('#<?php echo $fltrr_name; ?>_display_value').html(resp.results[0].text);
								});
							});
						</script>
					<?php
					break; // currently, only one filterer can be applied at a time
				}
			}
		?>

		<?php for($filterGroupIndex = 1; $filterGroupIndex <= $filterGroups; $filterGroupIndex++) { ?>
			<!-- filter group panel -->
			<div class="panel panel-default filter-group hidden" data-group="<?php echo $filterGroupIndex; ?>">
				<div class="panel-heading text-center">
					<h4 class="panel-title group-title-match-all hidden text-bold"><?php echo $Translation['all of the following conditions']; ?></h4>
					<h4 class="panel-title group-title-match-any hidden text-italic"><?php echo $Translation['any of the following conditions']; ?></h4>
				</div>
				<div class="panel-body">
					<?php for($filterConditionIndex = 1; $filterConditionIndex <= FILTERS_PER_GROUP; $filterConditionIndex++) { ?>
						<?php $filterIndex = (($filterGroupIndex - 1) * FILTERS_PER_GROUP) + $filterConditionIndex; ?>
						<div 
							class="row filter-condition hidden" 
							data-group="<?php echo $filterGroupIndex; ?>" 
							data-condition="<?php echo $filterConditionIndex; ?>" 
							data-index="<?php echo $filterIndex; ?>" 
						>
							<div class="col-sm-4 vspacer-lg">
								<input type="hidden" name="FilterAnd[<?php echo $filterIndex; ?>]" class="filter-and" value="<?php echo $FilterAnd[$filterIndex]; ?>">
								<?php echo $comboFields($filterIndex); ?>
							</div>
							<div class="col-sm-3 vspacer-lg">
								<?php echo $comboOperators($filterIndex); ?>
							</div>
							<div class="col-sm-5 vspacer-lg">
								<div class="input-group">
									<input type="text" name="FilterValue[<?php echo $filterIndex; ?>]" class="filter-value form-control" value="<?php echo html_attr($FilterValue[$filterIndex]); ?>" placeholder="<?php echo html_attr($Translation['value']); ?>">
									<span class="input-group-btn">
										<button type="button" class="btn btn-default delete-filter" data-group="<?php echo $filterGroupIndex; ?>" data-condition="<?php echo $filterConditionIndex; ?>" data-index="<?php echo $filterIndex; ?>" title="<?php echo $Translation['remove this condition']; ?>">
											<span class="glyphicon glyphicon-remove text-danger"></span>
										</button>
										<button type="button" class="btn btn-default add-filter" data-group="<?php echo $filterGroupIndex; ?>" data-condition="<?php echo $filterConditionIndex; ?>" data-index="<?php echo $filterIndex; ?>" title="<?php echo $Translation['add another condition to this group']; ?>">
											<span class="glyphicon glyphicon-plus text-primary"></span>
										</button>
									</span>
								</div>
							</div>
						</div>
					<?php } ?>
				</div>
				<div class="panel-footer">
					<div class="row">
						<div class="col-sm-6">
							<div class="checkbox checkbox-match-all-conditions">
								<label>
									<input type="checkbox" data-group="<?php echo $filterGroupIndex; ?>" class="match-all-conditions">
									<?php echo $Translation['match all above conditions']; ?>
								</label>
							</div>
						</div>
						<div class="col-sm-6 text-right">
							<button type="button" class="btn btn-default btn-lg add-group" data-group="<?php echo $filterGroupIndex; ?>" title="<?php echo $Translation['add another condition group']; ?>">
								<span class="glyphicon glyphicon-plus"></span>
							</button>
						</div>
					</div>
				</div>
			</div>
			<!-- end filter group panel -->
			<div class="group-and-or text-center hidden" data-group="<?php echo $filterGroupIndex; ?>">
				<span class="group-and label label-warning"><?php echo $Translation['and']; ?></span>
				<span class="group-or label label-default"><?php echo $Translation['or']; ?></span>
			</div>
		<?php } ?>
	</div>
	<div class="col-md-4 side-panel" id="sorting-ownership-section">
		<hr class="visible-xs visible-sm">
		<!-- this div is just a spacer to vertically align the order by panel with the filter groups -->
		<div class="page-header hidden-sm hidden-xs" style="visibility: hidden;"><h1>
			&nbsp;
			<small class="checkbox match-all-groups-checkbox hidden">
				<label>
					<input type="checkbox" style="margin-top: 1px;">
					&nbsp;
				</label>
			</small>
		</h1></div>

		<div class="panel panel-default">
			<div class="panel-heading">
				<h2 class="panel-title text-bold text-center">
					<span class="glyphicon glyphicon-sort-by-attributes"></span>
					<?php echo $Translation['order by']; ?>
				</h2>
			</div>
			<div class="panel-body">
				<?php for($i = 0; $i < 4; $i++) { echo $comboOrderBy($i); } ?>
			</div>
		</div>

		<?php
			// ownership options
			$mi = getMemberInfo();
			$adminConfig = config('adminConfig');
			$isAnonymous = ($mi['group'] == $adminConfig['anonymousGroup']);

			// get view permissions for current table
			$viewPerm = getTablePermissions($this->TableName)['view'];

			// set a default value for $DisplayRecords as follows:
				// 1. if viewPerm == 3 and DisplayRecords is not one of ['all', 'group', 'user'], set it to 'all'
				// 2. if viewPerm == 2 and DisplayRecords is not one of ['group', 'user'], set it to 'group'
				// 3. if viewPerm == 1 and DisplayRecords is not one of ['user'], set it to 'user'
			if($viewPerm == 3 && !in_array($DisplayRecords, ['all', 'group', 'user'])) {
				$DisplayRecords = 'all';
			} elseif($viewPerm == 2 && !in_array($DisplayRecords, ['group', 'user'])) {
				$DisplayRecords = 'group';
			} elseif($viewPerm == 1 && $DisplayRecords != 'user') {
				$DisplayRecords = 'user';
			}

			if(!$isAnonymous && $viewPerm > 1) {
				?>
				<div class="panel panel-default">
					<div class="panel-heading">
						<h2 class="panel-title text-bold text-center">
							<span class="glyphicon glyphicon-eye-open"></span>
							<?php echo $Translation['Records to display']; ?>
						</h2>
					</div>
					<div class="panel-body">
						<div class="block-flex">
							<label class="text-center highlight-checked">
								<input type="radio" name="DisplayRecords" value="user" <?php echo $DisplayRecords == 'user' ? 'checked' : ''; ?>>
								<img src="resources/images/user-solid.svg">
								<div><?php echo $Translation['Only your own records']; ?></div>
							</label>
							<label class="text-center highlight-checked">
								<input type="radio" name="DisplayRecords" value="group" <?php echo $DisplayRecords == 'group' ? 'checked' : ''; ?>>
								<img src="resources/images/users-solid.svg">
								<div><?php echo $Translation['All records owned by your group']; ?></div>
							</label>
							<?php if($viewPerm == 3) { ?>
								<label class="text-center highlight-checked">
									<input type="radio" name="DisplayRecords" value="all" <?php echo $DisplayRecords == 'all' ? 'checked' : ''; ?>>
									<img src="resources/images/user-secret-solid.svg">
									<div><?php echo $Translation['All records']; ?></div>
								</label>
							<?php } ?>
						</div>
					</div>
				</div>
				<?php
			}
		?>
		<input type="hidden" name="RecordsPerPage" value="<?php echo intval($this->RecordsPerPage); ?>">
		<input type="hidden" name="SearchString" value="<?php echo html_attr($SearchString); ?>">
		<input type="hidden" name="apply_sorting" value="1">

	</div>
</div>

<nav class="navbar navbar-default navbar-fixed-bottom" style="z-index: 2000;">
	<div class="container" style="margin-top: 10px; margin-bottom: 10px;">
		<div class="btn-group block-flex" style="max-width: 1000px;">
			<button type="submit" id="applyFilters" class="btn btn-success btn-lg auto-width">
				<i class="glyphicon glyphicon-ok"></i>
				<span class="hidden-xs"><?php echo $Translation['apply filters']; ?></span>
			</button>
			<?php if($this->AllowSavingFilters) { ?>
				<button type="submit" class="btn btn-default btn-lg" id="SaveFilter" name="SaveFilter_x" value="1">
					<i class="glyphicon glyphicon-bookmark"></i>
					<span class="hidden-xs"><?php echo $Translation['save filters']; ?></span>
				</button>
			<?php } ?>
			<button onclick="$j('form')[0].reset();" type="submit" id="cancelFilters" class="btn btn-warning btn-lg">
				<i class="glyphicon glyphicon-remove"></i>
				<span class="hidden-xs"><?php echo $Translation['Cancel']; ?></span>
			</button>
		</div>
	</div>
</nav>

<script>
	$j(() => {
		const FILTER_DEBUG = false; // set to true to enable debug mode
		const filterConditionsPerGroup = <?php echo FILTERS_PER_GROUP; ?>;
		const filterGroups = <?php echo $filterGroups; ?>;

		const consoleLog = (...args) => FILTER_DEBUG && console.log(...args);

		const _listFilters = () => {
			if(!FILTER_DEBUG) return; // do nothing if debug mode is off

			let filters = '';
			for(let gi = 1; gi <= filterGroups; gi++) {
				for(let fi = 1; fi <= filterConditionsPerGroup; fi++) {
					const filterIndex = ((gi - 1) * filterConditionsPerGroup) + fi;
					const filterAnd = $j(`[name="FilterAnd[${filterIndex}]"]`).val();
					const filterField = $j(`[name="FilterField[${filterIndex}]"]`).val();
					const filterOperator = $j(`[name="FilterOperator[${filterIndex}]"]`).val();
					const filterValue = $j(`[name="FilterValue[${filterIndex}]"]`).val();

					if(filterField === '' || filterOperator === '') continue; // skip empty conditions

					if(filterIndex === 1) {
						// first condition
						filters += `Match records where:\n  ${filterField} ${filterOperator} ${filterValue.length ? filterValue : 'NULL'}`;
					} else if(filterIndex === (gi - 1) * filterConditionsPerGroup + 1) {
						// first condition in group
						filters += `\n${filterAnd}:\n  ${filterField} ${filterOperator} ${filterValue.length ? filterValue : 'NULL'}`;
					} else {
						filters += `\n  ${filterAnd} ${filterField} ${filterOperator} ${filterValue.length ? filterValue : 'NULL'}`;
					}
				}
			}

			consoleLog(filters);
		}

		const applySelect2 = () => {
			// run only once
			if(AppGini._isSelect2Applied) return;
			AppGini._isSelect2Applied = true;

			// Initialize select2 for filter fields and operators
			$j('.filter-field, .filter-operator, .filter-order-by').select2({
				minimumResultsForSearch: 3,
				width: '100%'
			});
		}

		const applyFilterBorderAndBG = () => {
			// run only once
			if(AppGini._isFilterBorderAndBGApplied) return;
			AppGini._isFilterBorderAndBGApplied = true;

			// get the border color of #css-query-table
			const borderColor = getComputedStyle(document.querySelector('#css-query-table')).borderColor;
			// get the bg color of odd rows of #css-query-table
			const bgColor = getComputedStyle(document.querySelector('#css-query-table tr:nth-child(odd)')).backgroundColor;

			consoleLog({borderColor, bgColor});

			// set CSS variables for table-border-color and table-bg-color
			document.documentElement.style.setProperty('--table-border-color', borderColor);
			document.documentElement.style.setProperty('--table-bg-color', bgColor);
		}

		const filterConditionIsEmpty = () => {
			const results = [];
			$j('.filter-condition').each(function() {
				const filterCondition = $j(this);
				const filterIndex = filterCondition.data('index');
				const filterField = filterCondition.find('.filter-field').select2('val');
				const filterOperator = filterCondition.find('.filter-operator').select2('val');

				results[filterIndex] = (filterField === '' || filterOperator === '');
			});
			consoleLog({call: 'filterConditionIsEmpty()', results: results.join(', ')});
			return results;
		}

		const filterGroupIsEmpty = () => {
			// results array has {filters+1} elements, all initialized to true         
			const results = Array(filterGroups + 1).fill(true);
			const filterConditionEmpty = filterConditionIsEmpty();

			for(let gi = 1; gi <= filterGroups; gi++) {
				for(let fi = 1; fi <= filterConditionsPerGroup; fi++) {
					const filterIndex = ((gi - 1) * filterConditionsPerGroup) + fi;
					if (!filterConditionEmpty[filterIndex]) {
						results[gi] = false;
						break;
					}
				}
			}

			consoleLog({call: 'filterGroupIsEmpty()', results: results.join(', ')});

			return results;
		}

		const copyFilterCondition = (sourceIndex, targetIndex) => {
			const sourceCondition = $j(`.filter-condition[data-index="${sourceIndex}"]`);
			const targetCondition = $j(`.filter-condition[data-index="${targetIndex}"]`);

			consoleLog({call: `copyFilterCondition(${sourceIndex}, ${targetIndex})`, source: sourceCondition, target: targetCondition});

			// Copy the values from the source condition to the target condition
			targetCondition.find('.filter-and').val(sourceCondition.find('.filter-and').val());
			targetCondition.find('.filter-field').select2('val', sourceCondition.find('.filter-field').select2('val'));
			targetCondition.find('.filter-operator').select2('val', sourceCondition.find('.filter-operator').select2('val'));
			targetCondition.find('.filter-value').val(sourceCondition.find('.filter-value').val());
		}

		const copyFilterGroup = (sourceIndex, targetIndex) => {
			const sourceGroup = $j(`.filter-group[data-group="${sourceIndex}"]`);
			const targetGroup = $j(`.filter-group[data-group="${targetIndex}"]`);

			consoleLog({call: `copyFilterGroup(${sourceIndex}, ${targetIndex})`, source: sourceGroup, target: targetGroup});

			// Copy the values from the source group to the target group
			sourceGroup.find('.filter-condition').each(function() {
				const filterCondition = $j(this);
				const sourceConditionIndex = filterCondition.data('condition');
				const sourceFilterIndex = filterCondition.data('index');
				const targetCondition = targetGroup.find(`.filter-condition[data-condition="${sourceConditionIndex}"]`);
				const targetFilterIndex = targetCondition.data('index');
				// Copy the values from the source condition to the target condition
				copyFilterCondition(sourceFilterIndex, targetFilterIndex);
			});
		}

		const emptyFilterGroup = (filterGroupIndex) => {
			consoleLog({call: `emptyFilterGroup(${filterGroupIndex})`});

			// if already empty, do nothing
			if (filterGroupIsEmpty()[filterGroupIndex]) return;

			// Clear the values of the filter group
			$j(`.filter-group[data-group="${filterGroupIndex}"] .filter-condition`).each(function() {
				const filterCondition = $j(this);
				const filterIndex = filterCondition.data('index');

				// Clear the values of the filter condition
				// exception: if first condition in group, skip filter-and
				if (filterCondition.data('condition') > 1) {
					filterCondition.find('.filter-and').val('or');
				}
				emptyFilterCondition(filterIndex);
			});
		}

		const emptyFilterCondition = (filterIndex) => {
			consoleLog({call: `emptyFilterCondition(${filterIndex})`});

			// Clear the values of the filter condition
			const filterCondition = $j(`.filter-condition[data-index="${filterIndex}"]`);
			filterCondition.find('.filter-field').select2('val', '');
			filterCondition.find('.filter-operator').select2('val', '');
			filterCondition.find('.filter-value').val('');
		}

		const handleAddGroup = () => {
			// run only once
			if(AppGini._isAddGroupHandled) return;
			AppGini._isAddGroupHandled = true;

			// Add filter group
			$j('.add-group').click(function() {
				const filterGroupIndex = $j(this).data('group');
				const nextFilterGroup = $j(`.filter-group[data-group="${filterGroupIndex + 1}"]`);

				if (!nextFilterGroup.length) return; // No more filter groups to add

				const nextFilterIndex = nextFilterGroup.data('index');
				updateFiltersUI(nextFilterIndex, filterGroupIndex + 1);

				// focus filter field of the first condition in the next group
				nextFilterGroup.find('.filter-field').select2('open');
			});
		}

		const handleFilterUpdates = () => {
			// run only once
			if(AppGini._isFilterUpdatesHandled) return;
			AppGini._isFilterUpdatesHandled = true;

			// on changing filter field or operator, update filters UI
			$j('.filter-field, .filter-operator').change(function() {
				const filterCondition = $j(this).closest('.filter-condition');
				const filterIndex = filterCondition.data('index');
				const filterGroupIndex = filterCondition.data('group');

				updateFiltersUI(filterIndex, filterGroupIndex);
			});
		}

		const handleAddFilterCondition = () => {
			// run only once
			if(AppGini._isAddFilterConditionHandled) return;
			AppGini._isAddFilterConditionHandled = true;

			// Add filter condition
			$j('.add-filter').click(function() {
				const filterCondition = $j(this).closest('.filter-condition');
				const filterGroupIndex = filterCondition.data('group');
				const filterConditionIndex = filterCondition.data('condition');
				const nextFilterCondition = filterCondition.next('.filter-condition');

				if (!nextFilterCondition.length) return; // No more filter conditions to add in this group

				const nextFilterIndex = nextFilterCondition.data('index');
				updateFiltersUI(nextFilterIndex);

				// focus filter field of the next condition
				nextFilterCondition.find('.filter-field').select2('open');
			});
		}

		const handleDeleteFilterCondition = () => {
			// run only once
			if(AppGini._isDeleteFilterConditionHandled) return;
			AppGini._isDeleteFilterConditionHandled = true;

			// Delete filter condition
			$j('.delete-filter').click(function() {
				const btn = $j(this);
				const filterIndex = btn.data('index');
				const filterConditionIndex = btn.data('condition');
				const filterGroupIndex = btn.data('group');
				const firstConditionIndex = (filterGroupIndex - 1) * filterConditionsPerGroup + 1;
				const filterConditionEmpty = filterConditionIsEmpty();
				const filterGroupEmpty = filterGroupIsEmpty();

				consoleLog({action: 'delete', filterIndex, filterConditionIndex, filterGroupIndex, filterConditionsPerGroup});

				// if not last condition in group, shift group conditions up by
				// looping through the next conditions and copying their values to the previous condition
				if (filterConditionIndex < filterConditionsPerGroup) {
					for (let fi = filterIndex; fi < filterIndex + filterConditionsPerGroup - filterConditionIndex; fi++) {
						// if next condition is empty, just clear the current condition
						if (filterConditionEmpty[fi + 1]) {
							emptyFilterCondition(fi);
							// update cached filterConditionEmpty
							filterConditionEmpty[fi] = true;
							continue;
						}

						copyFilterCondition(fi + 1, fi);
					}

					// check if the group is empty by checking filterConditinEmpty, and update the cached filterGroupEmpty accordingly
					filterGroupEmpty[filterGroupIndex] = true; // assume empty till proven otherwise
					for (let fi = firstConditionIndex; fi < firstConditionIndex + filterConditionsPerGroup; fi++) {
						if (!filterConditionEmpty[fi]) {
							filterGroupEmpty[filterGroupIndex] = false;
							break;
						}
					}
				}

				// then clear the last condition in the group
				emptyFilterCondition(filterGroupIndex * filterConditionsPerGroup);
				filterConditionEmpty[filterGroupIndex * filterConditionsPerGroup] = true;

				// if no more conditions in the group, and not last group, shift groups up by
				// looping through the next groups and copying their values to the previous group
				if (filterGroupIndex < filterGroups && filterGroupEmpty[filterGroupIndex]) {
					for (let gi = filterGroupIndex; gi < filterGroups; gi++) {
						// if next group is empty, just clear the current group
						if (filterGroupEmpty[gi + 1]) {
							emptyFilterGroup(gi);
							// update cached filterGroupEmpty
							filterGroupEmpty[gi] = true;
							continue;
						}

						copyFilterGroup(gi + 1, gi);
					}

					// then clear the last group
					emptyFilterGroup(filterGroups);
				}

				updateFiltersUI();
			});
		}

		const handleMatchAllConditions = () => {
			// run only once
			if(AppGini._isMatchAllConditionsHandled) return;
			AppGini._isMatchAllConditionsHandled = true;

			// Match all conditions
			$j('.match-all-conditions').change(function() {
				const filterGroupIndex = $j(this).data('group');
				const filterGroup = $j(`.filter-group[data-group="${filterGroupIndex}"]`);
				const filterAnd = $j(this).is(':checked') ? 'and' : 'or';

				// set the filter-and value of every condition in the group except the first one
				filterGroup.find('.filter-and').slice(1).val(filterAnd);
				updateFiltersUI(null, filterGroupIndex);
			});
		}

		const handleMatchAllConditionGroups = () => {
			// run only once
			if(AppGini._isMatchAllConditionGroupsHandled) return;
			AppGini._isMatchAllConditionGroupsHandled = true;

			// Match all condition groups
			$j('#match-all-groups').change(function() {
				const filterAnd = $j(this).is(':checked') ? 'and' : 'or';
				$j('.filter-group').each(function() {
					const filterGroup = $j(this);
					const firstFilterCondition = filterGroup.find('.filter-condition').first();
					const firstFilterAnd = firstFilterCondition.find('.filter-and');

					firstFilterAnd.val(filterAnd);
				});
				updateFiltersUI();
			});
		}

		const handleOrderBy = () => {
			// run only once
			if(AppGini._isOrderByHandled) return;
			AppGini._isOrderByHandled = true;

			// Order by dropdown
			$j('.filter-order-by').change(function() {
				const filterIndex = $j(this).closest('.filter-order-by-container').data('index');
				const orderByAsc = $j(`#OrderByAsc-${filterIndex}`);

				// if order dir buttons are disabled, set 'asc' as default
				if (orderByAsc.prop('disabled')) {
					$j(`[name="OrderDir${filterIndex}"]`).val('asc');
				}

				updateFiltersUI();
			});

			// Order by buttons
			$j('.order-by-buttons button').click(function() {
				const filterIndex = $j(this).closest('.filter-order-by-container').data('index');
				const orderByAsc = $j(`#OrderByAsc-${filterIndex}`);
				const orderByDesc = $j(`#OrderByDesc-${filterIndex}`);

				if ($j(this).is(orderByAsc)) {
					$j(`[name="OrderDir${filterIndex}"]`).val('asc');
				} else {
					$j(`[name="OrderDir${filterIndex}"]`).val('desc');
				}

				updateFiltersUI();
			});
		}

		const fixBtnBorders = (btn, skipFirstBtnCheck = false) => {
			// get border radious of .btn
			if(AppGini._btnBorderRadius === undefined) {
				// add a dummy element to get the border radius
				const dummy = $j('<div class="btn btn-default" style="position: absolute; top: -9999px; left: -9999px;"></div>');
				$j('body').append(dummy);
				AppGini._btnBorderRadius = parseInt(dummy.css('border-radius'));
				dummy.remove();
			}

			// if btn is first visible in group, set left border radius
			if(btn.prevAll('.btn:visible').length === 0 && !skipFirstBtnCheck) {
				btn.css('border-top-left-radius', AppGini._btnBorderRadius);
				btn.css('border-bottom-left-radius', AppGini._btnBorderRadius);
			} else {
				btn.css('border-top-left-radius', 0);
				btn.css('border-bottom-left-radius', 0);
			}
			// if btn is last visible in group, set right border radius
			if(btn.nextAll('.btn:visible').length === 0) {
				btn.css('border-top-right-radius', AppGini._btnBorderRadius);
				btn.css('border-bottom-right-radius', AppGini._btnBorderRadius);
			} else {
				btn.css('border-top-right-radius', 0);
				btn.css('border-bottom-right-radius', 0);
			}
		}

		const handleFieldSelected = () => {
			// run only once
			if(AppGini._isFieldSelectedHandled) return;
			AppGini._isFieldSelectedHandled = true;

			// Handle field selected event
			$j('.filter-field').on('change', function() {
				const filterCondition = $j(this).closest('.filter-condition');
				const filterIndex = filterCondition.data('index');
				const filterGroupIndex = filterCondition.data('group');
				const filterField = $j(this).val();
				const filterOperator = $j(this).closest('.filter-condition').find('.filter-operator');

				consoleLog({action: 'field selected', filterIndex, filterGroupIndex, filterField, filterCondition});

				// set the operator to 'equal-to' if the operator is not already set
				if (filterOperator.select2('val') === '') {
					filterOperator.select2('val', 'equal-to');
				}
				// focus on the operator field
				filterOperator.select2('focus');

				updateFiltersUI(filterIndex, filterGroupIndex);
			});
		}

		const handleOperatorSelected = () => {
			// run only once
			if(AppGini._isOperatorSelectedHandled) return;
			AppGini._isOperatorSelectedHandled = true;

			// Handle operator selected event
			$j('.filter-operator').on('change', function() {
				const filterCondition = $j(this).closest('.filter-condition');
				const filterIndex = filterCondition.data('index');
				const filterGroupIndex = filterCondition.data('group');
				const filterField = $j(this).closest('.filter-condition').find('.filter-field').select2('val');
				const filterOperator = $j(this).val();

				consoleLog({action: 'operator selected', filterIndex, filterGroupIndex, filterField, filterOperator});

				// if operator not empty, and not 'is-empty', and not 'is-not-empty', focus on the value field
				if (filterOperator !== '' && filterOperator !== 'is-empty' && filterOperator !== 'is-not-empty') {
					$j(this).closest('.filter-condition').find('.filter-value').focus();
				}

				updateFiltersUI(filterIndex, filterGroupIndex);
			});
		}

		const updateFiltersUI = (showFilterIndex=null, showGroupIndex = null) => {
			applyFilterBorderAndBG();
			applySelect2();
			handleFilterUpdates();
			handleAddFilterCondition();
			handleDeleteFilterCondition();
			handleAddGroup();
			handleMatchAllConditions();
			handleMatchAllConditionGroups();
			handleOrderBy();
			handleFieldSelected();
			handleOperatorSelected();

			consoleLog(`updateFiltersUI(${showFilterIndex}, ${showGroupIndex})`);

			// cache filterConditionIsEmpty and filterGroupIsEmpty
			const fcie = filterConditionIsEmpty(), fgie = filterGroupIsEmpty();

			// Hide empty filter conditions except the first one in each group
			$j('.filter-condition').each(function() {
				const filterCondition = $j(this);

				const filterIndex = filterCondition.data('index');
				const filterGroupIndex = filterCondition.data('group');
				const filterConditionIndex = filterCondition.data('condition');

				// Show the filter condition if it's not empty, or if it's the first one in the group, or if it's the one to be shown
				filterCondition.toggleClass('hidden', fcie[filterIndex] && filterConditionIndex > 1 && filterIndex !== showFilterIndex);

				// hide the add condition button?
				const addButton = filterCondition.find('.add-filter');
				addButton.toggleClass('hidden', // if:
					fcie[filterIndex]
					// or last condition in group
					|| filterConditionIndex === filterConditionsPerGroup
					// or not last condition in group and next condition is not empty
					|| (filterConditionIndex < filterConditionsPerGroup && !fcie[filterIndex + 1])
					// or not last condition in group and next condition index is showFilterIndex
					|| (filterConditionIndex < filterConditionsPerGroup && filterIndex + 1 === showFilterIndex)
				);
				// fix delete button borders
				fixBtnBorders(filterCondition.find('.delete-filter'), true);
			});

			// Hide empty filter groups except the first one and the one to be shown if specified
			$j('.filter-group').each(function() {
				const filterGroup = $j(this);
				const filterGroupIndex = filterGroup.data('group');

				filterGroup.toggleClass('hidden', // if:
					// if the group is empty
					fgie[filterGroupIndex]
					// and not the first group
					&& filterGroupIndex > 1
					// and not the one to be shown
					&& filterGroupIndex !== showGroupIndex
				);

				// hide the add group button?
				const addButton = filterGroup.find('.add-group');
				addButton.toggleClass('hidden', // if:
					// if the group is empty
					fgie[filterGroupIndex]
					// or last group
					|| filterGroupIndex === filterGroups
					// or not last group and next group is not empty
					|| (filterGroupIndex < filterGroups && !fgie[filterGroupIndex + 1])
					// or not last group and next group index is showGroupIndex
					|| (filterGroupIndex < filterGroups && filterGroupIndex + 1 === showGroupIndex)
				);

				// hide the group-and-or label?
				const groupAndOr = filterGroup.next('.group-and-or');
				groupAndOr.toggleClass('hidden', // if:
					// if the group is empty
					fgie[filterGroupIndex]
					// or the last group
					|| filterGroupIndex === filterGroups
					// or the next group is empty and is not the one to be shown
					|| (filterGroupIndex < filterGroups && fgie[filterGroupIndex + 1] && filterGroupIndex + 1 !== showGroupIndex)
				);

				// set group title and match all conditions checkbox based on the second condition's filter-and value
				// if group has only one condition, hide all group titles and match all conditions checkbox
				const secondCondition = filterGroup.find('.filter-condition').eq(1);
				const filterAnd = secondCondition.find('.filter-and').val();
				const matchAllConditions = filterGroup.find('.match-all-conditions');
				const groupTitleMatchAll = filterGroup.find('.group-title-match-all');
				const groupTitleMatchAny = filterGroup.find('.group-title-match-any');
				const hasMoreThanOneCondition = filterGroup.find('.filter-condition:visible').length > 1;

				matchAllConditions.prop('checked', filterAnd.toLowerCase() !== 'or');
				groupTitleMatchAll.toggleClass('hidden', filterAnd.toLowerCase() === 'or' || !hasMoreThanOneCondition);
				groupTitleMatchAny.toggleClass('hidden', filterAnd.toLowerCase() !== 'or' || !hasMoreThanOneCondition);
				matchAllConditions.closest('.checkbox').toggleClass('hidden', !hasMoreThanOneCondition);
			});

			// toggle .group-and and .group-or based on #match-all-groups
			const matchAllGroups = $j('#match-all-groups').is(':checked');
			$j('.group-and').toggleClass('hidden', !matchAllGroups);
			$j('.group-or').toggleClass('hidden', matchAllGroups);

			// toggle match-all-groups checkbox based on count of non-empty groups
			const nonEmptyGroups = fgie.filter((group, index) => group === false && index > 0).length;
			$j('.match-all-groups-checkbox').toggleClass('hidden', nonEmptyGroups <= 1);

			// enable/disable .order-by buttons based on OrderByField select and OrderDir hidden inputs
			$j('.filter-order-by').each(function() {
				const orderByField = $j(this);
				const orderByIndex = orderByField.closest('.filter-order-by-container').data('index');
				const orderByFieldVal = orderByField.select2('val');
				const orderByDir = $j(`[name="OrderDir${orderByIndex}"]`).val();
				const orderByAsc = $j(`#OrderByAsc-${orderByIndex}`);
				const orderByDesc = $j(`#OrderByDesc-${orderByIndex}`);

				if (orderByFieldVal === '') {
					orderByAsc.removeClass('active').prop('disabled', true);
					orderByDesc.removeClass('active').prop('disabled', true);
				} else {
					orderByAsc.prop('disabled', false).toggleClass('active', orderByDir === 'asc');
					orderByDesc.prop('disabled', false).toggleClass('active', orderByDir === 'desc');
				}
			});

			_listFilters();
		}

		updateFiltersUI();

		// if #match-all-groups is visible, focus it
		if ($j('#match-all-groups').is(':visible')) {
			$j('#match-all-groups').focus();
		} else {
			// focus the first filter field
			$j('.filter-field').first().select2('focus');
		}
	})
</script>

<style>
	.filter-condition + .filter-condition {
		border-top: 1px dotted var(--table-border-color);
	}
	.filter-condition {
		padding-bottom: 10px;
		padding-top: 10px;
	}
	/* bg color for even filter conditions */
	.filter-condition:nth-child(even) {
		background-color: var(--table-bg-color);
	}
	.filter-group > .panel-body {
		padding-top: 0;
		padding-bottom: 0;
	}
	.group-and-or {
		text-transform: uppercase;
		margin-bottom: 20px;
		font-size: large;
	}
	.page-header small.checkbox {
		text-align: right;
	}
	.filter-order-by-label {
		margin-top: 1em;
		font-weight: bold;
	}
	@media (max-width: 767px) {
		.page-header small.checkbox {
			text-align: center;
		}
	}
</style>

