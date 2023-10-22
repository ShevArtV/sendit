<?php

return [
    'default' => [
        'validate' => 'phone:required,age:required,name:required,email:email:required,politics:checkbox:required',
    ],
    'onestepform' => [
        'extends' => 'default',
        'redirectTo' => 0,
        'redirectTimeout' => 3000,
        'clearFieldsOnSuccess' => 1,
        'fieldNames' => 'age==Возраст,name==Имя,phone==Телефон,email==Почта',
        'successMessage' => 'Форма отправлена!',
        'validationErrorMessage' => 'Исправьте ошибки!',
    ],
    'testchangesubmit' => [
        'validate' => 'email:email',
        'successMessage' => 'Код отправлен!',
        'formName' => 'testChange',
    ],
    'testinputsubmit' => [
        'hooks' => 'FormItSaveForm',
        'successMessage' => 'Тут должны быть подсказки',
        'formName' => 'testInput',
    ],
    'fileupload' => [
        'attachFilesToEmail' => 'files',
        'allowFiles' => 'filelist',
        'maxSize' => 6,
        'maxCount' => 2,
        'allowExt' => 'jpg,png',
        'portion' => 0.1,
        'clearFieldsOnSuccess' => 1,
        'successMessage' => 'Файлы отправлены!',
    ],
    'quiz' => [
        'validate' => 'phone:required,name:required,answers[*]:required,answers[7][]:checkbox:required,answers[3]:requiredIf=^answers[2]|Да^',
        'clearFieldsOnSuccess' => 0,
        'hooks' => 'FormItSaveForm',
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
        'autoLogin' => 0,
        'redirectTo' => '',
        'authenticateContexts' => '',
        'passwordField' => '',
        'usernameField' => 'email',
        'usergroupsField' => '',
        'moderate' => '',
        'redirectTimeout' => 3000,
        'usergroups' => 2,
        'activationResourceId' => 1,
        'activationUrlTime' => 10800,
        'validate' => 'email:required,password:checkPassLength=^8^,password_confirm:passwordConfirm=^password^,politics:checkbox:required',
        'politics.vTextRequired' => 'Примите наши условия.',
        'password.vTextRequired' => 'Придумайте пароль.',
        'password.vTextMinLength' => 'Пароль должен быть не менее 8 символов.',
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
        'successMessage' => 'Новый пароль отправлен на ваш email',

        'usernameField' => 'email',
        'validate' => 'email:required:userNotExists',

        'fiarSubject' => 'Восстановление пароля',
        'fiarFrom' => 'email@domain.ru',
        'fiarTpl' => 'siResetPassEmail',

        'email.vTextRequired' => 'Укажите email.',
        'email.vTextUserNotExists' => 'Пользователь не найден',
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
