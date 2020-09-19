<?php
namespace kilyakus\helper\media\bundles;

use yii\web\AssetBundle;

class PreloaderAsset extends AssetBundle
{
	public $depends = [
		'yii\web\JqueryAsset'
	];

	public function init()
	{
		$this->sourcePath = realpath(__DIR__  . '/..') . '/assets/preloader';

		$this->js[] = 'js/widget-preloader.min.js';
		$this->css[] = 'css/widget-preloader.min.css';
		
		parent::init();
	}
}
