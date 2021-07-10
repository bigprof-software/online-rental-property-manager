<?php 

define('NOLABEL', '{%%NOLABEL%%}'); // a special placeholder for label fields having no value

class SummaryReport {
	protected $precision = 0;
	protected $_request;
	private $report_translation;
	
	public function __construct($config) {
		global $ReportTranslation;

		$this->report_translation = $ReportTranslation;
		
		$this->_validate_group($config['groups_array']);
		
		$this->reportHash = $config['reportHash'];
		$this->title = $config['title'];
		$this->table = $config['table'];
		$this->label = $config['label'];
		$this->group_function = $config['group_function'];
		$this->label_title = $config['label_title'];
		$this->value_title = $config['value_title'];
		$this->thousands_separator = $config['thousands_separator'];
		$this->decimal_point = $config['decimal_point'];

		$this->override_permissions = isset($config['override_permissions']) ? $config['override_permissions'] : false;
		$this->custom_where = isset($config['custom_where']) ? $config['custom_where'] : '';

		$this->date_separator = $config['date_separator'];
		$this->php_date_format = $config['date_format'];
		$this->jsmoment_date_format = $config['jsmoment_date_format'];
		$this->filterable_fields = $config['filterable_fields'];

		$this->data_table_section = $config['data_table_section'];
		$this->chart_data_points = $config['chart_data_points'];

		// lookup field, or field from a parent table, that could in turn be a lookup field
		if(isset($config['look_up_table']))	$this->look_up_table = $config['look_up_table']; 
		if(isset($config['parent_table'])) $this->parent_table = $config['parent_table'];
		if(isset($config['label_field_index'])) $this->label_field_index = $config['label_field_index'];
		if(isset($config['parent_label_is_lookup'])) $this->parent_label_is_lookup = $config['parent_label_is_lookup'];
		if(isset($config['grand_parent_table'])) $this->grand_parent_table = $config['grand_parent_table'];
		
		// report header and footer images
		if(isset($config['report_header_url'])) $this->report_header_url = $config['report_header_url'];
		if(isset($config['report_footer_url'])) $this->report_footer_url = $config['report_footer_url'];
		
		// barchart settings
		$this->barchart_section = $config['barchart_section'];
		$this->barchart_options = $config['barchart_options'];
		$this->barchart_grid_classes = $config['barchart_grid_classes'];
		
		// piechart settings
		$this->piechart_section = $config['piechart_section'];
		$this->piechart_options = $config['piechart_options'];
		$this->piechart_classes = $config['piechart_classes'];
		
		if(isset($config['group_function_field']))
			$this->group_function_field = $config['group_function_field'];
		
		if(isset($config['date_field']) && !isset($config['parent_table']))
			$this->date_field_index = $config['date_field_index'];

		$this->set_precision();

		/*
			processing REQUEST here as the above code is independent of REQUEST object,
			yet processing of REQUEST is dependent on some of the above config.
			Then the below code is dependent on processed REQUEST object!
		*/
		$this->_process_request($config['request'], $config['date_format']);

		if(isset($config['date_field'])) {
			$this->date_field = $config['date_field'];
			$this->ts_p0_start = $this->date_to_ts($this->_request['p0-start']);
			$this->ts_p0_end = $this->date_to_ts($this->_request['p0-end']);

			$this->show_p1 = false;
			if($this->_request['comparison-period-1'] && $this->_request['p1-start'] && $this->_request['p1-end']) {
				$this->ts_p1_start = $this->date_to_ts($this->_request['p1-start']);
				$this->ts_p1_end = $this->date_to_ts($this->_request['p1-end']);
				$this->show_p1 = true;
			}

			$this->show_p2 = false;
			if($this->_request['comparison-period-1'] && $this->_request['p2-start'] && $this->_request['p2-end']) {
				$this->ts_p2_start = $this->date_to_ts($this->_request['p2-start']);		
				$this->ts_p2_end = $this->date_to_ts($this->_request['p2-end']);			
				$this->show_p2 = true;
			}

			$this->p0_start = $this->_request['p0-start'];
			$this->p0_end = $this->_request['p0-end'];
			$this->p1_start = $this->_request['p1-start'];
			$this->p1_end = $this->_request['p1-end'];
			$this->p2_start = $this->_request['p2-start'];
			$this->p2_end = $this->_request['p2-end'];
		}
		
		$this->order_by = $this->_request['order-by'];	
		$this->sorting_order = $this->_request['sorting-order'];
	}

	private function logs($log = null) {
		if(!isset($this->_logs)) $this->_logs = array();
		if(!empty($log)) $this->_logs[] = $log;

		return $this->_logs;
	}
	
	private function _validate_group($groups_array) {
		if(empty($groups_array) || !isset($groups_array)) return;
		
		$memberInfo = getMemberInfo();
		if(!in_array(strtolower($memberInfo["group"]), array_map("strtolower", $groups_array))){
			header("Location: ../index.php");
			exit;
		}
	}
	
	private function _process_request($request, $date_format) {
		// temporarily set _request then re-adjust it after validation to the valid values
		$this->_request = $request;
		
		$request['apply'] = $this->request('apply', false);
		
		// If a barchart or piechart section present, sort data by value desc by default rather than by label
		if($this->barchart_section || $this->piechart_section) {
			$request['order-by'] = $this->request('order-by', 'value', array('label'));
			$request['sorting-order'] = $this->request('sorting-order', 'desc', array('asc'));
		} else {
			$request['order-by'] = $this->request('order-by', 'label', array('value'));
			$request['sorting-order'] = $this->request('sorting-order', 'asc', array('desc'));
		}
		
		
		// set dates to false if empty/invalid and set default dates on report initial loading ...
		$dates = array(
			'p0-start' => 'first day of this month', 
			'p0-end' => 'this day', 
			'p1-start' => 'first day of previous month', 
			'p1-end' => 'this day last month', 
			'p2-start' => 'first day of this month last year', 
			'p2-end' => 'this day last year'
		);
		foreach($dates as $date => $default) {
			if(!$request['apply'])
				$request[$date] = date($date_format, strtotime($default));
			else
				$request[$date] = $this->valid_app_date($this->request($date));
		}

		// on initial loading of report, also set comparison-period checkboxes as checked
		if(!$request['apply']) {
			$request['comparison-period-1'] = 1;
			$request['comparison-period-2'] = 1;
		}

		/* if report config changed by user, and period 1 not set while 2 is set, shift 2 to 1 */
		if(
			$request['apply'] &&
			$request['comparison-period-2'] &&
			!$request['comparison-period-1']
		) {
			$request['p1-start'] = $request['p2-start'];
			$request['p1-end'] = $request['p2-end'];
			$request['comparison-period-1'] = 1;

			unset($request['p2-start']);
			unset($request['p2-end']);
			unset($request['comparison-period-2']);
		}
		
		// sort dates
		$this->sort_dates($request['p0-start'], $request['p0-end']);
		$this->sort_dates($request['p1-start'], $request['p1-end']);
		$this->sort_dates($request['p2-start'], $request['p2-end']);
		
		// finally save validated request
		$this->_request = $request;
	}
	
	/* get the precision of the aggregate field to use for rounding the aggregate function */
	private function set_precision() {
		if(!isset($this->group_function_field) || !isset($this->table)) return;
		$this->precision = sqlValue("SELECT NUMERIC_SCALE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='{$this->table}' AND COLUMN_NAME='{$this->group_function_field}'");
	}
	
	private function add_filter($tableName, $filterNo, $filterFieldNo, $filterOperator, $filterValue){
		$filter = urlencode("FilterField[{$filterNo}]") . '=' . urlencode($filterFieldNo) . '&' .
			urlencode("FilterOperator[{$filterNo}]") . '=' . urlencode($filterOperator) . '&' .
			urlencode("FilterValue[{$filterNo}]") . '=' . urlencode($filterValue);

		if(isset($tableName)) return "{$tableName}_view.php?{$filter}"; 

		return $filter;
	}
	
	/**
	 *  @brief retrieves specified key from $this->_request if set
	 *  
	 *  @param [in] $key key to retrieve from $this->_request
	 *  @param [in] $default optional, value to return if key not set
	 *  @param [in] $allowed_values optional, array of allowed values
	 *  @return value corresponding to key or default if not set
	 */
	private function request($key, $default = null, $allowed_values = null) {
		if(!isset($this->_request[$key])) return $default;		
		if(!is_array($allowed_values)) return $this->_request[$key];		
		if(!in_array($this->_request[$key], $allowed_values)) return $default;
		return $this->_request[$key];
	}

	private function sql_fields($table) {
		$sql_fields_str = get_sql_fields($table);
		$sql_fields_as = explode(' as \'', $sql_fields_str);
		
		$sql_fields = array();
		foreach($sql_fields_as as $prev_cap_next_field) {
			if(!isset($sfai)) $sfai = 0; // sql_fields_as iteration
			
			if(!$sfai) {
				// first iteration, field only
				$sql_fields[$sfai] = ['field' => $prev_cap_next_field, 'caption' => ''];
				$sfai++;
			} elseif($sfai == count($sql_fields_as) - 1) {
				// last iteration, caption only
				$sql_fields[$sfai - 1]['caption'] = "'" . $prev_cap_next_field;
			} else {
				$first_comma = strpos($prev_cap_next_field, '\', ');
				$sql_fields[$sfai - 1]['caption'] = "'" . substr($prev_cap_next_field, 0, $first_comma + 1);
				$sql_fields[$sfai] = ['field' => substr($prev_cap_next_field, $first_comma + 3)];
				$sfai++;
			}
		}

		return $sql_fields;
	}

	private function table_alias($tn = null, $ptn = null) {
		// what's the parent table alias in the query of table tn?
		// regex: LEFT JOIN `ptn` as alias ON
		if($tn === null && $ptn === null &&  !isset($this->parent_table))
			return $this->table;

		if($tn === null) $tn = $this->table;
		if($ptn === null && isset($this->parent_table)) $ptn = $this->parent_table;

		
		$from = get_sql_from($tn, $this->override_permissions);
		$this->logs(["get_sql_from('{$tn}')" => $from]);
		$m = array();
		if(!preg_match("/ LEFT JOIN `{$ptn}` as (.*?) ON /", $from, $m))
			return false;
		return isset($m[1]) ? $m[1] : false;	
	}

