<?php
/**
 * Выпуск и отправка одноразового кода входа: 'snippet' => 'siSendCode' в пресете.
 * codeMode: login — только существующему пользователю, register — только новому, пусто — любому.
 *
 * @var \modX $modx
 * @var array $scriptProperties
 */
$SendIt = $scriptProperties['SendIt'] ?? null;
if (!$SendIt instanceof SendIt) {
    return '';
}
$corePath = $modx->getOption('core_path', null, MODX_CORE_PATH);
require_once $corePath . 'components/sendit/services/auth/codeidentification.class.php';
unset($scriptProperties['SendIt']);
$modx->lexicon->load('sendit:default');

$field = $scriptProperties['codeField'] ?? 'phone';
$recipient = trim((string)($_POST[$field] ?? ''));
$mode = $scriptProperties['codeMode'] ?? '';
if ($recipient && $mode) {
    $exists = siCodeIdentification::findUsername($modx, $field, $recipient) !== '';
    if ($mode === 'login' && !$exists) {
        return $SendIt->error('', ['errors' => [$field => $modx->lexicon('si_msg_code_user_nf')]]);
    }
    if ($mode === 'register' && $exists) {
        return $SendIt->error('', ['errors' => [$field => $modx->lexicon('si_msg_code_user_exists_err')]]);
    }
}

$codes = new siAuthCode($modx, $scriptProperties);
if (!$codes->issue($recipient)) {
    return $SendIt->error($codes->getError());
}
return $SendIt->success($scriptProperties['successMessage'] ?? $modx->lexicon('si_msg_code_sent'), ['codeSent' => 1]);
