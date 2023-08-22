<form action="#" class="bs_big_shadow border-1 border_main radius_small p-30-15 bg_body" data-si-form="quizBig" data-si-preset="quiz">
    <div class="progress border-1 border_main radius_pill mb-60" data-qf-progress>
        <div class="progress__value bg_success radius_pill ta_center color_main" data-qf-progress-value>0%</div>
    </div>

    <!-- Общие вопросы -->
    <div data-qf-item="1" class="mb-100 d_flex flex_column">
        <p class="mb-30 fs-24-18 ts_default">Сфера деятельности Вашей компании или название продукта?</p>
        <input type="hidden" name="questions[1]" value="Сфера деятельности Вашей компании?">
        <label class="mb-15">
            <input type="text" name="answers[1]"
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="Продажа вантузов">
            <p class="color_error px-15 fs-16-12" data-si-error="answers[1]"></p>
        </label>
    </div>
    <div data-qf-item="2" data-qf-auto="1" class="mb-100 d_flex flex_column">
        <p class="mb-30 fs-24-18 ts_default">Есть ли действующий сайт?</p>
        <input type="hidden" name="questions[2]" value="Есть ли действующий сайт?">
        <div class="form__input_radio mb-15">
            <input type="radio" name="answers[2]" data-qf-next="" class="" id="answer-2-1" value="Да">
            <label class="fs-20-16 hover_color_primary" for="answer-2-1">Да</label>
        </div>
        <div class="form__input_radio mb-15">
            <input type="radio" name="answers[2]" data-qf-next="4" class="" id="answer-2-2" value="Нет">
            <label class="fs-20-16 hover_color_primary" for="answer-2-2">Нет</label>
        </div>
    </div>
    <div data-qf-item="3" class="mb-100 d_flex flex_column">
        <p class="mb-30 fs-24-18 ts_default">Укажите ссылку на действующий сайт и кратко опишите, что Вас в нём не устраивает?</p>
        <input type="hidden" name="questions[3]" value="Что не так с действующим сайтом?">
        <label class="mb-15">
                                    <textarea name="answers[3]"
                                              class="form__textarea radius_middle border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                                              placeholder="Причины заказа нового сайта"></textarea>
            <p class="color_error px-15 fs-16-12" data-si-error="answers[3]"></p>
        </label>
    </div>
    <div data-qf-item="4" class="mb-100 d_flex flex_column">
        <p class="mb-30 fs-24-18 ts_default">Укажите 2-3 ссылки на сайты, которые Вам НЕ нравятся и кратко опишите, что именно НЕ нравится?</p>
        <input type="hidden" name="questions[4]" value="Какие сайты не нравятся?">
        <label class="mb-15">
                                    <textarea name="answers[4]"
                                              class="form__textarea radius_middle border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                                              placeholder="На сайте гугла мне не нравятся кнопки  - портят лаконичность"></textarea>
            <p class="color_error px-15 fs-16-12" data-si-error="answers[4]"></p>
        </label>
    </div>
    <div data-qf-item="5" class="mb-100 d_flex flex_column">
        <p class="mb-30 fs-24-18 ts_default">Укажите 2-3 ссылки на сайты, которые Вам нравятся и кратко опишите, что именно нравится?</p>
        <input type="hidden" name="questions[5]" value="Какие сайты нравятся?">
        <label class="mb-15">
                                    <textarea name="answers[5]"
                                              class="form__textarea radius_middle border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                                              placeholder="Мне нравится этот сайт возможностью изменить тему с темной на светлую и обратно"></textarea>
            <p class="color_error px-15 fs-16-12" data-si-error="answers[5]"></p>
        </label>
    </div>

    <div data-qf-item="49" class="mb-100 d_flex flex_column">
        <p class="mb-30 fs-24-18 ts_default">На каких языках будет публиковаться контент сайта?</p>
        <input type="hidden" name="questions[6]" value="На каких языках будет публиковаться контент сайта?">
        <label class="mb-15">
            <input type="text" name="answers[6]"
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="Русский">
            <p class="color_error px-15 fs-16-12" data-si-error="answers[6]"></p>
        </label>
    </div>
    <!-- Общие вопросы -->

    <!-- Вопросы по многостраничнику -->
    <div data-qf-item="38" class="mb-100 d_flex flex_column">
        <p class="mb-30 fs-24-18 ts_default">Выберите внутренние страницы сайта?</p>
        <input type="hidden" name="questions[7]" value="Выберите внутренние страницы сайта?">
        <div class="grid-2-1 gap-15">
            <div class="form__input_checkbox mb-15">
                <input type="checkbox" name="answers[7][]" class="" id="answer-34-1" value="О компании">
                <label class="fs-20-16 hover_color_primary" for="answer-34-1">О компании</label>
            </div>
            <div class="form__input_checkbox mb-15">
                <input type="checkbox" name="answers[7][]" class="" id="answer-34-2" value="Контакты">
                <label class="fs-20-16 hover_color_primary" for="answer-34-2">Контакты</label>
            </div>
            <div class="form__input_checkbox mb-15">
                <input type="checkbox" name="answers[7][]" class="" id="answer-34-3" value="Блог">
                <label class="fs-20-16 hover_color_primary" for="answer-34-3">Блог</label>
            </div>
            <div class="form__input_checkbox mb-15">
                <input type="checkbox" name="answers[7][]" class="" id="answer-34-4" value="Статья">
                <label class="fs-20-16 hover_color_primary" for="answer-34-4">Статья</label>
            </div>
            <div class="form__input_checkbox mb-15">
                <input type="checkbox" name="answers[7][]" class="" id="answer-34-5" value="Новости">
                <label class="fs-20-16 hover_color_primary" for="answer-34-5">Новости</label>
            </div>
            <div class="form__input_checkbox mb-15">
                <input type="checkbox" name="answers[7][]" class="" id="answer-34-6" value="Новость">
                <label class="fs-20-16 hover_color_primary" for="answer-34-6">Новость</label>
            </div>
            <div class="form__input_checkbox mb-15">
                <input type="checkbox" name="answers[7][]" class="" id="answer-34-7" value="Оплата и доставка">
                <label class="fs-20-16 hover_color_primary" for="answer-34-7">Оплата и доставка</label>
            </div>
            <div class="form__input_checkbox mb-15">
                <input type="checkbox" name="answers[7][]" class="" id="answer-34-8" value="Кейсы/портфолио/гелерея">
                <label class="fs-20-16 hover_color_primary" for="answer-34-8">Кейсы/портфолио/гелерея</label>
            </div>
        </div>
        <p class="color_error px-15 fs-16-12" data-si-error="answers[7][]"></p>
    </div>
    <!-- /Вопросы по многостраничнику -->

    <!-- Финальные блоки -->
    <div data-qf-item="47" data-qf-auto="1" class="mb-100 d_flex flex_column">
        <p class="mb-30 fs-24-18 ts_default">
            <span class="color_primary">Отлично!</span> Остался всего один шаг <br>
            и мы <span class="color_primary">подготовим</span> Вам предварительный <span class="color_primary">расчет.</span>
        </p>
        <label class="mb-30">
            <input type="text" name="name"
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="Ваше имя">
            <p class="color_error px-15 fs-16-12"></p>
        </label>
        <label class="">
            <input type="tel" name="phone"
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="+7(">
            <p class="color_error px-15 fs-16-12"></p>
        </label>
    </div>
    <div data-qf-finish class="mb-100 d_flex flex_column">
        <p class="mb-30 fs-24-18 ts_default ta_center">
            <span class="color_primary">Спасибо за потраченное время!</span><br>
            Ваш подарок: скидка 10% на первый заказ!
        </p>
    </div>
    <!-- /Финальные блоки -->

    <!-- Кнопки управления и пагинация -->
    <div class="d_flex jc_between ai_center">
        <button data-qf-btn="prev" type="button"
                class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_placeholder hover_bg_placeholder_w hover_shadow_main">Назад
        </button>
        <p class="fs-20-16 ts_default" data-qf-pages>
            <span data-qf-page></span>/<span data-qf-total></span>
        </p>
        <button data-qf-btn="next" type="button"
                class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main-alt bg_secondary hover_bg_secondary_w hover_shadow_secondary">Вперед
        </button>
        <div data-qf-btn="reset" class="d_flex jc_center ai_center w-100">
            <button type="reset"
                    class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main-alt bg_secondary hover_bg_secondary_w hover_shadow_secondary">
                Начать с начала
            </button>
        </div>
        <button data-qf-btn="send" type="submit"
                class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_success hover_bg_success_w hover_shadow_success">Отправить
        </button>
    </div>
    <!-- /Кнопки управления и пагинация -->
</form>