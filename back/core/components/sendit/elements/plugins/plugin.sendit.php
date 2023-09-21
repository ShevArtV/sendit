<?php
require_once MODX_CORE_PATH . 'components/sendit/model/sendit/sendit.class.php';

switch($modx->event->name){
    case 'OnLoadWebDocument':
        SendIt::loadCssJs($modx);
        break;
    case 'OnHandleRequest':
        unset($_SESSION['SendIt']['sendingLimits']);
        $basePath = $modx->getOption('base_path');
        $uploaddir = $modx->getOption('si_uploaddir', '', '/assets/components/sendit/uploaded_files/');
        $jsConfigPath = $modx->getOption('si_js_config_path', '', './sendit.inc.js');
        $cookies = $_COOKIE['SendIt'] ? json_decode($_COOKIE['SendIt'],1) : [];
        $uploaddir = $basePath . $uploaddir . session_id() . '/';
        if(strpos($uploaddir, 'assets/') !== false){
            SendIt::removeDir($uploaddir);
        }
        $data = [
            'sitoken' => md5($_SERVER['REMOTE_ADDR'].time()),
            'sitrusted' => '0',
            'simsgantispam' => $modx->lexicon('si_msg_antispam'),
            'sijsconfigpath' => $jsConfigPath
        ];
        $data = array_merge($cookies, $data);
        setcookie('SendIt', json_encode($data), 0, '/');
        break;
}