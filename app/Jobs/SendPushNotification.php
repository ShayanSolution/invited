<?php

namespace App\Jobs;
use App\Models\Event;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Log;

class SendPushNotification extends Job
{

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $token;
    protected $user;
    protected $event_id;
    protected $request_to_user;
    protected $message;

    public function __construct($token,$user,$event_id,$request_to_user,$message)
    {
        Log::info("/********* IOS push notification job dispatch Constructor ************/");
        $this->token = $token;
        $this->user = $user;
        $this->event_id = $event_id;
        $this->request_to_user = $request_to_user;
        $this->message = $message;

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("/********* IOS push notification job dispatch ************/");
        Log::info("Eent ID: ". $this->event_id);
        Log::info("device Token: ". $this->token);
        Log::info("/********* End ************/");

        Log::info("Create Event Notification response: ".$this->event_id);
        $event = Event::where('id',$this->event_id)->first();
        $user = $this->user;
        $request_to = $this->request_to_user;
        $message = $this->message;
        
        if(!empty($user->firstName)){
            $user_name = $user->firstName;
        }else{
            $user_name = $user->phone;
        }

        $message = PushNotification::Message($user_name.' '.$message.' '. $event->title.'.'  ,array(
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
                'request_to' => $request_to->id,
                'event_id' => $this->event_id,
                'status' => 'request'
            ))
        ));

        try {
            // Validate the value...
            $response = PushNotification::app('invitedIOS')->to($this->token)->send($message);
        } catch (\Exception $e) {
            Log::error("Invalid device token.");
            return true;
        }
        Log::info("Create Event Notification response: ".print_r($response));
    }
}