	private function aggregate() {
		if($this->group_function == 'count') return 'COUNT(1)';
		return "{$this->group_function}(`{$this->table}`.`{$this->group_function_field}`)";
	}

	private function date_condition($start_date, $end_date) {
		if($start_date === false || $end_date === false || !isset($this->date_field))
			return '';

		return "AND `{$this->table}`.`{$this->date_field}` BETWEEN '{$start_date}' AND '{$end_date}'";
	}

	private function label_field() {
		$fields = $this->sql_fields($this->table);
		$this->logs(["this->sql_fields('{$this->table}')" => $fields]);

		if(!isset($this->parent_table))
			return $fields[$this->label_field_index - 1]['field'];

		// if label field is from a parent table, retrieve the ID stored in our table
		// to be replaced later before display with the corresponding label from parent
		// using $this->replace_label_ids()
		// 
		// get alias of parent table
		$alias = $this->table_alias();
		return "`{$alias}`.`{$this->label}`";
	}

	private function report_query($start_date = false, $end_date = false) {
		$label = $this->label_field();
		$this->logs(["label_field()" => $label]);

		$aggregate = $this->aggregate();
		$this->logs(["aggregate" => $aggregate]);

		$fields = get_sql_fields($this->table);
		$this->logs(["get_sql_fields('{$this->table}')" => $fields]);

		$from = get_sql_from($this->table, $this->override_permissions);
		$custom_where = $this->custom_where ? $this->custom_where : '1=1';
		$date_range = $this->date_condition($start_date, $end_date);
		$sorting_order = isset($this->sorting_order) ? $this->sorting_order : 'ASC';
		
		return trim("
			SELECT
				{$label} AS 'label', {$aggregate} AS 'value'
			FROM {$from}
				 {$date_range}
				 AND ({$custom_where})
			GROUP BY label
			ORDER BY {$this->order_by} {$sorting_order}
		");
	}

	private function periods() {
		if(!isset($this->date_field)) return array(array(false, false));

		$periods = array();
		for($i = 0; $i < 3; $i++) {
			$start = "ts_p{$i}_start";
			$end = "ts_p{$i}_end";
			if(!isset($this->$start) || !isset($this->$end)) continue;
			$periods[] = array(
				date('Y-m-d', $this->$start),
				date('Y-m-d', $this->$end)
			);
		}

		return $periods;
	}

	private function sort_data($items) {
		// change $items to a numeric array
		$items = array_values($items);

		$sorting_order = ($this->sorting_order == 'desc' ? SORT_DESC : SORT_ASC);

		// sort by value or label?
		$sorter = array();
		foreach($items as $item)
			$sorter[] = $this->order_by == 'value' ? $item['value1'] : $item['label'];

		array_multisort($sorter, $sorting_order, $items);
		return $items;
	}

	private function replace_label_ids($items) {
		if(
			!isset($this->parent_table) || 
			!isset($this->parent_label_is_lookup) ||
			!isset($this->grand_parent_table)
		) return $items;

		// get unique label IDs as CSV
		// to clarify, these IDs are PKs of the parent of parent table, as stored
		// in the lookup field in the parent table
		$ids_arr = array();
		foreach ($items as $item)
			if(isset($item['label']))
				$ids_arr[makeSafe($item['label'])] = true;
		$ids = "'" . implode("','", array_keys($ids_arr)) . "'";

		// get alias of grand parent as used in the FROM clause of parent
		$gpt_alias = $this->table_alias($this->parent_table, $this->grand_parent_table);

		// get PK of grand parent table
		$pk = getPKFieldName($this->grand_parent_table);

		// get list of fields using this->sql_fields
		$fields = $this->sql_fields($this->parent_table);
		$this->logs(["this->sql_fields('{$this->parent_table}')" => $fields]);

		// then select label field using this->label_field_index
		$caption = $fields[$this->label_field_index - 1]['field'];

		$from = get_sql_from($this->parent_table, $this->override_permissions);
		$eo = array('silentErrors' => true);

		$query = "SELECT DISTINCT
				`{$gpt_alias}`.`{$pk}` AS 'id',
				{$caption} AS 'caption'
			FROM {$from} AND `{$gpt_alias}`.`{$pk}` IN ({$ids})";
		$this->logs(["replace_label_ids query" => $query]);

		$res = sql($query, $eo);
		$replace = array();
		while($row = db_fetch_assoc($res))
			$replace[$row['id']] = $row['caption'];

		foreach ($items as $i => $item)
			if(isset($replace[$items[$i]['label']]))
				$items[$i]['label'] = $replace[$items[$i]['label']];

		return $items;
	}

	private function report_data() {
		if(isset($this->cached_items)) return $this->cached_items;

		$items = array();
		$periods = $this->periods();
		$this->logs(['periods' => $periods]);
		$eo = array('silentErrors' => true);
		$i = 1;

		foreach ($periods as $p) {
			$query = $this->report_query($p[0], $p[1]);
			$this->logs(["query{$i}" => $query]);
			$res = sql($query, $eo);
			while($row = db_fetch_assoc($res)){
				/*
					We're using strtoupper since MySQL performs joins and grouping in a
					case-insensitive manner. Thus, if the label is a lookup storing a string 
					PK value, we should	be making case-insenstive comparisons in 
					PHP just like MySQL.
				*/
				$items[strtoupper($row['label'])]['label'] = $row['label'];
				$items[strtoupper($row['label'])]["value{$i}"] = $row['value'];
			}
			$this->logs(["items{$i}" => $items]);
			$i++;
		}

		$items = $this->populate_missing_period_values($items);
		$this->logs(["populate_missing_period_values" => $items]);

		$items = $this->replace_label_ids($items);
		$this->logs(["replace_label_ids" => $items]);
		
		$items = $this->sort_data($items);
		$this->logs(["sort_data" => $items]);

		$this->cached_items = $items;
		return $items;
	}

	private function populate_missing_period_values($items) {
		foreach($items as $i => $item) {
			if(empty($item['label']))
				$items[$i]['label'] = NOLABEL;

			for($j = 1; $j < 4; $j++)
				if(!isset($item["value{$j}"]))
					$items[$i]["value{$j}"] = 0.0;
		}

		return $items;
	}
	
	private function render_report_title(){

		if(isset($this->date_field)) {
			$from = date($this->php_date_format, $this->ts_p0_start);
			$to = date($this->php_date_format, $this->ts_p0_end);
			$from_to_title = str_replace(
				array('<FROM>', '<TO>'),
				array($from, $to),
				$this->report_translation['from to']
			);
		}
		
		ob_start(); ?>
		<?php if($this->report_header_url) { ?>
			<img src="<?php echo html_attr($this->report_header_url); ?>" class="img-responsive report-header-preview" alt="<?php echo html_attr($this->report_translation['loading report header']); ?>">

			<!--
				There is no reliable way of including a page header on every page unfortunately.
				@see: https://stackoverflow.com/a/7197225/1945185

				<div class="report-header visible-print">
					<img src="<?php echo html_attr($this->report_header_url); ?>" class="img-responsive" alt="<?php echo html_attr($this->report_translation['loading report header']); ?>">
				</div>
				<div class="visible-print print-top-margin"></div>
			-->
		<?php } ?>

		<h1> <?php echo ($report_title = $this->title . ' ' . $from_to_title); ?> 
			<div class="form-group pull-right hidden-print"> 
				<a href="summary-reports-list.php" class="btn btn-default btn-lg" > <i class="glyphicon glyphicon-chevron-left"></i> <?php echo $this->report_translation['back'] ?> </a> 
				<button class="btn btn-primary btn-lg" type="button" id="sendToPrinter" onclick="window.print();"> <i class="glyphicon glyphicon-print"></i> <?php echo $this->report_translation['print report'] ?> </button>	
			 
				<button onclick="exportTableToCSV('<?php echo str_replace(array(' ', '/'), '-', $report_title); ?>.csv')" class="btn btn-primary btn-lg"><i class="glyphicon glyphicon-floppy-disk"></i> <?php echo $this->report_translation['export report to csv file'] ?></button>
			</div>	
			<div class="clearfix"></div>			
		</h1>

		<script>
			$j(function() {
				// this id uniquely identifies this report for purposes of localStorage, .. etc.
				AppGini.srReportHash = '<?php echo $this->reportHash; ?>';

				// report data, to be available for charts
				<?php
					list($labels, $series) = $this->chart_data();

					// convert labels to unicode if necessary to avoid json_encoding them to null
					if(function_exists('to_utf8')) {
						$labels = array_map(function($l) { return to_utf8($l); }, $labels);
					}
				?>
				AppGini.srData = {
					labels: <?php echo json_encode($labels); ?>,
					series: <?php echo json_encode($series); ?>
				};
			})
		</script>

		<?php return ob_get_clean();
	}

	private function chart_data() {
		// retrieve items data and separate labels and values
		// each item is an array of 'label' and up to 3 values 'value1', 'value2', 'value3'
		// labels and values are trimmed according to configured $this->chart_data_points

		$items = $this->report_data();

		$labels = array_slice(
			array_map(function($i) { return $i['label'] == NOLABEL ? 'None' : strip_tags($i['label']); }, $items), 
			0, 
			$this->chart_data_points
		);

		$values = array(
			array_slice(
				array_map(function($i) { return floatval($i['value1']); }, $items),
				0,
				$this->chart_data_points
			)
		);
		
		if(isset($this->date_field)) {
			if(isset($items[0]['value2']))
				$values[] =	array_slice(
					array_map(function($i) { return floatval($i['value2']); }, $items),
					0,
					$this->chart_data_points
				);
			if(isset($items[0]['value3']))
				$values[] = array_slice(
					array_map(function($i) { return floatval($i['value3']); }, $items),
					0,
					$this->chart_data_points
				);
		}

		return array($labels, $values);
	}
	
	private function section_barchart() {
		list($labels, $series) = $this->chart_data();
		ob_start();
		?>
		<div class="row barchart-grid">
			<div class="barchart-grid-col">
				<?php if($this->report_header_url) { ?>
					<div class="visible-print print-top-margin"></div>
				<?php } ?>

				<h3 class="text-center"><?php echo $this->value_title; ?></h3>
				<div class="ct-chart" id="barchart"></div>
			</div>
		</div>

		<?php if(isset($this->date_field)) { ?>
			<div id="barchart-legend">
				<?php
					$ser = ' abc';
					for($i = 1; $i < 4; $i++) {
						$im1 = $i - 1;
						$pstart = "p{$im1}_start";
						$pend = "p{$im1}_end";

						if($this->$pstart) echo str_replace(
							array('<FROM>', '<TO>'),
							array($this->$pstart, $this->$pend),
							"<p class=\"ct-series-{$ser[$i]}\"><i class=\"glyphicon glyphicon-stop\"></i> {$this->report_translation['period from to']}</p>"
						);
					}
				?>
			</div>
		<?php } ?>
		
		<script>
			$j(function() {
				var options = <?php echo json_encode($this->barchart_options); ?>;

				AppGini.srBarchart = new Chartist.Bar(
					'#barchart',
					{ labels: AppGini.srData.labels, series: AppGini.srData.series }, 
					options
				);

				$j('#barchart-legend').appendTo('#barchart');
			})
		</script>
		<?php
		return ob_get_clean();
	}
	
	private function section_piechart() {
		// we should plot a pie chart of 'label' with 'value1' values,
		// another with 'label' and 'value2' ... etc
		list($labels, $series) = $this->chart_data();

		ob_start();
		?>
		<div class="adjacent-piecharts-row">
			<?php for($i = 1; $i < 4; $i++) { ?>
				<?php
					// skip piechart if no data series present for it
					if(!isset($series[$i - 1])) continue;
				?>
				<div class="adjacent-piecharts-col">
					<div class="row hidden piechart-grid" id="piechart-grid-<?php echo $i; ?>">
						<div class="piechart-grid-col">
							<?php if($this->report_header_url) { ?>
								<div class="visible-print print-top-margin"></div>
							<?php } ?>

							<h3 class="text-center">
								<?php
									echo $this->value_title;

									if(isset($this->date_field)) 
										$im1 = $i - 1;
										$pstart = "p{$im1}_start";
										$pend = "p{$im1}_end";

										if($this->$pstart) echo str_replace(
											array('<FROM>', '<TO>'),
											array($this->$pstart, $this->$pend),
											" ({$this->report_translation['period from to']})"
										);
								?>
							</h3>
							<div class="ct-chart piechart <?php echo $this->piechart_classes; ?>" id="piechart-<?php echo $i; ?>"></div>
						</div>
					</div>
				</div> <!-- /div.adjacent-piecharts-col -->
			<?php } ?>
		</div> <!-- /div.adjacent-piecharts-row -->
		
		<script>
			$j(function() {
				var options = <?php echo json_encode($this->piechart_options); ?>;

				// loop through series and plot a separate piechart for each series
				AppGini.srPiechart = [];
				for(var i = 0; i < 3; i++) {
					if(AppGini.srData.series[i] == undefined) continue;
					if(AppGini.srData.series[i].reduce(function(sum, v) { return sum + v; }, 0) <= 0) continue;

					var piechartGridId = '#piechart-grid-' + (i + 1);
					var piechartId = '#piechart-' + (i + 1);
					$j(piechartGridId).removeClass('hidden');
					AppGini.srPiechart[i] = new Chartist.Pie(piechartId, {
						labels: AppGini.srData.labels,
						series: AppGini.srData.series[i]
					}, options);
				}
			})
		</script>
		<?php
		return ob_get_clean();
	}
	
	private function section_data_table() {
		$title_date_format = $this->php_date_format;
		$to = date($this->php_date_format, $this->ts_p0_end);
		$from_to_title = '';	
		$items = $this->report_data();
		
		$num_items = count($items);
		
		ob_start(); ?>
		
		<div class="pull-right text-bold vspacer-lg"><?php echo str_replace('<n>', $num_items, $this->report_translation['records']); ?></div>
		<div class="clearfix"></div>
		
		<div class="table-responsive">
			<table class="table table-striped table-bordered table-hover report-data">
				<thead>
					<?php $rowspan = (isset($this->date_field) ? 'rowspan="2"' : ''); ?>

					<!-- hidden rows only for the purpose of well-formatted CSV -->
					<tr class="hidden">
						<th class="text-center label-row-numbers hidden"><?php echo isset($this->date_field) ? ' ' : '#'; ?></th>
						<th class="text-center label-group-by"><?php echo isset($this->date_field) ? ' ' : $this->label_title; ?></th>
						<th class="text-center label-summarize"><?php echo isset($this->date_field) ? str_replace(
							array('<FROM>', '<TO>'),
							array($this->p0_start, $this->p0_end),
							$this->report_translation['period from to']
						) : $this->value_title;?></th>	
						<?php if(isset($this->date_field)){ ?>
							<?php if($this->show_p1) { ?>
								<th class="text-center label-comparison-period-1"><?php echo str_replace(
									array('<FROM>', '<TO>'),
									array($this->p1_start, $this->p1_end),
									$this->report_translation['period from to']
								); ?></th>
								<th> </th>
							<?php } ?> 
						
							<?php if($this->show_p2) { ?>
								<th class="text-center label-comparison-period-2"><?php echo str_replace(
									array('<FROM>', '<TO>'),
									array($this->p2_start, $this->p2_end),
									$this->report_translation['period from to']
								); ?></th>
								<th> </th>
							<?php } ?>
						<?php } ?>
					</tr>
					<?php if(isset($this->date_field)) { ?>
						<tr class="hidden">
							<th class="text-center label-row-numbers hidden">#</th>
							
							<th class="text-center label-group-by"><?php echo $this->label_title; ?></th>
							<th class="text-center label-summarize"><?php echo $this->value_title; ?></th>	
							
							<?php if($this->show_p1) { ?>
								<th class="text-center" align="center" valign="middle" ><?php echo $this->value_title ?></th>
								<th class="text-center" align="center" valign="middle" ><?php echo $this->report_translation['change'] ?> %</th>
							<?php } ?>

							<?php if($this->show_p2) { ?>
								<th class="text-center" align="center" valign="middle"><?php echo $this->value_title ?></th>
								<th class="text-center" align="center" valign="middle"><?php echo $this->report_translation['change'] ?> %</th>
							<?php } ?>
						</tr>
					<?php } ?>
					<!-- end of hidden rows for CSV -->

					<tr>
						<th <?php echo $rowspan; ?> class="text-center label-row-numbers hidden">#</th>
						<th <?php echo $rowspan; ?> class="text-center label-group-by"><?php echo $this->label_title; ?></th>
						<th <?php echo $rowspan; ?> class="text-center label-summarize"><?php echo $this->value_title; ?></th>	
						<?php if(isset($this->date_field)){ ?>
							<?php if($this->show_p1) { ?>
								<th colspan="2" class="text-center label-comparison-period-1">
									<?php echo str_replace(
										array('<FROM>', '<TO>'),
										array($this->p1_start, $this->p1_end),
										$this->report_translation['period from to']
									); ?>
								</th>
							<?php } ?> 
						
							<?php if($this->show_p2) { ?>
								<th colspan="2" class="text-center label-comparison-period-2">
									<?php echo str_replace(
										array('<FROM>', '<TO>'),
										array($this->p2_start, $this->p2_end),
										$this->report_translation['period from to']
									); ?>
								</th>
							<?php } ?>
						<?php } ?>
					</tr>
					<?php if(isset($this->date_field)) { ?>
						<tr>
							<?php if($this->show_p1) { ?>
								<th class="text-center" align="center" valign="middle" ><?php echo $this->value_title ?></th>
								<th class="text-center" align="center" valign="middle" ><?php echo $this->report_translation['change'] ?> %</th>
							<?php } ?>

							<?php if($this->show_p2) { ?>
								<th class="text-center" align="center" valign="middle"><?php echo $this->value_title ?></th>
								<th class="text-center" align="center" valign="middle"><?php echo $this->report_translation['change'] ?> %</th>
							<?php } ?>
						</tr>
					<?php } ?>
				</thead>
				<tbody>
					<?php
						if(!is_null($items)) foreach($items as $item) {
							// set empty comparison values to 0 (formatted to comparison field precision)
							for($i = 1; $i <= 3; $i++) {
								$key = "value{$i}";
								if(!isset($item[$key])) $item[$key] = 0;
								${$key} = $this->number_format($item[$key]);
							}
							
							if(!isset($this->parent_table) && in_array($this->label, $this->filterable_fields)) {
								$filterFieldNo = array_search($this->label, $this->filterable_fields) + 1;
								$filterValue = $item['label'];
								$filter_flag = true;
							}

							if(isset($filter_flag) && !isset($this->date_field) && $filterValue != NOLABEL) {
								$value1_filter = '../' . $this->add_filter($this->table, 1, $filterFieldNo, 'equal-to', $filterValue);
								$value1 = "<a href=\"{$value1_filter}\">{$value1}</a>";
							}

							if(isset($filter_flag) && !isset($this->date_field) && $filterValue == NOLABEL) {
								$value1_filter = '../' . $this->add_filter($this->table, 1, $filterFieldNo, 'is-empty', "");
								$value1 = "<a href=\"{$value1_filter}\">{$value1}</a>";
							}

							if(isset($filter_flag) && isset($this->date_field)) {				
								$date_field_no = $this->date_field_index;
								$second_filter_value = date($this->php_date_format, $this->ts_p0_start);
								$third_filter_value = date($this->php_date_format, $this->ts_p0_end);
								$label_filter = '../' . $this->add_filter($this->table, 1, $filterFieldNo, 'equal-to', $filterValue);
								
								$value1_filter = $label_filter . '&' . 
									urlencode('FilterAnd[2]') . '=and&' .
									$this->add_filter(null, 2, $date_field_no, 'greater-than-or-equal-to', $second_filter_value) . '&' .
									urlencode('FilterAnd[3]') . '=and&' . 
									$this->add_filter(null, 3, $date_field_no, 'less-than-or-equal-to', $third_filter_value);
							
								$value2_filter = $label_filter . '&' . 
									urlencode('FilterAnd[2]') . '=and&' .
									$this->add_filter(null, 2, $date_field_no, 'greater-than-or-equal-to', $this->p1_start) . '&' .
									urlencode('FilterAnd[3]') . '=and&' . 
									$this->add_filter(null, 3, $date_field_no, 'less-than-or-equal-to', $this->p1_end);
								
								$value3_filter = $label_filter . '&' .
									urlencode('FilterAnd[2]') . '=and&' .
									$this->add_filter(null, 2, $date_field_no, 'greater-than-or-equal-to', $this->p2_start) . '&' .
									urlencode('FilterAnd[3]') . '=and&' . 
									$this->add_filter(null, 3, $date_field_no, 'less-than-or-equal-to', $this->p2_end);
								
								$value1 = "<a href=\"{$value1_filter}\">{$value1}</a>";
								$value2 = "<a href=\"{$value2_filter}\">{$value2}</a>";
								$value3 = "<a href=\"{$value3_filter}\">{$value3}</a>";
							}
							// handling NOLABEL labels
							if($item["label"] == NOLABEL) $item["label"] = "NONE";
		
							/* Change % for comparison period 1 */
							$first_percentage = $this->change_percent($item['value1'], $item['value2']);
							
							/* Change % for comparison period 2 */
							$second_percentage = $this->change_percent($item['value1'], $item['value3']);
						?>
						<tr>
							<td class="text-right hidden row-number"><?php echo ++$row_number; ?></th>		
							<td class="text-left item-label"><?php echo safe_html($item['label']); ?></td>

							<td class="text-right item-value"><?php echo $value1; ?></td>
							<?php if(isset($this->date_field)) { ?>
								<?php if($this->show_p1) { ?>
									<td class="text-right item-value"><?php echo $value2; ?></td>
									<td class="percentage text-right"><?php echo $first_percentage; ?></td>
								<?php } ?>
								<?php if($this->show_p2) { ?>
									<td class="text-right item-value"><?php echo $value3; ?></td>
									<td class="percentage text-right"><?php echo $second_percentage; ?></td>
								<?php } ?>
							<?php } ?>
						</tr>
							
					<?php } /* end of foreach */ ?> 
				</tbody>
			</table>
		</div>
		
		<div class="pull-right text-bold"><?php echo str_replace('<n>', $num_items, $this->report_translation['records']); ?></div>
		<div class="clearfix"></div>

		<script>
			$j(function() {
				/* highlight change% cells to indicate change direction */
				for(var i = 0; i < $j('.percentage').length; i++) {
					var percentage = parseFloat($j('.percentage')[i].innerText);
					if(percentage < 0) {	
						$j('.percentage')[i].innerText = parseFloat($j('.percentage')[i].innerText) + '%';
						$j('.percentage').eq(i).addClass('text-danger danger');	
					}else if(isNaN(percentage)) {
						$j('.percentage').eq(i).css("font-size","1.4em");
						$j('.percentage').eq(i).addClass('text-success success');
					}else {
						$j('.percentage').eq(i).addClass('text-success success');
					}
				}
			});
		</script>
		<?php return ob_get_clean();
	}
	
	private function change_percent($v1, $v2) {
		/* Change % for comparison period 1 */
		if($v1 > 0 && $v2 == 0) return '&#8734'; /* infinity */

		if($v1 == $v2) return '-'; /* no change */
		
		return $this->number_format(round((($v1 - $v2) / $v2) * 100, 1), 1) . '%';
	}

	private function number_format($num, $precision = null) {
		if($precision == null) $precision = $this->precision;
		return number_format($num, $precision, $this->decimal_point, $this->thousands_separator);
	}

	private function add_report_configuration() {
		ob_start(); ?>
		<div class="panel-group hidden-print" id="report-config">
			<div class="panel panel-primary">
				<div class="panel-heading" data-toggle="collapse" data-target="#collapse1" href="#collapse1" style="cursor: pointer;">
					<h4 class="panel-title">
						<span class="glyphicon glyphicon-cog"></span> <?php echo $this->report_translation['report configuration'] ?>
					</h4>
				</div>  
			<div id="collapse1" class="panel-collapse collapse">
				<div class="panel-body">
					
					<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"])?>" method="get" class="form-inline">
						<?php if($this->date_field){ ?>
							<h4><label><i class="glyphicon glyphicon-calendar text-info"></i> <?php echo $this->report_translation['report period'] ?></label></h4>
							
							<table class="table table-bordered" style="width: calc(100% - 8em); margin: 0 4em;">
								<!-- header row of 'Report Period' table, contining date range buttons, and comparison-period checkboxes -->
								<thead>
									<tr class="active">
										<th class="text-center">
											<div class="btn-group vspacer-md">
												<button type="button" class="btn btn-default" id="month-to-date" data-toggle="tooltip" title="<?php echo html_attr($this->report_translation['month to date description']); ?>">
													<?php echo $this->report_translation['month to date'] ?>
												</button>
												<button type="button" class="btn btn-default" id="current-month" data-toggle="tooltip" title="<?php echo html_attr($this->report_translation['current month description']); ?>">
													<?php echo $this->report_translation['current month'] ?>
												</button>
												<button type="button" class="btn btn-default" id="last-month" data-toggle="tooltip" title="<?php echo html_attr($this->report_translation['last month description']); ?>">
													<?php echo $this->report_translation['last month'] ?>
												</button>
											</div>
											<div class="btn-group vspacer-md">
												<button type="button" class="btn btn-default" id="quarter-to-date" data-toggle="tooltip" title="<?php echo html_attr($this->report_translation['quarter to date description']); ?>">
													<?php echo $this->report_translation['quarter to date']?>
												</button>
												<button type="button" class="btn btn-default" id="current-quarter" data-toggle="tooltip" title="<?php echo html_attr($this->report_translation['current quarter description']); ?>">
													<?php echo $this->report_translation['current quarter']?>
												</button>
												<button type="button" class="btn btn-default" id="last-quarter" data-toggle="tooltip" title="<?php echo html_attr($this->report_translation['last quarter description']); ?>">
													<?php echo $this->report_translation['last quarter']?>
												</button>
											</div>
											<div class="btn-group vspacer-md">
												<button type="button" class="btn btn-default" id="year-to-date" data-toggle="tooltip" title="<?php echo html_attr($this->report_translation['year to date description']); ?>">
													<?php echo $this->report_translation['year to date']?>
												</button>
												<button type="button" class="btn btn-default" id="current-year" data-toggle="tooltip" title="<?php echo html_attr($this->report_translation['current year description']); ?>">
													<?php echo $this->report_translation['current year']?>
												</button>
												<button type="button" class="btn btn-default" id="last-year" data-toggle="tooltip" title="<?php echo html_attr($this->report_translation['last year description']); ?>">
													<?php echo $this->report_translation['last year']?>
												</button>
											</div>
										</th>
										<th class="text-center" style="vertical-align: middle;">
											<div class="checkbox">
												<label>
													<input class="form-check-input" type="checkbox" value="1" id="comparison-period-1" name="comparison-period-1" <?php echo ($this->_request['comparison-period-1'] ? 'checked' : ''); ?> >
													<strong><?php echo $this->report_translation['comparison period 1']?></strong>
												</label>
											</div>
										</th>
										<th class="text-center" style="vertical-align: middle;">
											<div class="checkbox">
												<label>
													<input class="form-check-input" type="checkbox" value="1" id="comparison-period-2" name="comparison-period-2" <?php echo ($this->_request['comparison-period-2'] ? 'checked' : ''); ?> >
													<strong><?php echo $this->report_translation['comparison period 2']?></strong>
												</label>
											</div>
										</th>
									</tr>
								</thead>
								<!-- end of header row of 'Report Period' table -->
								<tbody>
									<tr>
										<!-- Main report period -->
										<td id="report-period-selector" class="text-center">
											<div class="form-group">
												<label for="p0-start" class="vspacer-lg"> <?php echo $this->report_translation['from']?> </label>
												<div class="input-group date vspacer-lg">
													<input type="text" class="form-control" name="p0-start" id="p0-start" value="<?php echo html_attr($this->_request['p0-start']); ?>" size="10">
													<div class="input-group-addon">
														<span class="glyphicon glyphicon-calendar"></span>
													</div>
												</div>
												<span class="hspacer-lg"></span>
												<label for="p0-end"> <?php echo $this->report_translation['to']?></label>
												<div class="input-group date vspacer-lg">
													<input type="text" class="form-control" name="p0-end" id="p0-end" value="<?php echo html_attr($this->_request['p0-end']) ?>" size="10">
													<div class="input-group-addon">
														<span class="glyphicon glyphicon-calendar"></span>
													</div>
												</div>
											</div>
										</td>
										
										<!-- Comparison period 1 -->
										<td id="period-1-selector" class="text-center">
											<div class="form-group">
												<label for="p1-start" class="vspacer-lg" id="p1-start-label"> <?php echo $this->report_translation['from']?> </label>
												<div class="input-group date vspacer-lg">
													<input type="text"  class="form-control" name="p1-start" id="p1-start" value="<?php echo htmlspecialchars($this->p1_start); ?>" size="10">
													<div class="input-group-addon">
														<span class="glyphicon glyphicon-calendar"></span>
													</div>
												</div>
												<span class="hspacer-lg"></span>
												<label for="p1-end" id="p1-end-label"> <?php echo $this->report_translation['to']?></label>
												<div class="input-group date vspacer-lg">
													<input type="text" class="form-control" name="p1-end" id="p1-end" value="<?php echo htmlspecialchars($this->p1_end); ?>" size="10">
													<div class="input-group-addon">
														<span class="glyphicon glyphicon-calendar"></span>
													</div>
												</div>
											</div>
										</td>
										
										<!-- Comparison period 2 -->
										<td id="period-2-selector" class="text-center">
											<div class="form-group">
												<label for="p2-start" class="vspacer-lg" id="p2-start-label"> <?php echo $this->report_translation['from']?> </label>
												<div class="input-group date vspacer-lg">
													<input type="text" class="form-control" name="p2-start" id="p2-start" value="<?php echo htmlspecialchars($this->p2_start); ?>" size="10">
													<div class="input-group-addon">
														<span class="glyphicon glyphicon-calendar"></span>
													</div>
												</div>
												<span class="hspacer-lg"></span>
												<label for="p2-end" id="p2-end-label"> <?php echo $this->report_translation['to']?></label>
												<div class="input-group date vspacer-lg">
													<input type="text" class="form-control" name="p2-end" id="p2-end" value="<?php echo htmlspecialchars($this->p2_end); ?>" size="10">
													<div class="input-group-addon">
														<span class="glyphicon glyphicon-calendar"></span>
													</div>
												</div>
											</div>
										</td>
									</tr>
								</tbody>
							</table>

						<?php } /* end of period selection */ ?>
						
						<h4><label for="order-by"><i class="glyphicon glyphicon-sort text-info"></i> <?php echo $this->report_translation['order by']?></label></h4>
						<div class="form-check" style="margin: 0 4em;">
							<div class="radio-inline">
								<label>
									<input type="radio" name="order-by" value="label" <?php echo $this->order_by == 'label' ? 'checked' : ''; ?>>
									<?php echo $this->label_title; ?>
								</label>
							</div>
							<div class="radio-inline">
								<label>
									<input type="radio" name="order-by" value="value" <?php echo $this->order_by == 'value' ? 'checked' : ''; ?>>
									<?php echo $this->value_title; ?>
								</label>
							</div>
							<div class="clearfix"></div>
							<div class="radio-inline">
								<label>
									<input type="radio" name="sorting-order" id="sorting-order-desc" value="desc" <?php echo $this->_request['sorting-order'] == 'desc' ? 'checked' : ''; ?>>
									<strong class="hspacer-md"><?php echo $this->report_translation['descending']?></strong>
								</label>
							</div>
							<div class="radio-inline">
								<label>
									<input type="radio" name="sorting-order" id="sorting-order-asc" value="asc" <?php echo $this->_request['sorting-order'] == 'asc' ? 'checked' : ''; ?>>
									<strong class="hspacer-md"><?php echo $this->report_translation['ascending']?></strong>
								</label>
							</div>
							<div class="clearfix"></div>
						</div>

						<hr>

						<h4><label><i class="glyphicon glyphicon-picture text-info"></i> <?php echo $this->report_translation['appearance']?></label></h4>
						<div style="margin: 0 4em;" id="dvData">
							<table class="report-config-table">
								<tbody>
									<tr>
										<th></th>
										<td>
											<div class="checkbox hspacer-lg">
												<label>
													<input type="checkbox" value="1" id="row-numbers">
													<b><?php echo $this->report_translation['show row numbers']?></b>
												</label>
											</div>
										</td>
									</tr>
									<tr>
										<th class="text-right"><?php echo str_replace('<LABEL>', "<i>{$this->label_title}</i>", $this->report_translation['alignment of label column']); ?></th>
										<td>
											<div class="radio-inline hspacer-lg">
												<label title="Left">
													<input type="radio" name="radio-label-align" value="text-left">
													<i class="glyphicon glyphicon-align-left"></i> 
												</label>
											</div>
											<div class="radio-inline hspacer-lg">
												<label title="Center">
													<input type="radio" name="radio-label-align" value="text-center">
													<i class="glyphicon glyphicon-align-center"></i> 
												</label>
											</div>
											<div class="radio-inline hspacer-lg">
												<label title="Right">
													<input type="radio" name="radio-label-align" value="text-right">
													<i class="glyphicon glyphicon-align-right"></i> 
												</label>
											</div>
										</td>
									</tr>
									<tr>
										<th class="text-right"><?php echo $this->report_translation['alignment of other columns']?></th>
										<td>
											<div class="radio-inline hspacer-lg">
												<label title="Left">
													<input type="radio" name="radio-value-align" value="text-left">
													<i class="glyphicon glyphicon-align-left"></i> 
												</label>
											</div>
											<div class="radio-inline hspacer-lg">
												<label title="Center">
													<input type="radio" name="radio-value-align" value="text-center">
													<i class="glyphicon glyphicon-align-center"></i> 
												</label>
											</div>
											<div class="radio-inline hspacer-lg">
												<label title="Right">
													<input type="radio" name="radio-value-align" value="text-right">
													<i class="glyphicon glyphicon-align-right"></i> 
												</label>
											</div>
										</td>
									</tr>
									<?php if($this->barchart_section || $this->piechart_section) { ?>
										<!-- charts-specific settings -->
										<tr>
											<th class="text-right"><?php echo $this->report_translation['charts-color-theme']; ?></th>
											<td>
												<select class="form-control hspacer-lg" id="charts-color-theme">
													<option value="charts-theme-none"><?php echo $this->report_translation['rainbow']; ?></option>
													<option value="charts-theme-reds"><?php echo $this->report_translation['reds']; ?></option>
													<option value="charts-theme-oranges"><?php echo $this->report_translation['oranges']; ?></option>
													<option value="charts-theme-yellows"><?php echo $this->report_translation['yellows']; ?></option>
													<option value="charts-theme-greens"><?php echo $this->report_translation['greens']; ?></option>
													<option value="charts-theme-blues"><?php echo $this->report_translation['blues']; ?></option>
													<option value="charts-theme-violets"><?php echo $this->report_translation['violets']; ?></option>
													<option value="charts-theme-grays"><?php echo $this->report_translation['grays']; ?></option>
												</select>
											</td>
										</tr>
										<!-- end of charts-specific settings -->
									<?php } ?>
										
									<?php if($this->barchart_section) { ?>
										<!-- barchart-specific settings -->
										<tr>
											<th class="text-right"><?php echo $this->report_translation['barchart-orientation']?></th>
											<td>
												<div class="radio-inline hspacer-lg">
													<label title="Vertical">
														<input type="radio" name="barchart-orientation" value="vertical">
														<i class="glyphicon glyphicon-stats"></i> 
													</label>
												</div>
												<div class="radio-inline hspacer-lg">
													<label title="Horizontal">
														<input type="radio" name="barchart-orientation" value="horizontal">
														<i class="glyphicon glyphicon-stats rotate90"></i> 
													</label>
												</div>
											</td>
										</tr>
										<tr>
											<th class="text-right"><?php echo $this->report_translation['barchart-aspect-ratio']; ?></th>
											<td>
												<select class="form-control hspacer-lg" id="barchart-aspect-ratio">
													<option value="ct-square">1 : 1</option>
													<option value="ct-minor-second">15 : 16</option>
													<option value="ct-major-second">8 : 9</option>
													<option value="ct-minor-third">5 : 6</option>
													<option value="ct-major-third">4 : 5</option>
													<option value="ct-perfect-fourth">3 : 4</option>
													<option value="ct-perfect-fifth">2 : 3</option>
													<option value="ct-minor-sixth">5 : 8</option>
													<option value="ct-golden-section">1 : 1.618</option>
													<option value="ct-major-sixth">3 : 5</option>
													<option value="ct-minor-seventh">9 : 16</option>
													<option value="ct-major-seventh">8 : 15</option>
													<option value="ct-octave">1 : 2</option>
													<option value="ct-major-tenth">2 : 5</option>
													<option value="ct-major-eleventh">3 : 8</option>
													<option value="ct-major-twelfth">1 : 3</option>
													<option value="ct-double-octave">1 : 4</option>
												</select>
											</td>
										</tr>
										<tr>
											<th class="text-right"><?php echo $this->report_translation['barchart-width']; ?></th>
											<td>
												<select class="form-control hspacer-lg" id="barchart-width">
													<option value="col-md-12">100%</option>
													<option value="col-md-offset-2 col-md-8">66%</option>
													<option value="col-md-offset-3 col-md-6">50%</option>
													<option value="col-md-offset-4 col-md-4">33%</option>
												</select>
											</td>
										</tr>
										<tr>
											<th class="text-right"><?php echo $this->report_translation['barchart-bar-width']; ?></th>
											<td>
												<div class="hspacer-lg input-group">
													<span class="input-group-btn">
														<button class="btn btn-default" type="button" id="bar-width-less"><i class="glyphicon glyphicon-minus"></i></button>
													</span>
													<input type="text" class="form-control text-center" id="bar-width" readonly>
													<span class="input-group-btn">
														<button class="btn btn-default" type="button" id="bar-width-more"><i class="glyphicon glyphicon-plus"></i></button>
													</span>
												</div>
												<span class="help-block hspacer-lg"><?php echo $this->report_translation['barchart-bar-width-default-hint']; ?></span>
											</td>
										</tr>
										<tr>
											<th class="text-right"><?php echo $this->report_translation['barchart-bar-spacing']; ?></th>
											<td>
												<div class="hspacer-lg input-group">
													<span class="input-group-btn">
														<button class="btn btn-default" type="button" id="bar-spacing-less"><i class="glyphicon glyphicon-minus"></i></button>
													</span>
													<input type="text" class="form-control text-center" id="bar-spacing" readonly>
													<span class="input-group-btn">
														<button class="btn btn-default" type="button" id="bar-spacing-more"><i class="glyphicon glyphicon-plus"></i></button>
													</span>
												</div>
											</td>
										</tr>
										<!-- end of barchart-specific settings -->
									<?php } ?>
									<?php if($this->piechart_section) { ?>
										<tr>
											<th class="text-right"><?php echo $this->report_translation['piechart-width']; ?></th>
											<td>
												<select class="form-control hspacer-lg" id="piechart-width">
													<option value="col-md-12">100%</option>
													<option value="col-md-offset-2 col-md-8">66%</option>
													<option value="col-md-offset-3 col-md-6">50%</option>
													<option value="col-md-offset-4 col-md-4">33%</option>
													<option value="adjacent col-md-12">33% - 33% - 33%</option>
												</select>
											</td>
										</tr>
										<tr>
											<th class="text-right"><?php echo $this->report_translation['piechart-labels']; ?></th>
											<td>
												<select class="form-control hspacer-lg" id="piechart-labels">
													<option value="labels"><?php echo $this->report_translation['labels']; ?></option>
													<option value="labels-values"><?php echo $this->report_translation['labels-values']; ?></option>
													<option value="labels-percentages"><?php echo $this->report_translation['labels-percentages']; ?></option>
													<option value="labels-values-percentages"><?php echo $this->report_translation['labels-values-percentages']; ?></option>
												</select>
											</td>
										</tr>
									<?php } ?>
								</tbody>
							</table>
						</div>
						<hr>
						<div class="form-group">
							<button type="button" class="btn btn-primary vspacer-lg btn-lg" id="apply-filter" value="1"> <span class="glyphicon glyphicon-ok"></span> <?php echo $this->report_translation['apply']?> </button>
						</div>
						<input type="hidden" name="apply" value="1">
					</form>
				</div>
			  </div>
			</div>
		</div>
		
		<?php return ob_get_clean();
		
	}

	private function post_render() {
		ob_start();
		?>

		<div id="no-report-data" class="hidden alert alert-danger h4 text-center text-bold"><?php echo $this->report_translation['no-report-data']; ?></div>

		<?php if(isset($this->_request['_debug']) && getLoggedAdmin() && $_SERVER['SERVER_NAME'] == 'localhost') { ?>
			<pre class="text-left ltr"><?php print_r($this->logs()); ?></pre>
		<?php } ?>

		<?php if($this->report_footer_url) { ?>
			<div id="footer-top-margin" class="hidden-print"></div>
			<img src="<?php echo html_attr($this->report_footer_url); ?>" class="img-responsive hidden-print report-footer-preview" alt="<?php echo html_attr($this->report_translation['loading report footer']); ?>">

			<!--
				There is no reliable way of including a page footer on every page unfortunately.
				@see: https://stackoverflow.com/a/7197225/1945185

				<div class="report-footer visible-print">
					<img src="<?php echo html_attr($this->report_footer_url); ?>" class="img-responsive" alt="<?php echo html_attr($this->report_translation['loading report footer']); ?>">
				</div>
			-->
		<?php } ?>
	 
		<script>
			function exportTableToCSV(filename) {
				var csv = [];
				var rows = document.querySelectorAll(".report-data thead tr.hidden, .report-data tbody tr");
	 
				for (var i = 0; i < rows.length; i++) {
					var row = [], cols = rows[i].querySelectorAll("td, th");
					
					for (var j = 0; j < cols.length; j++) 
						row.push('"' + $j.trim(cols[j].innerText.replace(/\"/, '""')) + '"');
					
					csv.push(row.join(","));
				}
				
				// Download CSV file
				downloadCSV(csv.join("\n"), filename);
			}

			function downloadCSV(csv, filename) {
				var csvFile = new Blob([csv], { type: "text/csv" });
				var downloadLink = document.createElement("a");

				// File name
				downloadLink.download = filename;

				// Create a link to the file
				downloadLink.href = window.URL.createObjectURL(csvFile);

				// Hide download link
				downloadLink.style.display = "none";

				// Add the link to DOM
				document.body.appendChild(downloadLink);

				// Click download link
				downloadLink.click();
			}
		 
			$j(function(){
				var date_format = <?php echo json_encode($this->jsmoment_date_format); ?>;

				AppGini.hugeNumber = function(num, precision) {
					if(isNaN(precision)) precision = 1;
					
					var oneTrillion = 1000000000000,
						oneBillion = oneTrillion / 1000,
						oneMillion = oneBillion / 1000,
						oneThousand = oneMillion / 1000,
						divisor = Math.pow(10, precision),
						prep = function(n, d, m) { return (Math.round(n * d / m) / d).toLocaleString(); };

					if(num >=      oneTrillion) return prep(num, divisor, oneTrillion) + 'T';
					if(num >=      oneBillion)  return prep(num, divisor,  oneBillion) + 'B';
					if(num >=      oneMillion)  return prep(num, divisor,  oneMillion) + 'M';
					if(num >=      oneThousand) return prep(num, divisor, oneThousand) + 'K';
					if(num >= -1 * oneThousand) return num.toLocaleString();
					if(num >= -1 * oneMillion)  return prep(num, divisor, oneThousand) + 'K';
					if(num >= -1 * oneBillion)  return prep(num, divisor,  oneMillion) + 'M';
					if(num >= -1 * oneTrillion) return prep(num, divisor,  oneBillion) + 'B';
					                            return prep(num, divisor, oneTrillion) + 'T';
				}

				/*
					to set up live updating for a report config item:
						* add config item, and/or remove PHP code from config item if it already exists
						* remove name attribute from config item (unless it's a radio group)
						* add a unique CSS identifier to the item (class or id or name for radio groups)
						
						* add a ui function that performs the live update based on parameter passed to it, regardless
						  of the control status
						  
						* add code in applyStoredStatus() to retrieve config item status from localStorage and apply
						  it to config item and call the related ui function
						  
						* add an event handler on changing the config item that calls the ui function passing a param
						  based on control status, and stores the config item value to localStorage
						
						* set default value in call to state.setDefault()
				*/
				
				var ui = {
					configDatePicker: function(date_format){
						$j('#p0-start, #p0-end, #p1-start, #p1-end, #p2-start, #p2-end')
							.addClass('always_shown')
							.parents('.input-group')
							.datetimepicker({
								toolbarPlacement: 'top',
								showClear: true,
								showTodayButton: true,
								showClose: true,
								icons: { close: 'glyphicon glyphicon-ok' },
								format: date_format,
								widgetPositioning: { horizontal: 'left', vertical: 'auto' }
							})
							.on('dp.change', function() { AppGini.srSubmitRequired = true; });
					},

					setDates: function(dates) {
						$j.each(dates, function(k, v) {
							if($j("#" + k).val() == v) return;
							$j("#" + k).val(v);
							AppGini.srSubmitRequired = true;
						});
					},

					setPrintTopMargin: function() {
						var headerImg = $j('.report-header-preview');
						if(!headerImg.length) return;

						var tempImg = $j('<div style="width: 9in;"><img src="' + encodeURI(headerImg.attr('src')) + '" class="img-responsive">');
						tempImg.appendTo('body');
						$j('.print-top-margin').height(tempImg.height());
						tempImg.remove();
					},

					setPrintFooter: function() {
						if(!$j('.report-footer-preview').length) return;

						var paperHeight = (11.69 - 1.17) * 96; /* A4 paper net height in inches * pixels per inch */
						var footerHeight = $j('.report-footer-preview').height();
						var contentHeight = $j('body').height() - footerHeight;
						var numPapers = Math.ceil(contentHeight / paperHeight);

						$j('#footer-top-margin').height(numPapers * paperHeight - contentHeight - 2 * footerHeight);
					},

					comparisonPeriodDisplay: function(which) {
						if(which === undefined) which = 1;
						if(which != 2) which = 1;
						
						var enabled = $j('#comparison-period-' + which).prop('checked');
						var addClass = (!enabled ? 'text-muted' : '');
						var removeClass = ( enabled ? 'text-muted' : '');

						$j('#p' + which + '-start').prop('disabled', !enabled);
						$j('#p' + which + '-end').prop('disabled', !enabled);
						$j('#p' + which + '-start-label').addClass(addClass).removeClass(removeClass);
						$j('#p' + which + '-end-label').addClass(addClass).removeClass(removeClass);
					},

					displayRowNumbers: function(show) {
						if(show === undefined) show = true;

						$j('.row-number, .label-row-numbers')
							.addClass(show ? '' : 'hidden')
							.removeClass(!show ? '' : 'hidden');
					},

					alignElements: function(selector, alignClass) {
						$j(selector)
							.removeClass('text-left text-center text-right')
							.addClass(alignClass);
					},
					
					alignLabels: function(alignClass) {
						this.alignElements('.item-label', alignClass);
					},

					alignValues: function(alignClass) {
						this.alignElements('.row-number, .item-value, .percentage', alignClass);
					},

					optionsToString: function(selector, separator) {
						var classes = '';
						if(typeof(separator) == 'undefined') separator = ' ';
						$j(selector + ' option').each(function() { classes += $j(this).attr('value') + separator; });
						return classes;
					},

					chartColorTheme: function(theme) {
						var themeClasses = this.optionsToString('#charts-color-theme');
						$j('#barchart, .piechart').removeClass(themeClasses).addClass(theme);
					},

					barchartAspectRatio: function(aspectRatio) {
						var aspectRatioClasses = this.optionsToString('#barchart-aspect-ratio');
						$j('#barchart').removeClass(aspectRatioClasses).addClass(aspectRatio);
						if(typeof(AppGini.srBarchart) != 'undefined') AppGini.srBarchart.update();
					},

					barchartBarWidth: function(width) {
						if(width > 0)
							$j('.ct-bar').attr('style', 'stroke-width: ' + width + 'px !important;');
						else // 0 width means leaving width to be set automatically by chartist
							$j('.ct-bar').attr('style', '');

						// make sure the following code runs only once, and after barchart is created
						if(typeof(AppGini.srBarchartEventsCalled) != 'undefined') return;
						if(typeof(AppGini.srBarchart) == 'undefined') return;
						AppGini.srBarchartEventsCalled = true;

						// whenever chart is redrawn, reapply barchart bar widths
						var theUi = this;
						AppGini.srBarchart.on('created', function() {
							var options = state.getAll();
							theUi.barchartBarWidth(options.barchartBarWidth);
						})
					},
	
					barchartBarSpacing: function(spacing) {
						if(typeof(AppGini.srBarchart) != 'undefined')
							AppGini.srBarchart.update(false, { seriesBarDistance: spacing }, true);
					},

					piechartWidth: function(colClasses) {
						$j('.piechart-grid-col')
							.attr('class', '')
							.addClass('piechart-grid-col ' + colClasses);
						if(colClasses.match(/adjacent/)) {
							switch($j('.piechart').length) {
								case 3:
									var colMdClass = 'col-md-4';
									break;
								case 2:
									var colMdClass = 'col-md-6';
									break;
								default:
									var colMdClass = 'col-md-12';
							}
							$j('.adjacent-piecharts-row').addClass('row');
							$j('.adjacent-piecharts-col').addClass(colMdClass);
						} else {
							$j('.adjacent-piecharts-row').removeClass('row');
							$j('.adjacent-piecharts-col').removeClass('col-md-4 col-md-6 col-md-12');
						}

						this.redrawCharts();
					},

					barchartWidth: function(colClasses) {
						$j('.barchart-grid-col')
							.attr('class', '')
							.addClass('barchart-grid-col ' + colClasses);

						this.redrawCharts();
					},

					barchartOrientation: function(orientation) {
						if(typeof(AppGini.srBarchart) == 'undefined') return;

						if(orientation == 'horizontal') {
							AppGini.srBarchart.update(false, {
								horizontalBars: true,
								// reverseData: true,
								axisY: { /* labels */
									offset: 80,
									labelInterpolationFnc: Chartist.noop,
									position: 'start' /* = left */
								},
								axisX: { /* values */
									labelInterpolationFnc: function(v) { return AppGini.hugeNumber(v); },
									position: 'start' /* = top */
								}
							}, true);
							$j('#barchart-legend').addClass('horizontal');
							return;
						}

						// vertical (default) orientation
						AppGini.srBarchart.update(false, {
							horizontalBars: false,
							// reverseData: false,
							axisY: { /* values */
								offset: 40,
								labelInterpolationFnc: function(v) { return AppGini.hugeNumber(v); },
								position: 'start'  /* = left */
							},
							axisX: { /* labels */
								labelInterpolationFnc: Chartist.noop,
								position: 'end' /* = bottom */
							}
						}, true);
						$j('#barchart-legend').removeClass('horizontal');
					},

					piechartLabels: function(labels) {
						if(AppGini.srPiechart == undefined) return;

						for(var i = 0; i < 3; i++) {
							if(AppGini.srPiechart[i] == undefined) continue;

							// clone array rather than reference it
							var chartSeries = AppGini.srData.series[i].slice(0),
								chartLabels = AppGini.srData.labels.slice(0),
								seriesSum = chartSeries.reduce(function(s, v) { return s + v; }, 0);
	
							switch(labels) {
								case 'labels':
									// labels should be unchanged ...
									break;
								case 'labels-values':
									for(var j = 0; j < chartSeries.length; j++) {
										chartLabels[j] += '\n(' + AppGini.hugeNumber(chartSeries[j]) + ')';
									}
									break;
								case 'labels-percentages':
									for(var j = 0; j < chartSeries.length; j++) {
										chartLabels[j] += '\n(' + Math.round(chartSeries[j] / seriesSum * 100) + '%)';
									}
									break;
								case 'labels-values-percentages':
									for(var j = 0; j < chartSeries.length; j++) {
										chartLabels[j] += '\n(' + AppGini.hugeNumber(chartSeries[j]) + ')' + 
										             '\n(' + Math.round(chartSeries[j] / seriesSum * 100) + '%)';
									}
									break;
							}
							
							AppGini.srPiechart[i].update(
								{ labels: chartLabels, series: chartSeries },
								{ labelOffset: this.piechartLabelOffset(i) },
								true
							);
						}
					},

					piechartLabelOffset: function(i) {
						if(AppGini.srPiechart[i] == undefined) return 50;
						return $j('#piechart-' + (i + 1)).width() * 0.4;
					},

					redrawCharts: function() {
						if(AppGini.srBarchart != undefined) AppGini.srBarchart.update();

						// instead of redrawing piecharts directly here, we need to call
						// ui.piechartLabels to prevent a bug with labels if the
						// label interpolation function is not specified when calling update()
						var options = state.getAll();
						this.piechartLabels(options.piechartLabels);
					},

					applyStoredStatus: function() {
						var options = state.getAll();

						this.displayRowNumbers(options.displayRowNumbers);
						$j('#row-numbers').prop('checked', options.displayRowNumbers);

						this.alignLabels(options.alignLabels);
						$j('[name="radio-label-align"][value="' + options.alignLabels + '"]').prop('checked', true);

						this.alignValues(options.alignValues);
						$j('[name="radio-value-align"][value="' + options.alignValues + '"]').prop('checked', true);

						this.chartColorTheme(options.chartColorTheme);
						$j('#charts-color-theme option[value="' + options.chartColorTheme + '"]').prop('selected', true);

						this.barchartAspectRatio(options.barchartAspectRatio);
						$j('#barchart-aspect-ratio option[value="' + options.barchartAspectRatio + '"]').prop('selected', true);

						this.barchartBarWidth(options.barchartBarWidth);
						$j('#bar-width').val(options.barchartBarWidth);

						this.barchartBarSpacing(options.barchartBarSpacing);
						$j('#bar-spacing').val(options.barchartBarSpacing);

						this.piechartWidth(options.piechartWidth);
						$j('#piechart-width').val(options.piechartWidth);

						this.barchartWidth(options.barchartWidth);
						$j('#barchart-width').val(options.barchartWidth);

						this.barchartOrientation(options.barchartOrientation);
						$j('[name="barchart-orientation"][value="' + options.barchartOrientation + '"]').prop('checked', true);

						this.piechartLabels(options.piechartLabels);
						$j('#piechart-labels').val(options.piechartLabels);
					},

					hideSectionsIfNoAccess: function() {
						var tableRecordsCount = $j('table.report-data tbody tr').length,
							barchartRecordsCount = 0,
							piechartRecordsCount = 0;

						if(
							AppGini.srBarchart &&
							AppGini.srBarchart.data &&
							AppGini.srBarchart.data.series &&
							AppGini.srBarchart.data.series[0] &&
							AppGini.srBarchart.data.series[0].length
						) barchartRecordsCount = AppGini.srBarchart.data.series[0].length;

						if(
							AppGini.srPiechart &&
							AppGini.srPiechart[0] &&
							AppGini.srBarchart[0].data &&
							AppGini.srBarchart[0].data.series &&
							AppGini.srBarchart[0].data.series.length
						) barchartRecordsCount = AppGini.srPiechart[0].data.series.length;

						if(!tableRecordsCount && !barchartRecordsCount && !piechartRecordsCount) {
							$j('table.report-data, .barchart-grid, .adjacent-piecharts-row, .container > .pull-right.text-bold').addClass('hidden');
							$j('#no-report-data').removeClass('hidden');
						}
					}
				};


				var state = {
					store: function(key, val) {
						if(typeof(localStorage) == undefined) return;

						var reportId = 'summary-reports-' + AppGini.srReportHash;
						var optionsJson = localStorage.getItem(reportId);
						
						var options = JSON.parse(optionsJson) || {};
						options[key] = val;
						optionsJson = JSON.stringify(options);

						localStorage.setItem(reportId, optionsJson);
					},

					getAll: function() {
						if(typeof(localStorage) == undefined) return;

						var reportId = 'summary-reports-' + AppGini.srReportHash;
						var optionsJson = localStorage.getItem(reportId);
						
						var options = JSON.parse(optionsJson) || {};
						return options;
					},

					setDefaults: function(defaults) {
						if(typeof(localStorage) == undefined) return;

						var reportId = 'summary-reports-' + AppGini.srReportHash;
						var options = this.getAll();
						$j.extend(defaults, options);

						localStorage.setItem(reportId, JSON.stringify(defaults));
					}
				};

				// event handlers --------------------------------------------

				$j('#apply-filter').click(function() {
					if(AppGini.srSubmitRequired != undefined) {
						$j(this).parents('form').submit();
					} else {
						$j('#collapse1').collapse('hide');
					}
				});

				/* collapsing config panel applies changes */
				$j('#report-config').on('click', '.panel-heading', function() {
					if($j(this).hasClass('collapsed')) return;
					$j('#apply-filter').trigger('click');
				})

				// delay the event handler to avoid false detection of form initialization as being a 'change'
				setTimeout(function() {
					$j('#p0-start, #p0-end, #p1-start, #p1-end, #p2-start, #p2-end, [name="order-by"], [name="sorting-order"], #comparison-period-1, #comparison-period-2').on('change', function() {
						AppGini.srSubmitRequired = true;
					});
				}, 1000);
				
				$j('#month-to-date').click(function() {
					ui.setDates({
						'p0-start': moment().startOf('month').format(date_format),
						'p0-end': moment().format(date_format),
						'p1-start': moment().subtract(1, 'month').startOf('month').format(date_format),
						'p1-end': moment().subtract(1, 'month').format(date_format),
						'p2-start': moment().subtract(1, 'year').startOf('month').format(date_format),
						'p2-end': moment().subtract(1, 'year').format(date_format)
					});
				});
			
				$j('#current-month').click(function() {
					ui.setDates({
						'p0-start': moment().startOf('month').format(date_format),
						'p0-end': moment().endOf('month').format(date_format),
						'p1-start': moment().subtract(1, 'month').startOf('month').format(date_format),
						'p1-end': moment().subtract(1, 'month').endOf('month').format(date_format),
						'p2-start': moment().subtract(1, 'year').startOf('month').format(date_format),
						'p2-end': moment().subtract(1, 'year').endOf('month').format(date_format)
					});
				});
			
				$j('#last-month').click(function(){
					ui.setDates({
						'p0-start': moment().subtract(1, 'month').startOf('month').format(date_format),
						'p0-end': moment().subtract(1, 'month').endOf('month').format(date_format),
						'p1-start': moment().subtract(2, 'month').startOf('month').format(date_format),
						'p1-end': moment().subtract(2, 'month').endOf('month').format(date_format),
						'p2-start': moment().subtract(1, 'month').subtract(1, 'year').startOf('month').format(date_format),
						'p2-end': moment().subtract(1, 'month').subtract(1, 'year').endOf('month').format(date_format)
					});
				});

				$j('#quarter-to-date').click(function(){
					// compare quarter-to-date to same period last quarter and to same period last year
					ui.setDates({
						'p0-start': moment().startOf('quarter').format(date_format),
						'p0-end': moment().format(date_format),
						'p1-start': moment().subtract(1, 'quarter').startOf('quarter').format(date_format),
						'p1-end': moment().subtract(1, 'quarter').format(date_format),
						'p2-start': moment().subtract(1, 'year').startOf('quarter').format(date_format),
						'p2-end': moment().subtract(1, 'year').format(date_format)
					});
				});
					
				$j('#current-quarter').click(function(){
					// compare current quarter to last one and to this quarter last year
					ui.setDates({
						'p0-start': moment().startOf('quarter').format(date_format),
						'p0-end': moment().endOf('quarter').format(date_format),
						'p1-start': moment().subtract(1, 'quarter').startOf('quarter').format(date_format),
						'p1-end': moment().subtract(1, 'quarter').endOf('quarter').format(date_format),
						'p2-start': moment().subtract(1, 'year').startOf('quarter').format(date_format),
						'p2-end': moment().subtract(1, 'year').endOf('quarter').format(date_format)
					});
				});
	
				$j('#last-quarter').click(function(){
					// compare last quarter to the one before and to the same quarter last year
					ui.setDates({
						'p0-start': moment().subtract(1, 'quarter').startOf('quarter').format(date_format),
						'p0-end': moment().subtract(1, 'quarter').endOf('quarter').format(date_format),
						'p1-start': moment().subtract(2, 'quarter').startOf('quarter').format(date_format),
						'p1-end': moment().subtract(2, 'quarter').endOf('quarter').format(date_format),
						'p2-start': moment().subtract(1, 'quarter').subtract(1, 'year').startOf('quarter').format(date_format),
						'p2-end': moment().subtract(1, 'quarter').subtract(1, 'year').endOf('quarter').format(date_format)
					});
				});
	
				$j('#year-to-date').click(function(){
					// compare year-to-date with same period last year and same period the year before
					ui.setDates({
						'p0-start': moment().startOf('year').format(date_format),
						'p0-end': moment().format(date_format),
						'p1-start': moment().subtract(1, 'year').startOf('year').format(date_format),
						'p1-end': moment().subtract(1, 'year').format(date_format),
						'p2-start': moment().subtract(2, 'year').startOf('year').format(date_format),
						'p2-end': moment().subtract(2, 'year').format(date_format)
					});
				});
				
				$j('#current-year').click(function(){
					// compare current year with last year and the year before
					ui.setDates({
						'p0-start': moment().startOf('year').format(date_format),
						'p0-end': moment().endOf('year').format(date_format),
						'p1-start': moment().subtract(1, 'year').startOf('year').format(date_format),
						'p1-end': moment().subtract(1, 'year').endOf('year').format(date_format),
						'p2-start': moment().subtract(2, 'year').startOf('year').format(date_format),
						'p2-end': moment().subtract(2, 'year').endOf('year').format(date_format)
					});
				});
				
				$j('#last-year').click(function(){
					// compare last year and the two before.
					ui.setDates({
						'p0-start': moment().subtract(1, 'year').startOf('year').format(date_format),
						'p0-end': moment().subtract(1, 'year').endOf('year').format(date_format),
						'p1-start': moment().subtract(2, 'year').startOf('year').format(date_format),
						'p1-end': moment().subtract(2, 'year').endOf('year').format(date_format),
						'p2-start': moment().subtract(3, 'year').startOf('year').format(date_format),
						'p2-end': moment().subtract(3, 'year').endOf('year').format(date_format)
					});
				});
				
				$j('#comparison-period-1').on('change', function(){
					ui.comparisonPeriodDisplay(1);
				});
				
				$j('#comparison-period-2').on('change', function(){
					ui.comparisonPeriodDisplay(2);
				});

				$j(window).resize(function() {
					ui.redrawCharts() 
					ui.setPrintFooter();
				});

				// live config applier code ---------------------------------
				$j('#row-numbers').click(function() {
					var val = $j(this).prop('checked');
					ui.displayRowNumbers(val);
					state.store('displayRowNumbers', val);
				});

				$j('[name="radio-label-align"]').click(function() {
					var val = $j(this).val();
					ui.alignLabels(val);
					state.store('alignLabels', val);
				});
				
				$j('[name="radio-value-align"]').click(function() {
					var val = $j(this).val();
					ui.alignValues(val);
					state.store('alignValues', val);
				});

				$j('#charts-color-theme').change(function() {
					var val = $j(this).val();
					ui.chartColorTheme(val);
					state.store('chartColorTheme', val);
				});

				$j('#barchart-aspect-ratio').change(function() {
					var val = $j(this).val();
					ui.barchartAspectRatio(val);
					state.store('barchartAspectRatio', val);
				});

				$j('#bar-width-more, #bar-width-less').click(function() {
					var inc = $j(this).attr('id') == 'bar-width-more' ? 1 : -1;
					var bw = $j('#bar-width');
					var val = parseInt(bw.val()) + inc;
					val = (val < 0 ? 0 : val);

					bw.val(val);
					ui.barchartBarWidth(val);
					state.store('barchartBarWidth', val);
				});

				$j('#bar-spacing-more, #bar-spacing-less').click(function() {
					var inc = $j(this).attr('id') == 'bar-spacing-more' ? 1 : -1;
					var bs = $j('#bar-spacing');
					var val = parseInt(bs.val()) + inc;

					bs.val(val);
					ui.barchartBarSpacing(val);
					state.store('barchartBarSpacing', val);
				});

				$j('#piechart-width').change(function() {
					var val = $j(this).val();
					ui.piechartWidth(val);
					state.store('piechartWidth', val);
				});

				$j('#barchart-width').change(function() {
					var val = $j(this).val();
					ui.barchartWidth(val);
					state.store('barchartWidth', val);
				});

				$j('[name=barchart-orientation]').click(function() {
					var val = $j(this).val();
					ui.barchartOrientation(val);
					state.store('barchartOrientation', val);
				});

				$j('#piechart-labels').change(function() {
					var val = $j(this).val();
					ui.piechartLabels(val);
					state.store('piechartLabels', val);
				});

				$j('img.report-header-preview').on('load', ui.setPrintTopMargin);

				$j('.report-data tbody tr').on('click', function() {
					$j(this).toggleClass('warning');
				})

				// initial code ---------------------------------------------
				ui.comparisonPeriodDisplay(1);
				ui.comparisonPeriodDisplay(2);
 
 				ui.configDatePicker(date_format);

				$j('[data-toggle="tooltip"]').tooltip();

				state.setDefaults({
					displayRowNumbers: false,
					alignLabels: 'text-left',
					alignValues: 'text-right',
					chartColorTheme: 'charts-theme-blues',
					barchartAspectRatio: 'ct-perfect-fifth',
					barchartBarWidth: 0,
					barchartBarSpacing: 15,
					piechartWidth: 'col-md-offset-4 col-md-4',
					barchartWidth: 'col-md-offset-3 col-md-6',
					barchartOrientation: 'vertical',
					piechartLabels: 'labels',
				});
				ui.applyStoredStatus();
				ui.redrawCharts();
				ui.setPrintTopMargin();
				ui.setPrintFooter();
				ui.hideSectionsIfNoAccess();
			})
		</script>		

		<?php
		return ob_get_clean();
	}
	
	public function render() {
		return 	$this->render_report_title() .
				$this->add_report_configuration() .
				$this->resources() .
				($this->data_table_section ? $this->section_data_table() : '') .
				($this->barchart_section ? $this->section_barchart() : '') .
				($this->piechart_section ? $this->section_piechart() : '') .
				$this->post_render() .
				'';
	}
 	
	private function resources() {
		ob_start();
		?>
		<link rel="stylesheet" href="<?php echo PREPEND_PATH; ?>resources/chartist/chartist.min.css">
		<link rel="stylesheet" href="<?php echo PREPEND_PATH; ?>resources/chartist/chartist-themes.css.php">

		<script src="<?php echo PREPEND_PATH; ?>resources/chartist/chartist.min.js"></script>

		<style>
			@media print {
				@page {
					margin: 15mm;
				}
			}

			.barchart-grid, .piechart-grid {
				page-break-before: always;
			}
			#barchart .ct-label {
				font-size: 1.0rem;
				color: #000;
				fill: #000;
			}
			#barchart-legend {
				position: absolute;
				border: solid 1px black;
				border-radius: 3px;
				padding: 1rem;
				background-color: hsla(0, 100%, 100%, 0.5);
				top: 5%;
				right: 2%;
				font-weight: bold;
				z-index: 100;
			}
			#barchart-legend.horizontal {
				/* 
				top: unset !important;
				bottom: 10%;
				*/
				top: 10%;
				right: 2%;
			}
			.piechart .ct-label {
				font-size: 1.1rem;
				color: #fff;
				text-shadow: 0px 0px 4px #000;
				fill: #fff;
				font-weight: bold;
				white-space: pre;
			}
			.label-row-numbers,
			.label-comparison-period-1,
			.label-comparison-period-2,
			.label-group-by,
			.label-summarize {
				vertical-align: middle !important;
			}
			.report-header {
				position: fixed;
				top: 0;
				margin-top: 0;
				z-index: -100;
			}
			.report-footer {
				position: fixed;
				bottom: 0;
				margin-bottom: 0;
				z-index: -100;
			}
			#p0-start, #p0-end, #p1-start, #p1-end, #p2-start, #p2-end{ 
				display: inline !important; 
				text-align: right !important;
			}
			#p0-start, #p0-end, #p1-start, #p1-end, #p2-start, #p2-end:hover{
				cursor: pointer;
			}
			.datepicker.dropdown-menu {
				 right: initial;
			}
			.top-space{
				margin-top: 3rem;
			}
			/*
			#report-config {
				position: fixed;
				left: 0%;
				top: 9em;
				opacity: .92;
				max-width: 50%;
				z-index: 1000;
			}
			*/

			#report-config .btn { display: block !important; }
			.report-config-table td, .report-config-table th {
				padding: 0.2em;
				vertical-align: top;
			}
			.form-inline .report-config-table .form-control {
				width: 100% !important; /* this overrides a rule in dynamic.css.php that mal-formats input groups in this page */
			}
		</style>
		<?php
		return ob_get_clean();
	}

	private function date_to_ts($date){
		return strtotime(toMySQLDate($date));
	}
	
	private function valid_app_date($date, $default = false) {
		// only allow digits, a, p, m, whitespace and valid separators (.,-/) and strip everything else
		$date = trim(preg_replace('/[^\d\s-\.,\/apm:]/i', '', $date));
		return $this->date_to_ts($date) ? $date : $default;
	}
	
	/**
	 *  @brief Sort app-formatted dates. After caling this function, $date1 will be the older date and $date2 the newer one.
	 *  
	 *  @param [in,out] $date1 app-formatted date
	 *  @param [in,out] $date2 app-formatted date
	 */
	private function sort_dates(&$date1, &$date2) {
		if($this->date_to_ts($date1) > $this->date_to_ts($date2)) {
			$temp = $date1;
			$date1 = $date2;
			$date2 = $temp;
		}
	}
}
