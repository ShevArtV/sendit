<?php
/**
 * Действия кабинета покупателя для пресетов SendIt: 'snippet' => 'siCabinetAction', 'method' => '...'.
 * Заказы: toCart, repeat, pay, cancel. Адреса: save, remove, setDefault, apply.
 *
 * @var \modX $modx
 * @var array $scriptProperties
 */
$SendIt = $scriptProperties['SendIt'] ?? null;
if (!$SendIt instanceof SendIt) {
    return '';
}
$corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
require_once $corePath . 'components/sendit/services/minishop/orders.class.php';
require_once $corePath . 'components/sendit/services/minishop/addresses.class.php';

$method = (string)($scriptProperties['method'] ?? '');
unset($scriptProperties['SendIt']);
foreach (['siUserOrders', 'siUserAddresses'] as $class) {
    if ($class::handles($method)) {
        return (new $class($modx, $SendIt, $scriptProperties))->run($method);
    }
}
return $SendIt->error('si_msg_cabinet_action_err');
