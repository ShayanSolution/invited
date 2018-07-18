<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Twilio\Jwt\ClientToken;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Illuminate\Support\Facades\Validator;
use Twilio\Exceptions\TwilioException;
//Models
use App\Models\User;
use App\Models\PhoneCode;
use Log;
use App\Helpers\General;
use App\Helpers\JsonResponse;
use App\Helpers\TwilioHelper;

class SmsController extends Controller
{
    public function sendSms(Request $request)
    {
        
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
        ]);
        $response = User::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

        $request = $request->all();
        $phone = $request['phone'];


        Log::info("Got request for phone number =>".$phone);
        $user = new User;
        $phoneExist = $user->findByPhoneNumber($phone);

        
        if($phoneExist){
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Phone number already exist.'
                ],500
            );
        }

        $accountSid = config('twilio.accountId');
        $authToken  = config('twilio.authKey');

        $client = new Client($accountSid, $authToken);


        //$phone_code = new PhoneCode();
        $phoneCode = PhoneCode::getPhoneNumber($phone);

        if($phoneCode && $phoneCode->verified == 1){
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Phone number already verified.'
                ],500
            );
        }
        else
        {
            $code = $this->generateRandomCode();

            $toNumber = General::sanitizePhoneNumber($phone);
            // Use the client to do fun stuff like send text messages!
            $response=  TwilioHelper::sendCodeSms($toNumber, $code);
            
            if ($response) {
                //phone number is not verified
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
                return JsonResponse::generateResponse(
                    [
                        'status' => 'success',
                        'message' => 'Phone code created successfully'
                    ],200
                );
            }
            else{
                return JsonResponse::generateResponse(
                    [
                        'status' => 'error',
                        'message' => 'Unable to send SMS.'
                    ],500
                );
            }
        }

        

    }

    public function sanitizePhoneNumber($phone){
        $first_chr = $phone[0];
        if($first_chr == 0){
            $phone = substr($phone,1);
        }
        return "+92".$phone;
    }

    public function generateRandomCode($digits = 4){
        return rand(pow(10, $digits-1), pow(10, $digits)-1);
    }
    
    public function verifyPhoneCode(Request $request){
        $validator = Validator::make($request->all(), [
            'phone' => 'required',
            'code' => 'required',
        ]);
        $response = User::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

        $request = $request->all();
        
        $phone_code = new PhoneCode();

        $phone_verified = $phone_code->verifyPhoneCode($request);
        if($phone_verified){
            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                ],200
            );
        }else{

            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'Error' => 'Unable to verify phone number',
                ],500
            );
        }
    }

}
