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
        'event_time'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public static function CreateEvent($request){
        $request = $request->all();
        $user_id = $request['user_id'];
        $event_list = self::where('user_id',$user_id)->first();
        if($event_list){
            return  self::where('user_id',$user_id)->update($request);
        }else{
            return self::create($request)->id;
        }
    }
}
