<?php

/**
 * Lombardia Informatica S.p.A.
 * OPEN 2.0
 *
 *
 * @package    lispa\amos\m2m
 * @category   CategoryName
 */

namespace lispa\amos\m2m\widgets\base;

use lispa\amos\comuni\models\IstatRegioni;
use lispa\amos\core\helpers\Html;
use lispa\amos\core\views\AmosGridView;
use lispa\amos\core\views\grid\ActionColumn;
use yii\base\Widget;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;
use yii\widgets\Pjax;

class M2MWidget extends Widget
{
    public $modelFromObject;
    public $from_id = [
        'id' => ['scuola_id'],
        'name' => 'scuola.nome'];

    public $to_id = [
        'id' => ['my_profile_id'],
        'name' => 'myProfile.nomeCognome'];


    public $columnsModelMm = [];
    public $columnsModelTo = [];

    // Object Model from
    public $model;
    public $modelMm = '\backend\modules\m2m\models\MyProfileScuolaMm';
    public $modelTo = '\backend\modules\m2m\models\MyProfile';

    public $classTable = 'grid-view';

    //INPUT TYPE DEFAULT
    public $defaultInputType = [
        'select2' => '@vendor/lispa/amos-m2m/views/input_types/select2_input.php',
        'string' => '@vendor/lispa/amos-m2m/views/input_types/text_input.php',
        'text' => '@vendor/lispa/amos-m2m/views/input_types/textarea_input.php',
        'integer' => '@vendor/lispa/amos-m2m/views/input_types/text_input.php',
        'datetime' => '@vendor/lispa/amos-m2m/views/input_types/date_control_input.php',
    ];

    public $enableSearchGridTo = true;
    public $attributeSearchGridTo = ['nome'];

    public $enableSearchGridMm = true;
    public $attributeSearchGridMm = ['info', 'myProfile.nome'];

    public $enableLiveSave = false;

    public function init()
    {
        parent::init();
        $this->model->modelMm = $this->modelMm;
        $this->model->modelTo = $this->modelTo;
        $this->model->from_id = $this->from_id;
        $this->model->to_id = $this->to_id;
        $this->model->enableLiveSave = $this->enableLiveSave;

    }

//    public function run()
//    {
//
////        $model->className();
//    }

