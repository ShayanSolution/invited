<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'events';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'longitude',
        'latitude',
        'payment_method',
        'user_id',
        'event_time',
        'title',
        'event_address',
        'list_id'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public static function CreateEvent($request){
        $request = $request->all();
        return self::create($request)->id;
    }

    public static function getEvents($id){
        return self::where('user_id',$id)->get();
    }
    
    public static function getEventByID($id){
        return self::where('id',$id)->first();
    }

    public static function updateEvent($request){
        $id = $request['event_id'];
        self::where('id',$id)->update([
            'title'=>$request['title'],
            'event_address'=>$request['event_address'],
            'event_time'=>$request['event_time'],
            'payment_method'=>$request['payment_method'],
        ]);
        
        return $id;
    }
}
