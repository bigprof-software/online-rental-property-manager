<?php
	require(__DIR__ . '/incCommon.php');
	require(__DIR__ . '/incHeader.php');
?>

	<style>
		nav{ display: none; }
	</style>

<?php

	$recID = intval(Request::val('recID'));
	if(!$recID) {
		// no record provided
		include(__DIR__ . '/incFooter.php');
	}

	// fetch record data to fill in the form below
	$res = sql("select * from membership_userrecords where recID='$recID'", $eo);
	if(!($row = db_fetch_assoc($res))) {
		echo Notification::show([
			'message' => $Translation['record not found error'],
			'class' => 'danger',
			'dismiss_seconds' => 3600
		]);
		include(__DIR__ . '/incFooter.php');
	}

	// get record data
	$tableName = $row['tableName'];
	$pkValue = $row['pkValue'];
	$memberID = strtolower($row['memberID']);
	$dateAdded = @date($adminConfig['PHPDateTimeFormat'], $row['dateAdded']);
	$dateUpdated = @date($adminConfig['PHPDateTimeFormat'], $row['dateUpdated']);
	$groupID = $row['groupID'];

	// get pk field name
	$pkField = getPKFieldName($tableName);

	$res = sql("select * from `{$tableName}` where `{$pkField}`='" . makeSafe($pkValue, false) . "'", $eo);
	if(!($row = db_fetch_assoc($res))) {
		echo Notification::show([
			'message' => $Translation['record not found error'],
			'class' => 'danger',
			'dismiss_seconds' => 3600
		]);
		include(__DIR__ . '/incFooter.php');
	}

	?>
	<div class="page-header"><h1>
		<?php echo str_replace("<TABLENAME>", $tableName, $Translation["table name"]); ?>
	</h1></div>

	<table class="table table-striped table-bordered table-hover">
		<thead>
			<tr>
				<th><?php echo $Translation["field name"]; ?></th>
				<th><?php echo $Translation["value"]; ?></th>
			</tr>
		</thead>

		<tbody>
		<?php
			foreach($row as $fn => $fv) {
				$op = html_attr($fv);
				if(@is_file(__DIR__ . '/../' . getUploadDir('') . $fv)) {
					$op = "<a href=\"../" . getUploadDir('') . "{$fv}\" target=\"_blank\">" . html_attr($fv) . "</a>";
				}

				$tr_class = $pk_icon = '';
				if($fn == $pkField) {
					$tr_class = 'class="text-primary"';
					$pk_icon = '<i class="glyphicon glyphicon-star"></i> ';
				}
				?>
				<tr <?php echo $tr_class; ?>>
					<th valign="top"><?php echo $pk_icon . $fn; ?></th>
					<td valign="top"><?php echo $op; ?></td>
				</tr>
				<?php
			}
		?>
		</tbody>
	</table>

	<script>
		$j(function() {
			$j('nav').next('div').remove();
		})
	</script>

<?php include(__DIR__ . '/incFooter.php');
