<?php
/**
 * Идемпотентное создание событий SendIt в БД стенда
 * (сборка тянет события из БД по groupname=SendIt: build/data/transport.events.php).
 * Запускать на стенде из корня:
 *   php _mk_event.php
 */
@include dirname(__FILE__) . '/config.core.php';
if (!defined('MODX_CORE_PATH')) {
    die("config.core.php не найден — положите скрипт в корень стенда\n");
}
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';
$modx = new modX();
$modx->initialize('mgr');
$modx->getService('error', 'error.modError');

$groupName = 'SendIt';
$events = array(
    'siOnSanitizeFileName',
);

foreach ($events as $name) {
    /** @var modEvent $event */
    $event = $modx->getObject('modEvent', array('name' => $name));
    if ($event) {
        $event->set('groupname', $groupName);
        $event->set('service', 6);
        $event->save();
        echo "UPDATED {$name}\n";
        continue;
    }
    $event = $modx->newObject('modEvent');
    $event->fromArray(array(
        'name' => $name,
        // 6 — System: так заведены остальные события пакета
        'service' => 6,
        'groupname' => $groupName,
    ), '', true);
    $event->save();
    echo "CREATED {$name}\n";
}

$modx->cacheManager->refresh();
echo "cache refreshed\n";
