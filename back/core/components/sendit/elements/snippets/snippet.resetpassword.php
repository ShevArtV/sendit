<?php

$rp = !empty($_GET['rp']) ? $_GET['rp'] : false;
if ($rp) {
    $corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
    require_once $corePath . 'components/sendit/services/identification.class.php';
    require_once $corePath . 'components/sendit/services/redirect.class.php';
    $toPls = $scriptProperties['toPls'] ?? false;
    $userdata = Identification::resetPassword(Identification::base64url_decode($rp), $modx, $toPls);
    if (!empty($userdata['extended']['autologin'])) {
        if (Identification::loginWithoutPass($userdata['username'], $modx, $userdata['extended']['autologin']) && $userdata['extended']['autologin']['afterLoginRedirectId']) {
            if ($url = siRedirect::url($modx, $userdata['extended']['autologin']['afterLoginRedirectId'])) {
                $modx->sendRedirect($url);
            }
        }
    } else {
        return $userdata;
    }
}
