<?php

return array(

    'invitedIOS'     => array(
        'environment' =>'development',
        'certificate' =>base_path().'/certificat.pem',
        'passPhrase'  =>'password',
        'service'     =>'apns'
    ),
    'appNameAndroid' => array(
        'environment' =>'production',
        'apiKey'      =>'yourAPIKey',
        'service'     =>'gcm'
    )
);