    public function run() {
//        $this->resetSession();
        $classModelMm = $this->modelMm;
        $classModelTo = $this->modelTo;
        $this->idWidget = $classModelTo::tableName();
        $attributesListMm = null;
        $attributesListTo = null;
        $selectedIdModelMm = [];
        $from_id = $this->from_id['id'][0];
        $to_id = $this->to_id['id'][0];


        /** @var $modelFrom  Scuola*/
        $modelFrom = $this->model;
        $pkFrom = $modelFrom::getTableSchema()->primaryKey;
        $pkTo = $modelFrom::getTableSchema()->primaryKey;

        $confRelation = [];
        for($i=0; $i < count($this->from_id['id']) ; $i++) {
            if(is_array($pkFrom)) {
                $confRelation [$this->from_id['id'][$i]] = $pkFrom[$i];
            }
        }

        // -------------QUERY GRID MM---------
        $tableSchemaColumns = $classModelMm::getTableSchema()->columns;
        $attributesList =  $classModelMm::getTableSchema()->columnNames;
        $tmpModel = $modelFrom->hasMany($classModelMm,  $confRelation);
        $dataProviderMm = new ActiveDataProvider([
            'query' => $tmpModel
        ]);



        //-----------SEARCH QUERY MM ---------------
        if($this->enableSearchGridMm) {
            $columnsModelMm = $this->columnsModelMm;
            $attributesListMm = $classModelMm::getTableSchema()->columnNames;
            $params = \Yii::$app->request->get();
            if (!empty($params['searchAttributesMm'])) {
                foreach ($params['searchAttributesMm'] as $attribute => $value) {
                    if (in_array($attribute, $attributesListMm)) {
                        $tmpModel->andFilterWhere(['LIKE', $attribute, $value]);
                    }
                }
            }

            $attributesListTo =  $classModelTo::getTableSchema()->columnNames;
            if (!empty($params['searchAttributesToGridMm'])) {
                $tmpModel->innerJoin($classModelTo::getTableSchema()->name, $classModelTo::getTableSchema()->name.'.id'.'='.$classModelMm::getTableSchema()->name.'.'.$this->to_id['id'][0]);
                foreach ($params['searchAttributesToGridMm'] as $attribute => $value) {
                    if (in_array($attribute, $attributesListTo)) {
                        $tmpModel->andFilterWhere(['LIKE',  $classModelTo::getTableSchema()->name.'.'.$attribute, $value]);
                    }
                }
            }
        }

        //----------- NEW RECORDS GRID MM ----------
        $idNewRercords = $arrIdTo = \Yii::$app->session->get('_idToNewRecord');
        pr($idNewRercords, 'new');
        $idFrom = \Yii::$app->request->get('id');
        $arr = $dataProviderMm->models;
        $models = $arr;
        if($idFrom && !empty($idNewRercords)) {
            $newModelArr = [];
            foreach ($idNewRercords as $key => $fields) {
                $new = new $classModelMm();
                $new->id = 'tmp-' . $fields['idTo'];
                $new->$to_id = $fields['idTo'];
                $new->$from_id = $idFrom;
                foreach ($fields as $attribute => $value) {
                    if ($attribute !== 'idTo') {
                        $new->$attribute = $value;
                    }
                }
                $newModelArr [] = $new;
            }

            $models = ArrayHelper::merge($newModelArr, $arr);
        }
        //-------------- REMOVE DELETED RECORDS FROM GRID-MM ----------
        $idDeleteRercords = \Yii::$app->session->get('_idDeletedRecord');
        $idModifiedRercords = \Yii::$app->session->get('_idModifiedRecord');
        pr($idModifiedRercords,'modify');
        pr($idDeleteRercords,'delete');
        if(!empty($idDeleteRercords) || !empty($idModifiedRercords) ) {
            foreach ($models as $key => $model){
                //-------------- DELETED RECORDS GRID-MM ----------
                if(!empty($idDeleteRercords)) {
                    foreach ($idDeleteRercords as $idTo => $idMm) {
                        if ($idMm == $model->id) {
                            unset($models[$key]);
                        }
                    }
                }
                //-------------- MODIFIED RECORDS GRID-MM ----------
                if(!empty($idModifiedRercords)) {
                    foreach ((array) $idModifiedRercords as $idTo => $attributes) {
                        if ($model->$to_id == $idTo) {
                            foreach ($attributes as $attribute => $value) {
                                $model->$attribute = $value;
                            }
                        }
                    }
                }
            }
            array_values($models);
        }


        $dataProviderMm->setModels($models);
        $dataProviderMm = new ArrayDataProvider([
            'allModels' => $models,
            'key' => 'id'
        ]);

        $dataProviderMm->pagination->setPageSize(6);

        // ---------QUERY GRID TO-----------
        foreach ($tmpModel->all() as $a) {
            $selectedIdModelMm []= $a->$to_id;
        }
        //---------- DELETE NEW_MODELS FROM GRID-TO ----------
        if(!empty($idNewRercords)) {
            foreach ($idNewRercords as $key => $fields) {
                $selectedIdModelMm [] = $fields['idTo'];
            }
        }
        //---- RE-ADD DELETED RECORDS FROM GRID-MM  TO  GRID-TO
        $idDeleteRercords = \Yii::$app->session->get('_idDeletedRecord');
        if(!empty($idDeleteRercords)) {
            foreach ($idDeleteRercords as $idTo => $idMm) {
                if (($key = array_search($idTo, $selectedIdModelMm)) !== false) {
                    unset($selectedIdModelMm[$key]);
                }
            }
        }

        $queryTo = $classModelTo::find()->andWhere(['NOT IN', $pkTo[0], $selectedIdModelMm]);
        $dataProviderTo = new ActiveDataProvider([
            'query' => $queryTo,
        ]);

        //-----------SEARCH QUERY TO ---------------
        if($this->enableSearchGridTo) {
            $columnsModelTo = $this->columnsModelTo;
            $attributesListTo = $classModelTo::getTableSchema()->columnNames;
            $params = \Yii::$app->request->get();
            if (!empty($params['searchAttributesTo'])) {
                foreach ($params['searchAttributesTo'] as $attribute => $value) {
                    if (in_array($attribute, $attributesListTo)) {
                        $queryTo->andFilterWhere(['LIKE', $attribute, $value]);
                    }
                }
            }
        }
        $dataProviderTo->pagination->setPageSize(10);



        //----- BACKLIST ATTRIBUTES ---------
        $blackListAttributes = [
            'id',
            'created_by',
            'updated_by',
            'deleted_by',
            'created_at',
            'updated_at',
            'deleted_at'
        ];

        foreach ($attributesList as $key => $attribute) {
            if (in_array($attribute, $blackListAttributes)){
                unset($attributesList[$key]);
            }
        }
        //------- CUSTOM ATTRIBUTE COLUMNS MM-GRID ------
        $this->columnsModelMm = [
//            [
//                'attribute' => 'somma',
//                'value' => function() {return 1+1;},
//                'label' => 'somma 1+1'
//            ],
            'myProfile.nome',
            'myProfile.cognome',
            'info',
            [
                'attribute' => 'classe',
                'type' => 'select2',
                'data' => \yii\helpers\ArrayHelper::map(IstatRegioni::find()->all(), 'id', 'nome'),
                'format' => 'raw',
            ],
            [
                'attribute' => 'data_iscrizione',
                'type' => 'datetime',
                'format' => 'raw'
            ],
        ];

        //------- CREATE MMGRID COLUMNS ---------
        $attributesListMm = [];
//        $attributesListMm []= ['attribute' => $this->to_id['name']];
        foreach ($this->columnsModelMm as $column) {
            $mycolumn = [];
            if(is_array($column)) {
                $mycolumn ['attribute']= $column['attribute'];
                if(!empty($column['label'])) {
                    $mycolumn ['label']= $column['label'];
                }
                if(!empty($column['format'])) {
                    $mycolumn ['format']= $column['format'];
                }
                if(!empty($column['value'])) {
                    $mycolumn ['value']= $column['value'];
                }
                if(in_array($column['attribute'],$attributesList)) {
                    $attribute_name = $column['attribute'] ;
                    $data = '';
                    $inputClosure = '';
                    if (!empty($column['data'])){
                        $data = $column['data'];
                    }
                    if (!empty($column['type'])) {
                        include \Yii::getAlias($this->defaultInputType[$column['type']]);
                    }
                    else {
                        $type = $tableSchemaColumns[$column['attribute']]->phpType;
                        include \Yii::getAlias($this->defaultInputType[$type]);
                    }
                    $mycolumn ['value'] = $inputClosure;
                    $mycolumn ['format'] = 'raw';
                }
                $attributesListMm []= $mycolumn;
            }elseif(!is_array($column)){
                if(in_array($column,$attributesList)) {
                    $attribute_name = $column;
                    $data = '';
                    $type = $tableSchemaColumns[$column]->phpType;
                    include \Yii::getAlias($this->defaultInputType[$type]);
                    $mycolumn ['value'] = $inputClosure;
                    $mycolumn ['format'] = 'raw';
                    if (!empty($tableSchemaColumns[$column]->comment)) {
                        $mycolumn ['label'] = $tableSchemaColumns[$column]->comment;
                    } else {
                        $mycolumn ['label'] = $column;
                    }
                    $mycolumn ['attribute'] = $column;
                }
                else {
                    $mycolumn = $column;
                }
                $attributesListMm []= $mycolumn;
            }
        }


        //--------- REQUIRED COLUMNS MMGRID ----------
//        if(!empty($this->columnsModelMm)) {
//            $attributesListMm = $this->columnsModelMm;
//        }
        $attributesListMm []= [
            'class' => ActionColumn::className(),
            'template' => '{edit}{delete}',
            'buttons' => [
                'delete' => function ($model, $key, $index) {
                    return \lispa\amos\core\icons\AmosIcons::show('delete', [
                        'class' => 'btn btn-tool-secondary cancel-association',
                        'title' => 'Cancella associazione',
                        'data-confirm' => 'Vuoi cancellare l\'associazione?',
                    ]);
                },
                'edit' => function ($model, $key, $index) {
                    return \lispa\amos\core\icons\AmosIcons::show('edit', [
                        'class' => 'btn btn-tool-secondary',
                        'data-toggle' => 'modal',
                        'data-target' => '#model-to-modify',
                        'title' => 'Modifica tutti gli attributi',
                    ]);
                }
            ]
        ];

        // --------CUSTOM ATTRIBUTE COLUMNS TO-GRID
//        $explode = explode('.',$this->to_id['name']);
//        $attributeMm = end($explode);
        $this->columnsModelTo = [
//            [
//                'attribute' => $attributeMm,
//            ],
            'nome',
            'cognome'
        ];


        //--------- REQUIRED COLUMNS TO-GRID ----------
        if(!empty($this->columnsModelTo)) {
            $attributesListTo = $this->columnsModelTo;
        }
        $attributesListTo []= [
            'class' => '\kartik\grid\CheckboxColumn',
            'name' => 'attrUserProfileMinistryMms',
            'rowSelectedClass' => \kartik\grid\GridView::TYPE_SUCCESS,
            'checkboxOptions' => function ($model, $key, $index, $column) {
                return [
                    'value' => $model->id,
                ];

            }
        ];

        // ------ SEARCH MM ----------
        if($this->enableSearchGridMm) {
            $this->renderSearchGridMm();
        }

        //--------- GRID MM ----------
        Pjax::begin(['id' =>'pjax-container', 'timeout' => 2000, 'clientOptions' => ['data-pjax-container' => 'grid-mm']]);
        echo AmosGridView::widget([
            'options' => ['class' => 'grid-table-mm'.' '.$this->classTable, 'id' => 'grid-mm'],
            'dataProvider' => $dataProviderMm,
            'rowOptions' => function($model){
                $explode = explode('-', $model->id);
                if($explode[0] == 'tmp'){
                    return ['class' => 'success','data-model-to' => $model->my_profile_id];
                }
                else {
                    return ['data-model-to' => $model->my_profile_id];
                }
            },
            'columns' => $attributesListMm,
        ]);
        Pjax::end();

        // ------ SEARCH TO ----------
        if($this->enableSearchGridTo) {
            $this->renderSearchGridTo();
        }

        // ---------- GRID TO ----------
        Pjax::begin(['id' =>'pjax-modelTo', 'timeout' => 2000, 'clientOptions' => ['data-pjax-container' => 'grid-modelTo']]);
        echo AmosGridView::widget([
            'options' => ['class' => 'grid-table-to'.' '.$this->classTable,'id' => 'grid-modelTo'],
            'dataProvider' => $dataProviderTo,
            'columns' => $attributesListTo
        ]);
        Pjax::end();

        echo Html::hiddenInput('classNameMm', $classModelMm, ['id' => 'classNameMm']);
        echo Html::hiddenInput('idFrom', $this->owner->id, ['id' => 'idFrom']);
        if($this->enableLiveSave) {
            echo Html::hiddenInput('m2m-enable-live-save', 1 , ['id' => 'm2m-enable-live-save']);
        }

        echo '<div class="m2m-new-records-container"></div>';
        echo '<div class="m2m-delete-records-container"></div>';

//        Modal::begin([
//            'header' => '<h2>Hello world</h2>',
//            'toggleButton' => ['label' => 'click me'],
//            'id' => 'model-to-modify'
//        ]);
//
//        echo 'Say hello...';
//        $attribute_name = 'nome' ;
//        $data = '';
//        $inputClosure = '';
//        $a = $dataProviderMm->models;
//        include \Yii::getAlias($this->defaultInputType['string']);
//        echo $inputClosure($a[0]);
//        Modal::end();

    }





