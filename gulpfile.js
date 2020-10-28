const proxyURL = 'opencart.dev.com';
let theme        = 'catalog/view/theme/default_dev'; //Custom theme opencart name
let fileswatch   = '+(twig|php|tpl)'; // File monitoring, extensions

// Load Gulp...of course
const { src, dest, task, watch, series, parallel } = require('gulp');

// CSS related plugins
var sass = require('gulp-sass');
var concatCss = require('gulp-concat-css');
let cleanCSS = require('gulp-clean-css');

// JS related plugins
var uglify = require('gulp-uglify');
var babelify = require('babelify');
var browserify = require('browserify');
var source = require('vinyl-source-stream');
var buffer = require('vinyl-buffer');
var stripDebug = require('gulp-strip-debug');

// Utility plugins
var rename = require('gulp-rename');
var sourcemaps = require('gulp-sourcemaps');
var notify = require('gulp-notify');
var plumber = require('gulp-plumber');
var options = require('gulp-options');
var gulpif = require('gulp-if');

// Browers related plugins
var browserSync = require('browser-sync').create();

// Project related variables
var styleSRC = `${theme}/scss/*.scss`;
var stylesBootstrap = `catalog/view/javascript/bootstrap/css/bootstrap.min.css`;
var stylesfont_awesome = `catalog/view/javascript/font-awesome/css/font-awesome.min.css`;


var styleURL = `${theme}/stylesheet/`;
var mapURL = './';

var jsSRC = `${theme}/js/`;
var jsFront = 'app.js';
var jsFiles = [jsFront];
var jsURL = `${theme}/js/`;

var imgSRC = `${theme}/image/**/*`;
var imgURL = `${theme}/image/`;

var fontsSRC = `${theme}/fonts/**/*`;
var fontsURL = `${theme}/fonts/`;

var styleWatch = `${theme}/scss/**/*.scss`;
var jsWatch = `${theme}/js/**/*.js`;
var imgWatch = `${theme}/image/**/*.*`;
var fontsWatch = `${theme}/fonts/**/*.*`;
var twigWatch = `${theme}/template/**/*.twig`;

// Tasks
function browser_sync() {
    browserSync.init({
        proxy: proxyURL
    });
}

function reload(done) {
    browserSync.reload();
    done();
}

function css(done) {
    src([styleSRC,stylesBootstrap,stylesfont_awesome])
        .pipe(sourcemaps.init())
        .pipe(
            sass({
                errLogToConsole: true,
                outputStyle: 'compressed'
            })
        )
        .pipe(concatCss('stylesheet.css'))
        .pipe(cleanCSS({ compatibility: 'ie8' }))
        .on('error', console.error.bind(console))
        //.pipe(rename({ suffix: '.min' }))
        .pipe(sourcemaps.write(mapURL))
        .pipe(dest(styleURL))
        .pipe(browserSync.stream());
    done();
}

function js(done) {
    jsFiles.map(function(entry) {
        return browserify({
            entries: [jsSRC + entry]
        })
            .transform(babelify, { presets: ['@babel/preset-env'] })
            .bundle()
            .pipe(source(entry))
            .pipe(
                rename({
                    extname: '.min.js'
                })
            )
            .pipe(buffer())
            .pipe(gulpif(options.has('production'), stripDebug()))
            .pipe(sourcemaps.init({ loadMaps: true }))
            .pipe(uglify())
            .pipe(sourcemaps.write('.'))
            .pipe(dest(jsURL))
            .pipe(browserSync.stream());
    });
    done();
}

function triggerPlumber(src_file, dest_file) {
    return src(src_file)
        .pipe(plumber())
        .pipe(dest(dest_file));
}

function image() {
    return triggerPlumber(imgSRC, imgURL);
}

function fonts() {
    return triggerPlumber(fontsSRC, fontsURL);
}

function watch_files() {
    watch(styleWatch, series(css, reload));
    watch(jsWatch, series(js, reload));
    watch(imgWatch, series(image, reload));
    watch(fontsWatch, series(fonts, reload));
    watch(twigWatch, series(reload));
    src(jsURL + 'app.min.js').pipe(
        notify({ message: 'Gulp is Watching, Happy Coding!' })
    );
}

task('css', css);
task('js', js);
task('image', image);
task('fonts', fonts);
task('default', parallel(css, js, image, fonts));
task('watch', parallel(browser_sync, watch_files));

