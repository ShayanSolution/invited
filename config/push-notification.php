<?php

return array(

    'invitedIOS'     => array(
        'environment' =>'production',
        'certificate' =>base_path().env('INVITED_IOS_CERTIFICATE_PATH'),
        'passPhrase'  =>env('INVITED_IOS_PASS_PHRASE'),
        'service'     =>'apns'
    ),
    'invitedIOSDev'     => array(
        'environment' =>'development',
        'certificate' =>base_path().env('INVITED_IOS_CERTIFICATE_PATH'),
        'passPhrase'  =>env('INVITED_IOS_PASS_PHRASE'),
        'service'     =>'apns'
    ),
    'appNameAndroid' => array(
        'environment' =>'production',
        'apiKey'      =>'yourAPIKey',
        'service'     =>'gcm'
    )
);
