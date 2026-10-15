{* Сохранённые адреса доставки и форма нового адреса.
   На странице оформления тот же вызов siUserAddresses даёт быстрый выбор: кнопка «Выбрать» заполняет форму #msOrder. *}
{'!siUserAddresses' | snippet: [
    'tpl' => 'siUserAddressTpl',
    'tplEmpty' => '@INLINE <p>Адресов пока нет.</p>',
]}

<form action="#" class="mb-60" data-si-form="addressForm" data-si-preset="address_save">
    <p class="mb-30 fs-24-18">Новый адрес доставки</p>
    <label class="mb-15 d_block">
        <input type="text" name="name" placeholder="Название, например «Дом»">
    </label>
    {foreach ['receiver' => 'Получатель', 'phone' => 'Телефон', 'email' => 'Email', 'index' => 'Индекс', 'region' => 'Регион', 'city' => 'Город', 'street' => 'Улица', 'building' => 'Дом', 'entrance' => 'Подъезд', 'floor' => 'Этаж', 'room' => 'Квартира', 'comment' => 'Комментарий'] as $field => $label}
    <label class="mb-15 d_block">
        <input type="text" name="{$field}" placeholder="{$label}">
        <p class="color_error fs-16-12" data-si-error="{$field}"></p>
    </label>
    {/foreach}
    <label class="mb-30 d_block">
        <input type="checkbox" name="is_default" value="1"> Сделать основным
    </label>
    <button type="submit" class="btn btn_md">Сохранить адрес</button>
</form>
