<!-- Вход по одноразовому коду: «Получить код» шлёт форму пресетом code_send, «Войти» — пресетом code_auth.
     Для регистрации: data-si-preset="code_register" у формы, code_send_register у кнопки и поле fullname. -->
<form action="#" class="bs_big_shadow border-1 border_main radius_small p-30-15 bg_body" data-si-form="codeAuthForm" data-si-preset="code_auth" data-si-nosave>
    <p class="mb-30 fs-24-18 ts_default">Вход по коду</p>
    <input type="hidden" name="errorLogin">
    <p class="color_error px-15 fs-16-12" data-si-error="errorLogin"></p>
    <label class="mb-30 d_flex flex_column">
        <input type="tel" name="phone" class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow" placeholder="Телефон">
        <p class="color_error px-15 fs-16-12" data-si-error="phone"></p>
    </label>
    <label class="mb-30 d_flex flex_column">
        <input type="text" name="code" class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow" placeholder="Код" inputmode="numeric" autocomplete="one-time-code">
        <p class="color_error px-15 fs-16-12" data-si-error="code"></p>
    </label>
    <div class="d_flex flex_wrap ai_center gap-15">
        <button type="button" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_secondary hover_bg_secondary_w hover_shadow_secondary" data-si-preset="code_send" data-si-event="click">Получить код</button>
        <button type="submit" class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_success hover_bg_success_w hover_shadow_success">Войти</button>
    </div>
</form>
