<?php
/**
 * FiltersPanel class to generate a collapsible filter panel.
 * Usage: `echo FiltersPanel::html();`
 * 
 * CSS is in dynamic.css (namespaced under .filters-panel- prefix).
 * Other code injects filter components via FiltersPanel::addContent($html).
 */
class FiltersPanel {
	/** @var string Accumulated panel content (set before header.php renders). */
	public static $content = '';

	// Filter group allocation: panel owns groups 31-40 (indices 121-160)
	const PANEL_FILTER_GROUP_START = 31;
	const PANEL_FILTER_GROUP_END   = 40;
	const PANEL_FILTER_INDEX_START = FILTERS_PER_GROUP * (self::PANEL_FILTER_GROUP_START - 1) + 1; // 121; // 4 * (31 - 1) + 1
	const PANEL_FILTER_INDEX_END   = FILTERS_PER_GROUP * self::PANEL_FILTER_GROUP_END; // 160; // 4 * 40

	/**
	 * Renders the filter panel HTML + JS.
	 * Called from header.php.
	 * @return string
	 */
	public static function html() {
		// Skip in setup mode or embedded requests
		if(defined('APPGINI_SETUP') && APPGINI_SETUP) return '';
		if(Request::val('Embedded')) return '';
		if(self::$content === '') return '';

		return self::renderHtml() . self::renderJs();
	}

	/**
	 * Register content to be displayed inside the panel.
	 * Call this before header.php renders to inject filter components.
	 *
	 * @param string $html Raw HTML to append to the panel body.
	 */
	public static function addContent($html) {
		self::$content .= $html;
	}

	private static function renderHtml() {
		global $Translation;

		$filterLabel = $Translation['filters'] ?? 'Filters';
		$closeLabel = $Translation['close'] ?? 'Close';
		$toggleTitle = $Translation['filters panel'] ?? 'Toggle filters panel';

		// Fallback content if nothing was registered (shouldn't happen — html() guards against this)
		$bodyContent = self::$content;

		ob_start();
		?>
		<button class="btn btn-default btn-xs btn-toggle-filters-panel hidden-print"
			title="<?php echo html_attr($toggleTitle); ?>"
			aria-label="<?php echo html_attr($toggleTitle); ?>">
			<span class="glyphicon glyphicon-filter"></span>
			<span class="filters-panel-dot"></span>
		</button>
		<div class="panel panel-default filters-panel hidden-print">
			<div class="panel-heading filters-panel-header">
				<h4 class="panel-title"><?php echo html_attr($filterLabel); ?></h4>
				<button class="btn btn-xs btn-default filters-panel-close"
					title="<?php echo html_attr($closeLabel); ?>"
					aria-label="<?php echo html_attr($closeLabel); ?>">&times;</button>
			</div>
			<div class="panel-body filters-panel-body">
				<?php echo $bodyContent; ?>
			</div>
		</div>
		<div class="filters-panel-backdrop hidden"></div>
		<div class="filters-panel-portal"></div>
		<?php
		return ob_get_clean();
	}

	// ─────────────────────────────────────────────────────────
	// Filter template methods
	// ─────────────────────────────────────────────────────────

	/**
	 * Build filter panel HTML and register it via addContent().
	 * Called by DataList::Render() when in table view mode.
	 * This is the only method callers need — addContent() is handled internally.
	 *
	 * @param array $filters   Array of {field, type, label} definitions.
	 * @param DataList $dataList  The DataList instance (for field index lookups and table name).
	 */
	public static function buildFiltersHtml($filters, $dataList) {
		$html = '';

		// Resolve field indices once
		$filterDefs = [];
		foreach($filters as $filter) {
			$filter['fieldIndex'] = self::resolveFieldIndex($filter, $dataList, $dataList->TableName);
			$filterDefs[] = $filter;
		}

		// Determine if save button should be shown
		$showSaveButton = !empty($dataList->AllowSavingFilters) && !Authentication::isGuest();

		foreach($filterDefs as $filter) {
			$html .= self::filterTemplate($filter, $dataList->TableName);
		}

		// Bottom buttons: Apply + optional Save (stacked vertically)
		$html .= self::applyButtonHtml('bottom');
		if($showSaveButton) {
			$html .= self::saveButtonHtml();
		}

		self::addContent($html);
	}

	/**
	 * Find the filter field index (1-based, into QueryFieldsIndexed) for a given
	 * filter definition.  First tries standard column-name matching; for lookup
	 * fields falls back to matching via the $lookups array (parent table + column).
	 *
	 * @param array $filter     Filter definition: {field, type, label, ...}
	 * @param DataList $dataList
	 * @param string $tableName  Base table name for lookups fallback.
	 * @return int  The 1-based index, or 0 if not found.
	 */
	private static function resolveFieldIndex($filter, $dataList, $tableName = null) {
		if(empty($dataList->QueryFieldsIndexed)) return 0;

		$field = $filter['field'];
		$label = isset($filter['label']) ? $filter['label'] : $field;
		$safeField = makeSafe($field);

		// ── Standard matching: `table`.`FieldName` or `table.FieldName` ──
		foreach($dataList->QueryFieldsIndexed as $idx => $expr) {
			if(preg_match('/[.`]' . preg_quote($safeField, '/') . '[`\s\)]/i', $expr))
				return $idx;
			if(stripos($expr, ".`{$safeField}`") !== false)
				return $idx;
		}

		// Fallback: try matching the field as a key in QueryFieldsFilters
		foreach($dataList->QueryFieldsFilters as $expr => $caption) {
			if(stripos($expr, ".`{$safeField}`") !== false || stripos($expr, ".{$safeField}") !== false) {
				$pos = array_search($expr, $dataList->QueryFieldsIndexed);
				if($pos !== false) return $pos;
			}
		}

		// ── Lookups-based fallback ─────────────────────────────
		// For lookup fields the expression is a display formula
		// like IF(...`shippers1`.`CompanyName`...) that doesn't contain
		// the raw field name.  Use the $lookups array to find the parent
		// table and caption column, then match against QueryFieldsFilters.
		if($tableName) {
			$lookups = get_lookups();
			if(isset($lookups[$tableName][$field])) {
				$cfg = $lookups[$tableName][$field];
				$parentTable   = $cfg['parent_table'];
				$parentCaption = $cfg['parent_caption'];

				// Extract just the column name from parent_caption
				if(preg_match('/`(\w+)`[^`]*$/', $parentCaption, $m)) {
					$parentCol = $m[1];

					// Find expressions referencing parent_table (possibly
					// aliased, e.g. shippers1) and parent column.
					// The closing backtick after the table alias is optional:
					// MySQL expressions use `shippers1`.`CompanyName`
					$pattern = '/' . preg_quote($parentTable, '/') . '\d*`?\.`' . preg_quote($parentCol, '/') . '`/i';
					$candidates = [];
					foreach($dataList->QueryFieldsFilters as $expr => $caption) {
						if(preg_match($pattern, $expr)) {
							$pos = array_search($expr, $dataList->QueryFieldsIndexed);
							if($pos !== false)
								$candidates[] = ['index' => $pos, 'caption' => $caption];
						}
					}

					if(count($candidates) === 1)
						return $candidates[0]['index'];

					// Multiple candidates: prefer caption matching filter label
					foreach($candidates as $c) {
						if(strcasecmp($c['caption'], $label) === 0)
							return $c['index'];
					}

					if(count($candidates))
						return $candidates[0]['index'];
				}
			}
		}

		return 0;
	}

