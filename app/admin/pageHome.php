<?php
	$currDir = dirname(__FILE__);
	require("{$currDir}/incCommon.php");
	$GLOBALS['page_title'] = $Translation['membership management homepage'];
	include("{$currDir}/incHeader.php");
?>

<?php
	if(!sqlValue("select count(1) from membership_groups where allowSignup=1")){
		$noSignup=TRUE;
		?>
		<div class="alert alert-info">
			<i><?php echo $Translation["attention"]; ?></i>
			<br><?php echo $Translation["visitor sign up"]; ?>
			</div>
		<?php
	}
?>

<?php
	// get the count of records having no owners in each table
	$arrTables=getTableList();

	foreach($arrTables as $tn=>$tc){
		$countOwned=sqlValue("select count(1) from membership_userrecords where tableName='$tn' and not isnull(groupID)");
		$countAll=sqlValue("select count(1) from `$tn`");

		if($countAll>$countOwned){
			?>
			<div class="alert alert-info">
				<?php echo $Translation["table data without owner"]; ?>
				</div>
			<?php
			break;
		}
	}
?>

<div class="page-header"><h1><?php echo $Translation['membership management homepage']; ?></h1></div>

<?php if(!$adminConfig['hide_twitter_feed']){ ?>
	<div class="row" id="outer-row"><div class="col-md-8">
<?php } ?>

<div class="row" id="inner-row">

<!-- ################# Maintenance mode ###################### -->
<?php
	if(maintenance_mode()){
		$off_classes = 'btn-default locked_inactive';
		$on_classes = 'btn-danger unlocked_active';
	}else{
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
	$j('#toggle_maintenance_mode button').click(function(){
		if($j(this).hasClass('locked_active') || $j(this).hasClass('unlocked_inactive')){
			if(confirm('<?php echo html_attr($Translation['enable maintenance mode?']); ?>')){
				$j.ajax({
					url: 'ajax-maintenance-mode.php?status=on', 
					complete: function(){
						location.reload();
					}
				});
			}
		}else{
			if(confirm('<?php echo html_attr($Translation['disable maintenance mode?']); ?>')){
				$j.ajax({
					url: 'ajax-maintenance-mode.php?status=off', 
					complete: function(){
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
		while($row=db_fetch_row($res)){
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
		while($row=db_fetch_row($res)){
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
		$res=sql("select lcase(memberID), count(1) from membership_userrecords group by memberID order by 2 desc limit 5", $eo);
		while($row=db_fetch_row($res)){
			?>
			<tr>
				<th class="" style="max-width: 10em;"><a href="pageEditMember.php?memberID=<?php echo urlencode($row[0]); ?>" title="<?php echo $Translation["edit member details"]; ?>"><i class="glyphicon glyphicon-pencil"></i> <?php echo $row[0]; ?></a></th>
				<td class="remaining-width"><a href="pageViewRecords.php?memberID=<?php echo urlencode($row[0]); ?>"><img src="images/data_icon.gif" border="0" alt="<?php echo $Translation["view member records"]; ?>" title="<?php echo $Translation["view member records"]; ?>"></a> <?php echo $row[1]; ?> <?php echo $Translation["records"]; ?></td>
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

<?php if(!$adminConfig['hide_twitter_feed']){ ?>
		</div> <!-- /div.col-md-8 -->

		<div class="col-md-4" id="twitter-feed">
			<h3>
				<?php echo $Translation["BigProf tweets"]; ?>
				<span class="pull-right">
					<a class="twitter-follow-button" href="https://twitter.com/bigprof" data-show-count="false" data-lang="en"><?php echo $Translation["follow BigProf"]; ?></a>
					<script type="text/javascript">
						window.twttr = (function (d, s, id) {
							var t, js, fjs = d.getElementsByTagName(s)[0];
							if (d.getElementById(id)) return;
							js = d.createElement(s); js.id = id;
							js.src= "https://platform.twitter.com/widgets.js";
							fjs.parentNode.insertBefore(js, fjs);
							return window.twttr || (t = { _e: [], ready: function (f) { t._e.push(f) } });
						}(document, "script", "twitter-wjs"));
					</script>
				</span>
			</h3><hr>
			<div class="text-center">
				<a class="twitter-timeline" height="400" href="https://twitter.com/bigprof" data-widget-id="552758720300843008" data-chrome="nofooter noheader"><?php echo $Translation["loading bigprof feed"]; ?></a>
				<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
			</div>
			<div class="text-right hidden" id="remove-feed-link"><a href="pageSettings.php#hide_twitter_feed"><i class="glyphicon glyphicon-remove"></i> <?php echo $Translation["remove feed"]; ?></a></div>
			<script>
				$j(function(){
					show_remove_feed_link = function(){
						if(!$j('.twitter-timeline-rendered').length){
							setTimeout(function(){ show_remove_feed_link(); }, 1000);
						}else{
							$j('#remove-feed-link').removeClass('hidden');
						}
					};
					show_remove_feed_link();
				});
			</script>
		</div>
	</div> <!-- /div.row#outer-row -->
<?php } ?>

<script>
	$j(function(){
		$j(window).resize(function(){
			$j('.remaining-width').each(function(){
				var panel_width = $j(this).parents('.panel-body').width();
				var other_cell_width = $j(this).prev().width();

				$j(this).attr('style', 'max-width: ' + (panel_width * .9 - other_cell_width) + 'px !important;');
			});
		}).resize();
	})
</script>


<?php
	include("{$currDir}/incFooter.php");
?>

