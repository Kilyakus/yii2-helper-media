<?php
namespace kilyakus\helper\media;

use Yii;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\FileHelper;
use kilyakus\helper\media\extensions\GD;

class Image
{
	public static function upload(UploadedFile $fileInstance, $dir = '', $resizeWidth = null, $resizeHeight = null, $resizeCrop = false)
	{
		$pathInfo = pathinfo($fileInstance);

		list($width, $height) = getimagesize($fileInstance->tempName);


		if($width >= $resizeWidth && $resizeWidth != null || !$resizeWidth)
		{
			$oldWidth = $width;
			$width = $resizeWidth;
		}

		if($height >= $resizeHeight && $resizeHeight != null && $oldWidth || !$resizeHeight && $oldWidth)
		{
			$ratio = $oldWidth / $resizeWidth;
			$height = round($height / $ratio);
		}

		$fileName = 'original_' . (int)$width . 'x' . (int)$height . '_' . md5($pathInfo['filename'] . $pathInfo['extension'] . (int)$width . (int)$height . $fileInstance->size) . '.' . $pathInfo['extension'];

		$fileName = Upload::getUploadPath($dir) . DIRECTORY_SEPARATOR . $fileName;//Upload::getFileName($fileInstance);

		$uploaded = $resizeWidth
			? static::copyResizedImage($fileInstance->tempName, $fileName, $resizeWidth, $resizeHeight, $resizeCrop)
			: $fileInstance->saveAs($fileName);

		if(!$uploaded){
			throw new HttpException(500, 'Cannot upload file "'.$fileName.'". Please check write permissions or the file is too large.');
		}

		return Upload::getLink($fileName);
	}

	static function thumb($inputFile, $width = null, $height = null, $crop = true)
	{
		if(!empty($inputFile) && ($path = Yii::getAlias('@webroot') . $inputFile) && is_file($path))
		{
			$thumbName = Upload::traitName(
				$path, 
				(int)$width, 
				(int)$height,
				'thumbnail'
			);
		}else{
			$path = __DIR__ . '/assets/img/noimage.jpg';
			$thumbName = Upload::traitName(
				$path, 
				(int)$width, 
				(int)$height,
				'noimage'
			);
		}

		$imageData = pathinfo($path);

		if(strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false){
			$thumbExt = 'webp';
		}elseif($imageData['extension'] == 'png' && Upload::getImageAlphaChannel($path) === false){
			$thumbExt = 'jpg';
		}else{
			$thumbExt = $imageData['extension'];
		}

		$thumbFile = Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . Upload::$UPLOADS_DIR . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $thumbName . '.' . $thumbExt;
		$thumbWebFile = '/' . Upload::$UPLOADS_DIR . '/thumbs/' . $thumbName . '.' . $thumbExt;

		if(file_exists($thumbFile)){
			return $thumbWebFile;
		}
		elseif(FileHelper::createDirectory(dirname($thumbFile), 0777) && static::copyResizedImage($path, $thumbFile, (int)$width, (int)$height, $crop)){
			return $thumbWebFile;
		}

		return '';
	}

