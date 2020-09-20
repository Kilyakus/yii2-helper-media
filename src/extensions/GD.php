<?php
namespace kilyakus\helper\media\extensions;

use kilyakus\helper\media\Upload;

class GD
{
	private $_image;
	private $_mime;
	private $_width;
	private $_height;

	public function __construct($file)
	{
		$imageData = getimagesize($file);
		$this->_mime = image_type_to_mime_type($imageData[2]);
		$this->_width = $imageData[0];
		$this->_height = $imageData[1];
		
		switch ($this->_mime) {
			case 'image/jpeg':
				$this->_image = imagecreatefromjpeg($file);
				break;
			case 'image/png':
				$this->_image = static::imagecreatelessalpha($file); //imagecreatefrompng($file);
				break;
			case 'image/gif':
				$this->_image = imagecreatefromgif($file);
				break;
		}
	}

	protected function imagecreatelessalpha($file)
	{
		if(Upload::getImageAlphaChannel($file) === false)
		{
			$this->_mime = 'image/jpeg';

			$imageData = imagecreatefromstring(@file_get_contents($file));
			$bg = imagecreatetruecolor(imagesx($imageData), imagesy($imageData));
			imagefill($bg, 0, 0, imagecolorallocate($bg, 255, 255, 255));
			imagealphablending($bg, TRUE);
			imagecopy($bg, $imageData, 0, 0, 0, 0, imagesx($imageData), imagesy($imageData));
			imagedestroy($imageData);
			$quality = 100;
			imagejpeg($bg, $file, $quality);
			imagedestroy($bg);
			return imagecreatefromjpeg($file);
		}

		return imagecreatefrompng($file);
	}

	public function resize($width = null, $height = null)
	{
		if(!$this->_image || (!$width && !$height))
		{
			return false;
		}

		if(!$width)
		{
			if ($this->_height > $height) {
				$ratio = $this->_height / $height;
				$newWidth = round($this->_width / $ratio);
				$newHeight = $height;
			} else {
				$newWidth = $this->_width;
				$newHeight = $this->_height;
			}
		}
		elseif(!$height)
		{
			if ($this->_width > $width) {
				$ratio = $this->_width / $width;
				$newWidth = $width;
				$newHeight = round($this->_height / $ratio);
			} else {
				$newWidth = $this->_width;
				$newHeight = $this->_height;
			}
		}
		else
		{
			$newWidth = $width;
			$newHeight = $height;
		}

		$resizedImage = imagecreatetruecolor($newWidth, $newHeight);
		imagealphablending($resizedImage, false);

		imagecopyresampled(
			$resizedImage,
			$this->_image,
			0,
			0,
			0,
			0,
			$newWidth,
			$newHeight,
			$this->_width,
			$this->_height
		);

		$this->_image = $resizedImage;
	}

	public function cropThumbnail($width, $height)
	{
		if(!$this->_image || !$width || !$height){
			return false;
		}

		$sourceRatio = $this->_width / $this->_height;
		$thumbRatio = $width / $height;

		$newWidth = $this->_width;
		$newHeight = $this->_height;

		if($sourceRatio !== $thumbRatio)
		{
			if($this->_width >= $this->_height){
				if($thumbRatio > 1){
					$newHeight = $this->_width / $thumbRatio;
					if($newHeight > $this->_height){
						$newWidth = $this->_height * $thumbRatio;
						$newHeight = $this->_height;
					}
				} elseif($thumbRatio == 1) {
					$newWidth = $this->_height;
					$newHeight = $this->_height;
				} else {
					$newWidth = $this->_height * $thumbRatio;
				}
			} else {
				if($thumbRatio > 1){
					$newHeight = $this->_width / $thumbRatio;
				} elseif($thumbRatio == 1) {
					$newWidth = $this->_width;
					$newHeight = $this->_width;
				} else {
					$newHeight = $this->_width / $thumbRatio;
					if($newHeight > $this->_height){
						$newHeight = $this->_height;
						$newWidth = $this->_height * $thumbRatio;
					}
				}
			}
		}

		$resizedImage = imagecreatetruecolor($width, $height);
		imagealphablending($resizedImage, false);

		imagecopyresampled(
			$resizedImage,
			$this->_image,
			0,
			0,
			round(($this->_width - $newWidth) / 2),
			round(($this->_height - $newHeight) / 2),
			$width,
			$height,
			$newWidth,
			$newHeight
		);

		$this->_image = $resizedImage;
	}

	public function save($file, $quality = 90)
	{
		if(strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false && !empty($this->_image)){
			$imageData = pathinfo($file);
			$wpfile = implode('/', [$imageData['dirname'], $imageData['filename']]) . '.' . 'webp';
			imageWebp($this->_image, $file, $quality);
		}

		switch($this->_mime) {
			case 'image/jpeg':
				$imageData = pathinfo($file);
				$file = implode('/', [$imageData['dirname'], $imageData['filename']]) . '.' . 'jpg';
				return imagejpeg($this->_image, $file, $quality);
				break;
			case 'image/png':
				imagesavealpha($this->_image, true);
				return imagepng($this->_image, $file);
				break;
			case 'image/gif':
				return imagegif($this->_image, $file);
				break;
		}
		return false;
	}
}