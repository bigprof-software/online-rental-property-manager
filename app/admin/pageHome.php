<?php
	require(__DIR__ . '/incCommon.php');
	$GLOBALS['page_title'] = $Translation['membership management homepage'];
	include(__DIR__ . '/incHeader.php');

	$eo = ['silentErrors' => true];

	if(!sqlValue("SELECT COUNT(1) FROM `membership_groups` WHERE `allowSignup`=1")) {
		$noSignup = true;
		?>
		<div class="alert alert-info">
			<i><?php echo $Translation['attention']; ?></i>
			<br><?php echo $Translation['visitor sign up']; ?>
		</div>
		<?php
	}
?>

<?php
	// get the count of records having no owners in each table
	$arrTables = getTableList();

	foreach($arrTables as $tn => $tc) {
		$countOwned = sqlValue("SELECT COUNT(1) FROM membership_userrecords WHERE tableName='$tn' AND NOT ISNULL(groupID)");
		$countAll = sqlValue("SELECT COUNT(1) FROM `$tn`");

		if($countAll > $countOwned) {
			?>
			<div class="alert alert-info">
				<?php echo $Translation['table data without owner']; ?>
			</div>
			<?php
			break;
		}
	}
?>

<div class="page-header"><h1><?php echo $Translation['membership management homepage']; ?></h1></div>

<?php if(!$adminConfig['hide_twitter_feed']) { ?>
	<div class="row" id="outer-row"><div class="col-md-8">
<?php } ?>

<div class="row" id="inner-row">

<!-- ################# Maintenance mode ###################### -->
<?php
	if(maintenance_mode()) {
		$off_classes = 'btn-default locked_inactive';
		$on_classes = 'btn-danger unlocked_active';
	} else {
		$off_classes = 'btn-success locked_active';
		$on_classes = 'btn-default unlocked_inactive';
	}
?>
<div class="col-md-12 text-right vspacer-lg">
	<label><?php echo $Translation['maintenance mode']; ?></label>
	<div class="btn-group" id="toggle_maintenance_mode">
		<button type="button" class="btn <?php echo $off_classes; ?>"><?php echo $Translation['OFF']; ?></button>
		<button type="button" class="btn <?php echo $on_classes; ?>"><?php echo $Translation['ON']; ?></button>
	</div>
</div>
<script>
	$j('#toggle_maintenance_mode button').click(function() {
		if($j(this).hasClass('locked_active') || $j(this).hasClass('unlocked_inactive')) {
			if(confirm(<?php echo json_encode($Translation['enable maintenance mode?']); ?>)) {
				$j.ajax({
					url: 'ajax-maintenance-mode.php', 
					data: {
						status: 'on',
						csrf_token: '<?php echo csrf_token(false, true); ?>'
					},
					complete: function() {
						location.reload();
					}
				});
			}
		} else {
			if(confirm(<?php echo json_encode($Translation['disable maintenance mode?']); ?>)) {
				$j.ajax({
					url: 'ajax-maintenance-mode.php', 
					data: {
						status: 'off',
						csrf_token: '<?php echo csrf_token(false, true); ?>'
					},
					complete: function() {
						location.reload();
					}
				});
			}
		}
	});
</script>

<!-- ################# Newest Updates ######################## -->
<div class="col-md-6">
<div class="panel panel-info">
	<div class="panel-heading">
		<h3 class="panel-title"><?php echo $Translation["newest updates"]; ?> <a class="btn btn-default btn-sm" href="pageViewRecords.php?sort=dateUpdated&sortDir=desc"><i class="glyphicon glyphicon-chevron-right"></i></a></h3>
	</div>
	<div class="panel-body">
	<table class="table table-striped table-hover">
	<?php
		$res=sql("select tableName, pkValue, dateUpdated, recID from membership_userrecords order by dateUpdated desc limit 5", $eo);
		while($row=db_fetch_row($res)) {
			?>
			<tr>
				<th style="min-width: 13em;"><?php echo @date($adminConfig['PHPDateTimeFormat'], $row[2]); ?></th>
				<td class="remaining-width"><div class="clipped"><a href="pageEditOwnership.php?recID=<?php echo $row[3]; ?>"><img src="images/data_icon.gif" border="0" alt="<?php echo $Translation["view record details"]; ?>" title="<?php echo $Translation["view record details"]; ?>"></a> <?php echo getCSVData($row[0], $row[1]); ?></div></td>
			</tr>
			<?php
		}
	?>
	</table>
	</div>
</div>
</div>
<!-- ####################################################### -->


<!-- ################# Newest Entries ######################## -->
<div class="col-md-6">
<div class="panel panel-info">
	<div class="panel-heading">
		<h3 class="panel-title"><?php echo $Translation["newest entries"]; ?> <a class="btn btn-default btn-sm" href="pageViewRecords.php?sort=dateAdded&sortDir=desc"><i class="glyphicon glyphicon-chevron-right"></i></a></h3>
	</div>
	<div class="panel-body">
	<table class="table table-striped table-hover">
	<?php
		$res=sql("select tableName, pkValue, dateAdded, recID from membership_userrecords order by dateAdded desc limit 5", $eo);
		while($row=db_fetch_row($res)) {
			?>
			<tr>
				<th style="min-width: 13em;"><?php echo @date($adminConfig['PHPDateTimeFormat'], $row[2]); ?></th>
				<td class="remaining-width"><div class="clipped"><a href="pageEditOwnership.php?recID=<?php echo $row[3]; ?>"><img src="images/data_icon.gif" border="0" alt="<?php echo $Translation["view record details"]; ?>" title="<?php echo $Translation["view record details"]; ?>"></a> <?php echo getCSVData($row[0], $row[1]); ?></div></td>
			</tr>
			<?php
		}
	?>
	</table>
	</div>
