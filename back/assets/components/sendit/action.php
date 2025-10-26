<?php

$time_start = microtime(true);
define('MODX_API_MODE', true);
require_once dirname(__FILE__, 4) . '/index.php';
require_once MODX_CORE_PATH . 'components/sendit/services/sendit.class.php';

$modx->getService('error', 'error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);

$headers = array_change_key_case(getallheaders());
$token = $headers['x-sitoken'] ?? '';
$cookie = $_COOKIE['SendIt'] ? json_decode($_COOKIE['SendIt'], 1) : [];

$preset = $headers['x-sipreset'] ?? '';
$formName = $headers['x-siform'] ?? '';
$action = $headers['x-siaction'] ?? '';

$sendit = new SendIt($modx, (string)$preset, (string)$formName);

$res = [
    'success' => false,
    'msg' => '',
    'data' => []
];

$session = SendIt::getSession($modx);

if (!isset($session['sitoken']) || !$token || $token !== $session['sitoken']) {
    die(json_encode($sendit->error('si_msg_token_err')));
}
/*if (!$cookie['sitrusted']) {
    die(json_encode($sendit->error('si_msg_trusted_err')));
}*/

switch ($action) {
    case 'validate_files':
        $filesData = isset($_POST['filesData']) ? json_decode($_POST['filesData'], JSON_UNESCAPED_UNICODE) : [];
        $fileList = !empty($_POST['fileList']) ? explode(',', $_POST['fileList']) : [];
        $res = $sendit->validateFiles($filesData, count($fileList));
        break;

    case 'uploadChunk':
        $content = file_get_contents('php://input');
        $res = $sendit->uploadChunk($content, $headers);
        break;

    case 'send':
        $res = $sendit->process();
        break;

    case 'removeFile':
        $path = MODX_BASE_PATH . $_POST['path'];
        $nomsg = (bool)$_POST['nomsg'];
        $res = $sendit->removeFile($path, $nomsg);
        break;
}

if (is_array($res)) {
    $res = json_encode($res);
} else {
    $res = json_encode(['result' => $res]);
}
$time_end = microtime(true);
$duration = sprintf('TOTAL TIME %f sec.', $time_end - $time_start);
//$modx->log(1, print_r($duration, 1));
die($res);
