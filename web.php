<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/clear-cache', function() {
    Artisan::call('cache:clear');
});

// this will let laravel automatically redirect again if already logged in
Route::permanentRedirect('/', '/login');

Route::group(['middleware' => 'auth'], function() {


    ///////LIVE +LIVE-RESTAURANTE/////
    Route::get('/live', 'LiveController@index')->name('live');
    Route::get('/live-restaurante', 'LiveController@indexRestaurante')->name('live-restaurante');
    Route::post('/change-status', 'LiveController@changeStatus');
    Route::post('/respinge', 'LiveController@rejectStatus');

    ///////ADMIN/POZE-RECLAME-BANNER//////
    Route::get('/feature-photos', 'HomeController@indexFeaturePhotos')->middleware('role:0');
    Route::get('/led-alarm', 'HomeController@ledAlarm')->middleware('role:0');
    Route::post('/feature-photo', 'PhotosController@addFeaturePhoto')->name('produs.addfeaturephoto')->middleware('role:0');


    ///////DASHBOARD//////
    Route::get('/home', 'DashboardController@index')->name('home')->middleware('role:1');
    Route::get('/home/{month}', 'DashboardController@index')->name('home')->middleware('role:1');
    Route::get('/comenzi-luna', 'HomeController@comenziLuna')->middleware('role:1');


    ///////MENIU/CATEGORII//////
    Route::resource('/categorii', 'CategoriiController')->except(['create','show','edit'])->middleware('role:1');


    ///////MENIU/PRODUSE//////
    Route::resource('/produse', 'ProduseController')->except(['create','show','edit'])->middleware('role:1');
    Route::post('/set-timer', 'ProduseController@setTimer')->name('set-timer')->middleware('role:1');
    Route::post('/produsphoto', 'PhotosController@addProductPhoto')->name('produs.addphoto')->middleware('role:1');


    ///////MENIU/EXTRA//////
    Route::resource('/extras', 'ExtrasController')->except(['create','show','edit'])->middleware('role:1');


    ///////MENIU/OPTIUNI//////
    Route::resource('/optiuni-cat', 'OptiuniCatController')->except(['create','show','edit'])->middleware('role:1');
    Route::resource('/optiuni', 'OptiuniController')->except(['create','show','edit'])->middleware('role:1');


    ///////MENIU/ORAR PRODUSE//////
    Route::resource('/timers', 'TimersController')->except(['create','show','edit'])->middleware('role:1');


    ///////MENIU/LISTA MENIU//////
    Route::get('/lista-meniu', 'HomeController@indexListaMeniu')->name('lista-comenzi')->middleware('role:1');
    Route::post('/produs-change-state', 'ProduseController@produsChangeState')->middleware('role:1');
    Route::post('/produs-deselect-all', 'ProduseController@produsDeselectAll')->middleware('role:1');


    ///////COMENZI//////
    Route::get('/comenzi', 'HomeController@indexComenziTotale')->name('comenzi');


    ///////FACTURI//////
    Route::get('/invoices', 'InvoiceController@indexInvoices')->name('invoices')->middleware('role:1');
    Route::get('/invoice-download/{month}/{year}/', 'InvoiceController@invoiceDownload')->name('invoiceDownload')->middleware('role:1');
    Route::get('/orders/{month}/{year}/', 'InvoiceController@indexOrders')->name('orders')->middleware('role:1');



    ///////CLIENTI/NOTE CLIENTI//////
    Route::get('/reviews', 'HomeController@indexReviews')->name('reviews');


    ///////SETARI USER////////////
    Route::get('/user-settings', 'HomeController@indexUserSettings');
    Route::post('/change-password', 'UserController@changePassword')->name('change.password');



    ///////SETARI RESTAURANT////////////
    Route::resource('/restaurant', 'RestaurantsController')->except(['create','show','edit'])->middleware('role:1');
    Route::post('/userphoto', 'PhotosController@update_restaurant_photo')->name('user.updateRestaurantPhoto')->middleware('role:1');
    Route::post('/schedule', 'UserController@changeschedule')->middleware('role:1');

    ///////ADMINISTRATOR////////////
    Route::get('/administrator', 'AdministratorController@indexAdministrator')->middleware('role:0');
    Route::get('/istoric-comenzi/{id}', 'AdministratorController@indexComenziUtilizator')->middleware('role:0');
    Route::post('/restaurant-change-state', 'AdministratorController@restaurantChangeState')->middleware('role:0');
    Route::post('/restaurant-close', 'AdministratorController@restaurantClose')->middleware('role:0');
    Route::post('/user-change-restaurant', 'AdministratorController@userChangeRestaurant')->middleware('role:0');
    Route::post('/change-configuration', 'AdministratorController@changeConfiguration')->middleware('role:0');
    Route::get('/live-administrator', 'LiveController@indexLiveAdministrator')->name('live')->middleware('role:0');


    ///////RAPORTARE////////////
    Route::get('/sales-reporting', 'ReportController@indexSalesReporting')->middleware('role:0');
    Route::get('/orders-reporting', 'ReportController@indexOrdersReporting')->middleware('role:0');


    //////EMAIL/////////////////
    Route::get('/email/confirmed-order', 'EmailController@sendConfirmationEmail');
    Route::get('/email/unsubscribe/{token}', 'EmailController@unsubscribeUserEmail');




    ///////CHARTURI////////////
    Route::get('/chart', 'LiveController@chartData')->name('chart');
    Route::get('/chart-zile', 'LiveController@chartzileData')->name('chart-zile');
    Route::get('/monthly-sales-chart', 'ChartController@monthlySalesChart')->name('sales-chart');
    Route::get('/monthly-sales-chart-current', 'ChartController@monthlySalesChartCurrentResturant')->name('sales-chart-monthly');
    Route::get('/monthly-orders-chart', 'ChartController@monthlyOrdersChart')->name('orders-chat');


    ///////ALTELE//////////////////////////
    Route::get('/clean-scame', 'HomeController@cleanscame')->middleware('role:1');
    Route::resource('/user', 'UserController')->except(['create','show','edit'])->middleware('role:1');
    Route::get('/clean-order', 'HomeController@cleanOrder')->middleware('role:1');
    Route::post('/send-notification', 'HomeController@sendNotification')->name('send.notification')->middleware('role:0');
    Route::get('/calculate-review', 'HomeController@calculateReview')->middleware('role:0');

});

Route::get('/cron/check-cron', 'AdministratorController@checkOrdersCronJob');

Auth::routes();