	/**
	 * Dispatch to the correct template method based on filter type.
	 */
	private static function filterTemplate($filter, $tableName) {
		$field      = $filter['field'];
		$label      = isset($filter['label']) ? $filter['label'] : $field;
		$type       = $filter['type'];
		$fieldIndex = isset($filter['fieldIndex']) ? $filter['fieldIndex'] : 0;

		switch($type) {
			case 'date-range':     return self::dateRangeTemplate($field, $label, $fieldIndex);
			case 'datetime-range': return self::dateTimeRangeTemplate($field, $label, $fieldIndex);
			case 'numeric-range':  return self::numericRangeTemplate($field, $label, $fieldIndex);
			case 'select2':        return self::select2Template($field, $label, $tableName, $fieldIndex);
			case 'checkbox':       return self::checkboxTemplate($field, $label, $fieldIndex);
			default:               return '';
		}
	}

	/** Two text inputs with bootstrap-datetimepicker for a date range.
	 *  Uses the app's date format from AppGini.datetimeFormat('d').
	 */
	private static function dateRangeTemplate($field, $label, $fieldIndex) {
		global $Translation;
		$safeField = html_attr($field);
		$safeLabel = html_attr($label);
		$tQuickRanges = html_attr($Translation['quick date ranges'] ?? 'Quick date ranges');
		$tToday       = html_attr($Translation['Today'] ?? 'Today');
		$tYesterday   = html_attr($Translation['Yesterday'] ?? 'Yesterday');
		$tMTD         = html_attr($Translation['Month to date'] ?? 'Month to date');
		$tLastMonth   = html_attr($Translation['Last month'] ?? 'Last month');
		$tYTD         = html_attr($Translation['Year to date'] ?? 'Year to date');
		$tLastYear    = html_attr($Translation['Last year'] ?? 'Last year');
		$tClear       = html_attr($Translation['Clear'] ?? 'Clear');
		// The dropdown-menu-right class is a CSS fallback for alignment; in
		// practice the portal JS (show.bs.dropdown handler in renderJs())
		// repositions the menu inward toward the panel center on both LTR
		// and RTL, overriding this class with inline styles.
		return <<<HTML
<div class="filter-group" data-filter-type="date-range" data-filter-field="{$safeField}" data-filter-field-index="{$fieldIndex}">
	<div class="filter-group-label-row">
		<label>{$safeLabel}</label>
		<div class="dropdown filter-quick-dates">
			<button class="btn btn-link btn-xs dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{$tQuickRanges}">
				<span class="caret"></span>
			</button>
			<ul class="dropdown-menu dropdown-menu-right">
				<li><a href="#" data-quick-date="today">{$tToday}</a></li>
				<li><a href="#" data-quick-date="yesterday">{$tYesterday}</a></li>
				<li role="separator" class="divider"></li>
				<li><a href="#" data-quick-date="mtd">{$tMTD}</a></li>
				<li><a href="#" data-quick-date="last-month">{$tLastMonth}</a></li>
				<li role="separator" class="divider"></li>
				<li><a href="#" data-quick-date="ytd">{$tYTD}</a></li>
				<li><a href="#" data-quick-date="last-year">{$tLastYear}</a></li>
				<li role="separator" class="divider"></li>
				<li><a href="#" data-quick-date="clear">{$tClear}</a></li>
			</ul>
		</div>
	</div>
	<div class="filter-date-range">
		<input type="text" class="form-control filter-date-from filter-datepicker" placeholder="From">
		<span class="filter-range-sep">&ndash;</span>
		<input type="text" class="form-control filter-date-to filter-datepicker" placeholder="To">
	</div>
</div>
HTML;
	}

	/** Two text inputs with bootstrap-datetimepicker for a datetime range.
	 *  Uses the app's datetime format from AppGini.datetimeFormat('dt').
	 */
	private static function dateTimeRangeTemplate($field, $label, $fieldIndex) {
		global $Translation;
		$safeField = html_attr($field);
		$safeLabel = html_attr($label);
		$tQuickRanges = html_attr($Translation['quick date ranges']);
		$tToday       = html_attr($Translation['Today']);
		$tYesterday   = html_attr($Translation['Yesterday']);
		$tMTD         = html_attr($Translation['Month to date']);
		$tLastMonth   = html_attr($Translation['Last month']);
		$tYTD         = html_attr($Translation['Year to date']);
		$tLastYear    = html_attr($Translation['Last year']);
		$tClear       = html_attr($Translation['Clear']);
		return <<<HTML
<div class="filter-group" data-filter-type="datetime-range" data-filter-field="{$safeField}" data-filter-field-index="{$fieldIndex}">
	<div class="filter-group-label-row">
		<label>{$safeLabel}</label>
		<div class="dropdown filter-quick-dates">
			<button class="btn btn-link btn-xs dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{$tQuickRanges}">
				<span class="caret"></span>
			</button>
			<ul class="dropdown-menu dropdown-menu-right">
				<li><a href="#" data-quick-date="today">{$tToday}</a></li>
				<li><a href="#" data-quick-date="yesterday">{$tYesterday}</a></li>
				<li role="separator" class="divider"></li>
				<li><a href="#" data-quick-date="mtd">{$tMTD}</a></li>
				<li><a href="#" data-quick-date="last-month">{$tLastMonth}</a></li>
				<li role="separator" class="divider"></li>
				<li><a href="#" data-quick-date="ytd">{$tYTD}</a></li>
				<li><a href="#" data-quick-date="last-year">{$tLastYear}</a></li>
				<li role="separator" class="divider"></li>
				<li><a href="#" data-quick-date="clear">{$tClear}</a></li>
			</ul>
		</div>
	</div>
	<div class="filter-datetime-range">
		<input type="text" class="form-control filter-datetime-from filter-datetimepicker" placeholder="From">
		<span class="filter-range-sep">&ndash;</span>
		<input type="text" class="form-control filter-datetime-to filter-datetimepicker" placeholder="To">
	</div>
</div>
HTML;
	}

