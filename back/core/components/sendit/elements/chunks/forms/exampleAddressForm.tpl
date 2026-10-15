{* Сохранённые адреса доставки и форма нового адреса.
   На странице оформления тот же вызов siUserAddresses даёт быстрый выбор: кнопка «Выбрать» заполняет форму #msOrder. *}
{'!siUserAddresses' | snippet: [
    'tpl' => 'siUserAddressTpl',
    'tplEmpty' => '@INLINE <p class="mb-30 fs-18-16">Адресов пока нет.</p>',
]}

<form action="#" class="mb-60 bs_big_shadow border-1 border_main radius_small p-30-15 bg_body" data-si-form="addressForm" data-si-preset="address_save">
    <p class="mb-30 fs-24-18 ts_default">Новый адрес доставки</p>
    <label class="mb-15 d_flex flex_column">
        <input type="text" name="name" class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow" placeholder="Название, например «Дом»">
    </label>
    {foreach ['receiver' => 'Получатель', 'phone' => 'Телефон', 'email' => 'Email', 'index' => 'Индекс', 'region' => 'Регион', 'city' => 'Город', 'street' => 'Улица', 'building' => 'Дом', 'entrance' => 'Подъезд', 'floor' => 'Этаж', 'room' => 'Квартира', 'comment' => 'Комментарий'] as $field => $label}
    <label class="mb-15 d_flex flex_column">
        <input type="text" name="{$field}" class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow" placeholder="{$label}">
        <p class="color_error px-15 fs-16-12" data-si-error="{$field}"></p>
    </label>
    {/foreach}
    <div class="form__input_checkbox mb-30">
        <input type="checkbox" name="is_default" value="1" id="addressIsDefault">
        <label class="fs-20-16 hover_color_primary" for="addressIsDefault">Сделать основным</label>
    </div>
    <button type="submit" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_success hover_bg_success_w hover_shadow_success">Сохранить адрес</button>
</form>
