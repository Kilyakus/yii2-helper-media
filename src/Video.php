<?php
namespace kilyakus\helper\media;

use Yii;
use kilyakus\helper\media\extensions as Extension;
use kilyakus\helper\media\extensions\FFMpeg;
use kilyakus\helper\media\extensions\FFProbe;
use kilyakus\helper\media\extensions\Coordinate\TimeCode;
use kilyakus\helper\media\extensions\Coordinate\Dimension;

class Video
{
	protected function config($timeout = 3600, $threads = 12)
	{
		return [
			'ffmpeg.binaries'	=>	Yii::getAlias('@webroot') . '/vendor/kilyakus/yii2-helper-media/src/assets/ffmpeg/bin/ffmpeg.exe',
			'ffprobe.binaries'	=>	Yii::getAlias('@webroot') . '/vendor/kilyakus/yii2-helper-media/src/assets/ffmpeg/bin/ffprobe.exe',
			'timeout'			=>	$timeout,
			'ffmpeg.threads'	=>	$threads,
		];
	}

	static function createFFMpeg($inputFile, $timeout = null, $threads = null)
	{
		if (!self::fileExists($inputFile))
			return false;

		return $ffmpeg = FFMpeg::create(static::config($timeout, $threads))->open($inputFile);
	}

	static function createFFProbe($timeout = null, $threads = null)
	{
		return $ffmpeg = FFProbe::create(static::config($timeout, $threads));
	}

	static function frame($inputFile, $outputFile, $timeCode = null)
	{
		if($file = static::createFFMpeg($inputFile))
		{
			$timeCode = $timeCode ?? rand(0, (int)static::createFFProbe()->format($inputFile)->get('duration'));

			$file->frame(TimeCode::fromSeconds($timeCode))->save(Yii::getAlias('@webroot') . $outputFile);

			return $outputFile;
		}
	}

	static function webm($inputFile, $outputFile, $width = 1920, $height = 1080, $timeCode = 0)
	{
		if($file = static::create($inputFile))
		{
			$file
				->filters()
				->resize(new Dimension($width, $height))
				->synchronize();

			$file->save(new Extension\Format\Video\WebM(), Yii::getAlias('@webroot') . '/uploads/videos/export-webm.webm');

			return $outputFile;
		}
	}

	protected function fileExists($path)
	{
		return (@fopen($path, "r") == true);
	}
}