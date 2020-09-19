<?php
namespace kilyakus\helper\media\bundles;

use yii\web\AssetBundle;

class ParallaxAsset extends AssetBundle
{
	public $depends = [
		'yii\web\JqueryAsset'
	];

	public function init()
	{
		$this->sourcePath = realpath(__DIR__  . '/..') . '/assets/parallax';

		$this->js[] = 'js/app.js';
		// $this->js[] = 'js/src.js';
		
		parent::init();
	}
}
