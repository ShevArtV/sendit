import replace from "gulp-replace"; // поиск и замена
import plumber from "gulp-plumber"; // обработка ошибок
import notify from "gulp-notify"; // сообщения (подсказки)
import browsersync from "browser-sync"; // сообщения (подсказки)
import newer from "gulp-newer"; // сообщения (подсказки)
import ifPlugin from "gulp-if"; // сообщения (подсказки)

export const plugins = {
    replace: replace,
    plumber: plumber,
    notify: notify,
    browsersync: browsersync,
    newer: newer,
    if: ifPlugin,
}