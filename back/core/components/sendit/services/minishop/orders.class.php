<?php

include_once __DIR__ . '/cabinet.class.php';
include_once __DIR__ . '/orderfields.class.php';

/**
 * Действия покупателя с собственными заказами: в корзину, повтор, оплата, отмена.
 */
class siUserOrders extends siCabinet
{
    protected const METHODS = ['toCart' => 'tocart', 'repeat' => 'repeat', 'pay' => 'pay', 'cancel' => 'cancel'];

    /**
     * Какие действия доступны для заказа: включены в si_cabinet_actions и разрешены его статусом.
     * Оплата доступна только способу с обработчиком (msPayment.class): у «наличных» онлайн-оплаты нет.
     * @param \modX $modx
     * @param array $order поля msOrder; payment_class можно передать, чтобы не читать способ оплаты из БД
     * @return array ['tocart' => bool, 'repeat' => bool, 'pay' => bool, 'cancel' => bool]
     */
    public static function getOrderActions(\modX $modx, array $order): array
    {
        $enabled = self::getEnabledActions($modx);
        $status = (int)$order['status'];
        if (!array_key_exists('payment_class', $order)) {
            $payment = !empty($order['payment']) ? $modx->getObject('msPayment', (int)$order['payment']) : null;
            $order['payment_class'] = $payment ? $payment->get('class') : '';
        }
        return [
            'tocart' => in_array('tocart', $enabled, true),
            'repeat' => in_array('repeat', $enabled, true),
            'pay' => in_array('pay', $enabled, true) && !empty($order['payment_class'])
                && in_array($status, self::getIdsOption($modx, 'si_cabinet_pay_statuses', '1'), true),
            'cancel' => in_array('cancel', $enabled, true)
                && in_array($status, self::getIdsOption($modx, 'si_cabinet_cancel_statuses', '1'), true),
        ];
    }

    /**
     * Заказ текущего пользователя из POST order_id.
     * @return msOrder|null
     */
    protected function getOrder()
    {
        $id = (int)($_POST['order_id'] ?? 0);
        if (!$id || !$order = $this->modx->getObject('msOrder', ['id' => $id, 'user_id' => $this->userId])) {
            return null;
        }
        return $order;
    }

    /**
     * Позиции заказа для корзины; POST products[] сужает выбор до отмеченных msOrderProduct.id.
     * @param msOrder $order
     * @return array
     */
    protected function getItems($order): array
    {
        $selected = array_filter(array_map('intval', $this->getPostArray('products')));
        $items = [];
        foreach ($order->getMany('Products') as $product) {
            if ($selected && !in_array((int)$product->get('id'), $selected, true)) {
                continue;
            }
            $options = $product->get('options');
            $items[] = [
                'id' => (int)$product->get('product_id'),
                'count' => (int)$product->get('count'),
                'options' => is_array($options) ? $options : [],
                'name' => $product->get('name'),
            ];
        }
        return $items;
    }

    /**
     * Поля оформления из заказа: доставка, оплата и адрес.
     * @param msOrder $order
     * @return array
     */
    protected function getOrderFields($order): array
    {
        $fields = [];
        if ($address = $order->getOne('Address')) {
            foreach (siOrderFields::ADDRESS_FIELDS as $key) {
                $fields[$key] = (string)$address->get($key);
            }
        }
        return array_merge(array_filter([
            'delivery' => (int)$order->get('delivery'),
            'payment' => (int)$order->get('payment'),
        ]), siOrderFields::fillAddress(array_filter($fields)));
    }

    /**
     * @param array $items
     * @return array [int $added, string[] $skipped]
     */
    protected function addItems(array $items): array
    {
        $added = 0;
        $skipped = [];
        foreach ($items as $item) {
            $response = $this->ms2->cart->add($item['id'], $item['count'], $item['options']);
            if (is_string($response)) {
                $response = json_decode($response, true);
            }
            if (!empty($response['success'])) {
                $added++;
            } else {
                $skipped[] = $item['name'] ?? $item['id'];
            }
        }
        return [$added, $skipped];
    }

    /**
     * @return array
     */
    public function toCart(): array
    {
        if (!$order = $this->getOrder()) {
            return $this->SendIt->error('si_msg_cabinet_order_nf');
        }
        [$error, $data] = $this->before('toCart', [
            'items' => $this->getItems($order),
            'cleanCart' => (bool)($this->params['cleanCart'] ?? false),
            'redirectUrl' => $this->makeUrl($this->params['redirectTo'] ?? ''),
        ], $order);
        if ($error) {
            return $this->SendIt->error($error);
        }
        return $this->fillCart('toCart', $order, $data, 'si_msg_cabinet_tocart_success');
    }

