<?php
namespace kilyakus\helper\media;

use Yii;
use kilyakus\helper\media\extensions as Extension;

class Video
{
	static function thumb($inputFile, $width = 640, $height = 360, $fromSeconds = 0)
	{
		if (self::fileExists($inputFile)) {
			$ffmpeg = Extension\FFMpeg::create([
				'ffmpeg.binaries'	=>	Yii::getAlias('@webroot') . '/vendor/kilyakus/yii2-helper-media/src/assets/ffmpeg/bin/ffmpeg.exe',
				'ffprobe.binaries'	=>	Yii::getAlias('@webroot') . '/vendor/kilyakus/yii2-helper-media/src/assets/ffmpeg/bin/ffprobe.exe',
				'timeout'			=>	3600,
				'ffmpeg.threads'	=>	12,
			]);
			$video = $ffmpeg->open($inputFile);
			var_dump($ffmpeg);die;
			$video
				->filters()
				->resize(new \Extension\Coordinate\Dimension($width, $height))
				->synchronize();
			$video
				->frame(\Extension\Coordinate\TimeCode::fromSeconds($fromSeconds))
				->save(Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . Upload::$UPLOADS_DIR . '/videos/' . $fileName . '.jpg');

			return Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . Upload::$UPLOADS_DIR . '/videos/' . $fileName . '.jpg';
		} else {
			return false;
		}
	}

	public function fileExists($path)
	{
		return (@fopen($path, "r") == true);
	}
}