<?php
$inputClosure =  function($model, $column = null) use ($attribute_name, $data) {
    return \lispa\amos\core\helpers\Html::textInput('modifiedAttributes['.$model->id.']['.$attribute_name.']', $model->$attribute_name, [
        'class' => 'form-control',]);
};