<?php

namespace App\Http\Controllers;

use App\Models\PhoneCode;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Profile;
use App\Location;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use DB;
use Illuminate\Support\Facades\Validator;
use Twilio\Rest\Client;


class AuthenticationController extends Controller
{
    
    public function getPhoneVerificationCode(Request $request){
        $this->validate($request,[
            'phone' => 'required|digits_between:11,20'
        ]);

        $phone = $request->phone;

        $code = PhoneCode::where('phone', $phone)
            ->where('verified', 0)
            ->where('created_at', '>=', Carbon::today())
            ->orderBy('id')
            ->first();

        if(!$code){
            $record = [
                'phone' => $phone,
                'code' => $this->generateRandomCode(),
            ];
            PhoneCode::create($record);
            unset($record['phone']);
            return $record;
        }else{
            return [
                'code' => "$code->code"
            ];
        }
    }

    public function generateRandomCode($digits = 4){
        return rand(pow(10, $digits-1), pow(10, $digits)-1);
    }


    public function postPhoneVerificationCode(Request $request){
        $this->validate($request,[
            'phone' => 'required_without:|digits_between:11,20',
            'code' => 'required_without:|digits:4',
        ]);
        $request = $request->all();
        if(is_array($request)){
            $phone = $request['phone'];
            $code = $request['code'];
        }else{
            $phone = $request->phone;
            $code = $request->code;
        }

        $code = PhoneCode::where('phone', $phone)
            ->where('code', $code)
            ->where('verified', 0)
            ->where('created_at', '>=', Carbon::today())
            ->orderBy('id')
            ->first();

        if($code){
            $code->verified = 1;
            $code->save();
            return [
                'status' => 'success',
                'message' => 'Phone code has been verified'
            ];
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Invalid or expired phone code'
                ], 422
            );
        }
    }

    public function postRegisterStudent(Request $request){

        $this->validate($request,[
            'email' => 'required|email|unique:users',
            'phone' => 'required|digits_between:11,20|unique:users',
            'code' => 'required|digits:4',
            'device_token' => 'required',
        ]);
//        $confirmation_code = str_random(30);
//        $password = str_random(6);
//        $user = User::where('id',10)->first();
//        $user_detail = ['confirmation_code'=>$confirmation_code,'firstName'=>$user['firstName'],'phone'=>$user['phone'],'password'=>$password];
//        Mail::send('emails.welcome', ['confirmation_code'=>$confirmation_code,'user'=>$user_detail], function($message) use($user) {
//            $message->to($user['email'], $user['firstName'])->subject('Verify your email address');
//            $message->from('info@tutor4all.com','Tutor4all');
//        });dd();

        $email = $request->email;
        $phone = $request->phone;
        $code = $request->code;
        $device_token = $request->device_token;

        $code = PhoneCode::where('phone', $phone)
            ->where('code', $code)
            ->where('verified', 1)
            ->where('created_at', '>=', Carbon::today()) //TODO: This check can disabled if we need to validate code not generated on same day
            ->orderBy('id')
            ->first();

        if($code){
            $confirmation_code = str_random(30);
            $password = str_random(6);
            $user = User::create([
                'email' => $email,
                'phone' => $phone,
                'password' => Hash::make($password),
                'uid' => md5(microtime()),
                'role_id' => 3,
                'device_token' => $device_token,
                'confirmation_code' => $confirmation_code,
            ])->id;

            if($user){
                Profile::registerUserProfile($user);
                $user = User::where('id',$user)->first();
                $user_detail = ['confirmation_code'=>$confirmation_code,'firstName'=>$user['firstName'],'phone'=>$user['phone'],'password'=>$password];
//                Mail::send('emails.welcome', ['confirmation_code'=>$confirmation_code,'user'=>$user_detail], function($message) use($user) {
//                    $message->to($user['email'], $user['firstName'])->subject('Verify your email address');
//                    $message->from('info@tutor4all.com','Tutor4all');
//                });
                return [
                    'status' => 'success',
                    'user_id' => $user,
                    'messages' => 'Student has been created'
                ];
            }else{
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Unable to create student'
                    ], 422
                );
            }

        } else {

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Invalid or expired phone verification'
                ], 422
            );

        }
    }



    public function postUpdateLocation(Request $request){

        $this->validate($request,[
            'longitude' => 'required',
            'latitude' => 'required',
            'user_id' => 'required',
        ]);

        $longitude = $request->longitude;
        $latitude = $request->latitude;
        $user_id = $request->user_id;

        $user = User::where('id', '=', $user_id)->first();
        if($user){
            $location = User::where('id','=',$user_id)->update(['longitude'=>$longitude,'latitude'=>$latitude]);
        }else{

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to update location'
                ], 422
            );
        }
        if($location){
            return [
                'status' => 'success',
                'messages' => 'Location updated'
            ];
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to update location'
                ], 422
            );
        }

    }

    public function postRegisterTutor(Request $request){
        $this->validate($request,[
            'email' => 'required|email|unique:users',
            'name' => 'required',
            'phone' => 'required|digits_between:11,20|unique:users',
        ]);
        $request = $request->all();
        //register students
        $user = User::registerTutor($request);
        //insert user profile
        Profile::registerUserProfile($user);
        return [
            'status' => 'success',
            'messages' => 'Tutor registered'
        ];
    }

    public function confirm($confirmation_code)
    {
        if( ! $confirmation_code)
        {
            throw new InvalidConfirmationCodeException;
        }

        $user = User::whereConfirmationCode($confirmation_code)->first();

        if ( ! $user)
        {
            throw new InvalidConfirmationCodeException;
        }

        $user->confirmed = 1;
        $user->confirmation_code = null;
        $user->save();

        return [
            'status' => 'success',
            'messages' => 'You have successfully verified your account.'
        ];
    }

    public function updateUser(Request $request){
        $request = $request->all();
        $update_arr = [];
        if(isset($request['userid'])){
            $userid = $request['userid'];
            $password = isset($request['password'])&&!empty($request['password'])?$request['password']:'';
            $name = isset($request['name'])&&!empty($request['name'])?$request['name']:'';
            $email = isset($request['emailf'])&&!empty($request['emailf'])?$request['emailf']:'';
            $phone = isset($request['phonef'])&&!empty($request['phonef'])?$request['phonef']:'';
            if(!empty($password)){ $update_arr['password'] = $password;}
            if(!empty($name)){
                $fullName = explode(" ",$request['name']);
                if(count($fullName)>1){
                    $firstName = $fullName[0]; $lastName = $fullName[1];
                }else{
                    $firstName = $fullName[0]; $lastName = '';
                }
                $update_arr['firstName'] = $firstName;
                $update_arr['lastName'] = $lastName;
            }
            if(!empty($email)){ $update_arr['email'] = $email;}
            if(!empty($phone)){ $update_arr['phone'] = $phone;}
            //return $update_arr;
            User::where('id','=',$userid)->update($update_arr);
            //DB::statement("UPDATE users  SET  firstName = 'wasim' where id = 9");
            return [
                'status' => 'success',
                'user_id' => $userid,
                'messages' => 'User updated'
            ];
        }
        return response()->json(
            [
                'status' => 'error',
                'message' => 'Unable to update user'
            ], 422
        );
    }

    public function registerValidation(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'phone' => 'required|unique:users',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6'
        ]);
        $response = User::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }
        else{
            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Validations passed.',
                    'code' => 200
                ]
            );
        }
    }

    public function postRegisterUser(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'phone' => 'required|unique:users',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6'
        ]);
        $response = User::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

        $request = $request->all();

        $phone = PhoneCode::getPhoneNumber($request['phone']);

        if($phone && $phone->verified == 1){
            $user = User::registerUser($request);
            if($user){
                return response()->json(
                    [
                        'status' => 'success',
                        'message' => 'User registered successfully',
                        'user_id' => $user
                    ]
                );
            }else{
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Unable to register user',
                    ], 422
                );
            }
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Phone number is not verified.',
                ], 422
            );
        }
    }

}
