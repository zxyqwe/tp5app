module.paths.push('/usr/local/lib/node_modules');
var gulp = require('gulp'),
    rename = require('gulp-rename'),
    uglify = require('gulp-uglify'),
    cssnano = require('gulp-cssnano');
gulp.task('install', function () {
    gulp.src('public/src/*.js')
        .pipe(rename({suffix: '.min'}))
        .pipe(uglify({mangle: true}))
        .pipe(gulp.dest('public/static/'));
    gulp.src('public/src/*.css')
        .pipe(rename({suffix: '.min'}))
        .pipe(cssnano())
        .pipe(gulp.dest('public/static/'));
});