<?php

include_once dirname(__DIR__) . '/siLogger.class.php';
include_once dirname(__DIR__) . '/sendit.class.php';
include_once __DIR__ . '/codesender.interface.php';

/**
 * Одноразовые коды входа: выпуск, доставка через siCodeSender проекта, проверка.
 *
 * Код хранится хэшем в si_sessions (class_name siAuthCode) под ключом получателя, а не сессии:
 * лимиты попыток и отправок не обходятся сменой cookie.
 */
class siAuthCode
{
    public const CLASS_NAME = 'siAuthCode';

    private \modX $modx;
    private array $params;
    private string $error = '';

    public function __construct(\modX $modx, array $params = [])
    {
        $this->modx = $modx;
        $this->params = array_merge([
            'codeField' => 'phone',
            'codeLength' => 4,
            'codeTtl' => 300,
            'codeResendDelay' => 60,
            'codeAttempts' => 5,
            'codeHourlyLimit' => 5,
        ], array_filter($params, static function ($value) {
            return $value !== '' && $value !== null;
        }));
        $this->modx->lexicon->load('sendit:default');
        if (!isset($this->modx->packages['sendit'])) {
            $this->modx->addPackage('sendit', $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/sendit/model/');
        }
    }

    /**
     * @return string
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Ключ получателя: телефон сводится к цифрам (российский 8XXXXXXXXXX — к 7XXXXXXXXXX,
     * иначе лимиты обходились бы сменой записи номера), остальное — к нижнему регистру.
     * @param string $recipient
     * @return string
     */
    public static function normalize(string $recipient): string
    {
        $recipient = mb_strtolower(trim($recipient));
        if (!preg_match('/^[\d\s()+\-]+$/', $recipient)) {
            return $recipient;
        }
        $digits = preg_replace('/\D+/', '', $recipient);
        return strlen($digits) === 11 && $digits[0] === '8' ? '7' . substr($digits, 1) : $digits;
    }

    /**
     * Выпускает код и отдаёт его отправщику проекта.
     * @param string $recipient
     * @return bool
     */
    public function issue(string $recipient): bool
    {
        if (!$key = self::normalize($recipient)) {
            return $this->fail('si_msg_code_recipient_err');
        }
        $this->removeExpired();
        $data = $this->load($key);
        $now = time();
        if (!empty($data['sentAt']) && $now - $data['sentAt'] < (int)$this->params['codeResendDelay']) {
            return $this->fail('si_msg_code_delay_err', ['seconds' => (int)$this->params['codeResendDelay'] - ($now - $data['sentAt'])]);
        }
        $sends = array_values(array_filter($data['sends'] ?? [], static function ($time) use ($now) {
            return $now - $time < 3600;
        }));
        if (count($sends) >= (int)$this->params['codeHourlyLimit']) {
            return $this->fail('si_msg_code_limit_err');
        }
        if (!$sender = $this->getSender()) {
            return $this->fail('si_msg_code_sender_err');
        }

        $code = $this->generate((int)$this->params['codeLength']);
        if (is_object($this->modx->event)) {
            $this->modx->event->returnedValues = null;
        }
        $this->modx->invokeEvent('siOnBeforeSendCode', [
            'recipient' => $recipient,
            'code' => $code,
            'params' => $this->params,
        ]);
        $returned = is_object($this->modx->event) ? ($this->modx->event->returnedValues ?? []) : [];
        if (!empty($returned['error'])) {
            return $this->fail((string)$returned['error']);
        }
        $code = (string)($returned['code'] ?? $code);

        if (!$sender->send($recipient, $code, $this->params)) {
            (new siLogger($this->modx))->write('Отправщик не доставил код', ['class' => get_class($sender), 'error' => $sender->getError()], 'error', 'auth');
            return $this->fail($sender->getError() ?: 'si_msg_code_send_err');
        }
        $sends[] = $now;
        $this->save($key, [
            'hash' => $this->hash($key, $code),
            'expires' => $now + (int)$this->params['codeTtl'],
            'attempts' => 0,
            'sentAt' => $now,
            'sends' => $sends,
        ]);
        return true;
    }

    /**
     * Проверяет код; неверный ввод расходует попытку, после последней код сгорает.
     * @param string $recipient
     * @param string $code
     * @param bool $consume погасить код после успешной проверки
     * @return bool
     */
    public function verify(string $recipient, string $code, bool $consume = false): bool
    {
        $key = self::normalize($recipient);
        $data = $key ? $this->load($key) : [];
        if (empty($data['hash']) || $data['expires'] < time()) {
            return $this->fail('si_msg_code_expired_err');
        }
        if (!hash_equals($data['hash'], $this->hash($key, trim($code)))) {
            $data['attempts'] = (int)$data['attempts'] + 1;
            if ($data['attempts'] >= (int)$this->params['codeAttempts']) {
                $data['hash'] = '';
            }
            $this->save($key, $data);
            return $this->fail($data['hash'] ? 'si_msg_code_wrong_err' : 'si_msg_code_expired_err');
        }
        if ($consume) {
            $data['hash'] = '';
            $this->save($key, $data);
        }
        return true;
    }

    /**
     * @return siCodeSender|null
     */
    private function getSender(): ?siCodeSender
    {
        $class = (string)($this->params['codeSender'] ?? '') ?: (string)$this->modx->getOption('si_code_sender', null, '');
        $path = (string)($this->params['codeSenderPath'] ?? '') ?: (string)$this->modx->getOption('si_code_sender_path', null, '');
        if ($path) {
            $path = str_replace(['{core_path}', '{base_path}'], [MODX_CORE_PATH, MODX_BASE_PATH], $path);
            if (is_file($path)) {
                include_once $path;
            }
        }
        if (!$class || !class_exists($class) || !is_subclass_of($class, 'siCodeSender')) {
            (new siLogger($this->modx))->write('Отправщик кода не найден или не реализует siCodeSender', ['class' => $class, 'path' => $path], 'error', 'auth');
            return null;
        }
        return new $class($this->modx, $this->params);
    }

    /**
     * @param int $length
     * @return string
     */
    private function generate(int $length): string
    {
        $length = max(4, min(10, $length));
        return str_pad((string)random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * @param string $key
     * @param string $code
     * @return string
     */
    private function hash(string $key, string $code): string
    {
        return hash_hmac('sha256', $key . '|' . $code, (string)$this->modx->site_id);
    }

    /**
     * @param string $key
     * @return array
     */
    private function load(string $key): array
    {
        return SendIt::getSession($this->modx, $this->sessionKey($key), self::CLASS_NAME);
    }

    /**
     * @param string $key
     * @param array $data
     * @return void
     */
    private function save(string $key, array $data): void
    {
        unset($data['session_id']);
        SendIt::setSession($this->modx, $data, $this->sessionKey($key), self::CLASS_NAME);
    }

    /**
     * @param string $key
     * @return string
     */
    private function sessionKey(string $key): string
    {
        return 'code_' . md5($key);
    }

    /**
     * Записи старше часа: дольше не живут ни код, ни счётчик отправок.
     * @return void
     */
    private function removeExpired(): void
    {
        $this->modx->removeCollection('siSession', [
            'class_name' => self::CLASS_NAME,
            'createdon:<' => date('Y-m-d H:i:s', time() - max(3600, (int)$this->params['codeTtl'])),
        ]);
    }

    /**
     * @param string $message ключ лексикона или готовый текст
     * @param array $placeholders
     * @return bool
     */
    private function fail(string $message, array $placeholders = []): bool
    {
        $this->error = $this->modx->lexicon($message, $placeholders);
        return false;
    }
}
