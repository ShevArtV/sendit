<?php
/**
 * Хук FormIt: вход (method login) или регистрация (method register) по одноразовому коду.
 *
 * @var \modX $modx
 * @var fiHooks $hook
 * @var array $scriptProperties
 */
$corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
require_once $corePath . 'components/sendit/services/auth/codeidentification.class.php';

$method = $scriptProperties['method'] ?? '';
if (!in_array($method, ['login', 'register'], true)) {
    return false;
}
return (new siCodeIdentification($modx, $hook, $scriptProperties))->$method();
