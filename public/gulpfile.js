// npm install --save-dev gulp
// npm install --save dev gulp-uglify gulp-stylus gulp-jade gulp-plumber gulp-imagemin browser-sync gulp-autoprefixer

var gulp = require('gulp'),
	uglify = require('gulp-uglify'),
	stylus = require('gulp-stylus'),
	jade = require('gulp-jade'),
	plumber = require('gulp-plumber'),
	browserSync = require('browser-sync'),
	imagemin = require('gulp-imagemin'),
	prefix = require('gulp-autoprefixer'),
	reload = browserSync.reload;

//scripts
gulp.task('scripts', function(){
	gulp.src('public/js/*.js')
		.pipe(plumber())
		.pipe(uglify())
		.pipe(gulp.dest('public/build/js'));
});

/***************
** image task
****************/
gulp.task('image',function(){
	gulp.src('public/images/*')
		.pipe(imagemin())
		.pipe(gulp.dest('public/build/images'));
});

/************
** templates
*************/
gulp.task('templates', function(){
	gulp.src('modules/app/view/**/*.jade')
		.pipe(jade({
			jade: jade,
			pretty: true
			}))
		.on('error', console.error.bind(console))
		.pipe(gulp.dest('modules/app/view/'))
		.pipe(reload({stream: true}))
});
/**********
** styles
***********/
gulp.task('styles', function(){
	gulp.src('public/css/custom/styles.styl')
		.pipe(plumber())
		.pipe(stylus(
		{
			compress: true
			}))
		.pipe(prefix('last 2 versions', 'ie8', 'ie9'))
		.pipe(gulp.dest('public/css/custom/'))
		.pipe(reload({stream: true}))
});



/*********
** server
**********/
gulp.task('browser-sync', function() {
    browserSync({
        proxy: "hastrman.dev"
    });
});


//watch
gulp.task('watch', function(){

	gulp.watch('js/*.js', ['scripts']);
	gulp.watch('css/**/*.styl', ['styles'])
	gulp.watch('templates/**/*.jade', ['templates'])
	gulp.watch('img/*', ['image'])
});

gulp.task('default', ['browser-sync','scripts','templates','styles','image', 'watch']);