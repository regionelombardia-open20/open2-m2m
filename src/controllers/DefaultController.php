<?php

/**
 * Lombardia Informatica S.p.A.
 * OPEN 2.0
 *
 *
 * @package    lispa\amos\m2m
 * @category   CategoryName
 */

namespace lispa\amos\m2m\controllers;

use Yii;
use lispa\amos\core\controllers\BaseController;

class DefaultController extends BaseController {

    public function actionAjaxInsertMm() {
        $idTo = \Yii::$app->request->post('idTo');
        $idFrom = \Yii::$app->request->post('idFrom');
        $className =  \Yii::$app->request->post('classNameMm');
        $mmRecord = new $className();
        $mmRecord->scuola_id = $idFrom;
        $mmRecord->my_profile_id = $idTo;
        $mmRecord->save(false);

        $return = ['idFrom' => $idFrom, 'idTo' => $idTo];

        return \yii\helpers\Json::encode($return);
    }

    public function actionAjaxSaveSessionNewMm() {
        $idTo = \Yii::$app->request->post('idTo');
        $idFrom = \Yii::$app->request->post('idFrom');
        $className =  \Yii::$app->request->post('classNameMm');
        $arrIdTo = \Yii::$app->session->get('_idToNewRecord');
//        $arrIdTo []= $idTo;
        $arrIdTo[$idTo]['idTo'] = $idTo;
        \Yii::$app->session->set('_idToNewRecord', $arrIdTo);

        $return = ['idFrom' => $idFrom, 'idTo' => $idTo];

        return \yii\helpers\Json::encode($return);
    }


    public function actionAjaxDeleteMm() {
        $idMm = \Yii::$app->request->post('idMm');
        $idFrom = \Yii::$app->request->post('idFrom');
        $className =  \Yii::$app->request->post('classNameMm');
        $className::deleteAll(['id' => $idMm]);

        $return = ['idFrom' => $idFrom, 'idMm' => $idMm, 'className' => $className];

        return \yii\helpers\Json::encode($return);
    }

    public function actionAjaxDeleteSessionNewMm() {
        $idMm = \Yii::$app->request->post('idMm');
        $idFrom = \Yii::$app->request->post('idFrom');
        $idTo = \Yii::$app->request->post('idTo');
        $className =  \Yii::$app->request->post('classNameMm');

        if( strpos($idMm, 'tmp') !== false) {
            $arrIdTo = \Yii::$app->session->get('_idToNewRecord');
            if (isset($arrIdTo[$idTo])) {

                unset($arrIdTo[$idTo]);
                \Yii::$app->session->set('_idToNewRecord', $arrIdTo);
            }

        }
        else {
//        if (($key = array_search($idTo, $arrIdTo)) !== false) {
//            unset($arrIdTo[$key]);
//        }
//        $arrIdTo = array_values($arrIdTo);
            $arrIdMm = \Yii::$app->session->get('_idDeletedRecord');
            $arrIdMm [$idTo] = $idMm;
            \Yii::$app->session->set('_idDeletedRecord', $arrIdMm);
        }
        $return = ['idFrom' => $idFrom, 'idMm' => $idMm, 'className' => $className];

        return \yii\helpers\Json::encode($return);
    }

    public function actionAjaxSaveFieldSession() {
        $idFrom = \Yii::$app->request->post('idFrom');
        $idTo = \Yii::$app->request->post('idTo');
        $idMm = \Yii::$app->request->post('idMm');
        $fieldName = \Yii::$app->request->post('fieldName');
        $fieldValue = \Yii::$app->request->post('fieldValue');

        $arrIdTo = \Yii::$app->session->get('_idToNewRecord');
        if(!empty($arrIdTo[$idTo])) {
            $arrIdTo[$idTo][$fieldName] = $fieldValue;
            $arrIdTo[$idTo]['idTo'] = $idTo;
            \Yii::$app->session->set('_idToNewRecord', $arrIdTo);
        }
        else {
            $arrModified = \Yii::$app->session->get('_idModifiedRecord');
            $arrModified[$idTo][$fieldName] = $fieldValue;
            $arrModified[$idTo]['idMm'] = $idMm;
            \Yii::$app->session->set('_idModifiedRecord', $arrModified);
        }

//        pr($idTo,'idto');
//        pr($key);die;



        return \yii\helpers\Json::encode($arrIdTo);
    }
}