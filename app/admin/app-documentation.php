<?php
	require(__DIR__ . '/incCommon.php');

	$GLOBALS['page_title'] = $Translation['app documentation'];
	include(__DIR__ . '/incHeader.php');
?>
<div class="page-header"><h1><?php echo APP_TITLE . ' ' . $Translation['app documentation']; ?></h1></div>
<div class="row">
	<div class="col-md-3 col-lg-2" id="toc-section">
		<nav class="hidden-print hidden-xs hidden-sm affix">
			<ul class="nav">
				<li>
					<a href="#content-section"><?php echo APP_TITLE; ?></a>
					<ul class="nav">
					</ul>
				</li>
			</ul>
			<a class="back-to-top" href="#content-section"><?php echo $Translation['back to top']; ?></a>
		</nav>
	</div>

	<div class="col-md-9 col-lg-8" id="content-section">
		<p class="app-documentation" id="app-title">



		</p>

	</div>
</div>

<style>
	body { position: relative; }
	#toc-section ul.nav:nth-child(2) {
		margin-left: 1.5em;
	}
	#content-section { border-left: 1px dotted #ddd; padding-top: 6em; }
	h2.table-documentation, h3.field-documentation { padding-top: 3em; }
	#toc-section li.active { font-weight: bold; }
	#toc-section li:not(.active) { font-weight: normal; }
</style>

<script>
	$j(function() {
		$j('body').scrollspy({ target: '#toc-section', offset: 80 });
	})
</script>

<?php
	include(__DIR__ . '/incFooter.php');
