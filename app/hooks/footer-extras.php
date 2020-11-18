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
