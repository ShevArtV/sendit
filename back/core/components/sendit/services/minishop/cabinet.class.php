<?php

include_once dirname(__DIR__) . '/siLogger.class.php';

/**
 * Общее для действий кабинета покупателя: доступ к miniShop2 только через его публичный контракт
 * (сервис, cart/order handlers, changeOrderStatus, msPayment), набор действий из системных настроек
 * и точка расширения siOnBeforeUserCabinetAction / siOnUserCabinetAction.
 */
abstract class siCabinet
{
    public const ACTIONS = ['tocart', 'repeat', 'pay', 'cancel', 'addresses'];
    /** Публичные методы-действия класса => действие из si_cabinet_actions */
    protected const METHODS = [];

    public \modX $modx;
    public SendIt $SendIt;
    public array $params;
    /** @var miniShop2|null */
    public $ms2 = null;
    public int $userId = 0;

    public function __construct(\modX $modx, SendIt $SendIt, array $params = [])
    {
        $this->modx = $modx;
        $this->SendIt = $SendIt;
        $this->params = $params;
        $this->modx->lexicon->load('sendit:default');
        if ($this->modx->user->isAuthenticated($this->modx->context->get('key'))) {
            $this->userId = (int)$this->modx->user->get('id');
        }
    }

    /**
     * @param string $method
     * @return bool
     */
    public static function handles(string $method): bool
    {
        return isset(static::METHODS[$method]);
    }

    /**
     * Действия кабинета, включённые в системной настройке si_cabinet_actions.
     * @param \modX $modx
     * @return array
     */
    public static function getEnabledActions(\modX $modx): array
    {
        $actions = $modx->getOption('si_cabinet_actions', null, implode(',', self::ACTIONS));
        return array_values(array_intersect(self::ACTIONS, array_map('trim', explode(',', $actions))));
    }

    /**
     * @param \modX $modx
     * @param string $key
     * @param string $default
     * @return int[]
     */
    public static function getIdsOption(\modX $modx, string $key, string $default = ''): array
    {
        $value = (string)$modx->getOption($key, null, $default);
        return array_values(array_filter(array_map('intval', explode(',', $value))));
    }

    /**
     * @param \modX $modx
     * @param string $ctx
     * @return miniShop2|null
     */
    public static function loadMiniShop(\modX $modx, string $ctx = '')
    {
        $ms2 = $modx->getService('miniShop2');
        if (!$ms2 || !$ms2->initialize($ctx ?: $modx->context->get('key'), ['json_response' => false])) {
            return null;
        }
        return $ms2;
    }

    /**
     * @param string $method
     * @return array
     */
    public function run(string $method): array
    {
        if (!$this->userId) {
            return $this->SendIt->error('si_msg_cabinet_auth_err');
        }
        $action = static::METHODS[$method] ?? '';
        if (!$action || !in_array($action, self::getEnabledActions($this->modx), true)) {
            return $this->SendIt->error('si_msg_cabinet_action_err');
        }
        if (!$this->ms2 = self::loadMiniShop($this->modx)) {
            (new siLogger($this->modx))->write('miniShop2 недоступен для кабинета покупателя', ['method' => $method], 'error', 'cabinet');
            return $this->SendIt->error('si_msg_cabinet_ms2_err');
        }
        return $this->$method();
    }

    /**
     * Значения POST-поля как массив: SendIt отдаёт массивы из формы JSON-строкой.
     * @param string $key
     * @return array
     */
    protected function getPostArray(string $key): array
    {
        $value = $_POST[$key] ?? [];
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : explode(',', $value);
        }
        return (array)$value;
    }

    /**
     * Даёт проекту поменять данные действия или отменить его.
     * Плагин возвращает $modx->event->returnedValues['data'] (новые данные) и/или ['error'] (текст отказа).
     *
     * @param string $method
     * @param array $data
     * @param xPDOObject|null $object
     * @return array [string $error, array $data]
     */
    protected function before(string $method, array $data, $object = null): array
    {
        // без подписчиков MODX не пересоздаёт event, и в returnedValues остался бы ответ прошлого события
        if (is_object($this->modx->event)) {
            $this->modx->event->returnedValues = null;
        }
        $this->modx->invokeEvent('siOnBeforeUserCabinetAction', [
            'method' => $method,
            'data' => $data,
            'object' => $object,
            'Cabinet' => $this,
            'SendIt' => $this->SendIt,
        ]);
        $returned = is_object($this->modx->event) ? ($this->modx->event->returnedValues ?? []) : [];
        if (!empty($returned['data']) && is_array($returned['data'])) {
            $data = array_merge($data, $returned['data']);
        }
        return [(string)($returned['error'] ?? ''), $data];
    }

    /**
     * @param string $method
     * @param array $data
     * @param xPDOObject|null $object
     * @return void
     */
    protected function after(string $method, array $data, $object = null): void
    {
        $this->modx->invokeEvent('siOnUserCabinetAction', [
            'method' => $method,
            'data' => $data,
            'object' => $object,
            'Cabinet' => $this,
            'SendIt' => $this->SendIt,
        ]);
    }

    /**
     * @param int|string $target id ресурса или готовый URL
     * @return string
     */
    protected function makeUrl($target): string
    {
        if (!$target) {
            return '';
        }
        return is_numeric($target) ? $this->modx->makeUrl((int)$target, '', '', 'full') : (string)$target;
    }

    /**
     * @param string $message
     * @param array $data
     * @param array $placeholders
     * @return array
     */
    protected function success(string $message, array $data = [], array $placeholders = []): array
    {
        if (empty($data['redirectUrl']) && !empty($this->params['reloadPage'])) {
            $data['redirectUrl'] = $_SERVER['HTTP_REFERER'] ?? '';
        }
        return $this->SendIt->success($this->params['successMessage'] ?? $message, $data, $placeholders);
    }
}
