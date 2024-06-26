<?php

$param = $param ?: 'email';
$prefixes = [
    'username' => 'modUser.',
    'email' => 'Profile.',
    'phone' => 'Profile.',
    'mobilephone' => 'Profile.',
];
$keyName = $prefixes[$param] . $param;
$q = $modx->newQuery('modUser');
$q->leftJoin('modUserProfile', 'Profile');
$q->select('modUser.id as id');
$q->where([$keyName => $value]);
$userId = [];
if ($q->prepare() && $q->stmt->execute()) {
    $userId = $q->stmt->fetchAll(PDO::FETCH_COLUMN);
}

if (empty($userId)) {
    $msg = $scriptProperties[$key . '.vTextUserNotExists'] ?: 'Пользователь с такими данными не найден.';
    $validator->addError($key, $msg);
}
return true;
