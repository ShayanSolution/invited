<?php
use Laravel\Lumen\Routing\Router;
use Illuminate\Http\Request;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Symfony\Component\HttpFoundation\File\File;
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/
/** @var \Laravel\Lumen\Routing\Router $router */
$router->get('/', function () {
    return app()->version();
});

// Generate random string
$router->get('appKey', function () {
    return str_random('32');
});
$router->post('register-user', 'AuthenticationController@postRegisterUser');
Route::post('/user-notification','UserController@sendUserNotification');

Route::get('/download','EventController@getDownload');

// route for creating access_token
$router->post('login', 'AccessTokenController@createAccessToken');


$router->group(['middleware' => ['auth:api', 'throttle:60']], function () use ($router) {

    Route::get('/logout','UserController@logoutApi');
    //Dashboard Routes
    $router->get('dashboard-pie-chart-totals',  [
        'uses'       => 'UserController@getDashboardTotalOfPieCharts',
        //'middleware' => "scope:admin"
    ]);
    Route::post('/create-list','ListController@CreateUserContactList');

    Route::post('/update-list','ListController@UpdateUserContactList');

    Route::get('/get-event','EventController@getUserEvents');

    Route::post('/create-event','EventController@CreateEvent');

    Route::get('/get-contact-list','ListController@getUserContactList');

    Route::post('/update-event','EventController@updateUserEvent');

    Route::get('/delete-event','EventController@deleteEvent');

    Route::get('/get-request','EventController@getEventRequests');

    Route::get('/accept-request','EventController@acceptRequest');

    Route::get('/reject-request','EventController@rejectRequest');

    Route::get('/received-request','EventController@receivedRequest');

    Route::post('/update-device-token','UserController@updateUserDeviceToken');

});



