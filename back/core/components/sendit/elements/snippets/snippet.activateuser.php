<?php
if ($_GET['lu']) {
    require_once MODX_CORE_PATH . 'components/sendit/model/sendit/identification.class.php';
    $userdata = Identification::activateUser(Identification::base64url_decode($_GET['lu']), $modx, $toPls);
    if(!empty($userdata['extended']['autologin'])){
        if(Identification::loginWithoutPass($userdata['username'], $modx, $userdata['extended']['autologin']) && $userdata['extended']['autologin']['afterLoginRedirectId']){
            $url = $modx->makeUrl($userdata['extended']['autologin']['afterLoginRedirectId']);
            $modx->sendRedirect($url);
        }else{
            return $userdata;
        }
    }
}
return false;