module.paths.push('/usr/local/lib/node_modules');
var gulp = require('gulp'),
    uglify = require('gulp-uglify'),
    cssnano = require('gulp-cssnano'),
    rev = require('gulp-rev'),
    revCollector = require("gulp-rev-collector");
gulp.task('install', function () {
    gulp.src('public/src/*.js')
        .pipe(rev())
        .pipe(uglify({mangle: true}))
        .pipe(gulp.dest('public/static/'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('public/static/rev/js'));
    gulp.src('public/src/*.css')
        .pipe(rev())
        .pipe(cssnano())
        .pipe(gulp.dest('public/static/'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('public/static/rev/css'));
    gulp.src(['public/static/rev/**/*.json', 'application/hanbj/tpl/*.html'])
        .pipe( revCollector({
            replaceReved: true
        }) )
        .pipe( gulp.dest('application/hanbj/view/') );
});