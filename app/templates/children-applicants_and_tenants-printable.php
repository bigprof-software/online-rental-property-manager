<?php
	if(!isset($Translation)) die('No direct access allowed.');
	$current_table = 'applicants_and_tenants';
	$cleaner = new CI_Input(datalist_db_encoding);
	$firstRecord = null;
?>
<script>
	// show child records count in badge in tab title
	$j(() => { $j('.child-count-<?php echo $parameters['ChildTable']; ?>-<?php echo $parameters['ChildLookupField']; ?>').text(<?php echo $totalMatches; ?>) });

	<?php echo $current_table; ?>GetChildrenRecordsList = function(command) {
		var param = {
			ChildTable: "<?php echo $parameters['ChildTable']; ?>",
			ChildLookupField: "<?php echo $parameters['ChildLookupField']; ?>",
			SelectedID: "<?php echo addslashes($parameters['SelectedID']); ?>",
			Page: <?php echo addslashes($parameters['Page']); ?>,
			SortBy: <?php echo ($parameters['SortBy'] === false ? '""' : $parameters['SortBy']); ?>,
			SortDirection: '<?php echo $parameters['SortDirection']; ?>',
			AutoClose: <?php echo ($config['auto-close'] ? 'true' : 'false'); ?>
		};
		var panelID = "panel_<?php echo "{$parameters['ChildTable']}-{$parameters['ChildLookupField']}"; ?>";
		var mbWidth = window.innerWidth * 0.9;
		var mbHeight = window.innerHeight * 0.8;
		if(mbWidth > 1000) { mbWidth = 1000; }
		if(mbHeight > 800) { mbHeight = 800; }

		switch(command.Verb) {
			case 'sort': /* order by given field index in 'SortBy' */
				post("parent-children.php", {
					ChildTable: param.ChildTable,
					ChildLookupField: param.ChildLookupField,
					SelectedID: param.SelectedID,
					Page: param.Page,
					SortBy: command.SortBy,
					SortDirection: command.SortDirection,
					Operation: 'get-records-printable'
				}, panelID, undefined, 'pc-loading', function() { AppGini.calculatedFields.init() });
				break;
			case 'page': /* next or previous page as provided by 'Page' */
				if(command.Page.toLowerCase() == 'next') { command.Page = param.Page + 1; }
				else if(command.Page.toLowerCase() == 'previous') { command.Page = param.Page - 1; }

				if(command.Page < 1 || command.Page > <?php echo ceil($totalMatches / $config['records-per-page']); ?>) { return; }
				post("parent-children.php", {
					ChildTable: param.ChildTable,
					ChildLookupField: param.ChildLookupField,
					SelectedID: param.SelectedID,
					Page: command.Page,
					SortBy: param.SortBy,
					SortDirection: param.SortDirection,
					Operation: 'get-records-printable'
				}, panelID, undefined, 'pc-loading', function() { AppGini.calculatedFields.init() });
				break;
			case 'reload': /* just a way of refreshing children, retaining sorting and pagination & without reloading the whole page */
				post("parent-children.php", {
					ChildTable: param.ChildTable,
					ChildLookupField: param.ChildLookupField,
					SelectedID: param.SelectedID,
					Page: param.Page,
					SortBy: param.SortBy,
					SortDirection: param.SortDirection,
					Operation: 'get-records-printable'
				}, panelID, undefined, 'pc-loading', function() { AppGini.calculatedFields.init() });
				break;
		}
	};
</script>

