<?php
if ($_GET['lu']) {
    require_once MODX_CORE_PATH . 'components/sendit/model/sendit/ajaxidentification.class.php';
    return AjaxIdentification::activateUser(AjaxIdentification::base64url_decode($_GET['lu']), $modx);
}
return false;