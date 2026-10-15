<?php
/**
 * Валидатор FormIt: code:siCheckCode=^phone^ — код из siSendCode для получателя из поля phone.
 * Проверяет без погашения: код гасит хук siCodeIdentification после входа или регистрации.
 *
 * @var \modX $modx
 * @var fiValidator $validator
 * @var string $key
 * @var string $value
 * @var string $param
 * @var array $scriptProperties
 */
$corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
require_once $corePath . 'components/sendit/services/auth/authcode.class.php';

$field = $param ?: ($scriptProperties['codeField'] ?? 'phone');
$codes = new siAuthCode($modx, $scriptProperties);
if (!$codes->verify((string)($_POST[$field] ?? ''), (string)$value)) {
    $validator->addError($key, $scriptProperties[$key . '.vTextCode'] ?? $codes->getError());
}
return true;
