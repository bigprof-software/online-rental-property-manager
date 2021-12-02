<?php
	require(__DIR__ . '/incCommon.php');
	$GLOBALS['page_title'] = $Translation['rebuild thumbnails'];
	include(__DIR__ . '/incHeader.php');

	// image paths
	$p = [
		'properties' => [
			'photo' => '../' . getUploadDir(''),
		],
		'property_photos' => [
			'photo' => '../' . getUploadDir(''),
		],
		'units' => [
			'photo' => '../' . getUploadDir(''),
		],
		'unit_photos' => [
			'photo' => '../' . getUploadDir(''),
		],
	];

	if(!count($p)) exit;

	// validate input
	$t = Request::val('table');
	if(!in_array($t, array_keys($p))) {
		?>
		<div class="page-header"><h1><?php echo $Translation['rebuild thumbnails']; ?></h1></div>
		<form method="get" action="pageRebuildThumbnails.php" target="_blank">
			<?php echo $Translation['thumbnails utility']; ?><br><br>

			<b><?php echo $Translation['rebuild thumbnails of table'] ; ?></b> 
			<?php echo htmlSelect('table', array_keys($p), array_keys($p), ''); ?>
			<input type="submit" value="<?php echo $Translation['rebuild'] ; ?>">
		</form>


		<?php
		include(__DIR__ . '/incFooter.php');
		exit;
	}

	?>
	<div class="page-header"><h1><?php echo str_replace ( "<TABLENAME>" , $t , $Translation['rebuild thumbnails of table_name'] ); ?></h1></div>
	<?php echo $Translation['do not close page message'] ; ?><br><br>
	<div style="font-weight: bold; color: red; width:700px;" id="status"><?php echo $Translation['rebuild thumbnails status'] ; ?></div>
	<br>

	<div style="text-align:left; padding: 0 5px; width:700px; height:250px;overflow:auto; border: solid 1px green;">
	<?php
		foreach($p[$t] as $f=>$path) {
			$res=sql("select `$f` from `$t`", $eo);
			echo str_replace ( "<FIELD>" , $f , $Translation['building field thumbnails'] )."<br>";
			$tv = $dv = [];
			while($row=db_fetch_row($res)) {
				if($row[0]!='') {
					$tv[]=$row[0];
					$dv[]=$row[0];
				}
			}
			for($i=0; $i<count($tv); $i++) {
				if($i && !($i%4))  echo '<br style="clear: left;">';
				echo '<img src="../thumbnail.php?t='.$t.'&f='.$f.'&i='.$tv[$i].'&v=tv" align="left" style="margin: 10px 10px;"> ';
			}
			echo '<br style="clear: left;">';

			for($i=0; $i<count($dv); $i++) {
				if($i && !($i%4))  echo '<br style="clear: left;">';
				echo '<img src="../thumbnail.php?t='.$t.'&f='.$f.'&i='.$tv[$i].'&v=dv" align="left" style="margin: 10px 10px;"> ';
			}
			echo "<br style='clear: left;'>{$Translation['done']}<br><br>";
		}
	?>
	</div>

	<script>
		window.onload = function() {
			document.getElementById('status').innerHTML = "<?php echo $Translation['finished status'] ; ?>";
			document.getElementById('status').style.color = 'green';
			document.getElementById('status').style.fontSize = '25px';
			document.getElementById('status').style.backgroundColor = '#fff4cf';
		}
	</script>

<?php include(__DIR__ . '/incFooter.php');