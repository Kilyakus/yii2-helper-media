<?php
namespace kilyakus\helper\media;

use Yii;
use yii\helpers\Html;

class Parallax
{
    static function init($filename, $width = null, $height = null, $percent = 1.5, $options = [])
    {
        $attributes = self::__getAttributes($filename, $width, $height, $percent, $options);

        $attributes = array_merge($attributes,['class' => 'parallax ' . $options['class']]);

        $view = Yii::$app->view;

        $view->registerCss('#' . $attributes['id'] . ' {width:'.$width.'px;height:'.$height.'px;}');

        echo Html::tag('div','',$attributes);
    }

    static function setAttributes($filename, $width = null, $height = null, $percent = 1.5, $options = [])
    {
        $attributes = self::__getAttributes($filename, $width, $height, $percent, $options);

        echo Html::renderTagAttributes($attributes);
    }

    static function __getAttributes($filename, $width = null, $height = null, $percent = 1.5, $options = [])
    {
        $filename = Image::thumb($filename, $width, $height, true);

        $container = 'gl-' . substr(md5($filename), 0, 6) . rand(1000,10);

        $attributes = [
            'id' => 'gl',//$container,
            'data-imageOriginal' => $filename,
            'data-imageDepth' => Image::bump($filename, $width, $height, $percent),
            'data-horizontalThreshold' => 70,
            'data-verticalThreshold' => 50,
            'style' => 'background-image:url(' . Image::blur($filename, $width/1.5, $height/1.5, 1.5) . ');background-size:cover;'
        ];

        $view = Yii::$app->view;

        ParallaxAsset::register($view);

        return $attributes;
    }
}