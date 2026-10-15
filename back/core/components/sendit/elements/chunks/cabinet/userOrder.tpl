<div class="mb-30 bs_big_shadow border-1 border_main radius_small p-30-15 bg_body" data-si-order="{$id}">
    <div class="d_flex flex_wrap jc_between ai_center gap-15 mb-15">
        <p class="fs-24-18 ts_default">Заказ №{$num} от {$createdon | date: 'd.m.Y'}</p>
        <span class="fs-18-16" style="color:#{$status_color}">{$status_name}</span>
    </div>
    <ul class="mb-15 fs-18-16">{$products}</ul>
    <p class="mb-30 fs-18-16">
        Доставка: {$delivery_name}{if $address_city}, {$address_city}{/if}{if $address_street}, {$address_street}{/if}{if $address_building} {$address_building}{/if}<br>
        Оплата: {$payment_name}<br>
        Итого: <b>{$cost_formatted}</b>
    </p>
    <div class="d_flex flex_wrap ai_center gap-15">
        {if $can_tocart}
        <form data-si-form="orderToCart{$id}" data-si-preset="order_tocart" data-si-nosave>
            <input type="hidden" name="order_id" value="{$id}">
            <button type="submit" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_success hover_bg_success_w hover_shadow_success">В корзину</button>
        </form>
        {/if}
        {if $can_repeat}
        <form data-si-form="orderRepeat{$id}" data-si-preset="order_repeat" data-si-nosave>
            <input type="hidden" name="order_id" value="{$id}">
            <button type="submit" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_secondary hover_bg_secondary_w hover_shadow_secondary">Повторить</button>
        </form>
        {/if}
        {if $can_pay}
        <form data-si-form="orderPay{$id}" data-si-preset="order_pay" data-si-nosave>
            <input type="hidden" name="order_id" value="{$id}">
            <button type="submit" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_success hover_bg_success_w hover_shadow_success">Оплатить</button>
        </form>
        {/if}
        {if $can_cancel}
        <form data-si-form="orderCancel{$id}" data-si-preset="order_cancel" data-si-nosave>
            <input type="hidden" name="order_id" value="{$id}">
            <button type="submit" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_placeholder hover_bg_placeholder_w hover_shadow_main">Отменить</button>
        </form>
        {/if}
    </div>
    {if $can_return}
    <details class="pt-20">
        <summary class="fs-18-16 color_primary">Оформить возврат</summary>
        <form data-si-form="orderReturn{$id}" data-si-preset="order_return" data-si-nosave class="pt-20">
            <input type="hidden" name="order_id" value="{$id}">
            {foreach $products_list as $product}
            <label class="d_flex ai_center jc_between gap-15 mb-15 fs-18-16">
                <span>{$product.name} <small>(в заказе {$product.count})</small></span>
                <input type="number" name="return_{$product.id}" value="0" min="0" max="{$product.count}" class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow" style="max-width:120px">
            </label>
            {/foreach}
            <label class="mb-15 d_flex flex_column">
                <select name="reason" class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow">
                    <option value="">Причина возврата</option>
                    <option>Не подошёл размер</option>
                    <option>Брак или повреждение</option>
                    <option>Не соответствует описанию</option>
                    <option>Другое</option>
                </select>
                <p class="color_error px-15 fs-16-12" data-si-error="reason"></p>
            </label>
            <label class="mb-30 d_flex flex_column">
                <textarea name="comment" class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow" placeholder="Комментарий"></textarea>
            </label>
            <button type="submit" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_success hover_bg_success_w hover_shadow_success">Отправить заявку</button>
        </form>
    </details>
    {/if}
</div>
