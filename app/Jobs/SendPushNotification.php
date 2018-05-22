<?php

namespace App\Jobs;
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
    protected $class;
    protected $subject;
    protected $user_age;
    protected $programme_id;
    protected $subject_id;

    public function __construct($token,$user,$class,$subject,$user_age,$programme_id,$subject_id)
    {
        $this->token = $token;
        $this->user = $user;
        $this->class = $class;
        $this->subject = $subject;
        $this->user_age = $user_age;
        $this->programme_id = $programme_id;
        $this->subject_id = $subject_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $user = $this->user;
        $class = $this->class;
        $subject = $this->subject;
        $user_age = $this->user_age;
        $programme_id = $this->programme_id;
        $subject_id = $this->subject_id;
        
        $message = PushNotification::Message(
            $user->firstName.' '.$user->lastName.' wants a session with you',
            array(
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
                    'Student_Name' => $user->firstName." ".$user->lastName,
                    'Student_id' => $user->id,
                    'Class_Name' => $class->name,
                    'Subject_Name' => $subject->name,
                    'Class_id' => $programme_id,
                    'Subject_id' => $subject_id,
                    'IS_Group' => 0,
                    'Longitude' => $user->longitude,
                    'Latitude' => $user->latitude,
                    'Datetime' => Carbon::now()->toDateTimeString(),
                    'Age' => $user_age>0?$user_age:'',
                    'Profile_Image' => !empty($user->profileImage)?URL::to('/images').'/'.$user->profileImage:'',
                ))
            ));

        PushNotification::app('appNameIOS')
        ->to($this->token)
        ->send($message);
    }
}
