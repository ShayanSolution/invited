<?php

namespace App\Jobs;
use App\Models\Event;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

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
        PushNotification::app('invitedIOS')->to($this->token)->send($message);
    }
}
