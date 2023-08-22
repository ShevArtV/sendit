<?php
require_once MODX_CORE_PATH . 'components/sendit/model/sendit/sendit.class.php';

switch($modx->event->name){
    case 'OnLoadWebDocument':
        $sendit = new SendIt($modx);
        $sendit->loadCssJs();
        break;
    case 'OnHandleRequest':
        $sendit = new SendIt($modx);
        $cookies = $_COOKIE['SendIt'] ? json_decode($_COOKIE['SendIt'],1) : [];
        $uploaddir = $sendit->basePath . $sendit->uploaddir . session_id() . '/';
        if(strpos($uploaddir, 'assets/') !== false){
            $sendit->removeDir($uploaddir);
        }
        $data = [
            'sitoken' => md5($_SERVER['REMOTE_ADDR'].time()),
            'sitrusted' => '0',
            'simsgantispam' => $modx->lexicon('si_msg_antispam'),
            'sijsconfigpath' => $sendit->jsConfigPath
        ];
        $data = array_merge($cookies, $data);
        setcookie('SendIt', json_encode($data), '', '/');
        break;
}