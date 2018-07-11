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
        
        $user = new User;
        $phone_exist = $user->findByPhoneNumber($phone);
        if($phone_exist){
            return [
                'status' => 'error',
                'message' => 'Phone number already exist.'
            ];
        }

        $accountSid = 'AC8bf700a1081c05d96be08ce0aeacccf3';
        $authToken  = 'a174861ea684bc1546523c6324d638d7';

        $client = new Client($accountSid, $authToken);

        try
        {
            $code = $this->generateRandomCode();
            $phone_code = new PhoneCode();
            $phone_number = $phone_code->getPhoneNumber($phone);

            if($phone_number->verified == 1){
                return [
                    'status' => 'error',
                    'message' => 'Phone number already verified.'
                ];
            }

            $to_number = $this->sanitizePhoneNumber($phone);
            // Use the client to do fun stuff like send text messages!
            $response=  $client->messages->create(
            // the number you'd like to send the message to
                $to_number,
                array(
                    // A Twilio phone number you purchased at twilio.com/console
                    'from' => '+16162198881',
                    // the body of the text message you'd like to send
                    'body' => "Sent from your twilio trial account. Phone: $phone code: $code"
                )
            );

            if($phone_number && $phone_number->verified == 0){
                //update phone code
                $phone_code->updatePhoneCode($phone,$code);
            }else{
                $phone_code->createPhoneCode($phone,$code);
            }

            return [
                'status' => 'success',
                'message' => 'Phone code created successfully'
            ];

        }
        catch (TwilioException $e)
        {
            return response()->json(
                [
                    'Error' => $e->getMessage(),
                ]
            );
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
            return [
                'status' => 'success',
                'code' => $phone_verified
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