    public function renderSearchGridTo() {
        $columnsModelTo = $this->columnsModelTo;
        $classModelTo = $this->modelTo;
        $attributesListTo =  $classModelTo::getTableSchema()->columnNames;
        echo "<div class='search-form-m2m'>";
        if(!empty ($this->attributeSearchGridTo)) {
            foreach ($this->attributeSearchGridTo as $attribute) {
                if(in_array($attribute, $attributesListTo)){
                    echo '<div class="col-lg-6 form-group">';
                    echo '<label>'.$attribute.'</label>';
                    echo Html::textInput('searchAttributesTo['.$attribute.']', '',['class' => 'form-control']);
                    echo '</div>';
                }
            }
        }
        else {
            foreach ($columnsModelTo as $column) {
                if (is_array($column)) {
                    if (in_array($column['attribute'], $attributesListTo)) {
                        echo '<div class="col-lg-6 form-group">';
                        echo '<label>' . $column['attribute'] . '</label>';
                        echo Html::textInput('searchAttributesTo[' . $column['attribute'] . ']', '', ['class' => 'form-control']);
                        echo '</div>';
                    }
                } else {
                    if (in_array($column, $attributesListTo)) {
                        echo '<div class="col-lg-6 form-group">';
                        echo '<label>' . $column . '</label>';
                        echo Html::textInput('searchAttributesTo[' . $column . ']', '', ['class' => 'form-control']);
                        echo '</div>';
                    }
                }
            }

        }
        echo "<div class='col-lg-12'>";
        echo Html::submitButton('cerca',['id' => 'search-button-m2m','class' => 'btn btn-primary pull-right']);
        echo "</div>";
        echo "</div>";
    }

