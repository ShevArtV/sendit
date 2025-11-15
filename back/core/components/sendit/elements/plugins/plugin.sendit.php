<?php

/**
 * @var \modX $modx
 */
$corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
require_once $corePath . 'components/sendit/services/sendit.class.php';

switch ($modx->event->name) {
    case 'OnHandleRequest':
        if(!$_COOKIE['siSession']){
            $_COOKIE['siSession'] = SendIt::getSessionId($modx);
            $expires = time() + $modx->getOption('si_storage_time', '', 86400);
            setcookie('siSession', $_COOKIE['siSession'], $expires, '/', '', false, true);
        }
        break;
    case 'OnMODXInit':
        SendIt::clearSession($modx);
        break;
    case 'OnWebPageInit':
        $alias = !empty($_REQUEST['q']) ? explode('.', basename($_REQUEST['q'])) : [];
        if (isset($alias[1]) && $alias[1] !== 'html') {
            return;
        }

        $modx->lexicon->load('sendit:default');
        $jsConfigPath = $modx->getOption('si_js_config_path', '', './sendit.inc.js');
        $cookies = !empty($_COOKIE['SendIt']) ? json_decode($_COOKIE['SendIt'], 1) : [];

        $data = [
            'simsgantispam' => $modx->lexicon('si_msg_trusted_err'),
            'sitoken' => md5($_SERVER['REMOTE_ADDR'] . time()),
            'sitrusted' => '0',
            'sijsconfigpath' => $jsConfigPath
        ];
        SendIt::setSession($modx, [
            'sitoken' => $data['sitoken'],
            'sendingLimits' => []
        ]);

        $data = array_merge($cookies, $data);
        setcookie('SendIt', json_encode($data), 0, '/');

        break;
    case 'OnLoadWebDocument':
        $SendIt = new SendIt($modx);
        $SendIt->loadCssJs();
        break;
}
