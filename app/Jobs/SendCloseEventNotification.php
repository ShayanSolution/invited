<?php

namespace App\Jobs;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

class SendCloseEventNotification extends Job
{
    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $token;
    protected $event_title;
    
    
    public function __construct($token,$event_title)
    {
        $this->token = $token;
        $this->event_title = $event_title;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $message = PushNotification::Message($this->event_title."  has been closed ", array(
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
}
