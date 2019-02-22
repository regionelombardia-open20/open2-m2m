<?php
$inputClosure =  function($model, $column) use ($attribute_name, $data) {
    return \kartik\select2\Select2::widget([
        'name' => 'modifiedAttributes['.$model->id.']['.$attribute_name.']',
        'value' => (!empty($model->$attribute_name) ? [$model->$attribute_name => $model->$attribute_name] : []),
        'data' => $data,
        'options' => ['placeholder' => 'Select ...']
    ]);
};
