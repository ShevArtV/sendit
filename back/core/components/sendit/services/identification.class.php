<?php

/**
 *
 */
class Identification
{
    /**
     * @var modX
     */
    public \modX $modx;
    /**
     * @var array
     */
    public array $config;
    /**
     * @var object
     */
    public object $hook;
    /**
     * @var array
     */
    public array $values;
    /**
     * @var int
     */
    private int $version;

    /**
     * @param \modX $modx A reference to the Modx instance
     * @param array $config
     * @param object $hook
     */
    function __construct(\modX $modx, object $hook, array $config = array())
    {
        $this->modx = $modx;
        $this->config = $config;
        $this->hook = $hook;
        $this->values = $hook->getValues();
        $version = $modx->getVersionData();
        $this->version = (int)$version['version'];
    }

    public function generateUsername(): string
    {
        return 'user_' . time() . '_' . $this->generateCode($this->modx, 'code', 4);
    }

    /**
     * @return bool
     */
    public function register(): bool
    {
        $email = $this->values['email'] ?? '';
        $passwordField = !empty($this->config['passwordField']) ? $this->config['passwordField'] : 'password';
        $usernameField = !empty($this->config['usernameField']) ? $this->config['usernameField'] : 'username';

        if ($usernameField === 'username' && empty($this->values[$usernameField])) {
            $this->values[$usernameField] = $this->generateUsername();
        } else {
            $this->values['username'] = $this->values[$usernameField];
        }

        if (empty($this->values['email'])) {
            $this->values['email'] = ($this->values[$usernameField] ?? time()) . '@' . $this->modx->getOption('http_host');
        }

        $activation = $this->config['activation'] ?? false;
        $moderate = $this->config['moderate'] ?? false;
        $activationResourceId = $this->config['activationResourceId'] ?? $this->modx->getOption('site_start', '', 1);
        $userGroupsField = $this->config['usergroupsField'] ?? '';
        $defaultUserGroups = explode(',', $this->config['usergroups']) ?? [];
        $this->modx->user = $this->modx->getObject('modUser', 1);
        $userGroups = !empty($userGroupsField) && array_key_exists($userGroupsField, $this->values) ? $this->values[$userGroupsField] : $defaultUserGroups;
        if(is_string($userGroups)){
            $userGroups = explode(',', $userGroups);
        }
        if (!empty($userGroups)) {
            foreach ($userGroups as $k => $group) {
                $group = explode(':', $group);
                $this->values['groups'][] = array(
                    'usergroup' => $group[0],
                    'role' => $group[1] ?? 1,
                    'rank' => $group[2] ?? $k,
                );
            }
        }
        if (empty($this->values[$passwordField])) {
            $this->values[$passwordField] = $this->generateCode($this->modx, 'pass', 10);
        }

        $this->values['passwordgenmethod'] = 'none';
        $this->values['specifiedpassword'] = $this->values[$passwordField];
        $this->hook->setValue('password', $this->values[$passwordField]);
        $this->hook->setValue('username', $this->values[$usernameField]);
        $this->values['confirmpassword'] = $this->values[$passwordField];
        $this->values['passwordnotifymethod'] = 's';

        if (!$activation) {
            $this->values['active'] = 1;
        }

        if ($moderate) {
            $this->values['blocked'] = 1;
        }

        $extended = !empty($this->values['extended']) ? str_replace('&quot;', '"', $this->values['extended']) : '';
        $extended = $extended ? json_decode($extended, 1) : [];

        if ($this->config['autoLogin']) {
            $extended['autologin'] = [
                'rememberme' => $this->config['rememberme'] ?? 1,
                'authenticateContexts' => $this->config['authenticateContexts'] ?? 'web',
                'afterLoginRedirectId' => $this->config['afterLoginRedirectId'] ?? ''
            ];
        }

        $this->values['extended'] = $extended;

        $processorName = $this->version === 2 ? '/security/user/create' : 'Security/User/Create';
        $response = $this->modx->runProcessor($processorName, $this->values);

        if ($errors = $response->getFieldErrors()) {
            foreach ($errors as $error) {
                $key = $error->getField();
                if ($error->getField() === 'username') {
                    $key = $usernameField;
                }
                if (in_array($error->getField(), ['password', 'specifiedpassword'])) {
                    $key = $passwordField;
                }
                $this->hook->addError($key, $error->getMessage());
            }
            $this->modx->user = null;
            return false;
        }

        $this->modx->user = $this->modx->getObject('modUser', $response->response['object']['id']);

        if ($activation && !empty($email) && !empty($activationResourceId)) {
            $confirmUrl = $this->getConfirmUrl($activationResourceId);
            $this->hook->setValue('confirmUrl', $confirmUrl);
        }

        if ($this->config['autoLogin'] == true && !$activation && !$moderate) {
            $this->login();
        }
        return true;
    }

