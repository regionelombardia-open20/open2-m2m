<?php
$inputClosure = function($model) use ($attribute_name, $data) {
    return \kartik\datecontrol\DateControl::widget([
        'name'=> 'modifiedAttributes['.$model->id.']['.$attribute_name.']',
        'value' => (!empty($model->$attribute_name) ? $model->$attribute_name : ''),
        'type'=>\kartik\datecontrol\DateControl::FORMAT_DATETIME,
        'disabled'=>true
    ]);
};