<?php

namespace App\Http\Controllers;

use App\ContactList;
use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Http\Request;
use App\Transformers\UserTransformer;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Facades\FCM;
use App\Helpers\JsonResponse;
use Intervention\Image\ImageManagerStatic as Image;


class UserController extends Controller
{
    /**
     * Instance of UserRepository
     *
     * @var UserRepository
     */
    private $userRepository;

    /**
     * Instanceof UserTransformer
     *
     * @var UserTransformer
     */
    private $userTransformer;

    /**
     * Constructor
     *
     * @param UserRepository $userRepository
     * @param UserTransformer $userTransformer
     */
    public function __construct(UserRepository $userRepository, UserTransformer $userTransformer)
    {
        $this->userRepository = $userRepository;
        $this->userTransformer = $userTransformer;

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $users = $this->userRepository->findBy($request->all());

        return $this->respondWithCollection($users, $this->userTransformer);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function show($id)
    {
        $user = $this->userRepository->findOne($id);

        if (!$user instanceof User) {
            return $this->sendNotFoundResponse("The user with id {$id} doesn't exist");
        }

        // Authorization
        $this->authorize('show', $user);

        return $this->respondWithItem($user, $this->userTransformer);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function store(Request $request)
    {
        // Validation
        $validatorResponse = $this->validateRequest($request, $this->storeRequestValidationRules($request));

        // Send failed response if validation fails
        if ($validatorResponse !== true) {
            return $this->sendInvalidFieldResponse($validatorResponse);
        }

        $user = $this->userRepository->save($request->all());

        if (!$user instanceof User) {
            return $this->sendCustomResponse(500, 'Error occurred on creating User');
        }

        return $this->setStatusCode(201)->respondWithItem($user, $this->userTransformer);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Validation
        $validatorResponse = $this->validateRequest($request, $this->updateRequestValidationRules($request));

        // Send failed response if validation fails
        if ($validatorResponse !== true) {
            return $this->sendInvalidFieldResponse($validatorResponse);
        }

        $user = $this->userRepository->findOne($id);

        if (!$user instanceof User) {
            return $this->sendNotFoundResponse("The user with id {$id} doesn't exist");
        }

        // Authorization
        $this->authorize('update', $user);


        $user = $this->userRepository->update($user, $request->all());

        return $this->respondWithItem($user, $this->userTransformer);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function destroy($id)
    {
        $user = $this->userRepository->findOne($id);

        if (!$user instanceof User) {
            return $this->sendNotFoundResponse("The user with id {$id} doesn't exist");
        }

        // Authorization
        $this->authorize('destroy', $user);

        $this->userRepository->delete($user);

        return response()->json(null, 204);
    }

    /**
     * Store Request Validation Rules
     *
     * @param Request $request
     * @return array
     */
    private function storeRequestValidationRules(Request $request)
    {
        $rules = [
            'email'                 => 'email|required|unique:users',
            'firstName'             => 'required|max:100',
            'middleName'            => 'max:50',
            'lastName'              => 'required|max:100',
            'username'              => 'max:50',
            'address'               => 'max:255',
            'zipCode'               => 'max:10',
            'phone'                 => 'max:20',
            'mobile'                => 'max:20',
            'city'                  => 'max:100',
            'state'                 => 'max:100',
            'country'               => 'max:100',
            'password'              => 'min:5'
        ];

        $requestUser = $request->user();

        // Only admin user can set admin role.
        if ($requestUser instanceof User && $requestUser->role === User::ADMIN_ROLE) {
            $rules['role'] = 'in:BASIC_USER,ADMIN_USER';
        } else {
            $rules['role'] = 'in:BASIC_USER';
        }

        return $rules;
    }

    /**
     * Update Request validation Rules
     *
     * @param Request $request
     * @return array
     */
    private function updateRequestValidationRules(Request $request)
    {
        $userId = $request->segment(2);
        $rules = [
            'email'                 => 'email|unique:users,email,'. $userId,
            'firstName'             => 'max:100',
            'middleName'            => 'max:50',
            'lastName'              => 'max:100',
            'username'              => 'max:50',
            'address'               => 'max:255',
            'zipCode'               => 'max:10',
            'phone'                 => 'max:20',
            'mobile'                => 'max:20',
            'city'                  => 'max:100',
            'state'                 => 'max:100',
            'country'               => 'max:100',
            'password'              => 'min:5'
        ];

        $requestUser = $request->user();

        // Only admin user can update admin role.
        if ($requestUser instanceof User && $requestUser->role === User::ADMIN_ROLE) {
            $rules['role'] = 'in:BASIC_USER,ADMIN_USER';
        } else {
            $rules['role'] = 'in:BASIC_USER';
        }

        return $rules;
    }


    public function updateUserActiveStatus($id){
        //update student deserving status
        User::updateUserActiveStatus($id);
        $students =  User::getStudents();
        return $students;
    }
    
    public function removeUser($id){
        $id = User::find($id); $id ->delete();
        //User::withTrashed()->where('id', $id)->restore();
        return User::getStudents();
    }

    public function userProfile($id){
        $user = new User();
        return $user->userProfile($id);
        
    }

    public function sendUserNotification(Request $request){
        $request = $request->all();
        $id = $request['user_id'];
        $user = User::where('id',$id)->first();
        $device_token = $user->device_token;
        $message = PushNotification::Message('Hello World, i`m a push message',array(
            'badge' => 1,
            'sound' => 'example.aiff',

            'actionLocKey' => 'Action button title!',
            'locKey' => 'localized key',
            'locArgs' => array(
                'localized args',
                'localized args',
            ),
            'launchImage' => 'image.jpg',

            'custom' => array('custom data' => array(
                'we' => 'want', 'send to app'
            ))
        ));
        PushNotification::app('appNameIOS')->to($device_token)->send($message);
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard('api');
    }

    public function logoutApi(Request $request)
    {
        if (Auth::check()) {
            $user_id =  Auth::user()->id;
            User::where('id', $user_id)->update(['device_token'=>'']);
            Auth::user()->AauthAcessToken()->delete();
        }
    }

    public function updateUserDeviceToken(Request $request){
        $validator = Validator::make($request->all(), [
                        'user_id' => 'required',
                        'device_token' => 'required',
                        'platform' => 'required',
                    ]);
        $response = User::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }
        $token = User::updateToken($request);
        if($token){
            return response()->json(
                [
                    'status' => 'Device token updated successfully',
                ], 200
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to update device token'
                ], 422
            );
        }
        
    }

    public function sendFirebaseNotifications(Request $request){

        $validator = Validator::make($request->all(), [
            'device_token' => 'required',
            'message' => 'required',
        ]);
        $response = User::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

        $request = $request->all();
        $token = $request['device_token'];
        $message = $request['message'];

        // API access key from Google API's Console
        define( 'API_ACCESS_KEY', 'AIzaSyAIM2143LQUTw3Vw-9QWvCrT60bm1XDJa4' );
        $registrationIds = array($token);
        // prep the bundle
        $msg = array
        (
            'body'  => "abc",
            'title'     => $message,
            'vibrate'   => 1,
            'sound'     => 1,
        );

        $fields = array
        (
            'registration_ids'  => $registrationIds,
            'notification'          => $msg
        );

        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);

        $notificationBuilder = new PayloadNotificationBuilder('my title');
        $notificationBuilder->setBody($message)->setSound('default');
        //$notificationBuilder->setTitle('title')->setBody('body')->setSound('sound')->setBadge('badge');


        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData(['a_data' => 'my_data']);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        //$token = "f-9wrGC6i6g:APA91bG6ZtVrbL_BhVTXOT3WiGATM4rI9SuHYn32jheelqumbGmTGOcYqzB8He9CHjk6uj5N3NE3TOqMtoRgSDQh2TtmmnKai1NBHoPpx3EBYsFKpcht5m_6VWwq5vX4M2YDOpJWWXhQ";

        $downstreamResponse = FCM::sendTo($token, $option, $notification, $data);

        //print_r($downstreamResponse);
        echo "Success: ". $downstreamResponse->numberSuccess();
        echo "<br>Failure: ".  $downstreamResponse->numberFailure();
        echo "<br>Token: ". $token;

        $downstreamResponse->numberModification();

        //return Array - you must remove all this tokens in your database
        $downstreamResponse->tokensToDelete();

        //return Array (key : oldToken, value : new token - you must change the token in your database )
        $downstreamResponse->tokensToModify();

        print_r($downstreamResponse->hasMissingToken());


        //return Array - you should try to resend the message to the tokens in the array
        $downstreamResponse->tokensToRetry();

        // return Array (key:token, value:errror) - in production you should remove from your database the tokens
        echo "<br>Errors :";
        return  $downstreamResponse->tokensWithError();

    }

