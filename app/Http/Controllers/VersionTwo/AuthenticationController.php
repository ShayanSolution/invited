<?php

namespace App\Http\Controllers\VersionTwo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Validator;
//Helper
use App\Helpers\JsonResponse;
//Models
use App\VTwoModels\PhoneCode;
use App\VTwoModels\User;

class AuthenticationController extends Controller
{
    public function postRegisterUser(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users',
            'phone' => 'required|unique:users',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6'
        ]);
        $response = JsonResponse::generateErrorResponse($validator);
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
