<?php

/**
 * Адрес перехода после отправки формы. Значение 'cabinet' — страница кабинета из настройки
 * si_cabinet_page_id: пресеты входа и регистрации ведут туда, а проекту не нужно править пресеты.
 */
class siRedirect
{
    public const CABINET = 'cabinet';

    /**
     * @param \modX $modx
     * @param mixed $target id ресурса, URL или 'cabinet'
     * @return string URL или пустая строка, если переходить некуда
     */
    public static function url(\modX $modx, $target): string
    {
        if ($target === self::CABINET) {
            $target = (int)$modx->getOption('si_cabinet_page_id', null, 0);
        }
        if (!$target) {
            return '';
        }
        return (int)$target > 0 ? $modx->makeUrl((int)$target, '', '', 'full') : (string)$target;
    }
}
