<?php
	$currDir = dirname(__FILE__);
	include("{$currDir}/defaultLang.php");
	include("{$currDir}/language.php");
	include("{$currDir}/lib.php");
	include_once("{$currDir}/header.php");

	$current_user = isset($_REQUEST['currentUser']) ? $_REQUEST['currentUser'] : false;
	$username = is_allowed_username($_REQUEST['memberID'], $current_user);
?>

<style>
	nav, .hidden-print{ display: none; }
</style>

<div style="height: 1em;"></div>
<?php if($username){ ?>
	<div class="alert alert-success">
		<i class="glyphicon glyphicon-ok"></i>
		<?php echo str_replace('<MemberID>', "<b>{$username}</b>", $Translation['user available']); ?>
		<!-- AVAILABLE -->
	</div>
<?php }else{ ?>
	<div class="alert alert-danger">
		<i class="glyphicon glyphicon-warning-sign"></i>
		<?php echo str_replace('<MemberID>', '<b>' . html_attr($_REQUEST['memberID']) . '</b>', $Translation['username invalid']); ?>
		<!-- NOT AVAILABLE -->
	</div>
<?php } ?>

<div class="text-center">
	<input type="button" value="Close" onClick="window.close();" autofocus class="btn btn-default btn-lg">
</div>

<?php include_once("{$currDir}/footer.php"); ?>
