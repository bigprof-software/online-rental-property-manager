<?php
	require(__DIR__ . '/incCommon.php');
	require(__DIR__ . '/incHeader.php');

	// insertable items in SQL query
	$items = array_merge(
		array_keys(getTableList()),
		array_map(function($mf) {
			return preg_replace('/^update_/i', '', $mf);
		}, membership_table_functions())
	);

	$insertListItems = implode('', array_map(function($i) {
		return "<li><a href=\"#\" class=\"insertable\">$i</a></li>";
	}, $items));
?>

<div class="page-header"><h1>
	<i class="glyphicon glyphicon-console text-danger"></i> 
	<?php echo $Translation['Interactive SQL queries tool']; ?>
</h1></div>

<?php echo csrf_token(); ?>

<div class="form-group">
	<label for="sql" class="control-label"><?php echo $Translation['Enter SQL query']; ?></label>
	<div class="row">
		<div class="col-xs-7 col-sm-6 col-md-9">
			<textarea class="form-control" id="sql" autofocus></textarea>
			<div class="hidden text-danger tspacer-sm bspacer-lg" id="sql-begins-not-with-select">
				<?php printf($Translation['Query must start with select'], '<code>SELECT/SHOW&nbsp;</code>'); ?>
			</div>
		</div>
		<div class="col-xs-5 col-sm-6 col-md-3">
			<div class="panel panel-default bspacer-sm">
				<div class="panel-heading text-center">
					<div class="btn-group always-shown-inline-block">
						<button type="button" class="btn btn-default" id="execute"><?php echo $Translation['Display results']; ?></button>
						<button type="button" class="btn btn-default" id="auto-execute" title="<?php echo html_attr($Translation['Update results as you type']); ?>"><i class="glyphicon glyphicon-flash"></i></button>
						<button type="button" class="btn btn-default" data-toggle="dropdown" title="<?php echo html_attr($Translation['insert']); ?>"><span class="caret"></span></button>
							<ul class="dropdown-menu"><?php echo $insertListItems; ?></ul>
						<button type="button" class="btn btn-default" id="reset"><i class="glyphicon glyphicon-trash text-danger"></i></button>
						<button type="button" class="btn btn-default collapsed" data-toggle="collapse" data-target="#manage-queries-dialog" title="<?php echo html_attr($Translation['Manage bookmarked queries']); ?>"><i class="text-info glyphicon glyphicon-bookmark"></i></button>
					</div>
				</div>

				<div class="panel-body collapse" id="manage-queries-dialog" data-animation="">
					<div class="row">

						<!-- new bookmark form -->
						<div class="col-xs-10">
							<label for="save-query-as"><?php echo $Translation['Query name']; ?></label>
							<div class="input-group">
								<input type="text" class="form-control" id="save-query-as">
								<span class="input-group-btn">
									<button class="btn btn-default" type="button" id="save-query" title="<?php echo html_attr($Translation['Bookmark this query']); ?>"><i class="text-success glyphicon glyphicon-ok"></i></button>
								</span>
							</div>
						</div>

						<!-- close dialog -->
						<div class="col-xs-2">
							<button class="btn btn-link pull-right" type="button" data-toggle="collapse" data-target="#manage-queries-dialog"><i class="text-danger glyphicon glyphicon-remove"></i></button>
						</div>
					</div>

					<ul class="list-group tspacer-lg"></ul>
				</div>
			</div>

			<div class="checkbox">
				<label>
					<input type="checkbox" id="useCache" value="1" checked>
					<?php echo $Translation['Use cache']; ?>
				</label>
			</div>
		</div>
	</div>
</div>

<div id="sql-results" class="hidden table-responsive bspacer-lg">
	<div class="alert alert-info hidden" id="sql-results-truncated">
		<?php printf($Translation['results truncated'], '1000'); ?>
	</div>
	<table class="table table-striped table-bordered table-hover">
		<thead>
			<tr>
				<th class="row-counter">#</th>
			</tr>
		</thead>
		<tbody></tbody>
	</table>
</div>

<div class="alert alert-info" id="no-sql-results">
	<i class="glyphicon glyphicon-info-sign"></i> 
	<?php echo $Translation['Results will be displayed here']; ?>
</div>

<div class="hidden" id="results-loading">
	<i class="loop-rotate glyphicon glyphicon-refresh"></i>
	<?php echo $Translation['Loading ...']; ?>
</div>

<div class="alert alert-warning hidden" id="sql-error"></div>

<div class="alert alert-warning hidden" id="csrf-expired">
	<i class="glyphicon glyphicon-info-sign"></i>
	<?php 
		echo str_replace(
			'pageSettings.php',
			'pageSQL.php',
			$Translation['invalid security token']
		);
	?>
</div>

<script src="pageSQL.js"></script>
<style>
	#sql { font-family: monospace; font-size: large; }
</style>

<?php include(__DIR__ . '/incFooter.php');
