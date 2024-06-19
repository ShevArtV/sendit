<?php
if ($_GET['lu']) {
    $corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
    require_once $corePath . 'components/sendit/services/identification.class.php';
    $userdata = Identification::activateUser(Identification::base64url_decode($_GET['lu']), $modx, $toPls);
    if(!empty($userdata['extended']['autologin'])){
        if(Identification::loginWithoutPass($userdata['username'], $modx, $userdata['extended']['autologin']) && $userdata['extended']['autologin']['afterLoginRedirectId']){
            $url = $userdata['extended']['autologin']['afterLoginRedirectId'];
            if((int)$url > 0){
                $url = $modx->makeUrl($userdata['extended']['autologin']['afterLoginRedirectId']);
            }
            if($url){
                $modx->sendRedirect($url);
            }
        }else{
            return $userdata;
        }
    }
}
return false;
