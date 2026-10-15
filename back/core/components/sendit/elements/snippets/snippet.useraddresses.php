<?php
/**
 * Сохранённые адреса текущего покупателя: список в кабинете и выбор на странице оформления.
 *
 * @var \modX $modx
 * @var array $scriptProperties
 */
$corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
require_once $corePath . 'components/sendit/services/sendit.class.php';
require_once $corePath . 'components/sendit/services/minishop/addresses.class.php';

$tpl = $modx->getOption('tpl', $scriptProperties, 'siUserAddressTpl');
$tplEmpty = $modx->getOption('tplEmpty', $scriptProperties, '');
$tplWrapper = $modx->getOption('tplWrapper', $scriptProperties, '');
$outputSeparator = $modx->getOption('outputSeparator', $scriptProperties, "\n");

if (!in_array('addresses', siCabinet::getEnabledActions($modx), true)
    || !$modx->user->isAuthenticated($modx->context->get('key'))) {
    return '';
}

$parser = $modx->getService('pdoTools') ?: $modx;
$selected = siOrderFields::get($modx);
$items = [];
foreach (siUserAddresses::getList($modx, (int)$modx->user->get('id')) as $address) {
    $address['selected'] = $selected && !array_diff_assoc($address['fields'], array_intersect_key($selected, $address['fields']));
    $items[] = $parser->getChunk($tpl, array_merge($scriptProperties, $address));
}
if (!$items) {
    return $tplEmpty ? $parser->getChunk($tplEmpty, $scriptProperties) : '';
}
$output = implode($outputSeparator, $items);
return $tplWrapper ? $parser->getChunk($tplWrapper, array_merge($scriptProperties, ['output' => $output])) : $output;
