<?php
if(!$modx->getCount('modUser', array('username' => $value))){
    $msg = $scriptProperties[$key.'.vTextUserNotExists'] ?: 'Пользователь с такими данными не найден.';
    $validator->addError($key, $msg);
}
return true;