	/** Two <input type="number"> controls for a numeric range. */
	private static function numericRangeTemplate($field, $label, $fieldIndex) {
		global $Translation;
		$safeField = html_attr($field);
		$safeLabel = html_attr($label);
		$tClear = html_attr($Translation['Clear'] ?? 'Clear');
		return <<<HTML
<div class="filter-group" data-filter-type="numeric-range" data-filter-field="{$safeField}" data-filter-field-index="{$fieldIndex}">
	<div class="filter-group-label-row">
		<label>{$safeLabel}</label>
		<button type="button" class="btn btn-link btn-xs filter-clear-btn" title="{$tClear}">
			<span class="glyphicon glyphicon-remove"></span>
		</button>
	</div>
	<div class="filter-numeric-range">
		<input type="number" class="form-control filter-num-min" placeholder="Min">
		<span class="filter-range-sep">&ndash;</span>
		<input type="number" class="form-control filter-num-max" placeholder="Max">
	</div>
</div>
HTML;
	}

	/** Multi-select2 dropdown populated from distinct values in the table column.
	 *  For lookup fields, queries the parent table for display values rather than
	 *  showing raw stored IDs (which would confuse users and break filtering since
	 *  filters compare against the display expression, not the stored value).
	 */
	private static function select2Template($field, $label, $tableName, $fieldIndex) {
		global $Translation;
		$safeField = makeSafe($field);
		$safeTable = makeSafe($tableName);
		$eo = ['silentErrors' => true];

		// Check if this is a lookup field with a known parent table
		$lookups = get_lookups();
		$lookupConfig = $lookups[$tableName][$field] ?? null;

		$options = '';

		if($lookupConfig && !empty($lookupConfig['parent_caption']) && !empty($lookupConfig['parent_from'])) {
			// Lookup field: query parent table for display values.
			// The caption SQL expression (e.g. `shippers`.`CompanyName`) is the
			// display text that users see and that filters compare against.
			$parentCaption = $lookupConfig['parent_caption'];
			$parentFrom    = $lookupConfig['parent_from'];

			$res = sql(
				"SELECT {$parentCaption} as `__display` FROM {$parentFrom}" .
				" WHERE {$parentCaption} IS NOT NULL AND {$parentCaption} != ''" .
				" GROUP BY `__display`" .
				" ORDER BY `__display`",
				$eo
			);

			while($row = db_fetch_assoc($res)) {
				$val = html_attr($row['__display']);
				$options .= "<option value=\"{$val}\">{$val}</option>";
			}
		} else {
			// Plain field: query distinct values from the table column
			$res = sql(
				"SELECT DISTINCT `{$safeField}` FROM `{$safeTable}`" .
				" WHERE `{$safeField}` IS NOT NULL AND `{$safeField}` != ''" .
				" ORDER BY `{$safeField}`",
				$eo
			);

			while($row = db_fetch_row($res)) {
				$val = html_attr($row[0]);
				$options .= "<option value=\"{$val}\">{$val}</option>";
			}
		}

		$safeFieldAttr = html_attr($field);
		$safeLabel = html_attr($label);
		$tClear = html_attr($Translation['Clear'] ?? 'Clear');

		return <<<HTML
<div class="filter-group" data-filter-type="select2" data-filter-field="{$safeFieldAttr}" data-filter-field-index="{$fieldIndex}">
	<div class="filter-group-label-row">
		<label>{$safeLabel}</label>
		<button type="button" class="btn btn-link btn-xs filter-clear-btn" title="{$tClear}">
			<span class="glyphicon glyphicon-remove"></span>
		</button>
	</div>
	<input type="hidden" name="filter-{$safeFieldAttr}" class="filter-select2-value">
	<div class="filter-select2-container">
		<select class="filter-select2" multiple data-max-selections="4">
			{$options}
		</select>
	</div>
</div>
HTML;
	}

	/** Three-state checkbox for boolean/nullable fields.
	 *  Cycles: indeterminate (no filter) → checked (is-not-empty) →
	 *  unchecked (is-empty) → back to indeterminate.
	 */
	private static function checkboxTemplate($field, $label, $fieldIndex) {
		$safeField = html_attr($field);
		$safeLabel = html_attr($label);
		return <<<HTML
<div class="filter-group" data-filter-type="checkbox" data-filter-field="{$safeField}" data-filter-field-index="{$fieldIndex}">
	<div class="filter-group-label-row">
		<label class="filter-checkbox-container">
			<input type="checkbox" class="filter-checkbox" data-checkbox-state="indeterminate">
			{$safeLabel}
		</label>
	</div>
</div>
HTML;
	}

	/** Renders an Apply button with a positional class. */
	private static function applyButtonHtml($position) {
		global $Translation;
		$label = $Translation['apply filters'] ?? 'Apply Filters';
		$safeLabel = html_attr($label);
		$safePos = html_attr($position);
		return <<<HTML
<button type="button" class="btn btn-primary btn-block btn-apply-filters-panel"
	data-position="{$safePos}" disabled>
	<i class="glyphicon glyphicon-filter"></i> {$safeLabel}
</button>
HTML;
	}

	/** Renders a Save Filters button (full width, disabled by default). */
	private static function saveButtonHtml() {
		global $Translation;
		$label = $Translation['save filters'] ?? 'Save';
		$safeLabel = html_attr($label);
		return <<<HTML
<button type="button" class="btn btn-default btn-block btn-save-filters-panel" disabled>
	<i class="glyphicon glyphicon-bookmark"></i> {$safeLabel}
</button>
HTML;
	}

