{* Список заказов покупателя с пагинацией. Действия над заказом включаются настройкой si_cabinet_actions. *}
<form id="ordersForm" data-si-form="ordersForm" data-si-preset="user_orders" data-si-nosave></form>
<div data-pn-result="orders">
    {'!Pagination' | snippet: [
        'snippet' => '!Pagination',
        'presetName' => 'user_orders',
        'render' => '!siUserOrders',
        'tpl' => 'siUserOrderTpl',
        'tplProduct' => 'siUserOrderProductTpl',
        'tplEmpty' => '@INLINE <p class="mb-30 fs-18-16">Заказов пока нет.</p>',
        'limit' => 10,
        'pagination' => 'orders',
        'resultBlockSelector' => '[data-pn-result="orders"]',
        'resultShowMethod' => 'insert',
    ]}
</div>
{set $totalPages = 'orders.totalPages' | placeholder}
{set $currentPage = 'orders.currentPage' | placeholder}
<div data-pn-pagination="orders" data-pn-type="" class="d_flex flex_wrap jc_center ai_center gap-15 {$totalPages < 2 ? 'v_hidden' : ''}">
    <button type="button" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_secondary hover_bg_secondary_w hover_shadow_secondary" data-pn-first="1">&#8249;&#8249;</button>
    <button type="button" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_secondary hover_bg_secondary_w hover_shadow_secondary" data-pn-prev="">&#8249;</button>
    <input type="number" name="orderspage" data-pn-current data-si-preset="user_orders" form="ordersForm" class="form__input radius_pill border-1 border_primary px-15 py-15 color_primary fs-18-16 bs_small_shadow" style="width:90px" min="1" max="{$totalPages}" value="{$currentPage ?: 1}">
    <p class="fs-18-16">из <span data-pn-total="">{$totalPages ?: 1}</span></p>
    <button type="button" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_secondary hover_bg_secondary_w hover_shadow_secondary" data-pn-next="">&#8250;</button>
    <button type="button" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_secondary hover_bg_secondary_w hover_shadow_secondary" data-pn-last="{$totalPages}">&#8250;&#8250;</button>
</div>
