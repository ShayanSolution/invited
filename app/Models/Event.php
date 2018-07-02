<?php

namespace App\Models;

use App\ContactList;
use Illuminate\Database\Eloquent\Model;
use App\Models\RequestsEvent;
use Log;


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
        'list_id',
        'max_invited',
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
        $events = self::select('events.*','contactlists.list_name')
                   ->join('contactlists','contactlists.id','=','events.list_id')
                   ->where('events.user_id',$id)
                   ->orderBy('events.created_at','desc')
                   ->get();

        $user_events = [];
        $index=0;
        foreach($events as $event){
            $user_events[$index]['id'] = $event->id;
            $user_events[$index]['title'] = $event->title;
            $user_events[$index]['event_address'] = $event->event_address;
            $user_events[$index]['event_time'] = $event->event_time;
            $user_events[$index]['event_created_time'] = $event->created_at->date;
            $user_events[$index]['longitude'] = $event->longitude;
            $user_events[$index]['latitude'] = $event->latitude;
            $user_events[$index]['payment_method'] = $event->payment_method;
            $user_events[$index]['user_id'] = $event->user_id;
            $user_events[$index]['list_id'] = $event->list_id;
            $user_events[$index]['list_name'] = $event->list_name;
            $user_list = ContactList::getUserList($event->list_id);
            $user_events[$index]['list_count'] = count(json_decode($user_list->contact_list));
            $user_events[$index]['max_invited'] = $event->max_invited;
            $list_index=0;
            $arr = [];
            foreach (json_decode($user_list->contact_list) as $list){
               $user = User::where('phone',$list->phone)->first();
               //if user registered through app
               if($user){
                   $arr[$list_index]['name'] = $user->firstName." ".$user->lastName;
                   $arr[$list_index]['phone'] = $user->phone;
                   $arr[$list_index]['confirmed'] = $user->confirmed;

               }
               //add user list to array.
               else{
                   $arr[$list_index++] = $list;
               }
               $list_index++;
            }
            $user_events[$index]['list_users'] = $arr;
            $index++;
        }
        return $user_events;

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
            'list_id'=>$request['list_id'],
            'longitude'=>$request['longitude'],
            'latitude'=>$request['latitude'],
            'max_invited'=>$request['max_invited'],
        ]);
        return $id;
    }

    public static function deleteEvent($request){
        $id = $request['event_id'];
        $event = self::find($id);
        if($event){
            $event->delete();
            //delete event requests
            RequestsEvent::deleteRequest($id);
            return true;
        }else{
            return false;
        }

    }

    public static function generateErrorResponse($validator){
        $response = null;
        if ($validator->fails()) {

            $response = $validator->errors()->toArray();
            $response['error'] = $validator->errors()->toArray();
            $response['code'] = 500;
            $response['message'] = 'Error occured';
            Log::error("Code =>".$response['code']);
            Log::error("message =>".print_r($response['error'],true));
        }
        else{
            $response['code'] = 200;
            $response['message'] = 'operation completed successfully';
        }
        return $response;
    }
}
