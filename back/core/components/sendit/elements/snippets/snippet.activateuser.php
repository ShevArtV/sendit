<?php
if ($_GET['lu']) {
    require_once MODX_CORE_PATH . 'components/sendit/model/sendit/identification.class.php';
    return Identification::activateUser(Identification::base64url_decode($_GET['lu']), $modx, $toPls);
}
return false;