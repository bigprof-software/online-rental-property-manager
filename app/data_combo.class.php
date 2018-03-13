<?php if(!defined('datalist_date_separator')) die('datalist.php not included!');

class DataCombo{
	var $Query, // Only the first two fields of the query are used.
				// The first field is treated as the primary key (data values),
				// and the second field is the displayed data items.
		$Class,
		$Style,
		$SelectName,
		$FirstItem,     // if not empty, the first item in the combo with value of ''
		$SelectedData,  // a value compared to first field value of the query to select
						// an item from the combo.
		$SelectedText,

		$ListType, // 0: drop down combo, 1: list box, 2: radio buttons
		$ListBoxHeight, // if ListType=1, this is the height of the list box
		$RadiosPerLine, // if ListType=2, this is the number of options per line
		$AllowNull,

		$ItemCount, // this is returned. It indicates the number of items in the combo.
		$HTML,      // this is returned. The combo html source after calling Render().
		$MatchText; // will store the parent caption value of the matching item.

	function __construct(){  // PHP 7 compatibility
		$this->DataCombo();
	}

	function DataCombo(){ // Constructor function
		$this->FirstItem = '';
		$this->HTML = '';
		$this->Class = 'form-control Lookup';
		$this->MatchText = '';
		$this->ListType = 0;
		$this->ListBoxHeight=10;
		$this->RadiosPerLine=1;
		$this->AllowNull=1;
	}

	function Render(){
		global $Translation;

		$eo['silentErrors']=true;
		$result = sql($this->Query . ' limit ' . datalist_auto_complete_size, $eo);
		if($eo['error']!=''){
			$this->HTML = error_message(html_attr($eo['error']) . "\n\n<!--\n{$Translation['query:']}\n {$this->Query}\n-->\n\n");
			return;
		}

		$this->ItemCount = db_num_rows($result);
	
		$combo = new Combo();
		$combo->Class = $this->Class;
		$combo->Style = $this->Style;
		$combo->SelectName = $this->SelectName;
		$combo->SelectedData = $this->SelectedData;
		$combo->SelectedText = $this->SelectedText;
		$combo->SelectedClass = 'SelectedOption';
		$combo->ListType = $this->ListType;
		$combo->ListBoxHeight = $this->ListBoxHeight;
		$combo->RadiosPerLine = $this->RadiosPerLine;
		$combo->AllowNull = ($this->ListType == 2 ? 0 : $this->AllowNull);

		while($row = db_fetch_row($result)){
			$combo->ListData[] = html_attr($row[0]);
			$combo->ListItem[] = $row[1];
		}
		$combo->Render();
		$this->MatchText = $combo->MatchText;
		$this->SelectedText = $combo->SelectedText;
		$this->SelectedData = $combo->SelectedData;
		if($this->ListType == 2){
			$rnd = rand(100, 999);
			$SelectedID = html_attr(urlencode($this->SelectedData));
			$pt_perm = getTablePermissions($this->parent_table);
			if($pt_perm['view'] || $pt_perm['edit']){
				$this->HTML = str_replace(">{$this->MatchText}</label>", ">{$this->MatchText}</label> <button type=\"button\" class=\"btn btn-default view_parent hspacer-lg\" id=\"{$this->parent_table}_view_parent\" title=" . html_attr($Translation['View']) . "><i class=\"glyphicon glyphicon-eye-open\"></i></button>", $combo->HTML);
			}
			$this->HTML = str_replace(' type="radio" ', ' type="radio" onclick="' . $this->SelectName . '_changed();" ', $this->HTML);
		}else{
			$this->HTML = $combo->HTML;
		}
	}
}
