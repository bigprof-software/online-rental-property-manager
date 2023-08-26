<?php if(!empty($_REQUEST['Filter_x']) && !intval($_REQUEST['advanced_search_mode'])) { ?>
	<script>
		$j(function() {
			$j('.navbar-fixed-bottom small').after(
				'<small>' +
				'<i class="glyphicon glyphicon-asterisk"></i> ' +
				'This search page was created by ' +
				'<a href="https://bigprof.com/appgini/applications/search-page-maker-plugin" target="_blank" title="Search Page Maker plugin for AppGini">SPM plugin for AppGini</a>.' +
				'</small>'
			);
		})
	</script>
<?php } ?>

<?php if(strpos($_SERVER['PHP_SELF'], 'summary-reports-')) { ?>
	<script>
		$j(function() {
			$j('.navbar-fixed-bottom small').after(
				'<small>' +
				'<i class="glyphicon glyphicon-asterisk"></i> ' +
				'This report was created by ' +
				'<a href="https://bigprof.com/appgini/applications/summary-reports-plugin" target="_blank" title="Summary Reports plugin for AppGini">Summary Reports plugin for AppGini</a>.' +
				'</small>'
			);
		})
	</script>
<?php } ?>

<?php if(strpos($_SERVER['PHP_SELF'], 'hooks/calendar-')) { ?>
	<script>
		$j(function() {
			$j('.navbar-fixed-bottom small').after(
				'<small>' +
				'<i class="glyphicon glyphicon-asterisk"></i> ' +
				'This calendar was created by ' +
				'<a href="https://bigprof.com/appgini/applications/calendar-plugin" target="_blank" title="Calendar plugin for AppGini">Calendar plugin for AppGini</a>.' +
				'</small>'
			);
		})
	</script>
<?php } ?>

<!-- start of mass update plugin code -->
<?php
	if(isset($x) && strpos($x->HTML, 'selected_records_more') !== false) {
		if(strpos($x->HTML, 'nicEdit.js') === false) echo '<script src="nicEdit.js"></script>';
		echo '<script src="hooks/language-mass-update.js"></script>';
	}

?>
<!-- end of mass update plugin code -->
