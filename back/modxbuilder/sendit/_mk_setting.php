<?php
/**
 * Идемпотентное создание системных настроек логирования SendIt в БД стенда
 * (сборка тянет настройки из БД по namespace=sendit). Запускать на стенде из корня:
 *   php _mk_setting.php
 */
@include dirname(__FILE__) . '/config.core.php';
if (!defined('MODX_CORE_PATH')) {
    die("config.core.php не найден — положите скрипт в корень стенда\n");
}
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
$modx = new modX();
$modx->initialize('mgr');
$modx->getService('error', 'error.modError');

$defs = array(
    'si_debug' => array(
        'value'     => '1',
        'xtype'     => 'combo-boolean',
        'namespace' => 'sendit',
        'area'      => '',
    ),
    'si_log_level' => array(
        'value'     => 'error',
        'xtype'     => 'textfield',
        'namespace' => 'sendit',
        'area'      => '',
    ),
);

foreach ($defs as $key => $data) {
    /** @var modSystemSetting $setting */
    $setting = $modx->getObject('modSystemSetting', array('key' => $key));
    if ($setting) {
        $setting->fromArray($data);
        $setting->save();
        echo "UPDATED {$key} = {$data['value']}\n";
    } else {
        $setting = $modx->newObject('modSystemSetting');
        $setting->set('key', $key);
        $setting->fromArray($data);
        $setting->save();
        echo "CREATED {$key} = {$data['value']}\n";
    }
}

$modx->cacheManager->refresh();
echo "cache refreshed\n";
