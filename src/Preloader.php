<?php
namespace kilyakus\helper\media;

use Yii;
use yii\helpers\Html;
use kilyakus\helper\media\bundles as Bundle;

class Preloader
{
    static function init($inputFile, $width = null, $height = null, $percent = 3, $options = [])
    {
        $attributes = self::__getAttributes($inputFile, $width, $height, $percent, $options);

        $view = Yii::$app->view;

        echo Html::tag('div','',$attributes);

        $view->registerCss('#' . $attributes['id'] . ' {width:'.$width.'px;max-width:100%;height:'.$height.'px;}');
    }

    static function setAttributes($inputFile, $width = null, $height = null, $percent = 3, $options = [])
    {
        $attributes = self::__getAttributes($inputFile, $width, $height, $percent, $options);

        echo Html::renderTagAttributes($attributes);
    }

    static function __getAttributes($inputFile, $width = null, $height = null, $percent = 3, $options = [])
    {
        $fileName = Image::thumb($inputFile, $width, $height, true);

        $container = 'widget-prelaoder_' . hash('adler32', $fileName . @filemtime($fileName) . (int)$width . (int)$height . $percent);

        $attributes = [
            'id' => $container,
            'data-image' => $fileName,
            'style' => 'background-image:url(\'' . Image::blur($inputFile, $width, $height, $percent) . '\');',
        ];

        $attributes = isset($options['class']) ?
            array_merge($attributes, $options['class']) : $attributes;

        $view = Yii::$app->view;

        Bundle\PreloaderAsset::register($view);

        $view->registerCss('#' . $attributes['id'] . ' {background-size:cover;background-position:center;}');

        $view->registerJs("$('#" . $attributes['id'] . "').addClass('".$attributes['class']."')", $view::POS_END);

        return $attributes;
    }
}