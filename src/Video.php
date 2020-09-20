<?php
namespace kilyakus\helper\media;

use Yii;

class Video
{
	static function thumb($inputFile, $width = 640, $height = 360, $fromSeconds = 0)
	{
		if (self::fileExists($inputFile)) {
			$ffmpeg = \FFMpeg\FFMpeg::create([
	            'ffmpeg.binaries'  => Yii::getAlias('@webroot') . '/vendor/kilyakus/yii2-helper-media/src/assets/ffmpeg/bin/ffmpeg.exe',
	            'ffprobe.binaries' => Yii::getAlias('@webroot') . '/vendor/kilyakus/yii2-helper-media/src/assets/ffmpeg/bin/ffprobe.exe',
	            'timeout'          => 3600, // The timeout for the underlying process
	            'ffmpeg.threads'   => 12,   // The number of threads that FFMpeg should use
	        ]);
	        $video = $ffmpeg->open($inputFile);
	        $video
	            ->filters()
	            ->resize(new \FFMpeg\Coordinate\Dimension($width, $height))
	            ->synchronize();
	        $video
	            ->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds($fromSeconds))
	            ->save(Yii::getAlias('@webroot') . '/uploads/videos/' . $fileName . '.jpg');

	        return Yii::getAlias('@webroot') . '/uploads/videos/' . $fileName . '.jpg';
	    } else {
	    	return false;
	    }
	}

	public function fileExists($path)
	{
		return (@fopen($path, "r") == true);
	}
}