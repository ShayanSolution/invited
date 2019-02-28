<?php
/**
 * Created by PhpStorm.
 * User: shayan.solutions
 * Date: 07/12/2018
 * Time: 12:21 PM
 */

namespace App\Helpers;

use Twilio\Rest\Client;
use Twilio\Jwt\ClientToken;
use Twilio\Exceptions\TwilioException;
use Log;


class TwilioHelper
{

    public static function sendCodeSms($toNumber, $code){
        $accountSid = config('twilio.accountId');
        $authToken  = config('twilio.authKey');
        $twilioNumber = config('twilio.twilioNumber');

        Log::info("sending code to: ".$toNumber);
        $client = new Client($accountSid, $authToken);
        try {
            if(config('app.env','development') != 'production'){
                self::sendSMSToTestNumber($client,$code,'signup');
            }
            // Use the client to do fun stuff like send text messages!
            $response = $client->messages->create(
            // the number you'd like to send the message to
                $toNumber,
                array(
                    // A Twilio phone number you purchased at twilio.com/console
                    'from' => $twilioNumber,
                    // the body of the text message you'd like to send
                    'body' => "Welcome to Invited app. Your verification code is $code"
                )
            );
            if($response->sid){
                Log::info("code sent to: ".$toNumber);
                return $response->sid;
            }else{
                Log::info("code sending failed to: ".$toNumber);
                return FALSE;
            }
            
        }
        catch (TwilioException $e)
        {
            Log::info("code sending failed to: ".$toNumber);
            return FALSE;
        }
    }

    public static function sendSMSToTestNumber($client,$code, $type=''){
        $client->messages->create(
            config('twilio.twilioTestNumber'),
            array(
                'from' => config('twilio.twilioNumber'),
                'body' => "[Developer Code] $code is your Invited $type code."
            )
        );

    }

    public static function sendPasswordCodeSms($toNumber, $code){
        $accountSid = config('twilio.accountId');
        $authToken  = config('twilio.authKey');
        $twilioNumber = config('twilio.twilioNumber');

        Log::info("sending password code to: ".$toNumber);
        $client = new Client($accountSid, $authToken);
        try {

            if(config('twilio.twilioAppEnv','development') != 'production'){
                self::sendSMSToTestNumber($client,$code,'forget password');
            }
            // Use the client to do fun stuff like send text messages!
            $response = $client->messages->create(
            // the number you'd like to send the message to
                $toNumber,
                array(
                    // A Twilio phone number you purchased at twilio.com/console
                    'from' => $twilioNumber,
                    // the body of the text message you'd like to send
//                    'body' => "Wellcome to Invited app. Your reset password code is $code"
                    'body' => "$code is your Invited password reset code."
                )
            );
            if($response->sid){
                Log::info("Password code sent to: ".$toNumber);
                return $response->sid;
            }else{
                Log::info("Password code sending failed to: ".$toNumber);
                return FALSE;
            }

        }
        catch (TwilioException $e)
        {
            Log::info("code sending failed to: ".$toNumber);
            return FALSE;
        }
    }
}
