<?php

/** @var pdoTools $pdo */
if (class_exists('pdoTools') && $pdo = $modx->getService('pdoTools')) {
    $content = $pdo->parseChunk($scriptProperties['tpl'], $scriptProperties);
} else {
    $content = $modx->parseChunk($scriptProperties['tpl'], $scriptProperties);
}
$session = SendIt::getSession($modx);
$session['presets'][$scriptProperties['presetName']] = $scriptProperties;
SendIt::setSession($modx, $session);

return $content;