	static function copyResizedImage($inputFile, $outputFile, $width, $height = null, $crop = true)
	{
		if (extension_loaded('gd'))
		{
			$image = new GD($inputFile);

			if($height) {
				if($width && $crop){
					$image->cropThumbnail($width, $height);
				} else {
					$image->resize($width, $height);
				}
			} else {
				$image->resize($width);
			}

			return $image->save($outputFile);
		}
		elseif(extension_loaded('imagick'))
		{
			$image = new \Imagick($inputFile);

			if($height && !$crop) {
				$image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1, true);
			}
			else{
				$image->resizeImage($width, null, \Imagick::FILTER_LANCZOS, 1);
			}

			if($height && $crop){
				$image->cropThumbnailImage($width, $height);
			}

			return $image->writeImage($outputFile);
		}
		else {
			throw new HttpException(500, 'Please install GD or Imagick extension');
		}
	}

	public static function blur($fileName, $width = null, $height = null, $percent = 1.5)
	{
		if(!file_exists($fileName) && !file_exists(Yii::getAlias('@webroot') . $fileName)){
			return false;
		}
		
		if( !$width ){
			$width = 480;
		}

		$fileName = Image::thumb($fileName, (int)( $width / $percent ), (int)( $height / $percent ));

		if(!(!empty($fileName) && ($path = Yii::getAlias('@webroot') . $fileName) && is_file($path)))
		{
			$path = __DIR__ . '/assets/img/noimage.jpg';
		}

		$imageData = pathinfo($path);

		if(strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false){
			$blurExt = 'webp';
		}elseif($imageData['extension'] == 'png' && !Upload::getImageAlphaChannel($path)){
			$blurExt = 'jpg';
		}else{
			$blurExt = $imageData['extension'];
		}

		$blurName = Upload::traitName(
			Yii::getAlias('@webroot') . $fileName, 
			(int)( $width / $percent ), 
			(int)( $height / $percent ),
			'blur'
		);

		$blurFile = Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . Upload::$UPLOADS_DIR . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $blurName . '.' . $blurExt;
		$blurWebFile = '/' . Upload::$UPLOADS_DIR . '/thumbs/' . $blurName . '.' . $blurExt;

		if(!file_exists($blurFile) && !is_dir($path))
		{
			copy($path, $blurFile);

			$mimeType = Upload::getImageData($blurFile);

			if ($mimeType == 'image/webp') {
				$result = imagecreatefromwebp($blurFile);
			}
			else if ($mimeType == 'image/jpeg') {
				$result = imagecreatefromjpeg($blurFile);
			}
			else if ($mimeType == 'image/png') {
				$result = imagecreatefrompng($blurFile);
			}
			if($result){
				for ($x=1; $x<=10; $x++){
					imagefilter($result, IMG_FILTER_GAUSSIAN_BLUR, 999);
				} 
				imagefilter($result, IMG_FILTER_SMOOTH,99);

				imagejpeg($result, $blurFile);

				imagedestroy($result);
			}
		}

		if(file_exists($blurFile)){
			return $blurWebFile;
		}

		return '';
	}

	public static function bump($fileName, $w = null, $h = null,$percent = 1.5)
	{
		if(!file_exists($fileName) && !file_exists(Yii::getAlias('@webroot') . $fileName)){
			return false;
		}
		
		if(!$w){
			$w = 480;
		}

		$fileName = Image::thumb($fileName, ($w/$percent), ($h/$percent));

		$file = Yii::getAlias('@webroot') . $fileName;

		$fileName = '/' . Upload::$UPLOADS_DIR . '/thumbs/bump-' . md5($fileName) . '.jpg';
		$temp = Yii::getAlias('@webroot') . $fileName;
		if(!file_exists($temp)){
			copy($file, $temp);
			if (exif_imagetype($temp) == IMAGETYPE_JPEG) {
				$result = imagecreatefromjpeg($temp);
			}
			else if (exif_imagetype($temp) == IMAGETYPE_PNG) {
				$result = imagecreatefrompng($temp);
			}
			if($result){
				
				imagefilter($result, IMG_FILTER_MEAN_REMOVAL);
				imagefilter($result, IMG_FILTER_GRAYSCALE);

				// imagefilter($result, IMG_FILTER_CONTRAST,75);
				// imagefilter($result, IMG_FILTER_BRIGHTNESS,70);

				imagefilter($result, IMG_FILTER_CONTRAST,-35);
				imagefilter($result, IMG_FILTER_BRIGHTNESS,230);
				imagefilter($result, IMG_FILTER_NEGATE);

				for ($x=1; $x<=15; $x++){
					imagefilter($result, IMG_FILTER_GAUSSIAN_BLUR,999);
				} 

				imagefilter($result, IMG_FILTER_SMOOTH,99);
				
				imagejpeg($result, $temp);

				imagedestroy($result);
			}
		}

		return Image::thumb($fileName, $w, $h);
	}

	public function copyImage($image, $path)
	{
		$uploadUrl = '/' . Upload::$UPLOADS_DIR . '/' . $path . '/';
		
		$path = $uploadUrl . self::parseName($image);

        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
            return $path;
        }

		if (self::fileExists($image)) {

			if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $uploadUrl)) { 
				mkdir($_SERVER['DOCUMENT_ROOT'] . $uploadUrl, 0777, true);
			}

			copy($image, $_SERVER['DOCUMENT_ROOT'] . $path);

			return $path;

		} else {

			return false;

		}
	}

	public function parseName($path)
	{
		$info = pathinfo($path);

		$basename = Inflector::slug($info['filename']);

		if(!$info['extension'] || !(!preg_match('/[^A-Za-z]/', $info['extension']))){

			$basename = md5($basename);

			$info = curl_file_create($path, 'image/png', $basename . '.png');

			$path = md5($basename . $path) . '.png';

		}else{

			$path = $basename . '-' . md5($path) . '.' . $info['extension'];

		}

		return $path;
	}


	public function fileExists($path)
	{
		return (@fopen($path, "r") == true);
	}
}