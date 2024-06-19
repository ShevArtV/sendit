<?php
$corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
require_once $corePath . 'components/sendit/services/identification.class.php';

if($scriptProperties['method']){
    $method = $scriptProperties['method'];
    $ajaxident = new Identification($modx,$hook,$scriptProperties);
    return $ajaxident->$method();
}
return true;
