<?php
return [
    'accountId' => env('TWILIO_ACCOUNT_SID'),
    'authKey' => env('TWILIO_AUTH_TOKEN'),
    'twilioNumber' => env('TWILIO_NUMBER'),
    'twilioTestNumber' => env('TWILIO_TEST_NUMBER'),
    'twilioAppEnv' => env('TWILIO_APP_ENV')
];