</div>
</div>
<!-- ####################################################### -->




<!-- ################# Top Members ######################## -->
<div class="col-md-6">
<div class="panel panel-info">
	<div class="panel-heading">
		<h3 class="panel-title"><?php echo $Translation["top members"]; ?></h3>
	</div>
	<div class="panel-body">
	<table class="table table-striped table-hover">
	<?php
		$res = sql("SELECT LCASE(`memberID`), COUNT(1) FROM `membership_userrecords` GROUP BY `memberID` ORDER BY 2 DESC LIMIT 5", $eo);
		while($row = db_fetch_row($res)) {
			?>
			<tr>
				<th class="" style="max-width: 10em;">
					<?php if($row[0]) { ?>
						<a href="pageEditMember.php?memberID=<?php echo urlencode($row[0]); ?>" title="<?php echo $Translation["edit member details"]; ?>">
							<i class="glyphicon glyphicon-pencil"></i> <?php echo $row[0]; ?>
						</a>
					<?php } else { ?>
						<i class="glyphicon glyphicon-pencil text-muted"></i> <i><?php echo $Translation['none']; ?></i>
					<?php } ?>
				</th>
				<td class="remaining-width">
					<a href="pageViewRecords.php?memberID=<?php echo urlencode($row[0] ? $row[0] : '{none}'); ?>">
						<img src="images/data_icon.gif" border="0" alt="<?php echo $Translation["view member records"]; ?>" title="<?php echo $Translation["view member records"]; ?>">
					</a>
					<?php echo $row[1]; ?> <?php echo $Translation["records"]; ?>
				</td>
			</tr>
			<?php
		}
	?>
	</table>
	</div>
</div>
</div>
<!-- ####################################################### -->


<!-- ################# Members Stats ######################## -->
<div class="col-md-6">
<div class="panel panel-info">
	<div class="panel-heading">
		<h3 class="panel-title"><?php echo $Translation["members stats"]; ?></h3>
	</div>
	<div class="panel-body">
	<table class="table table-striped table-hover">
		<tr>
			<th class=""><?php echo $Translation["total groups"]; ?></th>
			<td class="remaining-width"><a href="pageViewGroups.php"title="<?php echo $Translation['view groups']; ?>"><i class="glyphicon glyphicon-search"></i> <?php echo sqlValue("select count(1) from membership_groups"); ?></a></td>
			</tr>
		<tr>
			<th class=""><?php echo $Translation["active members"]; ?></th>
			<td class="remaining-width"><a href="pageViewMembers.php?status=2" title="<?php echo $Translation["view active members"]; ?>"><i class="glyphicon glyphicon-search"></i> <?php echo sqlValue("select count(1) from membership_users where isApproved=1 and isBanned=0"); ?></a></td>
			</tr>
		<tr>
			<?php
				$awaiting = intval(sqlValue("select count(1) from membership_users where isApproved=0"));
			?>
			<th class="" <?php echo ($awaiting ? "style=\"color: red;\"" : ""); ?>><?php echo $Translation["members awaiting approval"]; ?></th>
			<td class="remaining-width"><a href="pageViewMembers.php?status=1" title="<?php echo $Translation["view members awaiting approval"]; ?>"><i class="glyphicon glyphicon-search"></i> <?php echo $awaiting; ?></a></td>
			</tr>
		<tr>
			<th class=""><?php echo $Translation["banned members"]; ?></th>
			<td class="remaining-width"><a href="pageViewMembers.php?status=3" title="<?php echo $Translation["view banned members"]; ?>"><i class="glyphicon glyphicon-search"></i> <?php echo sqlValue("select count(1) from membership_users where isApproved=1 and isBanned=1"); ?></a></td>
			</tr>
		<tr>
			<th class=""><?php echo $Translation["total members"]; ?></th>
			<td class="remaining-width"><a href="pageViewMembers.php" title="<?php echo $Translation["view all members"]; ?>"><i class="glyphicon glyphicon-search"></i> <?php echo sqlValue("select count(1) from membership_users"); ?></a></td>
			</tr>
		</table>
	</div>
</div>
</div>
<!-- ####################################################### -->

</div> <!-- /div.row#inner-row -->

<?php if(!$adminConfig['hide_twitter_feed']) { ?>
		</div> <!-- /div.col-md-8 -->

		<div class="col-md-4" id="twitter-feed">
			<a class="twitter-timeline" data-dnt="true" data-theme="light" data-height="300" href="https://twitter.com/bigprof?ref_src=twsrc%5Etfw"><?php echo $Translation["BigProf tweets"]; ?></a>
			<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script> 

			<div class="text-right hidden" id="remove-feed-link"><a href="pageSettings.php?search-settings=twitter"><i class="glyphicon glyphicon-remove"></i> <?php echo $Translation["remove feed"]; ?></a></div>

			<script>
				$j(function() {
					show_remove_feed_link = function() {
						if(!$j('.twitter-timeline-rendered').length) {
							setTimeout(function() { show_remove_feed_link(); }, 1000);
						} else {
							$j('#remove-feed-link').removeClass('hidden');
						}
					};
					show_remove_feed_link();
				});
			</script>
			<style> #twitter-feed > iframe { height: 54vh !important; } </style>
		</div>
	</div> <!-- /div.row#outer-row -->
<?php } ?>

<script>
	$j(function() {
		$j(window).resize(function() {
			$j('.remaining-width').each(function() {
				var panel_width = $j(this).parents('.panel-body').width();
				var other_cell_width = $j(this).prev().width();

				$j(this).attr('style', 'max-width: ' + (panel_width * .9 - other_cell_width) + 'px !important;');
			});
		}).resize();
	})
</script>

<?php include(__DIR__ . '/incFooter.php');
