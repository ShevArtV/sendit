<?php

include_once __DIR__ . '/cabinet.class.php';

/**
 * Корзина покупателя вне сессии: сохраняется на событиях корзины miniShop2 и возвращается при входе.
 * Работает только при ms2_tmp_storage = session: в режиме db miniShop2 сам держит корзину
 * черновиком заказа пользователя.
 */
class siSavedCart
{
    /** Поля позиции, которые нужны для повторного cart->add() */
    private const ITEM_FIELDS = ['id', 'count', 'options', 'key'];

    /**
     * @param \modX $modx
     * @return bool
     */
    public static function isActive(\modX $modx): bool
    {
        return $modx->getOption('ms2_tmp_storage', null, 'session') === 'session'
            && in_array('savecart', siCabinet::getEnabledActions($modx), true);
    }

    /**
     * Снимок корзины после её изменения (msOnAddToCart, msOnChangeInCart, msOnRemoveFromCart, msOnEmptyCart).
     * @param \modX $modx
     * @param msCartHandler $cart
     * @return void
     */
    public static function save(\modX $modx, $cart): void
    {
        $userId = self::getUserId($modx);
        if (!$userId || !self::isActive($modx) || !is_object($cart)) {
            return;
        }
        $items = [];
        foreach ($cart->get() as $key => $item) {
            $items[$key] = array_intersect_key($item, array_flip(self::ITEM_FIELDS));
        }
        self::write($modx, $userId, $items);
    }

    /**
     * Сохранённая корзина добавляется к текущей (OnWebLogin). Совпавшая позиция получает большее
     * количество, а не сумму: иначе каждый повторный вход в том же браузере удваивал бы корзину.
     * Товары идут через cart->add(), поэтому цены, наличие и проверки остаются за miniShop2.
     * @param \modX $modx
     * @param modUser|null $user
     * @return void
     */
    public static function restore(\modX $modx, $user = null): void
    {
        $userId = $user ? (int)$user->get('id') : self::getUserId($modx);
        if (!$userId || !self::isActive($modx)) {
            return;
        }
        $saved = self::read($modx, $userId);
        if (!$saved || !$ms2 = siCabinet::loadMiniShop($modx)) {
            return;
        }
        $current = $ms2->cart->get();
        foreach ($saved as $key => $item) {
            if (isset($current[$key])) {
                if ((int)$item['count'] > (int)$current[$key]['count']) {
                    $ms2->cart->change($key, (int)$item['count']);
                }
                continue;
            }
            $ms2->cart->add((int)$item['id'], (int)$item['count'], $item['options'] ?? []);
        }
        self::save($modx, $ms2->cart);
    }

    /**
     * Оформленный заказ забирает корзину (msOnCreateOrder).
     * @param \modX $modx
     * @param int $userId
     * @return void
     */
    public static function clear(\modX $modx, int $userId = 0): void
    {
        if ($userId = $userId ?: self::getUserId($modx)) {
            self::write($modx, $userId, []);
        }
    }

    /**
     * @param \modX $modx
     * @return int
     */
    private static function getUserId(\modX $modx): int
    {
        $ctx = $modx->context ? $modx->context->get('key') : 'web';
        return $modx->user && $modx->user->isAuthenticated($ctx) ? (int)$modx->user->get('id') : 0;
    }

    /**
     * @param \modX $modx
     * @return void
     */
    private static function addPackage(\modX $modx): void
    {
        if (!isset($modx->packages['sendit'])) {
            $modx->addPackage('sendit', $modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/sendit/model/');
        }
    }

    /**
     * @param \modX $modx
     * @param int $userId
     * @return array
     */
    private static function read(\modX $modx, int $userId): array
    {
        self::addPackage($modx);
        $row = $modx->getObject('siUserCart', ['user_id' => $userId]);
        $data = $row ? json_decode((string)$row->get('data'), true) : [];
        return is_array($data) ? $data : [];
    }

    /**
     * @param \modX $modx
     * @param int $userId
     * @param array $items
     * @return void
     */
    private static function write(\modX $modx, int $userId, array $items): void
    {
        self::addPackage($modx);
        $row = $modx->getObject('siUserCart', ['user_id' => $userId]);
        if (!$items) {
            if ($row) {
                $row->remove();
            }
            return;
        }
        if (!$row) {
            $row = $modx->newObject('siUserCart', ['user_id' => $userId]);
        }
        $row->fromArray(['data' => json_encode($items, JSON_UNESCAPED_UNICODE), 'updatedon' => time()]);
        $row->save();
    }
}
