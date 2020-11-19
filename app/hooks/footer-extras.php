<?php if(!empty($_REQUEST['Filter_x']) && !intval($_REQUEST['advanced_search_mode'])) { ?>

	<script>
		$j(function() {
			$j('.row').last().after('<div class="alert alert-info" style="margin-top: 3em;">' +
				'<i class="glyphicon glyphicon-exclamation-sign"></i> ' +
				'This search page was created by ' +
				'<a href="https://bigprof.com/appgini/applications/search-page-maker-plugin" target="_blank" title="Search Page Maker plugin for AppGini">SPM plugin for AppGini</a>.' +
			'</div>');
		})
	</script>

<?php } ?>

<?php if(strpos($_SERVER['PHP_SELF'], 'summary-reports-')) { ?>

	<script>
		$j(function() {
			var vSpacer = $j('div.hidden-print');
			vSpacer.eq(vSpacer.length - 2).replaceWith('<div class="alert alert-info">' +
				'<i class="glyphicon glyphicon-exclamation-sign"></i> ' +
				'This report was created by ' +
				'<a href="https://bigprof.com/appgini/applications/summary-reports-plugin" target="_blank" title="Summary Reports plugin for AppGini">Summary Reports plugin for AppGini</a>.' +
			'</div>');
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
