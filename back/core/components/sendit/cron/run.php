<?php
/**
 * @var \modX $modx
 */

define('MODX_API_MODE', true);
require_once dirname(__FILE__, 5) . '/index.php';
require_once dirname(__FILE__, 2) .  '/services/sendit.class.php';
$modx->getService('error', 'error.modError');
$modx->setLogLevel(\modX::LOG_LEVEL_ERROR);
SendIt::clearSession($modx);
