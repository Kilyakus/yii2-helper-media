<?php
namespace kilyakus\helper\media;

use Yii;
use yii\web\UploadedFile;
use \yii\web\HttpException;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\FileHelper;
use yii\imagine\Image as Imagine;

class Upload
{
	public static $UPLOADS_DIR = 'uploads';

	public static function file(UploadedFile $fileInstance, $dir = '', $namePostfix = true)
	{
		$fileName = Upload::getUploadPath($dir) . DIRECTORY_SEPARATOR . Upload::getFileName($fileInstance, $namePostfix);

		if(!$fileInstance->saveAs($fileName)){
			throw new HttpException(500, 'Cannot upload file "'.$fileName.'". Please check write permissions.');
		}
		return Upload::getLink($fileName);
	}

	static function getUploadPath($dir)
	{
		$uploadPath = Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . static::$UPLOADS_DIR . ($dir ? DIRECTORY_SEPARATOR . $dir : '');
		if(!FileHelper::createDirectory($uploadPath)){
			throw new HttpException(500, 'Cannot create "'.$uploadPath.'". Please check write permissions.');
		}
		return $uploadPath;
	}

	static function getLink($fileName)
	{
		return str_replace('\\', '/', str_replace(Yii::getAlias('@webroot'), '', $fileName));
	}

	static function getFileName($fileInstanse, $namePostfix = true)
	{
		$baseName = str_ireplace('.'.$fileInstanse->extension, '', $fileInstanse->name);
		$fileName = StringHelper::truncate(Inflector::slug($baseName), 32, '');
		if($namePostfix || !$fileName) {
			$fileName .= ($fileName ? '-' : '') . substr(uniqid(md5(rand()), true), 0, 10);
		}
		if($fileInstanse->type == 'image/png' && static::getImageAlphaChannel($fileInstanse->tempName) === false){
			$fileName .= '.jpg';
		}else{
			$fileName .= '.' . $fileInstanse->extension;
		}

		return $fileName;
	}

	static function traitName($path, $width = null, $height = null, $divide = 'original')
	{
		if(!$path) return false;

		$path = @filemtime($path) ? $path : Yii::getAlias('@webroot') . $path;

		$timeStamp = filemtime($path);

		$imageData = pathinfo($path);

		if($width == null || $height == null)
		{
			 list($width, $height) = getimagesize($path);
		}

		$thumbName = $divide . '_' . (int)$width . 'x' . (int)$height . '_' . md5( $imageData['filename'] . $imageData['extension'] . $timeStamp . (int)$width . (int)$height . (int)$crop );

		return $thumbName;
	}

	static function getImageData($image)
	{
		$imageData = finfo_open(FILEINFO_MIME);
		$imageType = finfo_file($imageData, $image);
		finfo_close($imageData);

		return stristr($imageType, ';', true);
	}

	static function getImageAlphaChannel($path)
	{
		list($width, $height) = getimagesize($path);

		if($width <= 1920 && $height <= 1920)
		{
			// $imageInfo = pathinfo($path);
			// $imageData = @imagecreatefromstring(file_get_contents($path));

			// $thumb = imagecreatetruecolor(10, 10);
			// imagealphablending($thumb, FALSE);
			// imagecopyresized($thumb, $imageData, 0, 0, 0, 0, 10, 10, $width, $height);

			// Временное решение, imagecolorat в старых браузерах почему-то кладет сайт
			// if (strpos(static::getClientBrowser(), 'Bot') !== false || strpos(static::getClientBrowser(), 'Unknown') !== false) {
			// 	return false;
			// }

			$thumbnail = Imagine::getImagine()->open($path)->thumbnail(new \Imagine\Image\Box(50, 50));
			$width = $thumbnail->getSize()->getWidth();
			$height = $thumbnail->getSize()->getHeight();

			for($i = 0; $i < $width; $i++)
			{
				for($j = 0; $j < $height; $j++)
				{
					// if(imagecolorat($thumb, $i, $j) & 0x7F000000 >> 24)
					// {
					// 	return true;
					// }
					set_time_limit(0);
					if($thumbnail->getColorAt(new \Imagine\Image\Point($i, $j))->getAlpha() == 1)
					{
						return true;
					}
				}
			}
		}
		return false;
	}

	static function getClientBrowser()
	{
		$t = strtolower($_SERVER['HTTP_USER_AGENT']);

		// If the string *starts* with the string, strpos returns 0 (i.e., FALSE). Do a ghetto hack and start with a space.
		// "[strpos()] may return Boolean FALSE, but may also return a non-Boolean value which evaluates to FALSE."
		//	 http://php.net/manual/en/function.strpos.php
		$t = ' ' . $t;

		// Humans / Regular Users	 
		if		(strpos($t, 'opera')	||	strpos($t, 'opr/'))			return 'Opera';
		elseif	(strpos($t, 'edge'))									return 'Edge';
		elseif	(strpos($t, 'chrome'))									return 'Chrome';
		elseif	(strpos($t, 'safari'))									return 'Safari';
		elseif	(strpos($t, 'firefox'))									return 'Firefox';
		elseif	(strpos($t, 'msie')		||	strpos($t, 'trident/7'))	return 'Internet Explorer';

		// Search Engines 
		elseif	(strpos($t, 'google'))									return '[Bot] Googlebot';
		elseif	(strpos($t, 'bing'))									return '[Bot] Bingbot';
		elseif	(strpos($t, 'slurp'))									return '[Bot] Yahoo! Slurp';
		elseif	(strpos($t, 'duckduckgo'))								return '[Bot] DuckDuckBot';
		elseif	(strpos($t, 'baidu'))									return '[Bot] Baidu';
		elseif	(strpos($t, 'yandex'))									return '[Bot] Yandex';
		elseif	(strpos($t, 'sogou'))									return '[Bot] Sogou';
		elseif	(strpos($t, 'exabot'))									return '[Bot] Exabot';
		elseif	(strpos($t, 'msn'))										return '[Bot] MSN';

		// Common Tools and Bots
		elseif (strpos($t, 'mj12bot'))									return '[Bot] Majestic';
		elseif (strpos($t, 'ahrefs'))									return '[Bot] Ahrefs';
		elseif (strpos($t, 'semrush'))									return '[Bot] SEMRush';
		elseif (strpos($t, 'rogerbot')	||	strpos($t, 'dotbot'))		return '[Bot] Moz or OpenSiteExplorer';
		elseif (strpos($t, 'frog')		||	strpos($t, 'screaming'))	return '[Bot] Screaming Frog';
	   
		// Miscellaneous
		elseif (strpos($t, 'facebook'))									return '[Bot] Facebook';
		elseif (strpos($t, 'pinterest'))								return '[Bot] Pinterest';
	   
		// Check for strings commonly used in bot user agents  
		elseif (strpos($t, 'crawler')	||	strpos($t, 'api')		||
				strpos($t, 'spider')	||	strpos($t, 'http')		||
				strpos($t, 'bot')		||	strpos($t, 'archive')	||
				strpos($t, 'info')		||	strpos($t, 'data'))			return '[Bot] Other';
	   
		return 'Other (Unknown)';
	}
}