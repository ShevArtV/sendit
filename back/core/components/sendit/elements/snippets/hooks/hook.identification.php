<?php
require_once MODX_CORE_PATH . 'components/sendit/services/identification.class.php';

if($scriptProperties['method']){
    $method = $scriptProperties['method'];
    $ajaxident = new Identification($modx,$hook,$scriptProperties);
    return $ajaxident->$method();
}
return true;
