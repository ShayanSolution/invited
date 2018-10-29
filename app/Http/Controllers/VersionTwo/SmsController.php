<?php

namespace App\Http\Controllers\VersionTwo;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Log;
//Helpers
use App\Helpers\General;
use App\Helpers\JsonResponse;
use App\Helpers\TwilioHelper;
//Models
use App\VTwoModels\User;
use App\VTwoModels\PhoneCode;



class SmsController extends Controller
{
    public function sendSms(Request $request)
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
        $phoneExist = User::findByPhoneNumber($phone);

        if($phoneExist){
            return [
                'status' => 'error',
                'message' => 'Phone number already registered.'
            ];
        }


        $phoneCode = PhoneCode::getPhoneNumber($phone);

        if($phoneCode && $phoneCode->verified == 1){
            return [
                'status' => 'error',
                'message' => 'Phone number already verified.'
            ];
        }
        else {
            $code = General::generateRandomCode();
            $toNumber = General::sanitizePhoneNumber($phone);
            
            $response = TwilioHelper::sendCodeSms($toNumber, $code);

            if ($response) {
                if ($phoneCode && $phoneCode->verified == 0) {
                    //update phone code
                    $phoneCode->code = $code;
                    $phoneCode->save();
                } else {
                    $phoneCode = new PhoneCode();
                    $phoneCode->phone = $toNumber;
                    $phoneCode->code = $code;
                    $phoneCode->save();
                }
                return [
                    'status' => 'success',
                    'message' => 'Phone code created successfully'
                ];
            }
            else {
                return [
                    'status' => 'error',
                    'message' => 'Unable to send SMS.'
                ];
            }
        }
    }


    public function verifyPhoneCode(Request $request){
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'code' => 'required',
        ]);
        $response = JsonResponse::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

        $request = $request->all();

        $phoneCode = new PhoneCode();

        $phoneVerified = $phoneCode->verifyPhoneCode($request);
        if($phoneVerified){
            return [
                'status' => 'success',
                'code' => $phoneVerified
            ];
        }else{

            return response()->json(
                [
                    'Error' => 'Unable to verify phone number',
                ]
            );
        }
    }


}
