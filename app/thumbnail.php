<?php
	include_once(__DIR__ . '/lib.php');

	handle_maintenance();

	// image paths
	$p = [
		'properties' => [
			'photo' => getUploadDir(''),
		],
		'property_photos' => [
			'photo' => getUploadDir(''),
		],
		'units' => [
			'photo' => getUploadDir(''),
		],
		'unit_photos' => [
			'photo' => getUploadDir(''),
		],
	];

	if(!count($p)) exit;

	// receive user input
	$t = Request::val('t'); // table name
	$f = Request::val('f'); // field name
	$v = Request::val('v'); // thumbnail view type: 'tv' or 'dv'
	$i = Request::val('i'); // original image file name

	// validate input
	if(!in_array($t, array_keys($p)))  getImage();
	if(!in_array($f, array_keys($p[$t])))  getImage();
	if(!preg_match('/^[a-z0-9_-]+\.(jpg|jpeg|gif|png|webp)$/i', $i, $m)) getImage();
	if($v != 'tv' && $v != 'dv')   getImage();
	if($i == 'blank.gif') getImage();

	$img = $p[$t][$f] . $i;
	$thumb = str_replace(".{$m[1]}ffffgggg", "_$v.{$m[1]}", $img . 'ffffgggg');

	// if thumbnail exists and the user is not admin, output it without rebuilding the thumbnail
	if(getImage($thumb) && !getLoggedAdmin())  exit;

	// otherwise, try to create the thumbnail and output it
	if(!Thumbnail::create($img, getThumbnailSpecs($t, $f, $v)))  getImage();
	if(!getImage($thumb))  getImage();

	function getImage($img = '') {
		$exit = false;
		if(!$img) { // default image to return
			$img = './photo.gif';
			$exit = true;
		}

		$maxAge = 30 * 86400; // 30 days
		$lastModified = @filemtime($img);
		$fileSize = @filesize($img);
		$etag = ($lastModified && $fileSize !== false) ? md5($img . $lastModified . $fileSize) : '';

		$cacheHeaders = function() use ($lastModified, $etag, $maxAge) {
			if($lastModified) header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
			if($etag !== '') header("ETag: $etag");
			header("Cache-Control: private, max-age=$maxAge");
			header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $maxAge) . ' GMT');
		};

		if(ob_get_level()) ob_end_clean();

		// Use ETag if provided, otherwise fall back to Last-Modified.
		$ifNoneMatch = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : '';
		$ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? @strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) : false;
		if(
			$lastModified &&
			(
				($ifNoneMatch !== '' && $ifNoneMatch == $etag) ||
				($ifNoneMatch === '' && $ifModifiedSince !== false && $ifModifiedSince == $lastModified)
			)
		) {
			header($_SERVER["SERVER_PROTOCOL"] . ' 304 Not Modified');
			$cacheHeaders();
			exit;
		}

		$thumbInfo = @getimagesize($img);
		$fp = fopen($img, 'rb');
		if($thumbInfo && $fp) {
			header('Content-type: ' . $thumbInfo['mime']);
			if($fileSize !== false) header("Content-Length: {$fileSize}");
			$cacheHeaders();
			fpassthru($fp);
			fclose($fp);
			if(!$exit) return true; else exit;
		}

		if($fp) fclose($fp);

		if(!$exit) return false; else exit;
	}
