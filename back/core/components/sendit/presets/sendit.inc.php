<?php

return [
    'default' => [
        'validate' => 'phone:required,age:required,name:required,email:email:required,politics:checkbox:required',
    ],
    'onestepform' => [
        'extends' => 'default',
        'redirectTo' => 1,
        'redirectTimeout' => 3000,
        'clearFieldsOnSuccess' => 1,
        'fieldNames' => 'age==Возраст',
        'successMessage' => 'Форма отправлена!',
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
        'fieldNames' => 'phone==Телефон,name==Имя',
    ],
    'register' => [
        'hooks' => 'AjaxIdentification,FormItSaveForm,FormItAutoResponder',
        'method' => 'register',
        'successMessage' => 'Вы успешно зарегистрированы. Подтвердите email для активации учётной записи.',
        'customValidators' => 'checkPassLength,passwordConfirm',
        'emailSubject' => 'Регистрация по email',

        'fiarSubject' => 'Активация пользователя',
        'fiarFrom' => 'email@domain.ru',
        'fiarTpl' => '@FILE chunks/emails/activateEmail.tpl',

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
        'extendedFieldPrefix' => 'extended_',
        'activationUrlTime' => 10800,
        'validate' => 'email:required,password:checkPassLength=^8^,password_confirm:passwordConfirm=^password^,politics:checkbox:required',
        'politics.vTextRequired' => 'Примите наши условия.',
        'password.vTextRequired' => 'Придумайте пароль.',
        'password.vTextMinLength' => 'Пароль должен быть не менее 8 символов.',
    ],
    'auth' => [
        'successMessage' => 'Вы успешно авторизованы и будете перенаправлены в личный кабинет.',
        'validate' => 'email:required,password:required',
        'validationErrorMessage' => 'Исправьте, пожалуйста, ошибки!',
        'hooks' => 'AjaxIdentification',

        'method' => 'login',

        'redirectTo' => 5,
        'redirectTimeout' => 3000,
        'usernameField' => 'email',

        'email.vTextRequired' => 'Укажите email.',
        'password.vTextRequired' => 'Введите пароль.',
        'errorFieldName' => 'errorLogin'
    ],
    'editpass' => [
        'hooks' => 'AjaxIdentification',
        'method' => 'update',
        'successMessage' => 'Пароль изменён.',

        'validate' => 'password:required:minLength=^8^:regexp=^/\A[\da-zA-Z!#\?&]*$/^,password_confirm:password_confirm=^password^',

        'password.vTextRequired' => 'Придумайте пароль.',
        'password.vTextRegexp' => 'Пароль может содержать только цифры, латинские буквы и символы !,#,?,&',
        'password.vTextMinLength' => 'Пароль должен быть не менее 8 символов.',
    ],
    'dataedit' => [
        'hooks' => 'AjaxIdentification',
        'method' => 'update',
        'successMessage' => 'Данные сохранены.',
        'clearFieldsOnSuccess' => 0,

        'validate' => 'email:required:email',
        'email.vTextRequired' => 'Укажите email.'
    ],
    'logout' => [
        'hooks' => 'AjaxIdentification',
        'method' => 'logout',
        'successMessage' => 'До новых встреч!',
        'redirectTo' => 1,
        'errorFieldName' => 'errorLogout'
    ],
    'forgot' => [
        'hooks' => 'AjaxIdentification,FormItSaveForm,FormItAutoResponder',
        'method' => 'forgot',
        'successMessage' => 'Новый пароль отправлен на ваш email',
        'customValidators' => 'userNotExists',
        'emailSubject' => 'Забыли пароль',

        'usernameField' => 'email',
        'validate' => 'email:required:userNotExists',
        'validationErrorMessage' => 'Исправьте, пожалуйста, ошибки!',

        'fiarSubject' => 'Восстановление пароля',
        'fiarFrom' => 'email@domain.ru',
        'fiarTpl' => '@FILE chunks/emails/resetPassEmail.tpl',

        'email.vTextRequired' => 'Укажите email.',
        'email.vTextUserNotExists' => 'Пользователь не найден',
    ]
];
