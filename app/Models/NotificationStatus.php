<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Notification;

class NotificationStatus extends Model
{
    protected $table = 'notifications_status';
    protected $fillable = [
        'notification_id',
        'receiver_id',
        'related_screen',
        'read_status',
    ];

    public static function saveNotificationStatus($notificationId,$receiverId,$relatedScreen){
        NotificationStatus::create([
            'notification_id' => $notificationId,
            'receiver_id' => $receiverId,
            'related_screen' => $relatedScreen,
        ]);
    }

    public function notification()
    {
        return $this->belongsTo('App\Models\Notification');
    }
}
