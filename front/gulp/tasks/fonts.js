import fs from 'fs';
import fonter from 'gulp-fonter';
import ttf2woff2 from 'gulp-ttf2woff2';

export const copyFonts = () => {
    return app.gulp.src(app.path.src.fonts)
        .pipe(app.gulp.dest(app.path.build.fonts))
}

export const otfToTtf = () => {
    return app.gulp.src(`${app.path.srcFolder}/assets/project_files/fonts/*.otf`, {})
        .pipe(app.plugins.plumber(
            app.plugins.notify.onError({
                title: "FONTS",
                message: "ERROR: <%= error.message %>"
            })
        ))
        .pipe(fonter({
            formats: ['ttf']
        }))
        .pipe(app.gulp.dest(`${app.path.srcFolder}/assets/project_files/fonts/`))
}

export const ttfToWoff = () => {
    return app.gulp.src(`${app.path.srcFolder}/assets/project_files/fonts/*.ttf`, {})
        .pipe(app.plugins.plumber(
            app.plugins.notify.onError({
                title: "FONTS",
                message: "ERROR: <%= error.message %>"
            })
        ))
        .pipe(fonter({
            formats: ['woff']
        }))
        .pipe(app.gulp.dest(`${app.path.build.fonts}`))
        .pipe(app.gulp.src(`${app.path.srcFolder}/assets/project_files/fonts/*.ttf`))
        .pipe(ttf2woff2())
        .pipe(app.gulp.dest(`${app.path.build.fonts}`))
}

export const fontsStyle = () => {
    let fontsFile = `${app.path.srcFolder}/assets/project_files/scss/utils/_fonts.scss`;
    let fontsClasses = `${app.path.srcFolder}/assets/project_files/scss/components/text/_font-family.scss`;
    const capitalize = str => str.charAt(0).toUpperCase() + str.slice(1);
    fs.readdir(app.path.build.fonts, function (err, fontsFiles) {
        if (fontsFiles) {
            if (!fs.existsSync(fontsFile)) {
                fs.writeFile(fontsFile, '', () => {});
                fs.writeFile(fontsClasses, '', () => {});
                let newFileOnly;
                for (var i = 0; i < fontsFiles.length; i++) {
                    let fontFileName = fontsFiles[i].split('.')[0];
                    if (newFileOnly !== fontFileName) {
                        let fontWeight = 400;
                        let className = fontFileName.toLocaleLowerCase();
                        let fontName = fontFileName.replaceAll('-', ' ');
                        const words = fontName.split(' ');
                        for (let i = 0; i < words.length; i++) {
                            words[i] = capitalize(words[i]);
                        }
                        fontName = words.join(' ');

                        if (className.indexOf('thin') !== -1){
                            fontWeight = 100;
                        }
                        else if (className.indexOf('extralight') !== -1){
                            fontWeight = 200;
                        }
                        else if (className.indexOf('light') !== -1) {
                            fontWeight = 300;
                        }
                        else if (className.indexOf('medium') !== -1){
                            fontWeight = 500;
                        }
                        else if (className.indexOf('semibold') !== -1){
                            fontWeight = 600;
                        }
                        else if (className.indexOf('bold') !== -1){
                            fontWeight = 700;
                        }
                        else if (className.indexOf('extrabold') !== -1){
                            fontWeight = 800;
                        }
                        else if (className.indexOf('black') !== -1){
                            fontWeight = 900;
                        }
                        fs.appendFile(fontsFile, `@font-face {\n\tfont-family: '${fontName}';\n\tfont-display: swap;\n\tsrc:url("../fonts/${fontFileName}.woff2") format("woff2"), url("../fonts/${fontFileName}.woff") format("woff");\n\tfont-weight: ${fontWeight};\n\tfont-style: normal;\n}\n`, (err) => {});
                        fs.appendFile(fontsClasses,`.ff_${className} {\n\tfont-family: '${fontName}', sans-serif;\n}\n`, (err) => {});
                        newFileOnly = fontFileName;
                    }
                }
            } else {
                console.log("Файл scss/_fonts.scss уже существует. Удалите его, чтобы обновить.");
            }
        }
    });
    return app.gulp.src(`${app.path.srcFolder}`);
}
