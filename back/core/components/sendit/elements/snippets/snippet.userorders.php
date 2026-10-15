<?php
/**
 * Заказы текущего покупателя. Подходит как render пресета Pagination.
 *
 * @var \modX $modx
 * @var array $scriptProperties
 */
$corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
require_once $corePath . 'components/sendit/services/sendit.class.php';
require_once $corePath . 'components/sendit/services/minishop/orderslist.class.php';

unset($scriptProperties['SendIt']);
return (new siUserOrdersList($modx, $scriptProperties))->run();
