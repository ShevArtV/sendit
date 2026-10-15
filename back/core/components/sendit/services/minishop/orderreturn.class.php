<?php

include_once __DIR__ . '/orders.class.php';

/**
 * Заявка покупателя на возврат позиций заказа. Пакет проверяет заказ, статус, срок и количества;
 * что делать с заявкой (CRM, письмо, смена статуса), решает проект на siOnUserCabinetAction (method requestReturn).
 * Количество к возврату приходит полями return_{msOrderProduct.id}.
 */
class siUserOrderReturn extends siUserOrders
{
    protected const METHODS = ['requestReturn' => 'return'];

    /**
     * @return array
     */
    public function requestReturn(): array
    {
        if (!$order = $this->getOrder()) {
            return $this->SendIt->error('si_msg_cabinet_order_nf');
        }
        if (empty(self::getOrderActions($this->modx, $order->toArray())['return'])) {
            return $this->SendIt->error('si_msg_cabinet_return_err');
        }
        $items = [];
        foreach ($order->getMany('Products') as $product) {
            $count = (int)($_POST['return_' . $product->get('id')] ?? 0);
            if ($count <= 0) {
                continue;
            }
            if ($count > (int)$product->get('count')) {
                return $this->SendIt->error('si_msg_cabinet_return_count_err', [], ['name' => $product->get('name')]);
            }
            $items[] = array_merge($product->toArray(), ['return_count' => $count]);
        }
        if (!$items) {
            return $this->SendIt->error('si_msg_cabinet_return_empty');
        }

        [$error, $data] = $this->before('requestReturn', [
            'items' => $items,
            'reason' => trim((string)($_POST['reason'] ?? '')),
            'comment' => trim((string)($_POST['comment'] ?? '')),
        ], $order);
        if ($error) {
            return $this->SendIt->error($error);
        }
        $this->after('requestReturn', $data, $order);

        return $this->success('si_msg_cabinet_return_success', ['order_id' => (int)$order->get('id')]);
    }
}