    public function renderSearchGridMm() {
        $columnsModelMm = $this->columnsModelMm;
        $classModelMm = $this->modelMm;
        $classModelTo = $this->modelTo;
        $attributesListMm =  $classModelMm::getTableSchema()->columnNames;
        $attributesListTo =  $classModelTo::getTableSchema()->columnNames;
        echo "<div class='search-form-m2m-mm'>";
        if(!empty ($this->attributeSearchGridMm)) {
            foreach ($this->attributeSearchGridMm as $attribute) {
                if(in_array($attribute, $attributesListMm)){
                    echo '<div class="col-lg-6 form-group">';
                    echo '<label>'.$attribute.'</label>';
                    echo Html::textInput('searchAttributesMm['.$attribute.']', '',['class' => 'form-control']);
                    echo '</div>';
                }
                else{
                    //ricerca du mm-grid per campo su model-to
                    $explode = explode('.',$attribute);
                    if ( method_exists($this->modelMm, 'get'.ucfirst($explode[0])) && count($explode) == 2 && in_array($explode[1], $attributesListTo)){
                        echo '<div class="col-lg-6 form-group">';
                        echo '<label>'.$explode[1].'</label>';
                        echo Html::textInput('searchAttributesToGridMm['.$explode[1].']', '',['class' => 'form-control']);
                        echo '</div>';
                    }
                }
            }
        } // campi di ricerca di default
        else {
            foreach ($columnsModelMm as $column) {
                if (is_array($column)) {
                    if (in_array($column['attribute'], $attributesListMm)) {
                        echo '<div class="col-lg-6 form-group">';
                        echo '<label>' . $column['attribute'] . '</label>';
                        echo Html::textInput('searchAttributesMm[' . $column['attribute'] . ']', '', ['class' => 'form-control']);
                        echo '</div>';
                    }
                } else {
                    if (in_array($column, $attributesListMm)) {
                        echo '<div class="col-lg-6 form-group">';
                        echo '<label>' . $column . '</label>';
                        echo Html::textInput('searchAttributesMm[' . $column . ']', '', ['class' => 'form-control']);
                        echo '</div>';
                    }
                    //ricerca du mm-grid per campo su model-to
                    $explode = explode('.',$column);
                    if ( method_exists($this->modelMm, 'get'.ucfirst($explode[0])) && count($explode) == 2 && in_array($explode[1], $attributesListTo)){
                        echo '<div class="col-lg-6 form-group">';
                        echo '<label>'.$explode[1].'</label>';
                        echo Html::textInput('searchAttributesToGridMm['.$explode[1].']', '',['class' => 'form-control']);
                        echo '</div>';
                    }
                }
            }

        }
        echo "<div class='col-lg-12'>";
        echo Html::submitButton('cerca',['id' => 'search-button-m2m-mm','class' => 'btn btn-primary pull-right']);
        echo "</div>";
        echo "</div>";
    }

}
