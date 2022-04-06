<?php

class DateCombo {
	// renders a date combo with a pre-selected date

	var $DateFormat,          // any combination of y,m,d
		$DefaultDate,         // format: yyyy-mm-dd
		$MinYear,
		$MaxYear,
		$MonthNames,
		$Comment,
		$NamePrefix,          // will be used in the HTML name prop as a prefix to "Year", "Month", "Day"
		$CSSOptionClass,
		$CSSSelectedClass,
		$CSSCommentClass;

	function __construct() {
		// set default values
		$this->DateFormat = "ymd";
		$this->DefaultDate = '';
		$this->MinYear = 1900;
		$this->MaxYear = 2100;
		$this->MonthNames = "January,February,March,April,May,June,July,August,September,October,November,December";
		$this->Comment = "<empty>";
		$this->NamePrefix = "Date";

		$this->CSSOptionClass = 'form-control';
		$this->CSSSelectedClass = 'active';
		$this->CSSCommentClass = '';
	}

	function GetHTML($readOnly = false) {
		list($xy, $xm, $xd) = explode('-', $this->DefaultDate);

		//$y : render years combo
		$years = new Combo;
		for($i = $this->MinYear; $i <= $this->MaxYear; $i++) {
			$years->ListItem[] = $i;
			$years->ListData[] = $i;
		}
		$years->SelectName = $this->NamePrefix . 'Year';
		$years->SelectID = $this->NamePrefix;
		$years->SelectedData = $xy;
		$years->Class = 'year-select split-date form-control';
		$years->SelectedClass = $this->CSSSelectedClass;
		$years->ApplySelect2 = false;
		$years->Render();
		$y = ($readOnly ? substr($this->DefaultDate, 0, 4) : $years->HTML);

		//$m : render months combo
		$months = new Combo;
		for($i = 1; $i <= 12; $i++) {
			$months->ListData[] = $i;
		}
		$months->ListItem = explode(",", $this->MonthNames);
		$months->SelectName = $this->NamePrefix . 'Month';
		$months->SelectID = $this->NamePrefix . '-mm';
		$months->SelectedData = intval($xm);
		$months->Class = 'month-select form-control';
		$months->SelectedClass = $this->CSSSelectedClass;
		$months->ApplySelect2 = false;
		$months->Render();
		$m = ($readOnly ? $xm : $months->HTML);

		//$d : render days combo
		$days = new Combo;
		for($i = 1; $i <= 31; $i++) {
			$days->ListItem[] = $i;
			$days->ListData[] = $i;
		}
		$days->SelectName = $this->NamePrefix . 'Day';
		$days->SelectID = $this->NamePrefix . '-dd';
		$days->SelectedData = intval($xd);
		$days->Class = 'day-select form-control';
		$days->SelectedClass = $this->CSSSelectedClass;
		$days->ApplySelect2 = false;
		$days->Render();
		$d = ($readOnly ? $xd : $days->HTML);

		$df = $this->DateFormat; // contains date order 'myd', 'dmy' ... etc

		if($readOnly) {
			if(
				!intval(${$df[0]})
				|| !intval(${$df[1]})
				|| !intval(${$df[2]})
			) return '';

			return ${$df[0]} . datalist_date_separator . ${$df[1]} . datalist_date_separator . ${$df[2]};
		}

		$editable_date = '<div class="date-flex">';
		for($i = 0; $i < 3; $i++)
			$editable_date .= ${$df[$i]};

		$editable_date .= '' .
			'<button type="button" class="btn btn-default" id="fd-but-' . $this->NamePrefix . '">' .
				'<i class="glyphicon glyphicon-calendar"></i>' .
			'</button>' .
			'<button type="button" class="btn btn-default fd-date-clearer" data-for="' . $this->NamePrefix . '">' .
				'<i class="glyphicon glyphicon-trash"></i>' .
			'</button>' .
		'</div>';

		return $editable_date;
	}
}
