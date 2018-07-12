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
Route::post('/sms','SmsController@sendSms');
Route::post('/phone/verification','SmsController@verifyPhoneCode');
// route for creating access_token
$router->post('login', 'AccessTokenController@createAccessToken');

Route::get('/send','UserController@sendFirebaseNotifications');

$router->group(['middleware' => ['auth:api', 'throttle:60']], function () use ($router) {

    Route::get('/logout','UserController@logoutApi');
    
    Route::post('/create-list','ListController@CreateUserContactList');

    Route::post('/update-list','ListController@UpdateUserContactList');

    Route::get('/delete-list','ListController@DeleteUserContactList');

    Route::get('/get-event','EventController@getUserEvents');

    Route::post('/create-event','EventController@CreateEvent');

    Route::get('/get-contact-list','ListController@getUserContactList');

    Route::post('/update-event','EventController@updateUserEvent');

    Route::get('/delete-event','EventController@deleteEvent');

    Route::get('/get-request','EventController@getEventRequests');

    Route::get('/accept-request','EventController@acceptRequest');

    Route::get('/reject-request','EventController@rejectRequest');

    Route::get('/received-request','EventController@receivedRequest');

    Route::get('/accepted-request-users','EventController@acceptedRequestUsers');

    Route::post('/update-device-token','UserController@updateUserDeviceToken');

    Route::post('/get-user','UserController@getUser');

});


Route::group(['prefix' => 'v2'], function () {
    Route::get('/', function () {
        return 'version 2';
    });

    // Generate random string
    Route::get('appKey', function () {
        return str_random('32');
    });
    Route::post('register-user', 'VersionTwo\AuthenticationController@postRegisterUser');
    Route::post('/user-notification','UserController@sendUserNotification');
    Route::get('/download','EventController@getDownload');
    Route::post('/sms','SmsController@sendSms');
    Route::post('/phone/verification','SmsController@verifyPhoneCode');
// route for creating access_token
    Route::post('login', 'AccessTokenController@createAccessToken');

    Route::get('/send','UserController@sendFirebaseNotifications');

    Route::group(['middleware' => ['auth:api', 'throttle:60']], function () {

        Route::get('/logout','UserController@logoutApi');

        Route::post('/create-list','ListController@CreateUserContactList');

        Route::post('/update-list','ListController@UpdateUserContactList');

        Route::get('/delete-list','ListController@DeleteUserContactList');

        Route::get('/get-event','EventController@getUserEvents');

        Route::post('/create-event','EventController@CreateEvent');

        Route::get('/get-contact-list','ListController@getUserContactList');

        Route::post('/update-event','EventController@updateUserEvent');

        Route::get('/delete-event','EventController@deleteEvent');

        Route::get('/get-request','EventController@getEventRequests');

        Route::get('/accept-request','EventController@acceptRequest');

        Route::get('/reject-request','EventController@rejectRequest');

        Route::get('/received-request','EventController@receivedRequest');

        Route::get('/accepted-request-users','EventController@acceptedRequestUsers');

        Route::post('/update-device-token','UserController@updateUserDeviceToken');

        Route::get('/get-user','UserController@getUser');

    });

});





