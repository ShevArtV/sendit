<?php

$headers = array_change_key_case(getallheaders());
$token = $headers['x-sitoken'] ?: $headers['X-SITOKEN'];
$cookie = $_COOKIE['SendIt'] ? json_decode($_COOKIE['SendIt'],1): [];
$res = [
    'success' => false,
    'msg' => '',
    'data' => []
];

if (!$token || $token !== $cookie['sitoken']) die(json_encode($res));
if (!$cookie['sitrusted']) die(json_encode($res));

define('MODX_API_MODE', true);
require_once dirname(__FILE__, 5) . '/index.php';
require_once MODX_CORE_PATH . 'components/sendit/model/sendit/sendit.class.php';

$modx->getService('error', 'error.modError');
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);

$preset = $headers['x-sipreset'] ?: $headers['X-SIPRESET'];
$formName = $headers['x-siform'] ?: $headers['X-SIFORM'];
$action = $headers['x-siaction'] ?: $headers['X-SIACTION'];

$sendit = new SendIt($modx, (string)$preset, (string)$formName);

switch ($action) {
    case 'send':
        $res = $sendit->process();
        break;

    case 'preset':
        $res = $sendit->getPreset();
        break;
    case 'upload':
        $filename = $headers['x-upload-id'] ?: $headers['X-UPLOAD-ID'];
        $portionSize = $headers['x-portion-size'] ?: $headers['X-PORTION-SIZE'];
        $from = $headers['x-position-from'] ?: $headers['X-POSITION-FROM'];
        $filesize = $headers['x-file-size'] ?: $headers['X-FILE-SIZE'];
        $currentIndex = $headers['x-current-index'] ?: $headers['X-CURRENT-INDEX'];
        $loaded = $headers['x-loaded'] ?: $headers['X-LOADED'];
        $validate['success'] = true;
        if(!$from){
            $validate = $sendit->validateFile($filename, $filesize, (int)$loaded);
        }
        if($validate['success']){
            $res = $sendit->uploadFile($filename, $filesize, $portionSize, (float)$from, (int)$currentIndex);
        }else{
            $validate['data']['nextIndex'] = (int)$currentIndex + 1;
            $res = $sendit->error('', $validate['data']);
        }
        break;

    case 'removeDir':
        $res = $sendit->success('', []);
        $uploaddir = $sendit->basePath . $sendit->uploaddir . session_id() . '/';
        if (strpos($uploaddir, 'assets/') !== false) {
            $sendit->removeDir($uploaddir);
        }
        break;
    case 'removeFile':
        $path = MODX_BASE_PATH . $_POST['path'];
        if(file_exists($path) && strpos($path, session_id()) !== false){
            unlink($path);
        }
        $res = $sendit->success('Файл удалён.', ['path' => $_POST['path']]);
        break;
}


die($res);