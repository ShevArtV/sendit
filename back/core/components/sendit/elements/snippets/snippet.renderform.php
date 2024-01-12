<?php

/** @var pdoTools $pdo */
if (class_exists('pdoTools') && $pdo = $modx->getService('pdoTools')) {
    $content = $pdo->parseChunk($scriptProperties['tpl'], $scriptProperties);
} else {
    $content = $modx->parseChunk($scriptProperties['tpl'], $scriptProperties);
}

$_SESSION['SendIt']['presets'][$scriptProperties['presetName']] = $scriptProperties;

return $content;
