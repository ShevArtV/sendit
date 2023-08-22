import webp from "gulp-webp";
import imagemin from "gulp-imagemin";

export const img = () => {
    return app.gulp.src(app.path.src.img)
        .pipe(app.plugins.plumber(
            app.plugins.notify.onError({
                title: "IMAGES",
                message: "ERROR: <%= error.message %>"
            })
        ))
        .pipe(app.plugins.newer(app.path.build.img))
        .pipe(webp())
        .pipe(app.gulp.dest(app.path.build.img))
        .pipe(
            app.plugins.if(
                app.isBuild,
                app.gulp.src(app.path.src.img),
                app.plugins.newer(app.path.build.img),
                imagemin({
                    progressive: true,
                    svgoPlugins: [{ removeViewBox: false }],
                    interlaced: true,
                    optimizationLevel: 3 // 0 to 7
                }),
                app.gulp.dest(app.path.build.img),
            )
        )
        .pipe(app.plugins.if(
            app.isDev,
            app.gulp.src(app.path.src.img),
            app.gulp.dest(app.path.build.img),
        ))
        .pipe(app.gulp.src(app.path.src.svg))
        .pipe(app.gulp.dest(app.path.build.img))
        .pipe(app.plugins.browsersync.stream());
}