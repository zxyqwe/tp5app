module.paths.push('/usr/local/lib/node_modules');
var gulp = require('gulp'),
    uglify = require('gulp-uglify'),
    cssnano = require('gulp-cssnano'),
    rev = require('gulp-rev'),
    revCollector = require("gulp-rev-collector"),
    htmlmin = require('gulp-htmlmin'),
    gulpSequence = require('gulp-sequence');

gulp.task('js', function () {
    return gulp.src('public/src/*.js')
        .pipe(rev())
        .pipe(uglify({
            mangle: true
        }))
        .pipe(gulp.dest('public/static/'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('public/static/rev/js'));
});
gulp.task('css', function () {
    return gulp.src('public/src/*.css')
        .pipe(rev())
        .pipe(cssnano())
        .pipe(gulp.dest('public/static/'))
        .pipe(rev.manifest())
        .pipe(gulp.dest('public/static/rev/css'));
});
gulp.task('hanbj_tpl', function () {
    return gulp.src(['public/static/rev/**/*.json', 'application/hanbj/tpl/*.html'])
        .pipe(revCollector({
            replaceReved: true
        }))
        .pipe(htmlmin({ collapseWhitespace: true }))
        .pipe(gulp.dest('application/hanbj/view/'));
});
gulp.task('index_tpl', function () {
    return gulp.src(['public/static/rev/**/*.json', 'application/index/tpl/*.html'])
        .pipe(revCollector({
            replaceReved: true
        }))
        .pipe(htmlmin({ collapseWhitespace: true }))
        .pipe(gulp.dest('application/index/view/'));
});
gulp.task('books_tpl', function () {
    return gulp.src(['public/static/rev/**/*.json', 'application/books/tpl/*.html'])
        .pipe(revCollector({
            replaceReved: true
        }))
        .pipe(htmlmin({ collapseWhitespace: true }))
        .pipe(gulp.dest('application/books/view/'));
});
gulp.task('hanbj', gulpSequence(['js', 'css'], ['hanbj_tpl', 'index_tpl', 'books_tpl']));