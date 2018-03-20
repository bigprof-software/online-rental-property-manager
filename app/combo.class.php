<?php if(!defined('datalist_date_separator')) die('datalist.php not included!');

class Combo{
	// The Combo class renders a drop down combo
	// filled with elements in an array ListItem[]
	// and associates each element with data from
	// an array ListData[], and optionally selects 
	// one of the items.

	var $ListItem, // array of items in the combo
		$ListData, // array of items data values
		$Class,
		$SelectedClass,
		$Style,
		$SelectName,
		$SelectID,
		$SelectedData,
		$SelectedText,
		$MatchText, // will store the text value of the matching item.

		$ListType, // 0: drop down combo, 1: list box, 2: radio buttons, 3: multi-selection list box
		$ListBoxHeight, // if ListType=1, this is the height of the list box
		$MultipleSeparator, // if ListType=3, specify the list separator here (default ,)
		$RadiosPerLine, // if ListType=2, this is the number of options per line

		$AllowNull,
		$ApplySelect2, // boolean, default is true

		$HTML; // the resulting output HTML code to use

	function __construct(){  // PHP 7 compatibility
		$this->Combo();
	}

	function Combo(){ // Constructor function
		$this->Class = 'form-control';
		$this->SelectedClass = 'active';
		$this->HTML = '';
		$this->ListType = 0;
		$this->ListBoxHeight = 10;
		$this->MultipleSeparator = ', ';
		$this->RadiosPerLine = 1;
		$this->AllowNull = true;
		$this->ApplySelect2 = true;
	}

	function Render(){
		global $Translation;
		$this->HTML = '';
		$ArrayCount = count($this->ListItem);

		if($ArrayCount > count($this->ListData)){
			$this->HTML .= 'Invalid Class Definition';
			return 0;
		}

		if(!$this->SelectID)    $this->SelectID=str_replace(array('[', ']'), '_', $this->SelectName);

		if($this->ListType != 2){
			if($this->ApplySelect2){
				$this->HTML .= "<select style=\"width: 100%;\" name=\"$this->SelectName".($this->ListType==3 ? '[]' : '')."\" id=\"$this->SelectID\"".($this->ListType==1 ? ' size="'.($this->ListBoxHeight < $ArrayCount ? $this->ListBoxHeight : ($ArrayCount + ($this->AllowNull ? 1 : 0))).'"' : '').($this->ListType==3 ? ' multiple' : '').'>';
			}else{
				$this->HTML .= "<select name=\"$this->SelectName".($this->ListType==3 ? '[]' : '')."\" id=\"$this->SelectID\" class=\"$this->Class\" style=\"$this->Style\"".($this->ListType==1 ? ' size="'.($this->ListBoxHeight < $ArrayCount ? $this->ListBoxHeight : ($ArrayCount + ($this->AllowNull ? 1 : 0))).'"' : '').($this->ListType==3 ? ' multiple' : '').'>';
			}

			$this->HTML .= ($this->AllowNull ? "\n\t<option value=\"\">&nbsp;</option>" : '');

			if($this->ListType==3) $arrSelectedData=explode($this->MultipleSeparator, $this->SelectedData);
			if($this->ListType==3) $arrSelectedText=explode($this->MultipleSeparator, $this->SelectedText);
			for($i = 0; $i < $ArrayCount; $i++){
				if($this->ListType==3){
					if(in_array($this->ListData[$i], $arrSelectedData)){
						$sel = "selected class=\"$this->SelectedClass\"";
						$this->MatchText.=$this->ListItem[$i].$this->MultipleSeparator;
					}else{
						$sel = '';
					}
				}else{
					if($this->SelectedData == $this->ListData[$i] || ($this->SelectedText == $this->ListItem[$i] && $this->SelectedText)){
						$sel = "selected class=\"$this->SelectedClass\"";
						$this->MatchText=$this->ListItem[$i];
						$this->SelectedData=$this->ListData[$i];
						$this->SelectedText=$this->ListItem[$i];
					}else{
						$sel = '';
					}
				}

				$this->HTML .= "\n\t<option value=\"" . $this->ListData[$i] . "\" $sel>" . stripslashes(strip_tags($this->ListItem[$i])) . "</option>";
			}
			$this->HTML .= '</select>';
			if($this->ApplySelect2){
				$this->HTML .= '<script>jQuery(function(){ jQuery("#' . $this->SelectID . '").addClass(\'option_list\').select2({ minimumResultsForSearch: 15 }); })</script>';
			}
			if($this->ListType == 3 && strlen($this->MatchText)>0)   $this->MatchText=substr($this->MatchText, 0, -1 * strlen($this->MultipleSeparator));
		}else{
			global $Translation;
			$separator = '&nbsp; &nbsp; &nbsp; &nbsp;';

			$j=0;
			$this->HTML .= '<div>';
			if($this->AllowNull){
				$this->HTML .= "<input id=\"$this->SelectName$j\" type=\"radio\" name=\"$this->SelectName\" value=\"\" ".($this->SelectedData==''?'checked':'')."> <label for=\"$this->SelectName$j\">{$Translation['none']}</label>";
				$this->HTML .= ($this->RadiosPerLine==1 ? '<br>' : $separator);
				$shift=2;
			}else{
				$shift=1;
			}
			for($i = 0; $i < $ArrayCount; $i++){
				$j++;
				if($this->SelectedData == $this->ListData[$i] || ($this->SelectedText == $this->ListItem[$i] && $this->SelectedText)){
					$sel = "checked class=\"$this->SelectedClass\"";
					$this->MatchText=$this->ListItem[$i];
					$this->SelectedData=$this->ListData[$i];
					$this->SelectedText=$this->ListItem[$i];
				}else{
					$sel = '';
				}

				$this->HTML .= "<input id=\"$this->SelectName$j\" type=\"radio\" name=\"$this->SelectName\" value=\"{$this->ListData[$i]}\" $sel> <label for=\"$this->SelectName$j\">".str_replace('&amp;', '&', html_attr(stripslashes($this->ListItem[$i])))."</label>";
				if(($i+$shift)%$this->RadiosPerLine){
					$this->HTML .= $separator;
				}else{
					$this->HTML .= '<br>';
				}
			}
			$this->HTML .= '</div>';
		}

		return 1;
	}
}
