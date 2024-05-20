<?php

class Thumbnail {
	/**
	 * Create a thumbnail of an image. The thumbnail is saved in the same directory as the original image, with the same name, suffixed with `$specs['identifier']`
	 * @param string $img - path to image file
	 * @param array $specs - array with thumbnail specs as returned by getThumbnailSpecs()
	 * @return bool - true on success, false on failure
	 */
	public static function create($img, $specs) {
		$w = $specs['width'];
		$h = $specs['height'];
		$id = $specs['identifier'];
		$path = dirname($img);

		// image doesn't exist or inaccessible?
		// known issue: for webp files, requires PHP 7.1+
		if(!$size = @getimagesize($img)) return false;

		// calculate thumbnail size to maintain aspect ratio
		$ow = $size[0]; // original image width
		$oh = $size[1]; // original image height
		$twbh = $h / $oh * $ow; // calculated thumbnail width based on given height
		$thbw = $w / $ow * $oh; // calculated thumbnail height based on given width
		if($w && $h) {
			if($twbh > $w) $h = $thbw;
			if($thbw > $h) $w = $twbh;
		} elseif($w) {
			$h = $thbw;
		} elseif($h) {
			$w = $twbh;
		} else {
			return false;
		}

		// dir not writeable?
		if(!is_writable($path)) return false;

		// GD lib not loaded?
		if(!function_exists('gd_info')) return false;
		$gd = gd_info();

		// GD lib older than 2.0?
		preg_match('/\d/', $gd['GD Version'], $gdm);
		if($gdm[0] < 2) return false;

		// get file extension
		preg_match('/\.[a-zA-Z]{3,4}$/U', $img, $matches);
		$ext = strtolower($matches[0]);

		// check if supplied image is supported and specify actions based on file type
		if($ext == '.gif') {
			if(!$gd['GIF Create Support']) return false;
			$thumbFunc = 'imagegif';
		} elseif($ext == '.png') {
			if(!$gd['PNG Support'])  return false;
			$thumbFunc = 'imagepng';
		} elseif($ext == '.webp') {
			if(!$gd['WebP Support'] && !$gd['WEBP Support'])  return false;
			$thumbFunc = 'imagewebp';
		} elseif($ext == '.jpg' || $ext == '.jpe' || $ext == '.jpeg') {
			if(!$gd['JPG Support'] && !$gd['JPEG Support'])  return false;
			$thumbFunc = 'imagejpeg';
		} else {
			return false;
		}

		// determine thumbnail file name
		$ext = $matches[0];
		$thumb = substr($img, 0, -5) . str_replace($ext, $id . $ext, substr($img, -5));

		// if the original image smaller than thumb, then just copy it to thumb
		if($h > $oh && $w > $ow) {
			return (@copy($img, $thumb) ? true : false);
		}

		// get image data
		if(
			$thumbFunc == 'imagewebp'
			&& !$imgData = imagecreatefromwebp($img)
		)
			return false;
		elseif(!$imgData = imagecreatefromstring(file_get_contents($img)))
			return false;

		// finally, create thumbnail
		$thumbData = imagecreatetruecolor($w, $h);

		//preserve transparency of png and gif images
		$transIndex = null;
		if($thumbFunc == 'imagepng' || $thumbFunc == 'imagewebp') {
			if(($clr = @imagecolorallocate($thumbData, 0, 0, 0)) != -1) {
				@imagecolortransparent($thumbData, $clr);
				@imagealphablending($thumbData, false);
				@imagesavealpha($thumbData, true);
			}
		} elseif($thumbFunc == 'imagegif') {
			@imagealphablending($thumbData, false);
			$transIndex = imagecolortransparent($imgData);
			if($transIndex >= 0) {
				$transClr = imagecolorsforindex($imgData, $transIndex);
				$transIndex = imagecolorallocatealpha($thumbData, $transClr['red'], $transClr['green'], $transClr['blue'], 127);
				imagefill($thumbData, 0, 0, $transIndex);
			}
		}

		// resize original image into thumbnail
		if(!imagecopyresampled($thumbData, $imgData, 0, 0 , 0, 0, $w, $h, $ow, $oh)) return false;
		unset($imgData);

		// gif transparency
		if($thumbFunc == 'imagegif' && $transIndex >= 0) {
			imagecolortransparent($thumbData, $transIndex);
			for($y = 0; $y < $h; ++$y)
				for($x = 0; $x < $w; ++$x)
					if(((imagecolorat($thumbData, $x, $y) >> 24) & 0x7F) >= 100) imagesetpixel($thumbData, $x, $y, $transIndex);
			imagetruecolortopalette($thumbData, true, 255);
			imagesavealpha($thumbData, false);
		}

		if(!$thumbFunc($thumbData, $thumb)) return false;
		unset($thumbData);

		self::fixOrientation($img, $thumb);

		return true;
	}

