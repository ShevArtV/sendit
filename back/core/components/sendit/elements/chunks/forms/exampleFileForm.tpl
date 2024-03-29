<form action="#" class="bs_big_shadow border-1 border_main radius_small p-30-15 bg_body" enctype="multipart/form-data" data-si-form="formWithFile" data-si-preset="form_with_file">
    <div class="mb-100 d_flex flex_column">
        <p class="mb-30 fs-24-18 ts_default">
            Форма для отправки файла.
        </p>
        <label class="mb-30">
            <input type="text" name="name"
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="Полное имя">
            <p class="color_error px-15 fs-16-12" data-si-error="name"></p>
        </label>

        <label class="mb-30" data-fu-wrap data-si-preset="upload_simple_file" data-si-nosave>
            <div data-fu-progress=""></div>
            <input type="hidden" name="filelist" data-fu-list>
            <input type="file" name="files" data-fu-field multiple
                   class="form__input radius_pill border-1 border_primary focus_shadow_primary px-15 py-15 color_primary fs-18-16 w-100 bs_small_shadow"
                   placeholder="Выберите файл">
            <template data-fu-tpl>
                <button type="button" class="btn file-list__btn" data-fu-path="$path">$filename&nbsp;x</button>
            </template>
        </label>

        <div class="d_flex jc_between ai_center">
            <button type="submit"
                    class="btn btn_md bs_small_shadow radius_pill fs-18-12 color_main bg_success hover_bg_success_w hover_shadow_success">Отправить
            </button>
        </div>
    </div>
</form>