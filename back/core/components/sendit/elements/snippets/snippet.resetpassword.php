<?php

if ($_GET['rp']) {
    require_once MODX_CORE_PATH . 'components/sendit/model/sendit/identification.class.php';
    $userdata = Identification::resetPassword(Identification::base64url_decode($_GET['rp']), $modx);
    if(!empty($userdata['extended']['autologin'])){
        if(Identification::loginWithoutPass($userdata['username'], $modx, $userdata['extended']['autologin']) && $userdata['extended']['autologin']['afterLoginRedirectId']){
            $url = $userdata['extended']['autologin']['afterLoginRedirectId'];
            if((int)$url > 0){
                $url = $modx->makeUrl($userdata['extended']['autologin']['afterLoginRedirectId']);
            }
            if($url){
                $modx->sendRedirect($url);
            }
        }
    }
}