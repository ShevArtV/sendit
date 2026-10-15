<?php

/**
 * Доставка одноразового кода входа (SMS, Telegram, почта и т.п.). Реализацию пишет проект.
 *
 * Класс подключается параметром пресета codeSender или системной настройкой si_code_sender,
 * файл класса — параметром codeSenderPath или настройкой si_code_sender_path.
 * Конструктор получает \modX $modx и параметры пресета.
 */
interface siCodeSender
{
    /**
     * @param string $recipient получатель как его ввёл пользователь (телефон, email, chat id)
     * @param string $code код для отправки
     * @param array $params параметры пресета
     * @return bool
     */
    public function send(string $recipient, string $code, array $params): bool;

    /**
     * Текст ошибки для пользователя, если send() вернул false.
     * @return string
     */
    public function getError(): string;
}
