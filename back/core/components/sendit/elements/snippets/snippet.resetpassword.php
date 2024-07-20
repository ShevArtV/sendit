<?php

$rp = !empty($_GET['rp']) ? $_GET['rp'] : false;
if ($rp) {
    $corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
    require_once $corePath . 'components/sendit/services/identification.class.php';
    $toPls = $scriptProperties['toPls'] ?? false;
    $userdata = Identification::resetPassword(Identification::base64url_decode($rp), $modx, $toPls);
    if (!empty($userdata['extended']['autologin'])) {
        if (Identification::loginWithoutPass($userdata['username'], $modx, $userdata['extended']['autologin']) && $userdata['extended']['autologin']['afterLoginRedirectId']) {
            $url = $userdata['extended']['autologin']['afterLoginRedirectId'];
            if ((int)$url > 0) {
                $url = $modx->makeUrl($userdata['extended']['autologin']['afterLoginRedirectId']);
            }
            if ($url) {
                $modx->sendRedirect($url);
            }
        }
    } else {
        return $userdata;
    }
}
