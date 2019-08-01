<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    /**
     * @var string
     */
    protected $table = 'notifications';
    protected $fillable = [
        'message',
        'event_id',
        'list_id',
        'sender_id',
    ];

    public static function saveNotification($message,$event_id,$list_id,$user_id){
//        $eventExist = Notification::where('event_id',$event_id)->first();
//        if (!empty($eventExist)){
//            NotificationStatus::where('notification_id',$eventExist->id)->delete();
//            Notification::where('event_id',$event_id)->delete();
//        }
        return Notification::create([
            'message' => $message,
            'event_id' => $event_id,
            'list_id' => $list_id,
            'sender_id' => $user_id,
        ])->id;
    }

    public function notification_statuses()
    {
        return $this->hasMany('App\Models\NotificationStatus');
    }
}