    /**
     * @param string $username
     * @param modX $modx
     * @param array|null $properties
     * @return bool
     */
    public static function loginWithoutPass(string $username, \modX $modx, ?array $properties = []): bool
    {
        $contexts = !empty($properties['authenticateContexts']) ? explode(',', $properties['authenticateContexts']) : ['web'];
        $q = $modx->newQuery('modUser');
        $q->leftJoin('modUserProfile', 'Profile');
        $q->select($modx->getSelectColumns('modUser', 'modUser', '', ['id', 'username', 'active']));
        $q->select($modx->getSelectColumns('modUserProfile', 'Profile', '', ['blocked']));
        $q->where(['modUser.username' => $username, 'modUser.active' => 1, 'Profile.blocked' => 0]);
        $user = $modx->getObject('modUser', $q);

        if (!$user) {
            $modx->log(1, "[Sendit|Identification::loginWithoutPass] Пользователь $username не существует, не активирован или заблокирован.");
            return false;
        }
        $session_id = session_id();
        foreach ($contexts as $ctx) {
            $user->addSessionContext($ctx);
        }
        $modx->user = $user;

        $modx->invokeEvent('OnWebLogin', array(
            'user' => $user,
            'attributes' => $properties['rememberme'] ?? 0,
            'lifetime' => $modx->getOption('session_gc_maxlifetime'),
            'loginContext' => $modx->context->key,
            'addContexts' => $properties['authenticateContexts'] ?? '',
            'session_id' => $session_id
        ));

        $user = $modx->getObject('modUser', $q);
        $profile = $user->getOne('Profile');
        $extended = $profile->get('extended');
        unset($extended['autologin']);
        $profile->set('extended', $extended);
        $profile->save();

        return true;
    }

    /**
     * @return bool
     */
    public function login(): bool
    {
        $contexts = $this->config['authenticateContexts'] ?? '';
        $passwordField = $this->config['passwordField'] ?? 'password';
        $usernameField = $this->config['usernameField'] ?? 'username';

        if (!$this->values[$usernameField] || !$this->values[$passwordField]) {
            $this->hook->addError($this->config['errorFieldName'], $this->modx->lexicon('si_msg_login_err'));
            return false;
        }

        if (!$username = $this->getUsername($usernameField, $this->values[$usernameField])) {
            $this->hook->addError($this->config['errorFieldName'], $this->modx->lexicon('si_msg_username_err'));
            return false;
        }

        $c = [
            'login_context' => $this->modx->context->key,
            'add_contexts' => $contexts,
            'username' => $username,
            'password' => $this->values[$passwordField],
            'rememberme' => $this->values['rememberme'] ?? 0,
        ];

        $processorName = $this->version === 2 ? '/security/login' : 'Security/Login';
        $response = $this->modx->runProcessor($processorName, $c);
        if ($response->getMessage()) {
            $this->hook->addError($this->config['errorFieldName'], $response->getMessage());
            return false;
        }
        return true;
    }

    public function getUsername(string $key, $value)
    {
        $userFields = $this->getTableColumns('modUser');
        if(in_array($key, $userFields)){
           $key = 'modUser.' . $key;
        }else{
            $profileFields = $this->getTableColumns('modUserProfile');
            if(in_array($key, $profileFields)){
                $key = 'Profile.' . $key;
            }
        }
        $q = $this->modx->newQuery('modUser');
        $q->leftJoin('modUserProfile', 'Profile');
        $q->where([$key => $value]);
        $q->select('username');
        $q->limit(1);
        $q->prepare();
        if ($q->stmt->execute()) {
            return $q->stmt->fetch(PDO::FETCH_COLUMN);
        }
        return '';
    }