<div class="row">
	<div class="col-xs-12 col-md-12">

		<div class="page-header"><h1>
			<?php echo ($config['table-icon'] ? '<img src="' . $config['table-icon'] . '">' : ''); ?>
			<?php echo $config['tab-label']; ?>
		</h1></div>


		<div class="table-responsive">
			<table data-tablename="<?php echo $current_table; ?>" class="table table-striped table-hover table-condensed table-bordered">
				<thead>
					<tr>
						<?php if(is_array($config['display-fields'])) foreach($config['display-fields'] as $fieldIndex => $fieldLabel) { ?>
							<th 
								<?php if($config['sortable-fields'][$fieldIndex]) { ?>
									onclick="<?php echo $current_table; ?>GetChildrenRecordsList({
										Verb: 'sort',
										SortBy: <?php echo $fieldIndex; ?>,
										SortDirection: '<?php echo ($parameters['SortBy'] == $fieldIndex && $parameters['SortDirection'] == 'asc' ? 'desc' : 'asc'); ?>'
									});"
									style="cursor: pointer;"
									tabindex="0"
								<?php } ?>
								class="<?php echo "{$current_table}-{$config['display-field-names'][$fieldIndex]}"; ?>">
								<?php echo $fieldLabel; ?>
								<?php if($parameters['SortBy'] == $fieldIndex && $parameters['SortDirection'] == 'desc') { ?>
									<i class="glyphicon glyphicon-sort-by-attributes-alt text-warning"></i>
								<?php } elseif($parameters['SortBy'] == $fieldIndex && $parameters['SortDirection'] == 'asc') { ?>
									<i class="glyphicon glyphicon-sort-by-attributes text-warning"></i>
								<?php } ?>
							</th>
						<?php } ?>
					</tr>
				</thead>
				<tbody>
					<?php if(is_array($records)) foreach($records as $pkValue => $record) { ?>
					<tr data-id="<?php echo html_attr($pkValue); ?>">
						<td class="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][1]}"; ?>" id="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][1]}-" . html_attr($record[$config['child-primary-key-index']]); ?>"><?php echo safe_html($record[1]); ?></td>
						<td class="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][2]}"; ?>" id="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][2]}-" . html_attr($record[$config['child-primary-key-index']]); ?>"><?php echo safe_html($record[2]); ?></td>
						<td class="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][3]}"; ?>" id="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][3]}-" . html_attr($record[$config['child-primary-key-index']]); ?>"><a href="mailto:<?php echo html_attr($record[3]); ?>" class="btn btn-info" title="<?php echo html_attr($record[3]); ?>"><i class="glyphicon glyphicon-envelope"></i></a></td>
						<td class="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][4]}"; ?>" id="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][4]}-" . html_attr($record[$config['child-primary-key-index']]); ?>"><?php echo safe_html($record[4]); ?></td>
						<td class="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][5]}"; ?>" id="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][5]}-" . html_attr($record[$config['child-primary-key-index']]); ?>"><?php echo safe_html($record[5]); ?></td>
						<td class="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][6]}"; ?> text-center" id="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][6]}-" . html_attr($record[$config['child-primary-key-index']]); ?>"><?php echo safe_html($record[6]); ?></td>
						<td class="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][9]}"; ?> text-right" id="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][9]}-" . html_attr($record[$config['child-primary-key-index']]); ?>"><?php echo safe_html($record[9]); ?></td>
						<td class="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][10]}"; ?> text-right" id="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][10]}-" . html_attr($record[$config['child-primary-key-index']]); ?>"><?php echo safe_html($record[10]); ?></td>
						<td class="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][11]}"; ?> text-right" id="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][11]}-" . html_attr($record[$config['child-primary-key-index']]); ?>"><?php echo safe_html($record[11]); ?></td>
						<td class="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][12]}"; ?> text-center" id="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][12]}-" . html_attr($record[$config['child-primary-key-index']]); ?>"><?php echo safe_html($record[12]); ?></td>
						<td class="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][13]}"; ?>" id="<?php echo "{$parameters['ChildTable']}-{$config['display-field-names'][13]}-" . html_attr($record[$config['child-primary-key-index']]); ?>"><?php echo safe_html($record[13]); ?></td>
					</tr>
					<?php } ?>
				</tbody>
				<tfoot>
					<tr>
						<td colspan="<?php echo count($config['display-fields']); ?>">
							<?php if($totalMatches) { ?>
								<?php if($config['show-page-progress']) { ?>
									<span style="margin: 10px;">
										<?php $firstRecord = ($parameters['Page'] - 1) * $config['records-per-page'] + 1; ?>
										<?php echo str_replace(['<FirstRecord>', '<LastRecord>', '<RecordCount>'], ['<span class="first-record locale-int">' . $firstRecord . '</span>', '<span class="last-record locale-int">' . ($firstRecord + count($records) - 1) . '</span>', '<span class="record-count locale-int">' . $totalMatches . '</span>'], $Translation['records x to y of z']); ?>
									</span>
								<?php } ?>
							<?php } else { ?>
								<span class="text-danger" style="margin: 10px;"><?php echo $Translation['No matches found!']; ?></span>
							<?php } ?>
						</td>
					</tr>
				</tfoot>
			</table>
		</div>
		<?php if($totalMatches > $config['records-per-page']) { ?>
			<div class="row hidden-print">
				<div class="col-xs-12">
					<button
						type="button" 
						class="btn btn-default btn-previous" 
						<?php echo $parameters['Page'] <= 1 ? 'disabled' : ''; ?>
						><i class="glyphicon glyphicon-chevron-left"></i>
					</button>
					<button
						type="button" 
						class="btn btn-default btn-next" 
						<?php echo ($firstRecord + count($records) - 1) == $totalMatches ? 'disabled' : ''; ?>
						><i class="glyphicon glyphicon-chevron-right"></i>
					</button>
				</div>
			</div>
		<?php } ?>
	</div>
</div>

<script>
	$j(function() {
		$j('img[src^="thumbnail.php?i=&"').parent().hide();

		$j('.btn-previous, .btn-next').on('click', function() {
			$j('.btn-previous, .btn-next')
				.prop('disabled', true);
			$j(this).find('.glyphicon')
				.removeClass('glyphicon-chevron-right glyphicon-chevron-left')
				.addClass('glyphicon-refresh loop-rotate');

			<?php echo $current_table; ?>GetChildrenRecordsList({
				Verb: 'page',
				Page: ($j(this).hasClass('btn-next') ? 'next' : 'previous')
			});
		});
	})
</script>
