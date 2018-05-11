<?php
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'HomeController@index')->name('home.index')->middleware('frontend.language');
Route::group(['middleware' => 'frontend.language', 'namespace'=>'Frontend'],
    function() {
        Route::get('/language/{lang}', 'LanguageController@show')
            ->middleware('frontend.language')->name('frontend.language')
            ->where('lang', 'vi|en');
        Route::resource('/sendfeedback', 'FeedBackController', ['only' => ['create', 'store']]);
        Route::group(['middleware'=> 'auth'], function() {
            Route::resource('/comments', 'RatingCommentController', ['only' => ['store', 'destroy']]);
            Route::put('/hotels/{hotel}/comments/{comment}', 'RatingCommentController@update')->name('user.comment.update');
            Route::put('/profile/{profile}/reservation/{reservation}', 'ReservationController@update')->name('user.cancelBooking');
            Route::group(['middleware'=> 'checkUser'], function() {
                Route::resource('/profile', 'UserController');
                Route::get('/profile/{profile}/reservation/{reservation}/show', 'ReservationController@show')->name('user.showBooking');
            });
            Route::get('/registerSuccess', function() {
                return view('frontend.notice');
            })->name('notice');
        });
        
        Route::get('hotels/{slug}', 'HotelController@show')->name('hotels.show');
        Route::resource('/hotels', 'HotelController', ['only' => ['index']]);
        Route::resource('/room/{room}/reservations', 'ReservationController', ['only' => ['create', 'store']]);
        Route::get('places/{slug}', 'PlaceController@show')->name('places.show');
        Route::resource('/news', 'NewsController', ['as' => 'frontend']);
        Route::get('/hint/places', 'PlaceController@hintPlaces')->name('places.hintPlaces');
        Route::get('/categories/{slug}/news', 'CategoryController@show')->name('categories.news');
});

Route::group(['namespace'=>'Admin', 'prefix'=>'admin', 'middleware'=>['adminLogin', 'admin.language']], function() {
    Route::group(['middleware'=>['adminstratorLogin']], function() {
        Route::resource('/user', 'UserController', ['except' => ['show']]);
        Route::resource('/place', 'PlaceController');
        Route::resource('/news', 'NewsController');
        Route::resource('/feedback', 'FeedbackController');
        Route::resource('/category', 'CategoryController');
        Route::resource('/service', 'ServiceController', ['except' => ['show']]);
        Route::put('/user/{id}/status', 'UserController@updateStatus')->name('user.updateStatus');
        Route::put('/user/{id}/role', 'UserController@updateRole')->name('user.updateRole');
    });
    Route::get('/language/{lang}', 'LanguageController@show')
        ->middleware('admin.language')->name('admin.language')
        ->where('lang', 'vi|en');
    Route::get('/', 'AdminController@index')->name('admin.index');
    Route::get('/reservation', 'ReservationController@index')->name('reservation.index');
    Route::get('/user/{user}', 'UserController@show')->name('user.show');
   
    Route::resource('/comment', 'RatingCommentController');
        Route::group(['middleware'=>['hotelierLogin']], function() {
        Route::resource('reservation', 'ReservationController', ['except' => ['create','store', 'index']]);
        Route::resource('/hotel', 'HotelController', ['only' => ['create','store', 'edit', 'update']]);        
    });
    Route::resource('/hotel', 'HotelController', ['except' => ['create','store', 'edit', 'update']]);
    Route::group(['prefix'=>'hotel/{hotel}'], function($hotel) {
        Route::resource('/room', 'RoomController');
    });
    Route::resource('/image', 'ImageController', ['only' => ['destroy']]);
});

Auth::routes();
