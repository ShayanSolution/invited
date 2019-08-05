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
        'sender_name',
        'sender_image',
        'sender_id',
    ];

    public static function saveNotification($message,$eventId,$listId,$senderName,$senderImage,$userId){
//        $eventExist = Notification::where('event_id',$event_id)->first();
//        if (!empty($eventExist)){
//            NotificationStatus::where('notification_id',$eventExist->id)->delete();
//            Notification::where('event_id',$event_id)->delete();
//        }
        return Notification::create([
            'message' => $message,
            'event_id' => $eventId,
            'list_id' => $listId,
            'sender_name' => $senderName,
            'sender_image' => $senderImage,
            'sender_id' => $userId,
        ])->id;
    }

    public function notification_statuses()
    {
        return $this->hasMany('App\Models\NotificationStatus');
    }
}
