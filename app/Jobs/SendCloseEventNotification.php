<?php

namespace App\Jobs;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Log;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Facades\FCM;
use LaravelFCM\Message\PayloadDataBuilder;

class SendCloseEventNotification extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $token;
    protected $event_title;
    protected $platform;


    public function __construct($token,$event_title,$platform,$environment)
    {
        $this->token = $token;
        $this->event_title = $event_title;
        $this->platform = $platform;
        $this->environment = $environment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info(" I am in queue ");
        if($this->platform == 'ios' || is_null($this->platform)) {
            Log::info(" I am in queue ios platform".$this->environment);
            $message = PushNotification::Message("Too late. ".$this->event_title . "  has been closed ", array(
                'badge' => 1,
                'sound' => 'example.aiff',

                'actionLocKey' => 'Action button title!',
                'locKey' => 'localized key',
                'locArgs' => array(
                    'localized args',
                    'localized args',
                ),
                'launchImage' => 'image.jpg',
                'custom' => array('custom_data' => array(
                    'status' => 'closed'
                ))

            ));
            //            PushNotification::app('invitedIOS')->to($this->token)->send($message);
            if($this->environment == 'development') {
                Log::info(" Environment is Development-----".$this->token."---- Before Send and Environment:-----".$this->environment);
                $response = PushNotification::app('invitedIOSDev')->to($this->token)->send($message);
                Log::info(" Environment is Development-----".$this->token."------After Send");
            } else{
                Log::info(" Environment is Production-----".$this->token."---- Before Send and Environment:-----".$this->environment);
                $response = PushNotification::app('invitedIOS')->to($this->token)->send($message);
                Log::info(" Environment is Production-----".$this->token."------After Send");
            }
        }
        else{
            Log::info(" I am in queue ios android");
            $request_status = "TooLate";
            // Log::info(" Send notification to android users ");
            $optionBuilder = new OptionsBuilder();
            $optionBuilder->setTimeToLive(60*20);
            $dataBuilder = new PayloadDataBuilder();
            if($request_status == "TooLate"){
                //$notificationBuilder = new PayloadNotificationBuilder('Accepted');
                //$notificationBuilder->setBody($user_name.' accepted your request')->setSound
                ('default');
//            $dataBuilder->addData(['code' => '3','Title' => 'Accepted','Body' => $user_name.'confirmed your request.']);
                $dataBuilder->addData(['code' => '7','Title' => 'Accepted','Body' => 'Too late. '.$this->event_title.' has been closed']);
                Log::info("Event Too Late:");
            }
//            $notificationBuilder = new PayloadNotificationBuilder('Event Closed');
//            $notificationBuilder->setBody('Too late. '.$this->event_title.' has beenclosed')->setSound('default');
//
//
//            $dataBuilder->addData(['a_data' => 'my_data']);

            $option = $optionBuilder->build();
//            $notification = $notificationBuilder->build();
            $data = $dataBuilder->build();

           // Log::info("Sending push notification to $this->token");
            $downstreamResponse = FCM::sendTo($this->token, $option, null, $data);
        }
    }
}
