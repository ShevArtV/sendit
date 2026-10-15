<?php

include_once __DIR__ . '/authcode.class.php';
include_once dirname(__DIR__) . '/identification.class.php';

/**
 * Вход и регистрация по одноразовому коду (хук siCodeIdentification, method login|register).
 * Код проверяется здесь заново и гасится: без этого пресет без валидатора siCheckCode
 * пускал бы в любой аккаунт по одному номеру телефона.
 */
class siCodeIdentification
{
    private \modX $modx;
    private object $hook;
    private array $config;
    private array $values;
    private siAuthCode $codes;

    public function __construct(\modX $modx, object $hook, array $config = [])
    {
        $this->modx = $modx;
        $this->hook = $hook;
        $this->config = array_merge(['codeField' => 'phone', 'codeInputField' => 'code', 'errorFieldName' => 'errorLogin'], $config);
        $this->values = $hook->getValues();
        $this->codes = new siAuthCode($modx, $this->config);
        $modx->lexicon->load('sendit:default');
    }

    /**
     * @return bool
     */
    public function login(): bool
    {
        $field = $this->config['codeField'];
        $recipient = (string)($this->values[$field] ?? '');
        $username = self::findUsername($this->modx, $field, $recipient);
        if (!$username) {
            $this->hook->addError($field, $this->modx->lexicon('si_msg_code_user_nf'));
            return false;
        }
        if (!$this->checkCode($recipient)) {
            return false;
        }
        if (!Identification::loginWithoutPass($username, $this->modx, $this->config)) {
            $this->hook->addError($this->config['errorFieldName'], $this->modx->lexicon('si_msg_login_err'));
            return false;
        }
        $this->modx->invokeEvent('siOnCodeLogin', ['method' => 'login', 'user' => $this->modx->user, 'recipient' => $recipient]);
        return true;
    }

    /**
     * Регистрация через Identification::register(): пароль генерируется, вход — по autoLogin пресета.
     * @return bool
     */
    public function register(): bool
    {
        $field = $this->config['codeField'];
        $recipient = (string)($this->values[$field] ?? '');
        if (self::findUsername($this->modx, $field, $recipient)) {
            $this->hook->addError($field, $this->modx->lexicon('si_msg_code_user_exists_err'));
            return false;
        }
        if (!$this->checkCode($recipient)) {
            return false;
        }
        if (!(new Identification($this->modx, $this->hook, $this->config))->register()) {
            return false;
        }
        $this->modx->invokeEvent('siOnCodeLogin', ['method' => 'register', 'user' => $this->modx->user, 'recipient' => $recipient]);
        return true;
    }

    /**
     * Пользователь по полю modUser или modUserProfile (username, email, phone, mobilephone...).
     * @param \modX $modx
     * @param string $field
     * @param string $value
     * @return string username или пустая строка
     */
    public static function findUsername(\modX $modx, string $field, string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (array_key_exists($field, $modx->getFields('modUser'))) {
            $column = 'modUser.' . $field;
        } elseif (array_key_exists($field, $modx->getFields('modUserProfile'))) {
            $column = 'Profile.' . $field;
        } else {
            return '';
        }
        $q = $modx->newQuery('modUser');
        $q->leftJoin('modUserProfile', 'Profile');
        $value = trim($value);
        if (preg_match('/^[\d\s()+\-]+$/', $value)) {
            // телефон сравнивается цифрами: маска ввода и маска в профиле могут не совпадать
            $stripped = $column;
            foreach ([' ', '-', '(', ')', '+'] as $char) {
                $stripped = "REPLACE({$stripped}, '{$char}', '')";
            }
            $quoted = array_map([$modx, 'quote'], self::phoneVariants(siAuthCode::normalize($value)));
            $q->where($stripped . ' IN (' . implode(',', $quoted) . ')');
        } else {
            $q->where([$column => $value]);
        }
        $q->select('modUser.username');
        $q->limit(1);
        return ($q->prepare() && $q->stmt->execute()) ? (string)$q->stmt->fetchColumn() : '';
    }

    /**
     * Российский номер мог быть сохранён и с 7, и с 8 в начале.
     * @param string $digits
     * @return array
     */
    private static function phoneVariants(string $digits): array
    {
        if (strlen($digits) === 11 && in_array($digits[0], ['7', '8'], true)) {
            return ['7' . substr($digits, 1), '8' . substr($digits, 1)];
        }
        return [$digits];
    }

    /**
     * @param string $recipient
     * @return bool
     */
    private function checkCode(string $recipient): bool
    {
        $input = $this->config['codeInputField'];
        if (!$this->codes->verify($recipient, (string)($this->values[$input] ?? ''), true)) {
            $this->hook->addError($input, $this->codes->getError());
            return false;
        }
        return true;
    }
}
