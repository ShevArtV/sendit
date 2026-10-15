<?php

include_once __DIR__ . '/orders.class.php';

/**
 * Вывод заказов текущего покупателя. Работает и сам по себе, и как render пресета Pagination
 * (limit/offset/totalVar приходят от пагинации).
 */
class siUserOrdersList
{
    private \modX $modx;
    private array $params;
    /** @var pdoTools|\modX */
    private $parser;

    public function __construct(\modX $modx, array $params = [])
    {
        $this->modx = $modx;
        $this->params = array_merge([
            'tpl' => 'siUserOrderTpl',
            'tplProduct' => 'siUserOrderProductTpl',
            'tplEmpty' => '',
            'limit' => 10,
            'offset' => 0,
            'sortby' => 'createdon',
            'sortdir' => 'DESC',
            'totalVar' => 'total',
            'context' => $modx->context->get('key'),
            'outputSeparator' => "\n",
        ], $params);
        $this->parser = $modx->getService('pdoTools') ?: $modx;
    }

    /**
     * @return string
     */
    public function run(): string
    {
        $userId = $this->modx->user->isAuthenticated($this->modx->context->get('key')) ? (int)$this->modx->user->get('id') : 0;
        $ms2 = $userId ? siCabinet::loadMiniShop($this->modx) : null;
        if (!$ms2) {
            $this->modx->setPlaceholder($this->params['totalVar'], 0);
            return $this->params['tplEmpty'] ? $this->parser->getChunk($this->params['tplEmpty'], $this->params) : '';
        }

        $where = ['user_id' => $userId];
        if ($this->params['context']) {
            $where['context'] = $this->params['context'];
        }
        $c = $this->modx->newQuery('msOrder', $where);
        $total = $this->modx->getCount('msOrder', $c);
        $this->modx->setPlaceholder($this->params['totalVar'], $total);

        $sortby = array_key_exists($this->params['sortby'], $this->modx->getFields('msOrder')) ? $this->params['sortby'] : 'createdon';
        $c->sortby('msOrder.' . $sortby, strtoupper($this->params['sortdir']) === 'ASC' ? 'ASC' : 'DESC');
        $c->limit((int)$this->params['limit'], (int)$this->params['offset']);
        $orders = $this->modx->getCollectionGraph('msOrder', '{"Status":{},"Delivery":{},"Payment":{},"Address":{}}', $c);
        if (!$orders) {
            return $this->params['tplEmpty'] ? $this->parser->getChunk($this->params['tplEmpty'], $this->params) : '';
        }

        $ids = [];
        foreach ($orders as $order) {
            $ids[] = (int)$order->get('id');
        }
        $products = $this->getProducts($ids, $ms2);
        $output = [];
        foreach ($orders as $order) {
            $id = (int)$order->get('id');
            $output[] = $this->parser->getChunk($this->params['tpl'], $this->prepareOrder($order, $products[$id] ?? [], $ms2));
        }
        return implode($this->params['outputSeparator'], $output);
    }

    /**
     * @param msOrder $order
     * @param array $products
     * @param miniShop2 $ms2
     * @return array
     */
    private function prepareOrder($order, array $products, $ms2): array
    {
        $data = $order->toArray();
        foreach (['Status' => 'status', 'Delivery' => 'delivery', 'Payment' => 'payment'] as $alias => $prefix) {
            $related = $order->getOne($alias);
            $data[$prefix . '_name'] = $related ? $related->get('name') : '';
            if ($alias === 'Status') {
                $data['status_color'] = $related ? $related->get('color') : '';
            }
            if ($alias === 'Payment') {
                $data['payment_class'] = $related ? (string)$related->get('class') : '';
            }
        }
        $address = $order->getOne('Address');
        $data['address'] = $address ? $address->toArray() : [];
        foreach ($data['address'] as $key => $value) {
            $data['address_' . $key] = $value;
        }
        foreach (['cost', 'cart_cost', 'delivery_cost'] as $key) {
            $data[$key . '_formatted'] = $ms2->formatPrice($data[$key]);
        }
        $data['products_list'] = $products;
        $data['products'] = '';
        if ($this->params['tplProduct']) {
            $rows = [];
            foreach ($products as $product) {
                $rows[] = $this->parser->getChunk($this->params['tplProduct'], array_merge($product, ['order_id' => $data['id']]));
            }
            $data['products'] = implode($this->params['outputSeparator'], $rows);
        }
        $data['actions'] = siUserOrders::getOrderActions($this->modx, $data);
        foreach ($data['actions'] as $action => $allowed) {
            $data['can_' . $action] = $allowed;
        }
        return array_merge($this->params, $data);
    }

    /**
     * Позиции всех заказов страницы одним запросом.
     * @param int[] $orderIds
     * @param miniShop2 $ms2
     * @return array [order_id => [позиции]]
     */
    private function getProducts(array $orderIds, $ms2): array
    {
        $c = $this->modx->newQuery('msOrderProduct', ['order_id:IN' => $orderIds]);
        $c->leftJoin('msProduct', 'Product', 'Product.id = msOrderProduct.product_id');
        $c->select($this->modx->getSelectColumns('msOrderProduct', 'msOrderProduct'));
        $c->select('Product.uri, Product.published, Product.deleted');
        $c->sortby('msOrderProduct.id', 'ASC');
        $result = [];
        if ($c->prepare() && $c->stmt->execute()) {
            while ($row = $c->stmt->fetch(PDO::FETCH_ASSOC)) {
                $row['options'] = json_decode((string)$row['options'], true) ?: [];
                $row['price_formatted'] = $ms2->formatPrice($row['price']);
                $row['cost_formatted'] = $ms2->formatPrice($row['cost']);
                $row['available'] = !empty($row['published']) && empty($row['deleted']);
                $result[(int)$row['order_id']][] = $row;
            }
        }
        return $result;
    }
}
