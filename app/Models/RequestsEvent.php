<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class RequestsEvent extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'requests';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['created_by','request_to','confirmed','event_id'];
    
    public static function CreateRequestEvent($created_by,$user,$event_id){
        $request = RequestsEvent::where('request_to',$user->id)->where('event_id',$event_id)->first();
        if(empty($request->id)){
            return  RequestsEvent::create(['created_by' => $created_by, 'request_to'=>$user->id,'event_id'=>$event_id,'confirmed'=>2])->id;
        }
    }

    public static function getEventRequest($request_to){
       $total_count =  self::where('request_to',$request_to)->count();
       dd($request_to);
       $request_event = self::select('event_id')->where('request_to','=',$request_to)->get();
       $request_count = [];
        $index = 0;
       foreach ($request_event as $event)
       {
           $event_requests  = self::select('event_id','created_by',DB::raw('count(event_id) as total'),'confirmed')
                               ->groupBy('event_id')
                               ->where('event_id','=',$event->event_id)
                               ->get();

           foreach ($event_requests as $request){

               $created_by = User::where('id',$request->created_by)->first();
               $event = Event::getEventByID($request->event_id);
               $request_count[$index]['event_id'] = $request->event_id;
               $request_count[$index]['total'] = $request->total;
               $request_count[$index]['create_by'] = $created_by->email;
               $request_count[$index]['address'] = $event->event_address;
               $request_count[$index]['event_time'] = $event->event_time;
               $request_count[$index]['event_title'] = $event->title;
               $request_count[$index]['longitude'] = $event->longitude;
               $request_count[$index]['latitude'] = $event->latitude;
               $request_count[$index]['payment_method'] = $event->payment_method;
               $request_count[$index]['confirmed'] = $request->confirmed;
               $index++;

           }
       }
        
        return array('event_request'=>$request_count);
    }

    public static function acceptRequest($event_id,$request_to){

      return  self::where('event_id',$event_id)->where('request_to',$request_to)->update(['confirmed'=>1]);

    }

    public static function createdByRequest($event_id,$request_to){

        return  self::where('event_id',$event_id)->where('request_to',$request_to)->first();

    }

    public static function rejectRequest($event_id,$request_to){

        return  self::where('event_id',$event_id)->where('request_to',$request_to)->update(['confirmed'=>0]);

    }

    public static function receivedRequest($created_by){

        return self::select('requests.*','users.firstName','users.lastName','users.phone','events.title','events.event_time')
                ->join('users','users.id','=','requests.request_to')
                ->join('events','events.id','=','requests.event_id')
                ->where('created_by',$created_by)
                ->get();
    }

    public static function deleteRequest($id){
        $requests = self::where('event_id',$id);
        $requests->delete();
    }
    
    public function getUserEventRequests($event_id,$user_id){
        
        return self::where('event_id',$event_id)->where('request_to',$user_id)->first();
    }

}
