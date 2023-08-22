import gulp from "gulp";

import {path} from "./gulp/config/path.js";
import {plugins} from "./gulp/config/plugins.js";

global.app = {
    isBuild: process.argv.includes('--build'),
    isDev: !process.argv.includes('--build'),
    path: path,
    gulp: gulp,
    plugins: plugins
}

import {copyIcons} from "./gulp/tasks/copy.js"
import {reset} from "./gulp/tasks/reset.js"
import {server} from "./gulp/tasks/server.js"
import {html} from "./gulp/tasks/html.js"
import {scss, purge, scssLibs} from "./gulp/tasks/scss.js"
import {copyModules, js} from "./gulp/tasks/js.js"
import {img} from "./gulp/tasks/img.js"
import {copyFonts, otfToTtf, ttfToWoff, fontsStyle} from "./gulp/tasks/fonts.js"

function watcher() {
    gulp.watch(path.watch.html, gulp.series(html,purge));
    gulp.watch(path.watch.scss, gulp.series(scss,purge));
    gulp.watch(path.watch.js, gulp.parallel(js,copyModules));
    gulp.watch(path.watch.img, img);
}

const fonts = gulp.series(otfToTtf, ttfToWoff, fontsStyle, copyFonts);

const prodTasks = gulp.series(fonts, copyIcons, gulp.parallel(html, scssLibs, gulp.series(scss,purge), js, copyModules, img));
const devTasks = gulp.series(fonts, copyIcons, gulp.parallel(html, scssLibs, gulp.series(scss,purge), js, copyModules, img));

const prod = gulp.series(reset, prodTasks, gulp.parallel(watcher,server));
const dev = gulp.series(reset, devTasks, gulp.parallel(watcher,server));
gulp.task('default', dev);
gulp.task('production', prod);