    public function getTableColumns($className)
    {
        $tableName = $this->modx->getTableName($className);
        $tableName = str_replace('`', '\'', $tableName);
        $sql = "SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = $tableName";
        $stmt = $this->modx->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * @return bool
     */
    public function update(): bool
    {
        if (isset($this->values['uid'])) {
            $user = $this->modx->getObject('modUser', (int)$this->values['uid']);
        } else {
            $user = $this->modx->user;
        }

        if ($this->modx->user->isAuthenticated($this->modx->context->get('key'))) {
            $profile = $user->getOne('Profile');
            $profileData = $profile->toArray();
            $profileExtended = $profileData['extended'] ?? [];
            $extended = !empty($this->values['extended']) ? str_replace('&quot;', '"', $this->values['extended']) : '';
            $extended = $extended ? json_decode($extended, 1) : [];
            $this->values['extended'] = array_merge($profileExtended, $extended);
            $this->values['dob'] = !empty($this->values['dob']) ? strtotime($this->values['dob']) : $profile->get('dob');
            $userData = $user->toArray();
            unset($userData['password']);
            unset($userData['cachepwd']);

            $user->fromArray(array_merge($userData, $this->values));
            $profile->fromArray(array_merge($profileData, $this->values));
            $user->save();
            $profile->save();

            $this->modx->invokeEvent('siOnUserUpdate', array(
                'user' => $user,
                'profile' => $profile,
                'data' => $this->values
            ));
        }
        return true;
    }

    /**
     * @return bool
     */
    public function logout(): bool
    {
        $contexts = $this->config['authenticateContexts'] ?? '';
        $processorName = $this->version === 2 ? '/security/logout' : 'Security/Logout';
        $response = $this->modx->runProcessor($processorName, array(
            'login_context' => $this->modx->context->key,
            'add_contexts' => $contexts
        ));

        if ($response->getMessage()) {
            $this->hook->addError($this->config['errorFieldName'], $response->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function forgot(): bool
    {
        $usernameField = $this->config['usernameField'] ?? 'username';
        $username = $this->values[$usernameField];
        if ($usernameField !== 'username') {
            $username = $this->getUsername($usernameField, $this->values[$usernameField]);
        }
        $activationResourceId = $this->config['activationResourceId'] ?? $this->modx->getOption('site_start', '', 1);
        $user = $this->modx->getObject('modUser', ['username' => $username]);

        if ($user) {
            $profile = $user->getOne('Profile');
            if (!$profile->get('email')) {
                $this->hook->addError($this->config['errorFieldName'], $this->modx->lexicon('si_msg_no_email_err'));
                return false;
            }
            $extended = $profile->get('extended');
            $extended['activate_pass_before'] = time() + $this->config['activationUrlTime'] ?: time() + 60 * 60 * 3; // срок жизни ссылки на активацию
            $extended['temp_password'] = $this->generateCode($this->modx);
            if ($this->config['autoLogin']) {
                $extended['autologin'] = [
                    'rememberme' => $this->config['rememberme'] ?? 1,
                    'authenticateContexts' => $this->config['authenticateContexts'] ?? 'web',
                    'afterLoginRedirectId' => $this->config['afterLoginRedirectId'] ?? ''
                ];
            }
            $profile->set('extended', $extended);
            $profile->save();
            $confirmParams['rp'] = $this->base64url_encode($user->get('username'));
            $confirmUrl = $this->modx->makeUrl($activationResourceId, '', $confirmParams, 'full');
            $this->hook->setValue('password', $extended['temp_password']);
            $this->hook->setValue('email', $profile->get('email'));
            $this->hook->setValue('confirmUrl', $confirmUrl);
        }
        return true;
    }

    /**
     * @param modX $modx
     * @param string|null $type
     * @param int|null $length
     *
     * @return string
     */
    public static function generateCode(\modX $modx, ?string $type = 'pass', ?int $length = 0): string
    {
        if (!$length) {
            $length = $modx->getOption('password_min_length');
        }

        $result = '';

        switch ($type) {
            case 'pass':
                $arr = [
                    'a',
                    'b',
                    'c',
                    'd',
                    'e',
                    'f',
                    'g',
                    'h',
                    'i',
                    'j',
                    'k',
                    'l',
                    'm',
                    'n',
                    'o',
                    'p',
                    'q',
                    'r',
                    's',
                    't',
                    'u',
                    'v',
                    'w',
                    'x',
                    'y',
                    'z',
                    'A',
                    'B',
                    'C',
                    'D',
                    'E',
                    'F',
                    'G',
                    'H',
                    'I',
                    'J',
                    'K',
                    'L',
                    'M',
                    'N',
                    'O',
                    'P',
                    'Q',
                    'R',
                    'S',
                    'T',
                    'U',
                    'V',
                    'W',
                    'X',
                    'Y',
                    'Z',
                    '1',
                    '2',
                    '3',
                    '4',
                    '5',
                    '6',
                    '7',
                    '8',
                    '9',
                    '0',
                    '#',
                    '!',
                    "?"
                ];
                break;
            case 'hash':
                $arr = [
                    'a',
                    'b',
                    'c',
                    'd',
                    'e',
                    'f',
                    'g',
                    'h',
                    'i',
                    'j',
                    'k',
                    'l',
                    'm',
                    'n',
                    'o',
                    'p',
                    'q',
                    'r',
                    's',
                    't',
                    'u',
                    'v',
                    'w',
                    'x',
                    'y',
                    'z',
                    'A',
                    'B',
                    'C',
                    'D',
                    'E',
                    'F',
                    'G',
                    'H',
                    'I',
                    'J',
                    'K',
                    'L',
                    'M',
                    'N',
                    'O',
                    'P',
                    'Q',
                    'R',
                    'S',
                    'T',
                    'U',
                    'V',
                    'W',
                    'X',
                    'Y',
                    'Z',
                    '1',
                    '2',
                    '3',
                    '4',
                    '5',
                    '6',
                    '7',
                    '8',
                    '9',
                    '0'
                ];
                break;
            case 'code':
                $arr = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'];
                break;
        }

        for ($i = 0; $i < $length; $i++) {
            $result .= $arr[mt_rand(0, count($arr) - 1)];
        }

        return $result;
    }

    /**
     * @param integer $activationResourceId
     *
     * @return string
     */
    public function getConfirmUrl(int $activationResourceId): string
    {
        $confirmParams['lu'] = $this->base64url_encode($this->modx->user->get('username'));
        $profile = $this->modx->user->getOne('Profile');
        $extended = $profile->get('extended');
        $extended['activate_before'] = time() + $this->config['activationUrlTime'] ?? time() + 60 * 60 * 3;
        $profile->set('extended', $extended);
        $profile->save();
        return $this->modx->makeUrl($activationResourceId, '', $confirmParams, 'full');
    }

    /**
     * @param string $str
     * @return string
     */
    public function base64url_encode(string $str): string
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }

    /**
     * @param string $str
     * @return string
     */
    public static function base64url_decode(string $str): string
    {
        return base64_decode(str_pad(strtr($str, '-_', '+/'), strlen($str) % 4, '=', STR_PAD_RIGHT));
    }


    /**
     * @param string $username
     * @param modX $modx
     * @param string|null $toPls
     * @return array
     */
    public static function activateUser(string $username, \modX $modx, ?string $toPls = ''): array
    {
        $userData = [];
        if ($user = $modx->getObject('modUser', array('username' => $username))) {
            $profile = $user->getOne('Profile');
            $extended = $profile->get('extended');

            if (!$user->get('active') && $extended['activate_before'] - time() <= 0) {
                $user->remove();
                return $userData;
            }

            $userData = array_merge($profile->toArray(), $user->toArray());

            if ($extended['activate_before'] - time() > 0) {
                $user->set('active', 1);
                $user->save();
                unset($extended['activate_before']);
                $profile->set('extended', $extended);
                $profile->save();
            }

            $modx->invokeEvent('OnUserActivate', array(
                'user' => $user,
                'profile' => $profile,
                'data' => $userData
            ));

            if ($toPls && $userData) {
                $modx->setPlaceholder($toPls, $userData);
            }
            return $userData;
        }
    }

    /**
     * @param string $username
     * @param modX $modx
     * @param string|null $toPls
     * @return array
     */
    public static function resetPassword(string $username, \modX $modx, ?string $toPls = '')
    {
        $user = $modx->getObject('modUser', array('username' => $username));
        if ($user) {
            $profile = $user->getOne('Profile');
            $extended = $profile->get('extended');
            $password = $extended['temp_password'] ?? '';
            $activateBefore = $extended['activate_pass_before'] ?? 0;
            unset($extended['activate_pass_before'], $extended['temp_password']);
            $profile->set('extended', $extended);
            $profile->save();

            if ($activateBefore - time() <= 0) {
                return [];
            }
            if ($password) {
                $user->set('password', $password);
                $user->save();
            }
            $userData = array_merge($profile->toArray(), $user->toArray());

            if ($toPls && $userData) {
                $modx->setPlaceholder($toPls, $userData);
            }

            return $userData;
        }
        return [];
    }
}
