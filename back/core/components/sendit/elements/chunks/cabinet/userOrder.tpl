<div class="mb-30 p-30-15 border-1 border_main radius_small" data-si-order="{$id}">
    <div class="d_flex jc_between ai_center mb-15">
        <p class="fs-20-16">Заказ №{$num} от {$createdon | date: 'd.m.Y'}</p>
        <span style="color:#{$status_color}">{$status_name}</span>
    </div>
    <ul class="mb-15">{$products}</ul>
    <p class="mb-15">
        Доставка: {$delivery_name}{if $address_city}, {$address_city}{/if}{if $address_street}, {$address_street}{/if}{if $address_building} {$address_building}{/if}<br>
        Оплата: {$payment_name}<br>
        Итого: <b>{$cost_formatted}</b>
    </p>
    <div class="d_flex ai_center">
        {if $can_tocart}
        <form data-si-form="orderToCart{$id}" data-si-preset="order_tocart" data-si-nosave class="mr-15">
            <input type="hidden" name="order_id" value="{$id}">
            <button type="submit" class="btn btn_md">В корзину</button>
        </form>
        {/if}
        {if $can_repeat}
        <form data-si-form="orderRepeat{$id}" data-si-preset="order_repeat" data-si-nosave class="mr-15">
            <input type="hidden" name="order_id" value="{$id}">
            <button type="submit" class="btn btn_md">Повторить</button>
        </form>
        {/if}
        {if $can_pay}
        <form data-si-form="orderPay{$id}" data-si-preset="order_pay" data-si-nosave class="mr-15">
            <input type="hidden" name="order_id" value="{$id}">
            <button type="submit" class="btn btn_md">Оплатить</button>
        </form>
        {/if}
        {if $can_cancel}
        <form data-si-form="orderCancel{$id}" data-si-preset="order_cancel" data-si-nosave>
            <input type="hidden" name="order_id" value="{$id}">
            <button type="submit" class="btn btn_md">Отменить</button>
        </form>
        {/if}
    </div>
</div>
