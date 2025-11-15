<?php

return [
    'default' => [
        'validate' => 'phone:required,age:required,name:required,email:email:required,politics:checkbox:required',
        'fieldNames' => 'age==Возраст,name==Имя,phone==Телефон,email==Почта',
    ],
    'simpleform' => [
        'validate' => 'email:email:required,name:required,politics:checkbox:required',
    ],
    'search_something' => [
        'hooks' => '',
        'snippet' => '@FILE snippets/snippet.search.php'
    ],
    'check_something' => [
        'hooks' => '',
        'snippet' => 'checkSnippet'
    ],
    'check_code' => [
        'hooks' => '',
        'snippet' => 'code'
    ],
    'upload_file' => [
        'hooks' => '',
        'allowExt' => 'jpg,png,jpeg,webp,tiff,tif',
        'portion' => 0.1,
        'threadsQuantity' => 12,
    ],
    'upload_simple_file' => [
        'extends' => 'upload_file',
        'maxSize' => 5,
        'maxCount' => 1,
    ],
    'upload_drop_file' => [
        'extends' => 'upload_file',
        'maxSize' => 1,
        'maxCount' => 1,
        'loadedUnit' => 'Mb', // %, b, mb, kb, gb, gb,
    ],
    'form_with_file' => [
        'extends' => 'default',
        'validate' => 'name:required',
        'attachFilesToEmail' => 'files',
        'allowFiles' => 'filelist',
        'clearFieldsOnSuccess' => 1,
    ],

    'quiz' => [
        'validate' => 'phone:required,name:required,answers[*]:required,answers[7][]:checkbox:required,answers[3]:requiredIf=^answers[2]|Да^',
        'clearFieldsOnSuccess' => 0,
        'hooks' => 'FormItSaveForm,email',
        'fieldNames' => 'phone==Телефон,name==Имя',
    ],
    'register' => [
        'hooks' => 'Identification,FormItSaveForm,FormItAutoResponder',
        'method' => 'register',
        'successMessage' => 'Вы успешно зарегистрированы. Подтвердите email для активации учётной записи.',

        'fiarSubject' => 'Активация пользователя',
        'fiarFrom' => 'email@domain.ru',
        'fiarTpl' => 'siActivateEmail',

        'activation' => 1,
        'rememberme' => 1,
        'authenticateContexts' => 'web',
        'afterLoginRedirectId' => 5,
        'autoLogin' => 1,
        'redirectTo' => '',
        'passwordField' => '',
        'usergroupsField' => '',
        'moderate' => '',
        'redirectTimeout' => 3000,
        'usergroups' => 2,
        'activationResourceId' => 1,
        'activationUrlTime' => 10800,
        'validate' => 'email:required,password:checkPassLength=^8^,password_confirm:passwordConfirm=^password^,politics:checkbox:required',
        'politics.vTextRequired' => 'Примите наши условия.',
        'password.vTextRequired' => 'Придумайте пароль.',
        'password.vTextCheckPassLength' => 'Пароль должен быть не менее 8 символов.',
    ],
    'auth' => [
        'successMessage' => 'Вы успешно авторизованы и будете перенаправлены в личный кабинет.',
        'validate' => 'email:required,password:required',
        'hooks' => 'Identification',

        'method' => 'login',

        'redirectTo' => 5,
        'redirectTimeout' => 3000,
        'usernameField' => 'email',

        'email.vTextRequired' => 'Укажите email.',
        'password.vTextRequired' => 'Введите пароль.',
        'errorFieldName' => 'errorLogin'
    ],
    'editpass' => [
        'hooks' => 'Identification',
        'method' => 'update',
        'successMessage' => 'Пароль изменён.',

        'validate' => 'password:required:minLength=^8^:regexp=^/\A[\da-zA-Z!#\?&]*$/^,password_confirm:password_confirm=^password^',

        'password.vTextRequired' => 'Придумайте пароль.',
        'password.vTextRegexp' => 'Пароль может содержать только цифры, латинские буквы и символы !,#,?,&',
        'password.vTextMinLength' => 'Пароль должен быть не менее 8 символов.',
    ],
    'dataedit' => [
        'hooks' => 'Identification',
        'method' => 'update',
        'successMessage' => 'Данные сохранены.',
        'clearFieldsOnSuccess' => 0,

        'validate' => 'email:required:email',
        'email.vTextRequired' => 'Укажите email.'
    ],
    'logout' => [
        'hooks' => 'Identification',
        'method' => 'logout',
        'successMessage' => 'До новых встреч!',
        'redirectTo' => 1,
        'errorFieldName' => 'errorLogout'
    ],
    'forgot' => [
        'hooks' => 'Identification,FormItSaveForm,FormItAutoResponder',
        'method' => 'forgot',
        'successMessage' => 'Письмо с инструкциями отправлено на ваш email',

        'usernameField' => 'email',
        'validate' => 'email:required:userNotExists',

        'fiarSubject' => 'Восстановление пароля',
        'fiarFrom' => 'email@domain.ru',
        'fiarTpl' => 'siResetPassEmail',

        'email.vTextRequired' => 'Укажите email.',
        'email.vTextUserNotExists' => 'Пользователь не найден',

        'rememberme' => 1,
        'authenticateContexts' => 'web',
        'afterLoginRedirectId' => 5,
        'autoLogin' => 1,

        'activationResourceId' => 1,
        'activationUrlTime' => 3600,
    ],
    'custom' => [
        'extends' => 'onestepform',
        'snippet' => '@FILE snippets/test.php',
        'hooks' => '',
        'validate' => ''
    ],
    'sendcode' => [
        'hooks' => '',
        'snippet' => '@FILE snippets/smsauth/snippet.sendcode.php',
        'successMessage' => 'Код отправлен на номер {$phone}',
        'validate' => 'phone:required',
        'phone.vTextRequired' => 'Укажите телефон.'
    ],
    'checkcode' => [
        'hooks' => '',
        'successMessage' => '',
        'validate' => 'code:CheckCode',
        'validationErrorMessage' => 'Неверный код.',
    ],
    'sms_auth' => [
        'extends' => 'checkcode',
        'successMessage' => 'Вы успешно авторизованы.',
        'hooks' => 'SetUserFields,Identification',
        'method' => 'login',
        'redirectTo' => 5,
        'user_exist' => 1,
        'redirectTimeout' => 3000,
        'clearFieldsOnSuccess' => 1,
    ],
    'sms_register' => [
        'hooks' => 'SetUserFields,Identification',
        'method' => 'register',
        'successMessage' => 'Вы успешно зарегистрированы.',
        'activation' => 0,
        'autoLogin' => 1,
        'redirectTo' => 5,
        'redirectTimeout' => 3000,
        'usergroups' => 2,
        'validate' => 'fullname:required,phone:required,code:CheckCode',
        'fullname.vTextRequired' => 'Укажите ваше имя.',
        'clearFieldsOnSuccess' => 1,
    ],
];
