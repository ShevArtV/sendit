<form action="#" class="bs_big_shadow border-1 border_main radius_small p-30-15 bg_body"  data-si-form="simpleForm" data-si-preset="simpleform">
    <div class="mb-100 d_flex flex_column">
        <p class="mb-30 fs-24-18 ts_default">
            Обычная форма. Поля &#171;Полное имя&#187; и &#171;Email&#187; обязательны, к тому же проверяется корректность ввода адреса электронной почты.
            Также есть проверка обязательности заполнения чекбокса.
        </p>
        <label class="mb-30">
            <input type="text" name="name"
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="Полное имя">
            <p class="color_error px-15 fs-16-12" data-si-error="name"></p>
        </label>
        <label class="mb-30">
            <input type="text" name="email"
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="Email">
            <p class="color_error px-15 fs-16-12" data-si-error="email"></p>
        </label>

        <div class="form__input_checkbox mb-30">
            <input type="checkbox" name="politics" class="" id="politics">
            <label class="fs-20-16 hover_color_primary" for="politics">Я на всё согласен!</label>
            <p class="color_error px-15 fs-16-12" data-si-error="politics"></p>
        </div>

        <div class="d_flex jc_between ai_center">
            <button type="submit"
                    class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_success hover_bg_success_w hover_shadow_success">Отправить
            </button>
        </div>
    </div>
</form>
