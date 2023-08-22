import fileInclude from "gulp-file-include";
import webpHtmlNosvg from "gulp-webp-html-nosvg";
import versionNumber from "gulp-version-number";
import browsersync from "browser-sync";

export const html = () => {
    return app.gulp.src(app.path.src.html)
        .pipe(app.plugins.plumber(
            app.plugins.notify.onError({
                title: "HTML",
                message: "ERROR: <%= error.message %>"
            })
        ))
        .pipe(fileInclude())
        /*.pipe(webpHtmlNosvg())*/
        .pipe(
            app.plugins.if(
                app.isBuild,
                versionNumber({
                    'value' : '%DT%',
                    'append' : {
                        'key':'_v',
                        'cover':0,
                        'to':[
                            'css',
                            'js',
                        ]
                    },
                    'output':{
                        'file': 'gulp/version.json'
                    }
                })
            )
        )
        .pipe(app.gulp.dest(app.path.build.html))
        .pipe(browsersync.stream())
}