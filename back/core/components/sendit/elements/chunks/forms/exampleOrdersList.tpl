{* Список заказов покупателя с пагинацией. Действия над заказом включаются настройкой si_cabinet_actions. *}
<form id="ordersForm" data-si-form="ordersForm" data-si-preset="user_orders" data-si-nosave></form>
<div data-pn-result="orders">
    {'!Pagination' | snippet: [
        'snippet' => '!Pagination',
        'presetName' => 'user_orders',
        'render' => '!siUserOrders',
        'tpl' => 'siUserOrderTpl',
        'tplProduct' => 'siUserOrderProductTpl',
        'tplEmpty' => '@INLINE <p>Заказов пока нет.</p>',
        'limit' => 10,
        'pagination' => 'orders',
        'resultBlockSelector' => '[data-pn-result="orders"]',
        'resultShowMethod' => 'insert',
    ]}
</div>
{set $totalPages = 'orders.totalPages' | placeholder}
{set $currentPage = 'orders.currentPage' | placeholder}
<div data-pn-pagination="orders" data-pn-type="" class="{$totalPages < 2 ? 'v_hidden' : ''}">
    <button type="button" data-pn-first="1">&#8249;&#8249;</button>
    <button type="button" data-pn-prev="">&#8249;</button>
    <input type="number" name="orderspage" data-pn-current data-si-preset="user_orders" form="ordersForm" min="1" max="{$totalPages}" value="{$currentPage ?: 1}">
    <p>из <span data-pn-total="">{$totalPages ?: 1}</span></p>
    <button type="button" data-pn-next="">&#8250;</button>
    <button type="button" data-pn-last="{$totalPages}">&#8250;&#8250;</button>
</div>
