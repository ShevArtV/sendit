<div class="mb-30 bs_big_shadow border-1 border_main radius_small p-30-15 bg_body{if $selected} border_primary{/if}" data-si-address="{$id}">
    <p class="mb-30 fs-18-16">
        <b>{$name}</b>{if $is_default} (по умолчанию){/if}<br>
        {$receiver}{if $phone}, {$phone}{/if}<br>
        {$city}{if $street}, {$street}{/if}{if $building} {$building}{/if}{if $room}, кв. {$room}{/if}
    </p>
    <div class="d_flex flex_wrap ai_center gap-15">
        <form data-si-form="addressApply{$id}" data-si-preset="address_apply" data-si-nosave>
            <input type="hidden" name="address_id" value="{$id}">
            <button type="submit" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_success hover_bg_success_w hover_shadow_success">{if $selected}Выбран{else}Выбрать{/if}</button>
        </form>
        {if !$is_default}
        <form data-si-form="addressDefault{$id}" data-si-preset="address_default" data-si-nosave>
            <input type="hidden" name="address_id" value="{$id}">
            <button type="submit" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_secondary hover_bg_secondary_w hover_shadow_secondary">Сделать основным</button>
        </form>
        {/if}
        <form data-si-form="addressRemove{$id}" data-si-preset="address_remove" data-si-nosave>
            <input type="hidden" name="address_id" value="{$id}">
            <button type="submit" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_placeholder hover_bg_placeholder_w hover_shadow_main">Удалить</button>
        </form>
    </div>
</div>