	private static function renderJs() {
		ob_start();
		?>
		<script>
			$j(function() {
				$j('.filter-select2').each(function() {
					var $sel = $j(this);
					var $container = $sel.closest('.filter-group');
					var $hidden = $container.find('.filter-select2-value');

					$sel.select2({
						width: '100%',
						formatNoMatches: function() { return 'No matches found'; },
						minimumResultsForSearch: 5,
						maximumSelectionSize: 4,
						placeholder: 'Select up to 4'
					}).on('change', function() {
						var vals = $sel.val() || [];
						$hidden.val(JSON.stringify(vals));
					});
				});

				// Initialize three-state checkbox indeterminate state
				$j('.filter-checkbox').each(function() {
					var state = $j(this).data('checkbox-state') || 'indeterminate';
					if(state === 'indeterminate')
						$j(this).prop('indeterminate', true);
					if(state === 'checked')
						$j(this).prop('checked', true);
				});

				// ── Initialize date/datetime pickers ──────────────
				// Uses bootstrap-datetimepicker (eonasdan) which is globally
				// loaded in header.php along with moment.js.  The format
				// string comes from AppGini.datetimeFormat(), which returns
				// the app's configured date/datetime format (moment.js tokens).
				$j('.filter-datepicker').datetimepicker({
					format: AppGini.datetimeFormat('d'),
					showClear: true,
					showClose: true
				})				.on('dp.show', function() {
					fitPickerToPanel(this);
				});
				$j('.filter-datetimepicker').datetimepicker({
					format: AppGini.datetimeFormat('dt'),
					showClear: true,
					showClose: true,
					sideBySide: false
				}).on('dp.show', function() {
					fitPickerToPanel(this);
				});

				function fitPickerToPanel(input) {
					var $widget = $j('.bootstrap-datetimepicker-widget.dropdown-menu');
					var $portal = $j('.filters-panel-portal');
					var panelW = $j('.filters-panel').width();
					var inputRect = input.getBoundingClientRect();
					var viewportW = $j(window).width();
					var viewportH = $j(window).height();
					var w = Math.floor(panelW * 0.9);
					var widgetH = $widget.outerHeight() || 350;

					// Determine vertical direction
					var isDropup = (inputRect.bottom + widgetH > viewportH - 10);
					var top = isDropup ? (inputRect.top - widgetH - 5) : (inputRect.bottom + 2);

					// Clamp horizontally
					var left = inputRect.left;
					if(left + w > viewportW - 5) left = viewportW - w - 5;
					if(left < 5) left = 5;

					var maxH = isDropup ? (inputRect.top - 20) : (viewportH - top - 20);
					if(maxH < 100) maxH = 100;

					$widget.css({
						position: 'fixed',
						top: top + 'px',
						left: left + 'px',
						bottom: 'auto',
						right: 'auto',
						width: w + 'px',
						'max-height': maxH + 'px'
					}).appendTo($portal);
				}

			// ── Reposition quick-date dropdowns to avoid panel overflow clipping ──
			// The panel has overflow-y: auto which clips the dropdown menu when it
			// extends beyond the panel. On show, move the menu to the portal div with
			// position:fixed so it renders outside the panel's overflow container.
			$j('.filters-panel').on('show.bs.dropdown', '.filter-quick-dates', function() {
				var $dd = $j(this);
				var $menu = $dd.find('.dropdown-menu');
				var $portal = $j('.filters-panel-portal');
				var panel = $j('.filters-panel')[0];
				var ddRect = $dd[0].getBoundingClientRect();
				var viewportH = $j(window).height();
				var viewportW = $j(window).width();
				var menuH = 250; // generous estimate (7 items×30px + 3 dividers×10px ≈ 240px)

				// Decide whether to open downward or upward based on viewport space
				var asDropup = (ddRect.bottom + menuH) > viewportH;

				// Align the menu inward (toward the center of the panel):
				// LTR: button is on the right side of the label row, menu extends left.
				// RTL: button is on the left side, menu extends right.
				var panelRect = panel.getBoundingClientRect();
				var buttonCenter = ddRect.left + ddRect.width / 2;
				var panelCenter = panelRect.left + panelRect.width / 2;
				var alignLeft = buttonCenter < panelCenter;

				// Use available panel space inward from the button, capped at 260px
				var menuW;
				if(alignLeft) {
					menuW = Math.min(260, panelRect.right - ddRect.left - 10);
				} else {
					menuW = Math.min(260, ddRect.right - panelRect.left - 10);
				}
				menuW = Math.max(160, menuW);

				// Compute horizontal position
				var left = alignLeft ? ddRect.left : (ddRect.right - menuW);
				if(left < 5) left = 5;
				if(left + menuW > viewportW - 5) left = viewportW - menuW - 5;

				// Store menu reference on the dropdown for restoration on hide,
				// and store the owning filter-group on the menu so quick-date
				// clicks can still find their related input fields after the menu
				// is moved to the portal.
				$dd.data('qdrop-menu', $menu);
				$menu.data('qdrop-group', $dd.closest('.filter-group'));

				$menu.css({
					position: 'fixed',
					left: left + 'px',
					width: menuW + 'px',
					'max-height': Math.min(menuH, asDropup ? ddRect.top - 20 : viewportH - ddRect.bottom - 20) + 'px',
					'overflow-y': 'auto',
					display: 'block'
				});

				if(asDropup) {
					$menu.css({ bottom: (viewportH - ddRect.top + 3) + 'px', top: 'auto' });
				} else {
					$menu.css({ top: ddRect.bottom + 'px', bottom: 'auto' });
				}

				$menu.appendTo($portal);

				// Bootstrap will add .open after this event. Since the menu was
				// moved out of .open's child tree, we must set display:block above
				// and cancel the default .open > .dropdown-menu display cascade.
			}).on('hidden.bs.dropdown', '.filter-quick-dates', function() {
				var $dd = $j(this);
				var $menu = $dd.data('qdrop-menu');
				if($menu && $menu.length) {
					$menu.css({
						position: '', top: '', bottom: '', left: '', right: '',
						width: '', 'max-height': '', 'overflow-y': '', display: ''
					});
					$dd.append($menu);
					$dd.removeData('qdrop-menu');
					$menu.removeData('qdrop-group');
				}
			});
			// ── Populate filter controls from existing hidden inputs ──
			// Reads FilterField/FilterOperator/FilterValue for indices 121-160
			// (panel groups 31-40) and pre-fills the corresponding controls
			// so users can see which filters are currently applied.
			(function populatePanelFromHiddenInputs() {
				var PANEL_INDEX_START = <?php echo self::PANEL_FILTER_INDEX_START; ?>;
				var PANEL_INDEX_END   = <?php echo self::PANEL_FILTER_INDEX_END; ?>;

				$j('.filters-panel .filter-group').each(function() {
					var $group = $j(this);
					var fieldIdx = parseInt($group.data('filter-field-index'), 10) || 0;
					var type = $group.data('filter-type');
					if(!fieldIdx) return;

					// Collect conditions from hidden inputs for this field index
					var conditions = [];
					$j('input[name^="FilterField["]').each(function() {
						var match = this.name.match(/\[(\d+)\]/);
						if(!match) return;
						var idx = parseInt(match[1], 10);
						if(idx < PANEL_INDEX_START || idx > PANEL_INDEX_END) return;
						if(parseInt(this.value, 10) !== fieldIdx) return;

						var opEl  = $j('input[name="FilterOperator[' + idx + ']"]')[0];
						var valEl = $j('input[name="FilterValue[' + idx + ']"]')[0];
						if(opEl && valEl && valEl.value !== undefined)
							conditions.push({ idx: idx, op: opEl.value, val: valEl.value });
					});

					if(!conditions.length) return;

					// Populate controls based on filter type
					switch(type) {
						case 'date-range':
							var dateFmt = AppGini.datetimeFormat('d');
							conditions.forEach(function(c) {
								if(c.op === 'greater-than-or-equal-to') {
									var $input = $group.find('.filter-date-from');
									$input.val(c.val);
									var dp = $input.data('DateTimePicker');
									if(dp) { var m = moment(c.val, dateFmt); if(m.isValid()) dp.date(m); }
								} else if(c.op === 'less-than-or-equal-to') {
									var $input = $group.find('.filter-date-to');
									$input.val(c.val);
									var dp = $input.data('DateTimePicker');
									if(dp) { var m = moment(c.val, dateFmt); if(m.isValid()) dp.date(m); }
								}
							});
							break;

						case 'datetime-range':
							var dtFmt = AppGini.datetimeFormat('dt');
							conditions.forEach(function(c) {
								if(c.op === 'greater-than-or-equal-to') {
									var $input = $group.find('.filter-datetime-from');
									$input.val(c.val);
									var dp = $input.data('DateTimePicker');
									if(dp) { var m = moment(c.val, dtFmt); if(m.isValid()) dp.date(m); }
								} else if(c.op === 'less-than-or-equal-to') {
									var $input = $group.find('.filter-datetime-to');
									$input.val(c.val);
									var dp = $input.data('DateTimePicker');
									if(dp) { var m = moment(c.val, dtFmt); if(m.isValid()) dp.date(m); }
								}
							});
							break;

						case 'numeric-range':
							conditions.forEach(function(c) {
								if(c.op === 'greater-than-or-equal-to')
									$group.find('.filter-num-min').val(c.val);
								else if(c.op === 'less-than-or-equal-to')
									$group.find('.filter-num-max').val(c.val);
							});
							break;

						case 'select2':
							var vals = conditions.filter(function(c) {
								return c.op === 'equal-to';
							}).map(function(c) { return c.val; });
							if(vals.length)
								$group.find('.filter-select2').val(vals).trigger('change');
							break;

						case 'checkbox':
							var isFoundChecked = false, isFoundUnchecked = false;
							conditions.forEach(function(c) {
								if(c.op === 'is-not-empty') isFoundChecked = true;
								if(c.op === 'is-empty') isFoundUnchecked = true;
							});
							var $cb = $group.find('.filter-checkbox');
							if(isFoundChecked) {
								$cb.prop('checked', true).prop('indeterminate', false);
								$cb.data('checkbox-state', 'checked');
							} else if(isFoundUnchecked) {
								$cb.prop('checked', false).prop('indeterminate', false);
								$cb.data('checkbox-state', 'unchecked');
							} else {
								$cb.prop('checked', false).prop('indeterminate', true);
								$cb.data('checkbox-state', 'indeterminate');
							}
							break;
					}
				});
			})();

			// ── Snapshots & change detection for apply buttons ──
			// Apply buttons start disabled; they enable only when the user
			// makes a change to any filter control.  Pre-population from
			// stored filters (above) does NOT count as a user change.

			function snapshotFilterControls() {
				$j('.filters-panel .filter-group').each(function() {
					var $group = $j(this);
					var type = $group.data('filter-type');
					switch(type) {
						case 'date-range':
							$group.find('.filter-date-from').data('original', $group.find('.filter-date-from').val() || '');
							$group.find('.filter-date-to').data('original', $group.find('.filter-date-to').val() || '');
							break;
						case 'datetime-range':
							$group.find('.filter-datetime-from').data('original', $group.find('.filter-datetime-from').val() || '');
							$group.find('.filter-datetime-to').data('original', $group.find('.filter-datetime-to').val() || '');
							break;
						case 'numeric-range':
							$group.find('.filter-num-min').data('original', $group.find('.filter-num-min').val() || '');
							$group.find('.filter-num-max').data('original', $group.find('.filter-num-max').val() || '');
							break;
						case 'select2':
							$group.find('.filter-select2-value').data('original', $group.find('.filter-select2-value').val() || '');
							break;
						case 'checkbox':
							$group.data('original-state', $group.find('.filter-checkbox').data('checkbox-state') || 'indeterminate');
							break;
					}
				});
			}

			function checkFiltersChanged() {
				var changed = false;
				var hasActiveFilters = false;
				$j('.filters-panel .filter-group').each(function() {
					var $group = $j(this);
					var type = $group.data('filter-type');
					var groupHasValue = false;
					switch(type) {
						case 'date-range':
							if(($group.find('.filter-date-from').val() || '') !== ($group.find('.filter-date-from').data('original') || '')) changed = true;
							if(($group.find('.filter-date-to').val() || '') !== ($group.find('.filter-date-to').data('original') || '')) changed = true;
							if($group.find('.filter-date-from').val() || $group.find('.filter-date-to').val()) hasActiveFilters = true;
							break;
						case 'datetime-range':
							if(($group.find('.filter-datetime-from').val() || '') !== ($group.find('.filter-datetime-from').data('original') || '')) changed = true;
							if(($group.find('.filter-datetime-to').val() || '') !== ($group.find('.filter-datetime-to').data('original') || '')) changed = true;
							if($group.find('.filter-datetime-from').val() || $group.find('.filter-datetime-to').val()) hasActiveFilters = true;
							break;
						case 'numeric-range':
							if(($group.find('.filter-num-min').val() || '') !== ($group.find('.filter-num-min').data('original') || '')) changed = true;
							if(($group.find('.filter-num-max').val() || '') !== ($group.find('.filter-num-max').data('original') || '')) changed = true;
							if($group.find('.filter-num-min').val() !== '' || $group.find('.filter-num-max').val() !== '') hasActiveFilters = true;
							groupHasValue = $group.find('.filter-num-min').val() !== '' || $group.find('.filter-num-max').val() !== '';
							break;
						case 'select2':
							if(($group.find('.filter-select2-value').val() || '') !== ($group.find('.filter-select2-value').data('original') || '')) changed = true;
							var selCount = (JSON.parse($group.find('.filter-select2-value').val() || '[]')).length;
							if(selCount) hasActiveFilters = true;
							groupHasValue = selCount > 0;
							break;
						case 'checkbox':
							var origState = $group.data('original-state') || 'indeterminate';
							var curState = $group.find('.filter-checkbox').data('checkbox-state') || 'indeterminate';
							if(curState !== origState) changed = true;
							if(curState !== 'indeterminate') hasActiveFilters = true;
							break;
					}
					$group.find('.filter-clear-btn').toggle(!!groupHasValue);
				});
				$j('.btn-apply-filters-panel').prop('disabled', !changed);
				$j('.btn-save-filters-panel').prop('disabled', !hasActiveFilters);
			}

			// Take snapshot AFTER pre-population completes
			snapshotFilterControls();

			// Bind change events on all filter controls
			var onFilterChange = function() { checkFiltersChanged(); };
			$j('.filter-datepicker, .filter-datetimepicker').on('dp.change', onFilterChange);
			$j('.filter-num-min, .filter-num-max').on('input change', onFilterChange);
			$j('.filter-select2').on('change', onFilterChange);

			// Evaluate initial state (enables buttons if filters are pre-populated)
			checkFiltersChanged();

			// ── Clear filter buttons ─────────────────────────
			$j('.filter-clear-btn').on('click', function(e) {
				e.preventDefault();
				var $group = $j(this).closest('.filter-group');
				var type = $group.data('filter-type');

				switch(type) {
					case 'numeric-range':
						$group.find('.filter-num-min').val('');
						$group.find('.filter-num-max').val('');
						break;
					case 'select2':
						$group.find('.filter-select2').val(null).trigger('change');
						break;
				}

				checkFiltersChanged();
			});

			// ── Three-state checkbox toggle ─────────────────
			$j('.filter-checkbox-container').on('click', function(e) {
				e.preventDefault();
				var $container = $j(this);
				var $cb = $container.find('.filter-checkbox');
				var currentState = $cb.data('checkbox-state') || 'indeterminate';

				if(currentState === 'indeterminate') {
					$cb.prop('checked', true).prop('indeterminate', false);
					$cb.data('checkbox-state', 'checked');
				} else if(currentState === 'checked') {
					$cb.prop('checked', false).prop('indeterminate', false);
					$cb.data('checkbox-state', 'unchecked');
				} else {
					$cb.prop('checked', false).prop('indeterminate', true);
					$cb.data('checkbox-state', 'indeterminate');
				}
				checkFiltersChanged();
			});

			// ── Quick date range dropdowns ──────────────────
				$j('.filter-quick-dates .dropdown-menu a').on('click', function(e) {
					e.preventDefault();
					var action = $j(this).data('quick-date');
					var $menu = $j(this).closest('.dropdown-menu');
					// The menu may have been moved to the portal, so closest() no
					// longer reaches the .filter-group. Use the stored reference
					// or fall back to closest() for un-portaled menus.
					var group = $menu.data('qdrop-group');
					if(!group || !group.length) group = $j(this).closest('.filter-group');
					var isDatetime = group.data('filter-type') === 'datetime-range';
					var fromInput = group.find(isDatetime ? '.filter-datetime-from' : '.filter-date-from');
					var toInput   = group.find(isDatetime ? '.filter-datetime-to'   : '.filter-date-to');
					var fmt = AppGini.datetimeFormat(isDatetime ? 'dt' : 'd');

					var now, fromM, toM;
					switch(action) {
						case 'today':
							now = moment();
							fromM = now.clone().startOf('day');
							toM = now.clone();
							break;
						case 'yesterday':
							now = moment().subtract(1, 'day');
							fromM = now.clone().startOf('day');
							toM = now.clone().endOf('day');
							break;
						case 'mtd':
							fromM = moment().startOf('month');
							toM = moment();
							break;
						case 'last-month':
							fromM = moment().subtract(1, 'month').startOf('month');
							toM   = moment().subtract(1, 'month').endOf('month');
							break;
						case 'ytd':
							fromM = moment().startOf('year');
							toM = moment();
							break;
						case 'last-year':
							fromM = moment().subtract(1, 'year').startOf('year');
							toM   = moment().subtract(1, 'year').endOf('year');
							break;
						case 'clear':
							fromM = toM = null;
							break;
						default:
							return;
					}

					// For datetime-range, set time portions appropriately
					if(isDatetime && fromM) {
						switch(action) {
							case 'mtd':
							case 'ytd':
								fromM.startOf('day');
								toM = moment();
								break;
							case 'last-month':
							case 'last-year':
								fromM.startOf('day');
								toM.endOf('day');
								break;
						}
					}

				fromInput.val(fromM ? fromM.format(fmt) : '');
					toInput.val(toM ? toM.format(fmt) : '');

					// Update datetimepicker internal state
					if(fromM) fromInput.data('DateTimePicker').date(fromM);
					else      fromInput.data('DateTimePicker').date(null);
					if(toM)   toInput.data('DateTimePicker').date(toM);
					else      toInput.data('DateTimePicker').date(null);
					checkFiltersChanged();
				});

				// ── Shared: build panel filter hidden inputs ──────
				function buildPanelFilterInputs(form) {
					var FILTERS_PER_GROUP = 4;
					var PANEL_INDEX_START = 121;
					var PANEL_INDEX_END   = 160;

					function appendHidden(name, value) {
						var input = document.createElement('input');
						input.type = 'hidden';
						input.name = name;
						input.value = value;
						form.appendChild(input);
					}

					// 1. Remove existing panel filter hidden inputs (indices 121-160)
					$j('input[name^="FilterAnd["], input[name^="FilterField["], input[name^="FilterOperator["], input[name^="FilterValue["]').each(function() {
						var match = this.name.match(/\[(\d+)\]/);
						if(match) {
							var idx = parseInt(match[1], 10);
							if(idx >= PANEL_INDEX_START && idx <= PANEL_INDEX_END)
								$j(this).remove();
						}
					});

					// 2. Build new filter entries from panel controls
					var groupIndex = <?php echo self::PANEL_FILTER_GROUP_START; ?>;
				$j('.filters-panel .filter-group').each(function() {
						var $group    = $j(this);
						var fieldIdx  = parseInt($group.data('filter-field-index'), 10) || 0;
						var type      = $group.data('filter-type');
						var condition = 1;

						if(!fieldIdx) { groupIndex++; return; }

						var baseIdx = (groupIndex - 1) * FILTERS_PER_GROUP;
						appendHidden('FilterAnd[' + (baseIdx + 1) + ']', 'and');

						switch(type) {
							case 'date-range':
								var from = $group.find('.filter-date-from').val();
								var to   = $group.find('.filter-date-to').val();
								if(from) {
									appendHidden('FilterField['    + (baseIdx + condition) + ']', fieldIdx);
									appendHidden('FilterOperator[' + (baseIdx + condition) + ']', 'greater-than-or-equal-to');
									appendHidden('FilterValue['    + (baseIdx + condition) + ']', from);
									if(condition > 1) appendHidden('FilterAnd[' + (baseIdx + condition) + ']', 'and');
									condition++;
								}
								if(to) {
									appendHidden('FilterField['    + (baseIdx + condition) + ']', fieldIdx);
									appendHidden('FilterOperator[' + (baseIdx + condition) + ']', 'less-than-or-equal-to');
									appendHidden('FilterValue['    + (baseIdx + condition) + ']', to);
									appendHidden('FilterAnd['      + (baseIdx + condition) + ']', 'and');
									condition++;
								}
								break;

							case 'datetime-range':
								var from = $group.find('.filter-datetime-from').val();
								var to   = $group.find('.filter-datetime-to').val();
								if(from) {
									appendHidden('FilterField['    + (baseIdx + condition) + ']', fieldIdx);
									appendHidden('FilterOperator[' + (baseIdx + condition) + ']', 'greater-than-or-equal-to');
									appendHidden('FilterValue['    + (baseIdx + condition) + ']', from);
									if(condition > 1) appendHidden('FilterAnd[' + (baseIdx + condition) + ']', 'and');
									condition++;
								}
								if(to) {
									appendHidden('FilterField['    + (baseIdx + condition) + ']', fieldIdx);
									appendHidden('FilterOperator[' + (baseIdx + condition) + ']', 'less-than-or-equal-to');
									appendHidden('FilterValue['    + (baseIdx + condition) + ']', to);
									appendHidden('FilterAnd['      + (baseIdx + condition) + ']', 'and');
									condition++;
								}
								break;

							case 'numeric-range':
								var min = $group.find('.filter-num-min').val();
								var max = $group.find('.filter-num-max').val();
								if(min !== '') {
									appendHidden('FilterField['    + (baseIdx + condition) + ']', fieldIdx);
									appendHidden('FilterOperator[' + (baseIdx + condition) + ']', 'greater-than-or-equal-to');
									appendHidden('FilterValue['    + (baseIdx + condition) + ']', min);
									if(condition > 1) appendHidden('FilterAnd[' + (baseIdx + condition) + ']', 'and');
									condition++;
								}
								if(max !== '') {
									appendHidden('FilterField['    + (baseIdx + condition) + ']', fieldIdx);
									appendHidden('FilterOperator[' + (baseIdx + condition) + ']', 'less-than-or-equal-to');
									appendHidden('FilterValue['    + (baseIdx + condition) + ']', max);
									appendHidden('FilterAnd['      + (baseIdx + condition) + ']', 'and');
									condition++;
								}
								break;

							case 'select2':
								var vals = JSON.parse($group.find('.filter-select2-value').val() || '[]');
								for(var i = 0; i < vals.length && i < FILTERS_PER_GROUP; i++) {
									appendHidden('FilterField['    + (baseIdx + condition) + ']', fieldIdx);
									appendHidden('FilterOperator[' + (baseIdx + condition) + ']', 'equal-to');
									appendHidden('FilterValue['    + (baseIdx + condition) + ']', vals[i]);
									if(i > 0) appendHidden('FilterAnd[' + (baseIdx + condition) + ']', 'or');
									condition++;
								}
								break;

							case 'checkbox':
								var ckState = $group.find('.filter-checkbox').data('checkbox-state') || 'indeterminate';
								if(ckState === 'checked') {
									appendHidden('FilterField['    + (baseIdx + condition) + ']', fieldIdx);
									appendHidden('FilterOperator[' + (baseIdx + condition) + ']', 'is-not-empty');
									appendHidden('FilterValue['    + (baseIdx + condition) + ']', '');
									appendHidden('FilterAnd['      + (baseIdx + condition) + ']', 'and');
									condition++;
								} else if(ckState === 'unchecked') {
									appendHidden('FilterField['    + (baseIdx + condition) + ']', fieldIdx);
									appendHidden('FilterOperator[' + (baseIdx + condition) + ']', 'is-empty');
									appendHidden('FilterValue['    + (baseIdx + condition) + ']', '');
									appendHidden('FilterAnd['      + (baseIdx + condition) + ']', 'and');
									condition++;
								}
								break;
						}
						groupIndex++;
					});
				}

				// ── Apply button: build inputs and submit ─────────
				$j('.btn-apply-filters-panel').on('click', function() {
					var form = document.forms['myform'];
					if(!form) return;
					buildPanelFilterInputs(form);
					form.submit();
				});

				// ── Save button: build inputs, add SaveFilter_x, submit ──
				// Replicates the filters page behavior: submitting the form
				// with SaveFilter_x=1 triggers datalist.php to render the
				// save panel, then AppGini.handleSaveFilters() handles the rest.
				$j('.btn-save-filters-panel').on('click', function() {
					if($j(this).prop('disabled')) return;

					var form = document.forms['myform'];
					if(!form) return;

					buildPanelFilterInputs(form);

					var h = document.createElement('input');
					h.type = 'hidden';
					h.name = 'SaveFilter_x';
					h.value = '1';
					form.appendChild(h);

					form.submit();
				});

				// ── Panel chrome (toggle, wrapper, mobile overlay) ─
				var $mainContent = $j('.main-content');
				if(!$mainContent.length) return;

				var $panel = $j('.filters-panel');
				var $toggleBtn = $j('.btn-toggle-filters-panel');
				var $backdrop = $j('.filters-panel-backdrop');
			var isRTL = $j('body > .theme-rtl').length > 0;
			var isOpen = false;

				// Wrap existing main-content children into a content area (leave panel/toggle/backdrop out)
				var $contentArea = $j('<div class="filters-panel-content"></div>');
				$mainContent.children().not('.filters-panel, .btn-toggle-filters-panel, .filters-panel-backdrop')
					.appendTo($contentArea);
				$mainContent.append($contentArea);

				// Make main-content a flex wrapper
				$mainContent.addClass('filters-panel-wrapper');

				// Prepend panel to wrapper in correct order (LTR: panel first, RTL: panel last)
				if(isRTL) {
					$mainContent.append($panel);
				} else {
					$mainContent.prepend($panel);
				}

			var isMobile = function() {
				return $j(window).width() < 768;
			};

			var wasMobile = isMobile();

			// ── Filter-active dot on toggle button ──────────
			// Shows a red dot when panel-applied filters (groups 31-40,
			// indices 121-160) are active and the panel is closed.
			function panelFiltersActive() {
				var found = false;
				$j('input[name^="FilterField["]').each(function() {
					var match = this.name.match(/\[(\d+)\]/);
					if(!match) return;
					var idx = parseInt(match[1], 10);
					if(idx >= 121 && idx <= 160) { found = true; return false; }
				});
				return found;
			}

			function updateFilterDot() {
				if(!isOpen && panelFiltersActive())
					$toggleBtn.addClass('has-filters');
				else
					$toggleBtn.removeClass('has-filters');
			}

				var positionToggleBtn = function() {
					var top = Math.max(($j('.navbar-fixed-top').outerHeight(true) || 0) + 10, 60);
					var $vertToggle = $j('.btn-toggle-vertical-nav');
					if($vertToggle.length && $vertToggle.is(':visible')) {
						var vertToggleBottom = $vertToggle.offset().top + $vertToggle.outerHeight(true);
						top = Math.max(top, vertToggleBottom + 10);
					}
					$toggleBtn.css({
						position: 'fixed',
						top: top + 'px',
						zIndex: 1040,
					});
					var $vertNav = $j('.vertical-nav');
					var navOpen = $vertNav.length && $vertNav.is(':visible');
					var horizPos = navOpen ? ($vertNav.outerWidth(true) || 0) : 0;
					if(isRTL) {
						$toggleBtn.css({ right: horizPos, left: 'auto' });
					} else {
						$toggleBtn.css({ left: horizPos, right: 'auto' });
					}
				};

				var openPanel = function() {
					$panel.removeClass('hidden');
					if(isMobile()) {
						var bodyBg = $j('body').css('background-color');
						$panel.css({
							position: 'fixed',
							top: 0,
							left: isRTL ? 'auto' : 0,
							right: isRTL ? 0 : 'auto',
							height: '100vh',
							zIndex: 1050,
							width: '280px',
							transform: 'translateX(0)',
							background: bodyBg
						});
						$backdrop.removeClass('hidden');
						$j('body').css('overflow', 'hidden');
					} else {
						$panel.css({
							position: '', top: '', left: '', right: '',
							height: '', zIndex: '', width: '', transform: '',
							background: ''
						});
					}
				isOpen = true;
				$toggleBtn.addClass('hidden');
				AppGini.localStorage.setItem('filtersPanelHidden', false);
				updateFilterDot();
			};

			var closePanel = function() {
					if(isMobile()) {
						$panel.css('transform', 'translateX(' + (isRTL ? '' : '-') + '100%)');
						$backdrop.addClass('hidden');
						$j('body').css('overflow', '');
						setTimeout(function() {
							if(!isMobile()) {
								$panel.css({
									position: '', top: '', left: '', right: '',
									height: '', zIndex: '', width: '', transform: '',
									background: ''
								});
							}
						}, 350);
					} else {
						$panel.addClass('hidden');
					}
				isOpen = false;
				$toggleBtn.removeClass('hidden');
				AppGini.localStorage.setItem('filtersPanelHidden', true);
				updateFilterDot();
			};

				$toggleBtn.on('click', function() {
					if(isOpen) closePanel(); else openPanel();
				});

				$panel.find('.filters-panel-close').on('click', function() {
					closePanel();
				});

				$backdrop.on('click', function() {
					closePanel();
				});

			positionToggleBtn();
			$j(window).on('resize', function() {
				positionToggleBtn();
				var nowMobile = isMobile();
				if(!nowMobile && $panel.css('position') === 'fixed') {
					$backdrop.addClass('hidden');
					$j('body').css('overflow', '');
					$panel.css({
						position: '', top: '', left: '', right: '',
						height: '', zIndex: '', width: '', transform: '',
						background: ''
					});
					$panel.removeClass('hidden');
					isOpen = true;
					$toggleBtn.addClass('hidden');
					updateFilterDot();
				}
				if(nowMobile && !wasMobile && isOpen) {
					closePanel();
				}
				wasMobile = nowMobile;
			});

				var storedHidden = AppGini.localStorage.getItem('filtersPanelHidden');
				if(isMobile()) {
					closePanel();
				} else {
					if(storedHidden === true) {
						closePanel();
					} else {
						openPanel();
					}
				}
				updateFilterDot();
			});
		</script>
		<?php
		return ob_get_clean();
	}
}
