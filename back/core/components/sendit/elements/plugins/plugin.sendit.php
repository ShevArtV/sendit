<?php
require_once MODX_CORE_PATH . 'components/sendit/model/sendit/sendit.class.php';

switch ($modx->event->name) {
    case 'OnLoadWebDocument':
        SendIt::loadCssJs($modx);
        break;
    case 'OnHandleRequest':
        $alias = explode('.', basename($_REQUEST['q']));
        if (isset($alias[1]) && $alias[1] !== 'html') {
            return;
        }
        $modx->lexicon->load('sendit:default');
        unset($_SESSION['SendIt']['sendingLimits'], $_SESSION['sitoken']);
        $basePath = $modx->getOption('base_path');
        $uploaddir = $modx->getOption('si_uploaddir', '', '[[+asseetsUrl]]components/sendit/uploaded_files/');
        $uploaddir = str_replace('[[+asseetsUrl]]', MODX_ASSETS_PATH, $uploaddir);
        $uploaddir = $basePath . $uploaddir . session_id() . '/';
        $jsConfigPath = $modx->getOption('si_js_config_path', '', './sendit.inc.js');
        $cookies = !empty($_COOKIE['SendIt']) ? json_decode($_COOKIE['SendIt'], 1) : [];
        if (strpos($uploaddir, MODX_ASSETS_PATH) !== false) {
            SendIt::removeDir($uploaddir);
        }
        $data = [
            'sitoken' => md5($_SERVER['REMOTE_ADDR'] . time()),
            'sitrusted' => '0',           
            'sijsconfigpath' => $jsConfigPath
        ];

        $_SESSION['sitoken'] = $data['sitoken'];
        $data = array_merge($cookies, $data);
        setcookie('SendIt', json_encode($data), 0, '/');
        break;
}
