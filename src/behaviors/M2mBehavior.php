<?php

/**
 * Lombardia Informatica S.p.A.
 * OPEN 2.0
 *
 *
 * @package    lispa\amos\m2m
 * @category   CategoryName
 */

namespace lispa\amos\m2m\behaviors;


use lispa\amos\comuni\models\IstatRegioni;
use lispa\amos\core\helpers\Html;
use lispa\amos\core\views\AmosGridView;
use lispa\amos\core\views\grid\ActionColumn;
use kartik\datecontrol\DateControl;
use yii\base\Behavior;
use yii\base\Controller;
use yii\base\Event;
use yii\bootstrap\Modal;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\ActiveRecord;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\StringHelper;
use yii\widgets\Pjax;

class M2mBehavior extends Behavior
{
    public $idWidget = '';
    public $modifiedAttributes = [];
    public $searchAttributesTo = [];
    public $searchAttributesMm = [];
    //------ TABLE MM
    // 'id' => (array) chiavi esterne rivolte ad table_from
    // 'name' => {nome_metodo_per_raggiungere_la_tabella_table_from}.{attributo}
    public $from_id = [
        'id' => ['scuola_id'],
        'name' => 'scuola.nome'];

    public $to_id = [
        'id' => ['my_profile_id'],
        'name' => 'myProfile.nomeCognome'];


    public $columnsModelMm = [];
    public $columnsModelTo = [];

    public $modelForm;
    public $modelMm = '\backend\modules\m2m\models\MyProfileScuolaMm';
    public $modelTo = '\backend\modules\m2m\models\MyProfile';

    public $enableLiveSave = false;


    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
//            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
        ];
    }

    public function afterSave(Event $event)
    {
        if ($this->enableLiveSave) {
            $modifiedAttributes = \Yii::$app->request->post('modifiedAttributes');
//        pr(\Yii::$app->request->post('modifiedAttributes'));die;
            foreach ($modifiedAttributes as $idMm => $attributes) {
                $modelMmClass = $this->modelMm;
                $modelMm = $modelMmClass::findOne($idMm);
                foreach ($attributes as $attribute => $value) {
                    $modelMm->$attribute = $value;
                }
                $modelMm->save(false);
            }
        } else {
            $idFrom = \Yii::$app->request->get('id');
            $from_attribute = $this->from_id['id'][0];
            $to_attribute = $this->to_id['id'][0];
            $modelMmClass = $this->modelMm;

            $deleted = \Yii::$app->session->get('_idDeletedRecord');
            if (!empty($deleted)) {
                foreach ((array)$deleted as $idMm) {
                    $modelMmClass::deleteAll(['id' => $idMm]);
                }
            }
            $newRecords = \Yii::$app->session->get('_idToNewRecord');
            if (!empty($newRecords)) {
                foreach ($newRecords as $idTo => $attributes) {
                    $modelMm = new $modelMmClass();
                    $modelMm->$from_attribute = $idFrom;
                    $modelMm->$to_attribute = $idTo;
                    foreach ($attributes as $attribute => $value) {
                        if ($attribute !== 'idTo') {
                            $modelMm->$attribute = $value;
                        }
                    }
                    $modelMm->save(false);
                }
            }
            $modifiedRecords = \Yii::$app->session->get('_idModifiedRecord');
            if (!empty($modifiedRecords)) {
                foreach ($modifiedRecords as $idTo => $attributes) {
                    $modelMm = $modelMmClass::findOne(['id' => $attributes['idMm']]);
                    foreach ($attributes as $attribute => $value) {
                        if ($attribute !== 'idMm') {
                            $modelMm->$attribute = $value;
                        }
                    }
                    $modelMm->save(false);
                }
            }

            $this->resetSession();

        }
    }

    private function resetSession()
    {
        \Yii::$app->session->remove('_idToNewRecord');
        \Yii::$app->session->remove('_idDeletedRecord');
        \Yii::$app->session->remove('_idModifiedRecord');
    }
}