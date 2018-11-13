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
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 5;
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
    protected $environment;

    public function __construct($token,$environment,$user,$event_id,$request_to_user,$message)
    {
        Log::info("/********* IOS push notification job dispatch Constructor ************/");
        $this->token = $token;
        $this->user = $user;
        $this->event_id = $event_id;
        $this->request_to_user = $request_to_user;
        $this->message = $message;
        $this->environment = $environment;

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

            Log::info("========================== In Try======================");
            // Validate the value...
            //dd($this->environment);
            if($this->environment == 'development') {
                Log::info(" Environment is Development-----".$this->token."---- Before Send");
                $response = PushNotification::app('invitedIOSDev')->to($this->token)->send($message);
                Log::info(" Environment is Development-----".$this->token."------After Send");
            } else{
                Log::info(" Environment is Production-----".$this->token."---- Before Send");
                $response = PushNotification::app('invitedIOS')->to($this->token)->send($message);
                Log::info(" Environment is Production-----".$this->token."------After Send");
            }
            Log::info("response in try: ".print_r($response));
        } catch (\Exception $e) {
            Log::info("========================== In Catch======================");
            Log::info("Invalid device token.".$this->token);
            return false;
        }
        Log::info("Create Event Notification response: ".print_r($response));

    }
}
