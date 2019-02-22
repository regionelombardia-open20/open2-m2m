<?php
$inputClosure =  function($model, $column) use ($attribute_name, $data) {
    return \lispa\amos\core\helpers\Html::textarea('modifiedAttributes['.$model->id.']['.$attribute_name.']', $model->$attribute_name, [
        'class' => 'form-control',]);
};