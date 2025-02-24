<?php

$version = $modx->getVersionData();
if ((int)$version['version'] === 3) {
    $parser = $modx->services->has('pdoTools') ? $modx->services->get('pdoTools') : $modx;
} else {
    $parser = $modx->getService('pdoTools') ?: $modx;
}
$tpl = $scriptProperties['tpl'] ?? $scriptProperties['form'] ?? '';
$presetName = $scriptProperties['presetName'] ?? '';
$content = $parser->parseChunk($tpl, $scriptProperties);
$session = SendIt::getSession($modx);
$session['presets'][$presetName] = $scriptProperties;
SendIt::setSession($modx, $session);

return $content;
