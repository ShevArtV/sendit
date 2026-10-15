<?php

include_once __DIR__ . '/cabinet.class.php';

/**
 * Поля оформления заказа, выбранные в кабинете (повтор заказа, сохранённый адрес).
 *
 * Сниппет msOrder у авторизованного покупателя заново пишет в заказ поля профиля, поэтому значения
 * держатся в сессии SendIt и подставляются плагином на msOnBeforeAddToOrder, пока покупатель
 * не поправит поле сам. Без выбора подставляется адрес по умолчанию.
 */
class siOrderFields
{
    public const ADDRESS_FIELDS = [
        'receiver', 'phone', 'email', 'country', 'index', 'region', 'city', 'metro',
        'street', 'building', 'entrance', 'floor', 'room', 'comment', 'text_address',
    ];
    /** контактные поля: адрес без них не затирает данные профиля */
    public const CONTACT_FIELDS = ['receiver', 'phone', 'email'];
    private const SESSION_KEY = 'cabinetOrderFields';

    /** @var array|null выбранные поля на время запроса */
    private static ?array $fields = null;

    /**
     * Записывает поля в заказ через order->add() и запоминает их для подстановки на странице оформления.
     * Пустая строка очищает поле заказа, null и массивы пропускаются.
     * @param \modX $modx
     * @param miniShop2 $ms2
     * @param array $fields
     * @return array поля, которые заказ принял
     */
    public static function apply(\modX $modx, $ms2, array $fields): array
    {
        $fields = array_filter($fields, static function ($value) {
            return $value !== null && !is_array($value);
        });
        self::store($modx, $fields);
        $applied = [];
        foreach ($fields as $key => $value) {
            $response = $ms2->order->add($key, $value);
            if (is_string($response)) {
                $response = json_decode($response, true);
            }
            if (!empty($response['success'])) {
                $applied[$key] = $response['data'][$key] ?? $value;
            }
        }
        return $applied;
    }

    /**
     * Поля адреса, которых нет в переданном, становятся пустыми, чтобы в заказе не осталось хвостов прошлого адреса.
     * Контактные поля не трогаются.
     * @param array $address
     * @return array
     */
    public static function fillAddress(array $address): array
    {
        return array_merge(array_fill_keys(array_diff(self::ADDRESS_FIELDS, self::CONTACT_FIELDS), ''), $address);
    }

    /**
     * Обработчик msOnBeforeAddToOrder.
     * @param \modX $modx
     * @param string $key
     * @param mixed $value
     * @return mixed|null значение для подстановки или null, если подменять нечего
     */
    public static function onBeforeAddToOrder(\modX $modx, string $key, $value)
    {
        if (empty($_COOKIE['siSession']) || !$modx->user->isAuthenticated($modx->context->get('key'))) {
            return null;
        }
        $fields = self::get($modx);
        if (!array_key_exists($key, $fields)) {
            return null;
        }
        // ms2_action есть только у запросов формы оформления: покупатель правит поле сам — выбор кабинета больше не держим
        if (!empty($_REQUEST['ms2_action'])) {
            if ((string)$fields[$key] !== (string)$value) {
                unset($fields[$key]);
                self::store($modx, $fields);
            }
            return null;
        }
        return $fields[$key];
    }

    /**
     * @param \modX $modx
     * @return void
     */
    public static function clear(\modX $modx): void
    {
        self::$fields = null;
        if (!empty($_COOKIE['siSession'])) {
            SendIt::setSession($modx, [self::SESSION_KEY => null]);
        }
    }

    /**
     * Поля выбора из сессии, без выбора — адрес покупателя по умолчанию.
     * @param \modX $modx
     * @return array
     */
    public static function get(\modX $modx): array
    {
        if (self::$fields !== null) {
            return self::$fields;
        }
        $session = SendIt::getSession($modx);
        if (isset($session[self::SESSION_KEY]) && is_array($session[self::SESSION_KEY])) {
            return self::$fields = $session[self::SESSION_KEY];
        }
        self::$fields = [];
        if (in_array('addresses', siCabinet::getEnabledActions($modx), true)) {
            include_once __DIR__ . '/addresses.class.php';
            self::$fields = siUserAddresses::getDefaultFields($modx, (int)$modx->user->get('id'));
        }
        return self::$fields;
    }

    /**
     * @param \modX $modx
     * @param array $fields
     * @return void
     */
    private static function store(\modX $modx, array $fields): void
    {
        self::$fields = $fields;
        SendIt::setSession($modx, [self::SESSION_KEY => $fields]);
    }
}
