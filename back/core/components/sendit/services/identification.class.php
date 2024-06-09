<?php

class Identification
{

    /**
     * @param modX $modx A reference to the Modx instance
     * @param array $config
     * @param object $hook
     */
    function __construct(modX $modx, object $hook, array $config = array())
    {
        $this->modx = $modx;
        $this->config = $config;
        $this->hook = $hook;
        $this->values = $hook->getValues();
    }


    /**
     * @return mixed
     */
    public function register()
    {
        $email = $this->values['email'];

        $passwordField = $this->config['passwordField'] ?: 'password';
        $usernameField = $this->config['usernameField'] ?: 'username';

        if (!$this->values['email']) {
            $this->values['email'] = $this->values[$usernameField] . '@' . $this->modx->getOption('http_host');
        }

        $activation = $this->config['activation'];
        $moderate = $this->config['moderate'];
        $activationResourceId = $this->config['activationResourceId'] ?: 1;
        $userGroupsField = $this->config['usergroupsField'] ?: '';
        $this->modx->user = $this->modx->getObject('modUser', 1);
        $userGroups = !empty($userGroupsField) && array_key_exists($userGroupsField, $this->values) ? $this->values[$userGroupsField] : explode(
            ',',
            $this->config['usergroups']
        );
        if ($userGroups) {
            foreach ($userGroups as $k => $group) {
                $group = explode(':', $group);
                $this->values['groups'][] = array(
                    'usergroup' => $group[0],
                    'role' => $group[1] ?: 1,
                    'rank' => $group[2] ?: $k,
                );
            }
        }
        if (!$this->values[$passwordField]) {
            $this->values[$passwordField] = $this->generateCode($this->modx, 'pass', 10);
        }

        $this->values['passwordgenmethod'] = 'none';
        $this->values['specifiedpassword'] = $this->values[$passwordField];
        $this->hook->setValue('password', $this->values[$passwordField]);
        $this->hook->setValue('username', $this->values[$usernameField]);
        $this->values['confirmpassword'] = $this->values[$passwordField];
        $this->values['username'] = $this->values[$usernameField];
        $this->values['passwordnotifymethod'] = 's';

        if (!$activation) {
            $this->values['active'] = 1;
        }

        if ($moderate) {
            $this->values['blocked'] = 1;
        }

        $extended = str_replace('&quot;', '"', $this->values['extended']);
        $extended = $extended ? json_decode($extended, 1) : [];

        if ($this->config['autoLogin']) {
            $extended['autologin'] = [
                'rememberme' => $this->config['rememberme'] ?: 1,
                'authenticateContexts' => $this->config['authenticateContexts'] ?: 'web',
                'afterLoginRedirectId' => $this->config['afterLoginRedirectId']
            ];
        }

        $this->values['extended'] = $extended;

        $response = $this->modx->runProcessor('/security/user/create', $this->values);

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

    public static function loginWithoutPass($username, $modx, $properties = [])
    {
        $contexts = $properties['authenticateContexts'] ? explode(',', $properties['authenticateContexts']) : ['web'];
        $q = $modx->newQuery('modUser');
        $q->leftJoin('modUserProfile', 'Profile');
        $q->select($modx->getSelectColumns('modUser', 'modUser', '', array('id', 'username', 'active')));
        $q->select($modx->getSelectColumns('modUserProfile', 'Profile', '', array('blocked')));
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
            'attributes' => $properties['rememberme'],
            'lifetime' => $modx->getOption('session_gc_maxlifetime'),
            'loginContext' => $modx->context->key,
            'addContexts' => $properties['authenticateContexts'],
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

    public function login()
    {
        $contexts = $this->config['authenticateContexts'];
        $passwordField = $this->config['passwordField'] ?: 'password';
        $usernameField = $this->config['usernameField'] ?: 'username';

        if (!$this->values[$usernameField] || !$this->values[$passwordField]) {
            return false;
        }

        $c = array(
            'login_context' => $this->modx->context->key,
            'add_contexts' => $contexts,
            'username' => $this->values[$usernameField],
            'password' => $this->values[$passwordField],
            'rememberme' => $this->values['rememberme'],
        );

        $response = $this->modx->runProcessor('/security/login', $c);
        if ($response->getMessage()) {
            $this->hook->addError($this->config['errorFieldName'], $response->getMessage());
            return false;
        }
        return true;
    }

    public function update()
    {
        if ((int)$this->values['uid']) {
            $user = $this->modx->getObject('modUser', (int)$this->values['uid']);
        } else {
            $user = $this->modx->user;
        }

        if ($this->modx->user->isAuthenticated($this->modx->context->get('key'))) {
            $profile = $user->getOne('Profile');
            $profileData = $profile->toArray();
            $extended = str_replace('&quot;', '"', $this->values['extended']);
            $extended = $extended ? json_decode($extended, 1) : [];
            $this->values['extended'] = array_merge($profileData['extended'] ?? [], $extended);
            $this->values['dob'] = $this->values['dob'] ? strtotime($this->values['dob']) : $profile->get('dob');
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

    public function logout()
    {
        $contexts = $this->config['authenticateContexts'];
        $response = $this->modx->runProcessor('/security/logout', array(
            'login_context' => $this->modx->context->key,
            'add_contexts' => $contexts
        ));

        if ($response->getMessage()) {
            $this->hook->addError($this->config['errorFieldName'], $response->getMessage());
            return false;
        }
        return true;
    }

    public function forgot()
    {
        $usernameField = $this->config['usernameField'] ?: 'username';
        $activationResourceId = $this->config['activationResourceId'] ?: 1;
        $user = $this->modx->getObject('modUser', ['username' => $this->values[$usernameField]]);

        if ($user) {
            $profile = $user->getOne('Profile');
            if ($profile->get('email')) {
                $this->hook->addError($this->config['errorFieldName'], $this->modx->lexicon('si_msg_no_email_err'));
                return false;
            }
            $extended = $profile->get('extended');
            $extended['activate_pass_before'] = time() + $this->config['activationUrlTime'] ?: time() + 60 * 60 * 3; // срок жизни ссылки на активацию
            $extended['temp_password'] = $this->generateCode($this->modx);
            if ($this->config['autoLogin']) {
                $extended['autologin'] = [
                    'rememberme' => $this->config['rememberme'] ?: 1,
                    'authenticateContexts' => $this->config['authenticateContexts'] ?: 'web',
                    'afterLoginRedirectId' => $this->config['afterLoginRedirectId']
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
     * @param string $type
     * @param integer $length
     *
     * @return string
     */
    public static function generateCode($modx, $type = 'pass', $length = 0)
    {
        if (!$length) {
            $length = $modx->getOption('password_min_length');
        }

        $password = "";
        /* Массив со всеми возможными символами в пароле */
        switch ($type) {
            case 'pass':
                $arr = array(
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
                    "?",
                    "&"
                );
                break;
            case 'hash':
                $arr = array(
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
                );
                break;
            case 'code':
                $arr = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
                break;
        }

        for ($i = 0; $i < $length; $i++) {
            $password .= $arr[mt_rand(0, count($arr) - 1)]; // Берём случайный элемент из массива
        }

        return $password;
    }

    /**
     * @param integer $activationResourceId
     *
     * @return string
     */
    public function getConfirmUrl($activationResourceId)
    {
        $confirmParams['lu'] = $this->base64url_encode($this->modx->user->get('username'));
        $profile = $this->modx->user->getOne('Profile');
        $extended = $profile->get('extended');
        $extended['activate_before'] = time() + $this->config['activationUrlTime'] ?: time() + 60 * 60 * 3; // срок жизни ссылки на активацию
        $profile->set('extended', $extended);
        $profile->save();
        return $this->modx->makeUrl($activationResourceId, '', $confirmParams, 'full');
    }

    /**
     * Encodes a string for URL safe transmission
     *
     * @access public
     * @param string $str
     * @return string
     */
    public function base64url_encode($str)
    {
        return rtrim(strtr(base64_encode($str), '+/', '-_'), '=');
    }

    /**
     * Decodes an URL safe encoded string
     *
     * @access public
     * @param string $str
     * @return string
     */
    public static function base64url_decode($str)
    {
        return base64_decode(str_pad(strtr($str, '-_', '+/'), strlen($str) % 4, '=', STR_PAD_RIGHT));
    }


    public static function activateUser($username, $modx, $toPls = '')
    {
        $userData = false;
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

    public static function resetPassword($username, $modx)
    {
        $user = $modx->getObject('modUser', array('username' => $username));
        if ($user) {
            $profile = $user->getOne('Profile');
            $extended = $profile->get('extended');
            $password = $extended['temp_password'];
            $activateBefore = $extended['activate_pass_before'];
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
            return array_merge($profile->toArray(), $user->toArray());
        }
        return [];
    }
}
