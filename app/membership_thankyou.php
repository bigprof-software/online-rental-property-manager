<?php
	$currDir = dirname(__FILE__);
	include_once("{$currDir}/lib.php");
	include_once("{$currDir}/header.php");

	if($_GET['redir'] == 1) {
		echo '<META HTTP-EQUIV="Refresh" CONTENT="5;url=index.php?signIn=1">';
	}
?>

<div style="width: 100%; max-width: 500px; margin: 0 auto; text-align: left;">
	<h1><?php echo $Translation['thanks']; ?></h1>

	<img src="handshake.svg">

	<div><?php echo $Translation['sign in no approval']; ?></div>
	<br>

	<div><?php echo $Translation['sign in wait approval']; ?></div>
</div>

<?php include_once("$currDir/footer.php");
