<?php
$param = explode('|', $param);
$msg = $validator->formit->config[$key.'.vTextRequiredIf'] ?: 'Это поле обязательно для заполнения';

if($_POST[$param[0]] == $param[1] && !$value){
    $validator->addError($key, $msg);
}
return true;