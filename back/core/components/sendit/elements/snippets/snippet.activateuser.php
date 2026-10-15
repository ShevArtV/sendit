<?php

$lu = !empty($_GET['lu']) ? $_GET['lu'] : false;
if ($lu) {
    $corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
    require_once $corePath . 'components/sendit/services/identification.class.php';
    require_once $corePath . 'components/sendit/services/redirect.class.php';
    $toPls = $scriptProperties['toPls'] ?? false;
    $userdata = Identification::activateUser(Identification::base64url_decode($lu), $modx, $toPls);
    if (!empty($userdata['extended']['autologin'])) {
        if (Identification::loginWithoutPass($userdata['username'], $modx, $userdata['extended']['autologin']) && $userdata['extended']['autologin']['afterLoginRedirectId']) {
            if ($url = siRedirect::url($modx, $userdata['extended']['autologin']['afterLoginRedirectId'])) {
                $modx->sendRedirect($url);
            }
        } else {
            return $userdata;
        }
    }
}
return false;
