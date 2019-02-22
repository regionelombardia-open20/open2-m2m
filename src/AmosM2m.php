<?php

/**
 * Lombardia Informatica S.p.A.
 * OPEN 2.0
 *
 *
 * @package    lispa\amos\m2m
 * @category   CategoryName
 */

namespace lispa\amos\m2m;

use lispa\amos\core\module\Module;
use lispa\amos\core\module\AmosModule;
use lispa\amos\core\module\ModuleInterface;
use lispa\amos\core\record\Record;
use yii\web\Application;
use yii\base\BootstrapInterface;
use Yii;
use yii\base\Event;
use yii\helpers\StringHelper;
use yii\helpers\FileHelper;

/**
 * Class AmosWorkflow
 * @package lispa\amos\workflow
 */
class AmosM2m extends AmosModule {

    public static $CONFIG_FOLDER = 'config';
    public $layout = 'main';
    public $name = 'M2M';
    public $newFileMode = 0666;
    public $newDirMode = 0777;
    public $controllerNamespace = 'lispa\amos\m2m\controllers';

    public function init() {
        parent::init();

        // initialize the module with the configuration loaded from config.php
        Yii::configure($this, require(__DIR__ . DIRECTORY_SEPARATOR . self::$CONFIG_FOLDER . DIRECTORY_SEPARATOR . 'config.php'));
    }

    public static function getModuleName() {
        return "m2m";
    }

    public function getDefaultModels() {
        return [];
    }

    public function getWidgetIcons() {
        return [];
    }

    public function getWidgetGraphics() {
        return [];
    }

}
