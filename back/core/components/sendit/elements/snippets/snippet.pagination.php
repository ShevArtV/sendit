<?php
$isAjax = false;
if($SendIt instanceof SendIt){
    $isAjax = true;
}else{
    $session = SendIt::getSession($modx);
    $session['presets'][$scriptProperties['presetName']] = $scriptProperties;
    SendIt::setSession($modx, [
        'presets' => $session['presets']
    ]);
    require_once MODX_CORE_PATH . 'components/sendit/services/sendit.class.php';
    $SendIt = new SendIt($modx, $scriptProperties['presetName'], $scriptProperties['formName']);
}

$response = $SendIt->paginationHandler();

if(!$isAjax){
    $modx->setPlaceholder($response['data']['pagination'].'.totalPages', $response['data']['totalPages']);
    $modx->setPlaceholder($response['data']['pagination'].'.limit', $response['data']['limit']);
    $modx->setPlaceholder($response['data']['pagination'].'.currentPage', $response['data']['currentPage']);
}
return $isAjax ? $response : $response['data']['html'];
