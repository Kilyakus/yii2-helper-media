<?php
namespace kilyakus\helper\media;

use Yii;
use yii\web\UploadedFile;
use yii\web\HttpException;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;

class Avatar
{
    static function get($filename, $text = null)
    {
        if(!($filename && is_file(($filename = Yii::getAlias('@webroot') . $filename))))
        {
            $text = static::translit($text);
            
            if(count(explode(' ', $text)) > 1){
                $text = explode(' ', $text);
                $letters = [];
                for ($i=0; $i < 2; $i++) { 
                    $letters[] = substr(Inflector::slug($text[$i]), 0, 1);
                }
                $text = implode('',$letters);
            }else{
                $text = substr($text, 0, 2);
            }
            $text = Inflector::slug($text);

            if(!is_file($filename = Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . Upload::$UPLOADS_DIR . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . 'avatar_' . $text .'.jpg')){

                $im = imagecreatetruecolor(300, 300);

                $FONT = __DIR__ . '/assets/avatar/fonts/text.ttf';

                if($text == null){
                    $r = 0 & 0xFF;
                    $g = 0 & 0xFF;
                    $b = 0 & 0xFF;
                }else{
                    $r = rand(60, 180) & 0xFF;
                    $g = rand(60, 180) & 0xFF;
                    $b = rand(60, 180) & 0xFF;
                }

                imagefill($im, 1, 1, imagecolorallocate($im, $r, $g, $b ));

                $box = imagettfbbox(100, 0, $FONT, mb_strtoupper($text));

                $left = 140-round(($box[2]-$box[0])/2);

                imagettftext($im, 100, 0, $left, 195, imagecolorallocate($im, 0xf8, 0xf8, 0xfb), $FONT, mb_strtoupper($text));

                imagejpeg($im, Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . Upload::$UPLOADS_DIR . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . 'avatar_' . $text .'.jpg');
                 
                imagedestroy($im);

                $filename = Yii::getAlias('@webroot') . '/' . Upload::$UPLOADS_DIR . '/avatars/' . 'avatar_' . $text .'.jpg';

            }
        }

        $info = pathinfo($filename);
        $thumbName = 'thumbnail_' . $info['filename'] . '_' . md5( $info['filename'] . filemtime($filename) . (int)$width . (int)$height . (int)$crop ) . '.' . $info['extension'];
        $thumbFile = Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . Upload::$UPLOADS_DIR . DIRECTORY_SEPARATOR . 'thumbs' . DIRECTORY_SEPARATOR . $thumbName;
        $thumbWebFile = '/' . Upload::$UPLOADS_DIR . '/thumbs/' . $thumbName;
        if(file_exists($thumbFile)){
            return $thumbWebFile;
        }
        elseif(FileHelper::createDirectory(dirname($thumbFile), 0777) && Image::copyResizedImage($filename, $thumbFile, $width, $height, $crop)){
            return $thumbWebFile;
        }

        return '';
    }

    private function translit($text)
    {
        $text = (string)$text;
        $text = preg_replace("/\s+/", ' ', $text);
        $text = trim($text);
        $text = function_exists('mb_strtolower') ? mb_strtolower($text) : strtolower($text);
        $text = strtr($text, array('а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'e','ж'=>'j','з'=>'z','и'=>'i','й'=>'y','к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f','х'=>'h','ц'=>'c','ч'=>'ch','ш'=>'sh','щ'=>'shch','ы'=>'y','э'=>'e','ю'=>'yu','я'=>'ya','ъ'=>'','ь'=>''));
        $text = preg_replace("/[^0-9a-z-_ ]/i", "", $text);
        $text = str_replace(" ", "", $text);
        return $text;
    }

    static function merge($files, $size = 300)
    {
        $width = $height = $size;
        $resImage = imagecreatetruecolor($width, $height);

        $count = count($files);

        $fileName = '';

        for ($i = 0; $i < $count; $i++) {

            if($i > 4){
                break;
            }

            $info = pathinfo($files[$i]);
            $image = Yii::getAlias('@webroot') . $files[$i];
            if(is_file($image)){
                $fileName .= md5( $info['filename'] . '-' . filemtime($image) . (int)$width . (int)$height );

                if($image = self::imagecreatefromfile($image))
                {
                    $srcWidth = imagesx($image);
                    $srcHeight = imagesy($image);

                    if($count == 1){

                        $x = ($i == 1 || $i == 2 ? $width / 2 : 0);
                        $y = ($i > 1 ? $height / 2 : 0);
                        $h2 = intval(($i == 0) ? $height : $height / 2);
                        $w2 = intval(($i == 0) ? $width : $width / 2);

                    }elseif($count == 2){

                        $x = ($i == 1 ? ($width / 2) : 0);
                        $y = ($i > 1 ? $height / 2 : 0);
                        $h2 = intval($height);
                        $w2 = intval($width / 2);

                    }elseif($count == 3){

                        $x = ($i == 1 || $i == 2 ? $width / 2 : -($width / 7));
                        $y = ($i > 1 ? $height / 2 : 0);
                        $h2 = intval(($i == 0) ? $height : $height / 2);
                        $w2 = intval(($i == 0) ? $width / 1.3 : $width / 2);

                    }elseif($count > 3){

                        $x = ($i == 1 || $i == 2 ? $width / 2 : 0);
                        $y = ($i > 1 ? $height / 2 : 0);
                        $h2 = intval($height / 2);
                        $w2 = intval($width / 2);

                    }

                    imagecopyresampled($resImage, $image, $x, $y, 0, 0, $w2, $h2, $srcWidth, $srcHeight);
                
                    imagedestroy($image);
                }
            }
        }

        $fileName = 'collage_' . md5($fileName);

        $imagePath = Yii::getAlias('@webroot') . DIRECTORY_SEPARATOR . Upload::$UPLOADS_DIR . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $fileName . '.';

        $imageExt = (strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false) ? 'webp' : 'jpg';

        if(strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false){
            imagewebp($resImage, $imagePath . $imageExt);
        }else{
            imagejpeg($resImage, $imagePath . $imageExt);
        }

        imagedestroy($resImage);

        $thumbFile = '/' . Upload::$UPLOADS_DIR . '/avatars/' . $fileName . '.' . $imageExt;

        return $thumbFile;
    }

    static function imagecreatefromfile($imagepath = false)
    {   
        if(!$imagepath || !is_readable($imagepath)){
            return false;
        }

        if(strpos( $_SERVER['HTTP_ACCEPT'], 'image/webp' ) !== false){
            return imagecreatefromwebp($imagepath);
        }else{
            return @imagecreatefromstring(file_get_contents($imagepath));
        }
    }
}