    public function getUser(){

        $user = Auth::user();

        if($user){
            return response()->json(
                [
                    'user' => $user,
                ], 200
            );
        }else{

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find user'
                ], 422
            );
        }

    }

    //Update User Profile
    public function updateUser(Request $request){
        $validator = Validator::make($request->all(), [
            'firstName' => 'required',
            'lastName' => 'required',
            'dob' => 'required',
            //            'dateofrelation' => 'required',
            'email' => 'required|email'
        ]);
        $response = User::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }
        $updateUser = User::where('id',$request->user_id);
        if($updateUser){
//            $updateUser->update([
//                "firstName"=> $request->input("firstName"),
//                "lastName"=> $request->input("lastName"),
//                "dob"=> $request->input("dob"),
//                "dateofrelation"=> $request->input("dateofrelation"),
//                "email"=> $request->input("email"),
//            ]);
            $data = [
                "firstName"=> $request["firstName"],
                "lastName"=> $request["lastName"],
                "dob"=> $request["dob"],
                "email"=> $request["email"],
                "gender_id"=> $request["gender"],
            ];
            if (!empty($request->input("dateofrelation"))){
                $data['dateofrelation'] = $request->input("dateofrelation");
            } else {
                $data['dateofrelation'] = null;
            }
            if (!empty($request->file("profileImage"))) {

                $originalImage= $request->file('profileImage');
                $thumbnailImage = Image::make($originalImage);
                $thumbnailPath = storage_path().'/thumbnail/';
                $originalPath = storage_path().'/images/';
                $thumbnailImage->save($originalPath.time().$originalImage->getClientOriginalName());
                $thumbnailImage->resize(150,150);
                $thumbnailImage->save($thumbnailPath.time().$originalImage->getClientOriginalName());
                $path = app("url")->asset("storage/images/");
                $uploadImagePath = app("url")->asset("storage/images/")."/".time().$originalImage->getClientOriginalName();
                $data['profileImage'] = $uploadImagePath;
//                dd($uploadImagePath);

            } else {
                $data['profileImage'] = null;
            }

            $updateUser->update($data);

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'User update successfully'
                ], 200
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find user'
                ], 422
            );
        }
    }

}
