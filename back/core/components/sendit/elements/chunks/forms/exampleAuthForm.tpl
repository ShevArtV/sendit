<form action="#" class="bs_big_shadow border-1 border_main radius_small p-30-15 bg_body" data-si-form="authForm" data-si-preset="auth">
    <input type="hidden" name="errorLogin">
    <!-- Финальные блоки -->
    <div data-qf-item="47" class="mb-100 d_flex flex_column">
        <p class="mb-30 fs-24-18 ts_default">
            Форма авторизации.
        </p>
        <label class="mb-30">
            <input type="text" name="email"
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="Email">
            <p class="color_error px-15 fs-16-12" data-si-error="email"></p>
        </label>
        <label class="mb-30">
            <input type="password" name="password"
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="Введите пароль">
            <p class="color_error px-15 fs-16-12" data-si-error="password"></p>
        </label>
    </div>
    <!-- /Финальные блоки -->

    <!-- Кнопки управления и пагинация -->
    <div class="d_flex jc_between ai_center">
        <button
                class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_success hover_bg_success_w hover_shadow_success">Отправить
        </button>
    </div>
    <!-- /Кнопки управления и пагинация -->
</form>