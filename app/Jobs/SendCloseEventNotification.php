<?php

namespace App\Jobs;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Log;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Facades\FCM;

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
    
    
    public function __construct($token,$event_title,$platform)
    {
        $this->token = $token;
        $this->event_title = $event_title;
        $this->platform = $platform;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->platform == 'ios' || is_null($this->platform)) {
            $message = PushNotification::Message($this->event_title . "  has been closed ", array(
                'badge' => 1,
                'sound' => 'example.aiff',

                'actionLocKey' => 'Action button title!',
                'locKey' => 'localized key',
                'locArgs' => array(
                    'localized args',
                    'localized args',
                ),
                'launchImage' => 'image.jpg',


            ));
            PushNotification::app('invitedIOS')->to($this->token)->send($message);
        }
        else{

            Log::info(" Send notification to android users ");
            $optionBuilder = new OptionsBuilder();
            $optionBuilder->setTimeToLive(60*20);
            $notificationBuilder = new PayloadNotificationBuilder('Event Closed');
            $notificationBuilder->setBody($this->event_title.' has been closed')->setSound('default');

            $dataBuilder = new PayloadDataBuilder();
            $dataBuilder->addData(['a_data' => 'my_data']);

            $option = $optionBuilder->build();
            $notification = $notificationBuilder->build();
            $data = $dataBuilder->build();

            Log::info("Sending push notification to $this->token");
            $downstreamResponse = FCM::sendTo($this->token, $option, $notification, $data);
        }
    }
}
