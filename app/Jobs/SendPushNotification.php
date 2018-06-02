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

    public function __construct($token,$user,$event_id)
    {
        $this->token = $token;
        $this->user = $user;
        $this->event_id = $event_id;
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
        if(!empty($user->firstName)){
            $user_name = $user->firstName;
        }else{
            $user_name = $user->phone;
        }

        $message = PushNotification::Message($user_name.' would like to invite you on the event '. $event->title.'.'  ,array(
            'badge' => 1,
            'sound' => 'example.aiff',

            'actionLocKey' => 'Action button title!',
            'locKey' => 'localized key',
            'locArgs' => array(
                'localized args',
                'localized args',
            ),
            'launchImage' => 'image.jpg',

            'custom' => array('custom data' => array(
                'we' => 'want', 'send to app'
            ))
        ));
        PushNotification::app('invitedIOS')->to($this->token)->send($message);
    }
}
