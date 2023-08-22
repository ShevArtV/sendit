import webpack from "webpack-stream";

export const js = () => {
    return app.gulp.src(app.path.src.js, {sourcemaps: true})
        .pipe(app.plugins.plumber(
            app.plugins.notify.onError({
                title: "SCRIPTS",
                message: "ERROR: <%= error.message %>"
            })
        ))
        .pipe(webpack({
            mode: 'production',
            optimization: {
                minimize: false,
            },
            output: {
                filename: "index.min.js"
            }
        }))
        .pipe(app.gulp.dest(app.path.build.js))
        .pipe(app.plugins.browsersync.stream());
}

export const copyModules = () => {
    return app.gulp.src(app.path.src.jsModules)
        .pipe(app.gulp.dest(app.path.build.jsModules))
}