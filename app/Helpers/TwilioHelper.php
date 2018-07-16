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


class TwilioHelper
{

    public static function sendCodeSms($toNumber, $code){
        $accountSid = config('twilio.accountId');
        $authToken  = config('twilio.authKey');
        $twilioNumber = config('twilio.twilioNumber');

        
        $client = new Client($accountSid, $authToken);
        try {
            
            // Use the client to do fun stuff like send text messages!
            $response = $client->messages->create(
            // the number you'd like to send the message to
                $toNumber,
                array(
                    // A Twilio phone number you purchased at twilio.com/console
                    'from' => $twilioNumber,
                    // the body of the text message you'd like to send
                    'body' => "Wellcome to Invited app. Your verification code is $code"
                )
            );
            if($response->sid){
                
                return $response->sid;
            }else{
                return FALSE;
            }
            
        }
        catch (TwilioException $e)
        {
            return FALSE;
        }
    }
}