    /**
     * Повтор: позиции в корзину, доставка/оплата/адрес в заказ, переход на оформление.
     * Оформляет и оплачивает покупатель штатной формой miniShop2.
     * @return array
     */
    public function repeat(): array
    {
        if (!$order = $this->getOrder()) {
            return $this->SendIt->error('si_msg_cabinet_order_nf');
        }
        $checkout = ($this->params['redirectTo'] ?? '') ?: $this->modx->getOption('si_cabinet_checkout_id', null, 0);
        [$error, $data] = $this->before('repeat', [
            'items' => $this->getItems($order),
            'orderFields' => $this->getOrderFields($order),
            'cleanCart' => (bool)($this->params['cleanCart'] ?? true),
            'redirectUrl' => $this->makeUrl($checkout),
        ], $order);
        if ($error) {
            return $this->SendIt->error($error);
        }
        return $this->fillCart('repeat', $order, $data, 'si_msg_cabinet_repeat_success');
    }

    /**
     * @param string $method
     * @param msOrder $order
     * @param array $data
     * @param string $message
     * @return array
     */
    protected function fillCart(string $method, $order, array $data, string $message): array
    {
        if (!empty($data['cleanCart'])) {
            $this->ms2->cart->clean();
        }
        [$added, $skipped] = $this->addItems($data['items'] ?? []);
        if (!$added) {
            return $this->SendIt->error('si_msg_cabinet_tocart_err');
        }
        if (!empty($data['orderFields'])) {
            siOrderFields::apply($this->modx, $this->ms2, $data['orderFields']);
        }
        $data['skipped'] = $skipped;
        $data['cart'] = $this->ms2->cart->status();
        $this->after($method, $data, $order);

        return $this->success($skipped ? 'si_msg_cabinet_tocart_partial' : $message, [
            'redirectUrl' => $data['redirectUrl'] ?? '',
            'cart' => $data['cart'],
            'skipped' => $skipped,
            'orderFields' => $data['orderFields'] ?? [],
        ], ['skipped' => implode(', ', $skipped)]);
    }

    /**
     * Оплата существующего заказа через его способ оплаты (msPayment::send).
     * @return array
     */
    public function pay(): array
    {
        if (!$order = $this->getOrder()) {
            return $this->SendIt->error('si_msg_cabinet_order_nf');
        }
        if (!self::getOrderActions($this->modx, $order->toArray())['pay'] || !$payment = $order->getOne('Payment')) {
            return $this->SendIt->error('si_msg_cabinet_pay_err');
        }
        [$error, $data] = $this->before('pay', [], $order);
        if ($error) {
            return $this->SendIt->error($error);
        }
        if (empty($data['redirectUrl'])) {
            $this->ms2->initialize($order->get('context'), ['json_response' => false]);
            $response = $payment->send($order);
            if (is_string($response)) {
                $response = json_decode($response, true);
            }
            $data['redirectUrl'] = $response['data']['redirect'] ?? '';
        }
        if (empty($data['redirectUrl'])) {
            return $this->SendIt->error('si_msg_cabinet_pay_err');
        }
        $this->after('pay', $data, $order);

        return $this->success('si_msg_cabinet_pay_success', ['redirectUrl' => $data['redirectUrl']]);
    }

    /**
     * Отмена: changeOrderStatus на ms2_status_canceled из статусов si_cabinet_cancel_statuses.
     * @return array
     */
    public function cancel(): array
    {
        if (!$order = $this->getOrder()) {
            return $this->SendIt->error('si_msg_cabinet_order_nf');
        }
        if (!self::getOrderActions($this->modx, $order->toArray())['cancel']) {
            return $this->SendIt->error('si_msg_cabinet_cancel_err');
        }
        [$error, $data] = $this->before('cancel', [
            // miniShop2 заводит ms2_status_canceled со значением 0
            'status' => (int)$this->modx->getOption('ms2_status_canceled', null, 4) ?: 4,
        ], $order);
        if ($error) {
            return $this->SendIt->error($error);
        }
        $result = $this->ms2->changeOrderStatus($order->get('id'), (int)$data['status']);
        if ($result !== true) {
            return $this->SendIt->error(is_string($result) ? $result : 'si_msg_cabinet_cancel_err');
        }
        $this->after('cancel', $data, $order);

        return $this->success('si_msg_cabinet_cancel_success', ['order_id' => $order->get('id')]);
    }
}
