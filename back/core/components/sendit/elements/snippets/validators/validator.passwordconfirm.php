<?php
$msg = $validator->formit->config[$key.'.vTextPasswordConfirm'] ?: 'Пароли не совпадают.';
if($_POST[$param] && $_POST[$param] !== $value){
    $validator->addError($key, $msg);
}
return true;