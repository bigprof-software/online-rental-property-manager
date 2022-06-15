<?php
	include_once(__DIR__ . '/lib.php');
	include_once(__DIR__ . '/header.php');

	if(Request::val('redir') == 1) {
		echo '<META HTTP-EQUIV="Refresh" CONTENT="5;url=index.php?signIn=1">';
	}
?>

<div style="width: 100%; max-width: 500px; margin: 0 auto; text-align: left;">
	<h1 class="text-center"><?php echo $Translation['thanks']; ?></h1>

	<img src="handshake.svg">

	<div><?php echo $Translation['sign in no approval']; ?></div>
	<br>

	<div><?php echo $Translation['sign in wait approval']; ?></div>
</div>

<?php include_once(__DIR__ . '/footer.php');
