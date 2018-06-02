<?php

return array(

    'appNameIOS'     => array(
        'environment' =>'development',
        'certificate' =>base_path().'/TutorPortal.pem',
        'passPhrase'  =>'password',
        'service'     =>'apns'
    ),
    'appStudentIOS'     => array(
        'environment' =>'development',
        'certificate' =>base_path().'/Tutor4All.pem',
        'passPhrase'  =>'password',
        'service'     =>'apns'
    ),
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
