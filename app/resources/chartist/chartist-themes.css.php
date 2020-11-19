<?php
	/* generates color themes for charts */
	$theme_hues = array(
		'reds' => 2, 
		'oranges' => 30, 
		'yellows' => 60, 
		'greens' => 100, 
		'blues' => 213, 
		'violets' => 290, 
		'grays' => 0
	);

	$series_sats_brights = array(
		'o' => array(60, 48),
		'n' => array(60, 53),
		'm' => array(59, 57),
		'l' => array(59, 61),
		'k' => array(59, 64),
		'j' => array(58, 68),
		'i' => array(58, 71),
		'h' => array(58, 74),
		'g' => array(44, 76),
		'f' => array(34, 79),
		'e' => array(27, 82),
		'd' => array(21, 84),
		'c' => array(17, 87),
		'b' => array(13, 89),
		'a' => array(10, 91)
	);

	$css = '';
	foreach($theme_hues as $theme => $hue) {
	    foreach($series_sats_brights as $series => $sb) {
	    	list($bri, $sat) = $sb;
	    	if($theme == 'grays') $sat = 0;

	    	/* piechart theme */
			$css .= ".charts-theme-{$theme} .ct-series-{$series} .ct-slice-pie { fill: hsl({$hue},{$sat}%,{$bri}%); }";

			/* for barcharts, variate the color theme to be more distiguishable */
			switch ($series) {
				case 'a':
					list($bsat, $bbri) = $series_sats_brights['o'];
					break;
				case 'b':
					list($bsat, $bbri) = $series_sats_brights['k'];
					break;
				case 'c':
					list($bsat, $bbri) = $series_sats_brights['g'];
					break;
			}
	    	if($theme == 'grays') $bsat = 0;
			if(strpos('abc', $series) !== false) {
				$css .= ".charts-theme-{$theme} .ct-series-{$series} .ct-bar { stroke: hsl({$hue}, {$bsat}%, {$bbri}%); }\n";
				// legend icon
				$css .= ".charts-theme-{$theme} .ct-series-{$series} .glyphicon { color: hsl({$hue}, {$bsat}%, {$bbri}%); }\n";
			}
	    }
	}
	
	// browser should cache this file
	if(function_exists('date_default_timezone_set')) @date_default_timezone_set('America/New_York');
	$last_modified = filemtime(__FILE__);
	$last_modified_gmt = gmdate('D, d M Y H:i:s', $last_modified) . ' GMT';
	$headers = (function_exists('getallheaders') ? getallheaders() : $_SERVER);
	if(isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == $last_modified)){
		@header("Last-Modified: {$last_modified_gmt}", true, 304);
		@header("Cache-Control: public, max-age=240", true);
		exit;
	}

	@header("Last-Modified: {$last_modified_gmt}", true, 200);
	@header("Cache-Control: public, max-age=240", true);
	@header('Content-type: text/css');

	echo $css;