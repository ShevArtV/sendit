import dartSass from 'sass';
import rename from 'gulp-rename';
import gulpSass from 'gulp-sass';
import purgecss from 'gulp-purgecss';
import cleanCss from 'gulp-clean-css';
import webpcss from 'gulp-webpcss';
import autoprefixer from 'gulp-autoprefixer';
import groupCssMediaQueries from 'gulp-group-css-media-queries';
import gulp from "gulp";

const sass = gulpSass(dartSass);

export const scss = () => {
    return app.gulp.src(app.path.src.scss, {sourcemaps: true})
        .pipe(app.plugins.plumber(
            app.plugins.notify.onError({
                title: "SCSS",
                message: "ERROR: <%= error.message %>"
            })
        ))
        .pipe(sass({
            outputStyle: 'expanded'
        }, true))
        .pipe(
            app.plugins.if(
                app.isBuild,
                groupCssMediaQueries()
            )
        )
       /* .pipe(webpcss({
            webpClass: ".webp",
            noWebpClass: ".no-webp",
        }))*/
        .pipe(
            app.plugins.if(
                app.isBuild,
                autoprefixer({
                    grid: true,
                    overrideBrowserList: ["last 5 versions"],
                    cascade: true
                })
            )
        )
        .pipe(app.gulp.dest(app.path.build.css))
}

export const purge = () => {
    return gulp.src(app.path.watch.css)
        .pipe(app.plugins.plumber(
            app.plugins.notify.onError({
                title: "SCSS",
                message: "ERROR: <%= error.message %>"
            })
        ))
        .pipe(purgecss({
            content: [app.path.watch.html, app.path.watch.js]
        }))
        //.pipe(cleanCss())
       /* .pipe(rename({
            extname: ".min.css"
        }))*/
        .pipe(gulp.dest(app.path.build.css))
        .pipe(app.plugins.browsersync.stream());
}

export const scssLibs = () => {
    return app.gulp.src(app.path.src.scssLibs, {sourcemaps: true})
        .pipe(app.plugins.plumber(
            app.plugins.notify.onError({
                title: "SCSSLIBS",
                message: "ERROR: <%= error.message %>"
            })
        ))
        .pipe(sass({
            outputStyle: 'expanded'
        }, true))
        .pipe(
            app.plugins.if(
                app.isBuild,
                groupCssMediaQueries()
            )
        )
        /*.pipe(webpcss({
            webpClass: ".webp",
            noWebpClass: ".no-webp",
        }))*/
        .pipe(
            app.plugins.if(
                app.isBuild,
                autoprefixer({
                    grid: true,
                    overrideBrowserList: ["last 5 versions"],
                    cascade: true
                })
            )
        )
        .pipe(app.gulp.dest(app.path.build.css))
        .pipe(
            app.plugins.if(
                app.isBuild,
                cleanCss()
            )
        )
        .pipe(rename({
            extname: ".min.css"
        }))
        .pipe(app.gulp.dest(app.path.build.css))
        .pipe(app.plugins.browsersync.stream());
}