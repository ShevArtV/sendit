<form class="mb-60" data-si-form="logoutForm" data-si-preset="logout">
    <input type="hidden" name="errorLogout">
    <button type="submit"
            class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_placeholder hover_bg_placeholder_w hover_shadow_main">Выйти
    </button>
</form>


<form action="#" class="bs_big_shadow border-1 border_main radius_small p-30-15 bg_body mb-60" data-si-form="dataForm" data-si-preset="dataedit">

    <!-- Финальные блоки -->
    <div data-qf-item="47" class="mb-100 d_flex flex_column">
        <p class="mb-30 fs-24-18 ts_default">
            Форма изменения личных данных.
        </p>
        <label class="mb-30">
            <input type="text" name="fullname" value="{$_modx->user.fullname}"
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="Полное имя">
            <p class="color_error px-15 fs-16-12" data-si-error="fullname"></p>
        </label>
        <label class="mb-30">
            <input type="text" name="email" value="{$_modx->user.email}"
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="Email">
            <p class="color_error px-15 fs-16-12" data-si-error="email"></p>
        </label>
        <label class="mb-30">
            <input type="tel" name="phone" value="{$_modx->user.phone}"
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="+7(">
            <p class="color_error px-15 fs-16-12" data-si-error="phone"></p>
        </label>
    </div>
    <!-- /Финальные блоки -->

    <!-- Кнопки управления и пагинация -->
    <div class="d_flex jc_between ai_center">
        <button type="submit"
                class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_success hover_bg_success_w hover_shadow_success">Сохранить
        </button>
    </div>
    <!-- /Кнопки управления и пагинация -->
</form>

<form action="#" class="bs_big_shadow border-1 border_main radius_small p-30-15 bg_body mb-60" data-si-form="editPassForm" data-si-preset="editpass">

    <!-- Финальные блоки -->
    <div data-qf-item="47" class="mb-100 d_flex flex_column">
        <p class="mb-30 fs-24-18 ts_default">
            Изменить пароль.
        </p>
        <label class="mb-30">
            <input type="password" name="password"
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="Введите пароль">
            <p class="color_error px-15 fs-16-12" data-si-error="password"></p>
        </label>
        <label class="mb-30">
            <input type="password" name="password_confirm"
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="Подтвердите пароль">
            <p class="color_error px-15 fs-16-12" data-si-error="password_confirm"></p>
        </label>
    </div>
    <!-- /Финальные блоки -->

    <!-- Кнопки управления и пагинация -->
    <div class="d_flex jc_between ai_center">
        <button type="submit"
                class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main-alt bg_secondary hover_bg_secondary_w hover_shadow_secondary">Изменить
        </button>
    </div>
    <!-- /Кнопки управления и пагинация -->
</form>