	/**
	 * Read the orientation of the source image and rotate the thumbnail accordingly. Requires the exif extension.
	 * @param string $src - path to source image
	 * @param string $thumb - path to thumbnail image
	 * @return string|null - error message if any, or null on success
	 */
	public static function fixOrientation($src, $thumb) {
		// if exif extension is not available, return
		if(!function_exists('exif_read_data')) return 'Exif extension not available';

		// read exif data from source image
		$exif = @exif_read_data($src);
		if(!$exif) return 'Failed to read exif data from source image';

		// if orientation is not set, either as a key, or as a key in a sub-array, return
		if(!isset($exif['Orientation']) && !isset($exif['IFD0']['Orientation'])) return 'Orientation not set in exif data';

		// determine orientation
		$orientation = isset($exif['Orientation']) ? $exif['Orientation'] : $exif['IFD0']['Orientation'];

		// if orientation is 1, no need to rotate
		if($orientation == 1) return null;

		// if orientation is not one of the 8 possible values, return
		if(!in_array($orientation, [2, 3, 4, 5, 6, 7, 8])) return 'Invalid orientation value';

		// Get the image type
		$image_type = @exif_imagetype($thumb);

		// if image type is not supported, return
		if($image_type !== IMAGETYPE_JPEG) return 'Destination image is not a JPEG image';

		// create image resource from thumbnail
		$image = @imagecreatefromjpeg($thumb);
		if(!$image) return 'Failed to create image resource from thumbnail';

		// rotate image based on orientation
		switch($orientation) {
			case 2:
				@imageflip($image, IMG_FLIP_HORIZONTAL);
				break;
			case 3:
				$image = @imagerotate($image, 180, 0);
				break;
			case 4:
				@imageflip($image, IMG_FLIP_VERTICAL);
				break;
			case 5:
				@imageflip($image, IMG_FLIP_HORIZONTAL);
				$image = @imagerotate($image, 270, 0);
				break;
			case 6:
				$image = @imagerotate($image, 270, 0);
				break;
			case 7:
				@imageflip($image, IMG_FLIP_HORIZONTAL);
				$image = @imagerotate($image, 90, 0);
				break;
			case 8:
				$image = @imagerotate($image, 90, 0);
				break;
		}

		// save the rotated image
		@imagejpeg($image, $thumb);

		// free the image resource
		@imagedestroy($image);

		return null;
	}

	/**
	 * Function to detect if image is rotated. Requires the exif extension.
	 * @param string $img - path to image file
	 * @return bool - true if image is rotated (-90 or 90 degrees), false otherwise
	 */
	public static function isImageRotated($img) {
		// if exif extension is not available, return
		if(!function_exists('exif_read_data')) return false;

		// read exif data from source image
		$exif = @exif_read_data($img);
		if(!$exif) return false;

		// if orientation is not set, either as a key, or as a key in a sub-array, return
		if(!isset($exif['Orientation']) && !isset($exif['IFD0']['Orientation'])) return false;

		// determine orientation
		$orientation = isset($exif['Orientation']) ? $exif['Orientation'] : $exif['IFD0']['Orientation'];

		// if orientation is not rotated (-90 or 90 degrees), return false
		if(!in_array($orientation, [5, 6, 7, 8])) return false;

		return true;
	}
}