<?php

return array(

    'invitedIOS'     => array(
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
