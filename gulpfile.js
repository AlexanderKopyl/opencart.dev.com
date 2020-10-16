let domain       = 'opencart.dev.com'; //Local domain name
let theme        = 'default_dev'; //Custom theme opencart name
let preprocessor = 'scss'; // Preprocessor (sass, scss, less, styl)
let fileswatch   = '+(twig|php|tpl)'; // File monitoring, extensions


const { src, dest, parallel, series, watch } = require('gulp');
const sass           = require('gulp-sass');
const scss           = require('gulp-sass');
const less           = require('gulp-less');
const styl           = require('gulp-stylus');
const cleancss       = require('gulp-clean-css');
const concat         = require('gulp-concat');
const browserSync    = require('browser-sync').create();
const uglify         = require('gulp-uglify-es').default;
const autoprefixer   = require('gulp-autoprefixer');
const newer          = require('gulp-newer');
const rsync          = require('gulp-rsync');
const del            = require('del');

// Local Server
function browsersync() {
    browserSync.init({
        proxy: `${domain}`,
        //server: { baseDir: 'app' },
        notify: false,
        // online: false, // Work offline without internet connection
        // tunnel: true,
        // tunnel: "gulp-opencart", //Demonstration page: http://gulp-opencart.localtunnel.me
    })
}

// Custom Styles
function styles() {
    return src(`catalog/view/theme/${theme}/${preprocessor}/stylesheet.scss`)
        .pipe(concat('stylesheet.css'))
        //.pipe(autoprefixer({ overrideBrowserslist: ['last 10 versions'], grid: true }))
        .pipe(cleancss( {level: { 1: { specialComments: 0 } } }))
        .pipe(dest(`catalog/view/theme/${theme}/stylesheet`))
        .pipe(browserSync.stream())
}

// Scripts & JS Libraries
function scripts() {
    return src([
        // 'node_modules/jquery/dist/jquery.min.js', // npm vendor example (npm i jquery --save-dev)
        'node_modules/jquery/dist/jquery.min.js', // npm vendor example (npm i jquery --save-dev)
        `catalog/view/theme/${theme}/js/app.js` // app.js. Always at the end
    ])
        .pipe(concat('app.min.js'))
        .pipe(uglify()) // Minify JS (opt.)
        .pipe(dest('catalog/view/javascript'))
        .pipe(browserSync.stream())
}

// Deploy rsync web-hosting site
function deploy() {
    return src('public_html/**')
        .pipe(rsync({
            root: '/',
            hostname: 'user123@domain.com',
            destination: 'www/domain.com/',
            include: ['*.htaccess'], // Included files
            exclude: ['**/Thumbs.db', '**/*.DS_Store', '**/.directory'], // Excluded files
            recursive: true,
            archive: true,
            silent: false,
            compress: true
        }))
    // Documentation: https://pinchukov.net/blog/gulp-rsync.html
}

// Watching
function startwatch() {
    watch(`catalog/view/theme/${theme}/${preprocessor}/stylesheet.scss*`, parallel('styles'));
    watch(`catalog/view/theme/${theme}/js/**/*.js`, parallel('scripts'));
    watch([`catalog/view/theme/**/*.${fileswatch}`]).on('change', browserSync.reload);
}

exports.browsersync = browsersync;
exports.assets      = series(styles, scripts);
exports.styles      = styles;
exports.scripts     = scripts;
exports.deploy      = deploy;
exports.default     = parallel(styles, scripts, browsersync, startwatch);
