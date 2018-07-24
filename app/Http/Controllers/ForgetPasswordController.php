<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Twilio\Jwt\ClientToken;
use Illuminate\Support\Facades\Validator;
use Twilio\Exceptions\TwilioException;
use Log;
use App\Helpers\JsonResponse;
use App\Helpers\General;
use App\Helpers\TwilioHelper;
//Models
use App\Models\PasswordCode;
use App\Models\User;

class ForgetPasswordController extends Controller
{
    public function sendPasswordCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
        ]);
        $response = JsonResponse::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }
        $request = $request->all();
        $phone = $request['phone'];

        Log::info("Got request for phone number =>".$phone);
        $user = User::findByPhoneNumber($phone);


        if(!$user){
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Phone number does not exist.'
                ],500
            );
        }

        $code = General::generateRandomCode(4);

        $toNumber = General::sanitizePhoneNumber($phone);
        $response=  TwilioHelper::sendPasswordCodeSms($toNumber, $code);
        if ($response) {
                $passwordCode = PasswordCode::firstOrNew(['user_id' => $user->id, 'phone'=>$toNumber]);;
                $passwordCode->user_id = $user->id;
                $passwordCode->phone = $toNumber;
                $passwordCode->code = $code;
                $passwordCode->verified = 0;
                $passwordCode->save();

            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                    'message' => 'Password code created successfully',
                    'phone' => $phone
                ],200
            );
        }
    }

    public function verifyPasswordCode(Request $request){
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'code' => 'required',
        ]);
        $response = JsonResponse::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }
        $request = $request->all();


        $passwordCode = PasswordCode::verifyPasswordCode($request);
        if($passwordCode){

            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                    'message' => 'Code verified successfully.',
                    'phone' => $request['phone']
                ],200
            );
        }else{

            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'Error' => 'Unable to verify code',
                ],500
            );
        }
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required|min:6'
        ]);
        $response = JsonResponse::generateErrorResponse($validator);
        if ($response['code'] == 500) {
            return $response;
        }

        $request = $request->all();
        $phone = $request['phone'];

        $user = User::findByPhoneNumber($phone);
        if($user)
        {
            $phone = $request['phone'];
            $phoneWithoutCode = substr($phone,-9);
            $update = $user->where('phone','like','%'.$phoneWithoutCode)->updatePassword($request['password']);
            if($update){
                return JsonResponse::generateResponse(
                    [
                        'status' => 'success',
                        'message' => 'Password updated successfully.',
                    ],200
                );
            }else{
                return JsonResponse::generateResponse(
                    [
                        'status' => 'error',
                        'message' => 'Unable to update password.',
                    ],500
                );
            }

        }
        else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Invalid phone number.',
                ],500
            );
        }
    }
    
    


}
