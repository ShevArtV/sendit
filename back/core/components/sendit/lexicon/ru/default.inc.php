<?php
$_lang['setting_si_storage_time'] = 'Время хранения загруженных файлов во временной папке.';
$_lang['setting_si_storage_time_desc'] = 'по умолчанию 86400 секунд';
$_lang['setting_si_precision'] = 'Точность округления процентов загрузки файлов.';
$_lang['setting_si_precision_desc'] = 'по умолчанию 2';
$_lang['setting_si_unset_params'] = 'Список параметров, которые не нужно возвращать в ответе.';
$_lang['setting_si_unset_params_desc'] = 'по умолчанию emailTo,extends';
$_lang['setting_si_frontend_css'] = 'Путь к основным стилям';
$_lang['setting_si_frontend_css_desc'] = 'поддерживает плейсхолдер [[+assetsUrl]]';
$_lang['setting_si_frontend_js'] = 'Путь к основным JS скриптам';
$_lang['setting_si_frontend_js_desc'] = 'поддерживает плейсхолдер [[+assetsUrl]]';
$_lang['setting_si_js_config_path'] = 'Путь к файлу JS конфигурации';
$_lang['setting_si_js_config_path_desc'] = 'указывать путь относительно файла JS скриптов';
$_lang['setting_si_uploaddir'] = 'Путь для загрузки файлов';
$_lang['setting_si_uploaddir_desc'] = 'указывать путь к папке относительно корня сайта, папка должна быть доступна для записи.';
$_lang['setting_si_path_to_presets'] = 'Путь к пресетам';
$_lang['setting_si_path_to_presets_desc'] = 'указывать путь к папке относительно папки core сайта.';
$_lang['setting_si_send_goal'] = 'Отправлять цели в Яндекс.Метрику';
$_lang['setting_si_send_goal_desc'] = 'обязательно укажите идентификатор счётчика метрики.';
$_lang['setting_si_counter_id'] = 'ID счётчика метрики.';
$_lang['setting_si_counter_id_desc'] = 'обязательно укажите это значение, если хотите отправлять цели в метрику. не забудьте установить код счётчика на сайт.';
$_lang['setting_si_default_email'] = 'Адрес для отправки писем по умолчанию.';
$_lang['setting_si_default_email_desc'] = 'будет использован только если для формы нет ни одного пресета.';
$_lang['setting_si_default_emailtpl'] = 'Чанк письма по умолчанию.';
$_lang['setting_si_default_emailtpl_desc'] = 'будет использован только если для формы нет ни одного пресета.';
$_lang['setting_si_max_sending_per_session'] = 'Максимальное количество отправок одной формы за сессию.';
$_lang['setting_si_max_sending_per_session_desc'] = 'по умолчанию 2.';
$_lang['setting_si_pause_between_sending'] = 'Пауза между отправками одной формы.';
$_lang['setting_si_pause_between_sending_desc'] = 'указывается в секундах';
$_lang['setting_si_default_admin'] = 'ID администратора по умолчанию.';
$_lang['setting_si_default_admin_desc'] = 'ему будут отправляться письма если настройка si_default_email пуста';
$_lang['setting_si_allow_dirs'] = 'Список имен папок из которых можно удалять другие папки.';
$_lang['setting_si_allow_dirs_desc'] = 'указывается через запятую, по умолчанию - uploaded_files';

$_lang['si_msg_loading'] = '[[+filename]]: загружено [[+percent]][[+unit]]';
$_lang['si_msg_loading_bytes'] = '[[+filename]]: загружено [[+bytes]] [[+unit]] из [[+total]] [[+unit]]';
$_lang['si_msg_file_remove_session_err'] = '[[+filename]]: принадлежит другому пользователю.';
$_lang['si_msg_file_size_err'] = 'Размер файла превышает максимально допустимый. ';
$_lang['si_msg_file_extention_err'] = 'Файл имеет недопустимое расширение. ';
$_lang['si_msg_files_count_err'] = 'Можно загрузить ещё [[+left]] [[+declension]].';
$_lang['si_msg_files_maxcount_err'] = 'Можно загрузить максимум [[+left]] [[+declension]].';
$_lang['si_msg_files_loaded_err'] = 'Добавлено максимальное количество файлов.';
$_lang['si_msg_loaded'] = '[[+filename]]: файл загружен полностью.';
$_lang['si_msg_queue'] = ' в очереди';

$_lang['si_msg_success'] = 'Форма отправлена!';
$_lang['si_msg_file_remove_success'] = 'Файл удалён.';
$_lang['si_msg_pause_err'] = 'Повторная отправка этой формы возможна через [[+left]] сек.';
$_lang['si_msg_no_email_err'] = 'Не указан адрес электронной почты.';
$_lang['si_msg_reject_err'] = 'Отправка формы запрещена.';
$_lang['si_msg_token_err'] = 'Невалидный токен.';
$_lang['si_msg_trusted_err'] = 'Если вы не робот - перезагрузите страницу!';
$_lang['si_msg_count_sending_err'] = 'Нельзя отправить эту форму больше [[+count]] раз без перезагрузки.';
$_lang['si_default_formname'] = 'Форма по умолчанию.';
$_lang['si_default_subject'] = 'Письмо с сайта [[+host]]';
