<div class="mb-15 p-15 border-1 radius_small{if $selected} border_success{else} border_main{/if}" data-si-address="{$id}">
    <p class="mb-15">
        <b>{$name}</b>{if $is_default} (по умолчанию){/if}<br>
        {$receiver}{if $phone}, {$phone}{/if}<br>
        {$city}{if $street}, {$street}{/if}{if $building} {$building}{/if}{if $room}, кв. {$room}{/if}
    </p>
    <div class="d_flex ai_center">
        <form data-si-form="addressApply{$id}" data-si-preset="address_apply" data-si-nosave class="mr-15">
            <input type="hidden" name="address_id" value="{$id}">
            <button type="submit" class="btn btn_md">{if $selected}Выбран{else}Выбрать{/if}</button>
        </form>
        {if !$is_default}
        <form data-si-form="addressDefault{$id}" data-si-preset="address_default" data-si-nosave class="mr-15">
            <input type="hidden" name="address_id" value="{$id}">
            <button type="submit" class="btn btn_md">Сделать основным</button>
        </form>
        {/if}
        <form data-si-form="addressRemove{$id}" data-si-preset="address_remove" data-si-nosave>
            <input type="hidden" name="address_id" value="{$id}">
            <button type="submit" class="btn btn_md">Удалить</button>
        </form>
    </div>
</div>
