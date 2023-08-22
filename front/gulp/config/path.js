import * as nodePath from 'path';
const rootFolder = nodePath.basename(nodePath.resolve());

const buildFolder = `./dist`;
const srcFolder = `./src`;

export const path = {
    build: {
        img: `${buildFolder}/assets/project_files/img/`,
        js: `${buildFolder}/assets/project_files/js/`,
        jsModules: `${buildFolder}/assets/project_files/js/modules/`,
        css: `${buildFolder}/assets/project_files/css/`,
        fonts: `${buildFolder}/assets/project_files/fonts/`,
        icons: `${buildFolder}/assets/project_files/icons/`,
        html: `${buildFolder}/`,
    },
    src: {
        img: `${srcFolder}/assets/project_files/img/**/*.{jpg,jpeg,png,gif,webp}`,
        svg: `${srcFolder}/assets/project_files/img/**/*.svg`,
        js: `${srcFolder}/assets/project_files/js/index.js`,
        jsModules: `${srcFolder}/assets/project_files/js/modules/*.js`,
        scss: `${srcFolder}/assets/project_files/scss/*.scss`,
        scssLibs: `${srcFolder}/assets/project_files/scss/libs/*.scss`,
        fonts: `${srcFolder}/assets/project_files/fonts/**/*.ttf`,
        icons: `${srcFolder}/assets/project_files/icons/**/*`,
        html: `${srcFolder}/*.html`,
        css: `${srcFolder}/assets/project_files/css/`,
    },
    watch: {
        img: `${srcFolder}/assets/project_files/img/**/*.{jpg,jpeg,png,gif,webp,svg,ico}`,
        js: `${srcFolder}/assets/project_files/js/**/*.js`,
        scss: `${srcFolder}/assets/project_files/scss/**/*.scss`,
        html: `${srcFolder}/**/*.html`,
        css: `${buildFolder}/assets/project_files/css/index.css`
    },
    clean: buildFolder,
    buildFolder: buildFolder,
    srcFolder: srcFolder,
    rootFolder: rootFolder
}