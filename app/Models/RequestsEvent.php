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
        return  RequestsEvent::create(['created_by' => $created_by, 'request_to'=>$user->id,'event_id'=>$event_id,'confirmed'=>0])->id;
    }

    public static function getEventRequest($request_to){
       $total_count =  self::where('request_to',$request_to)->count();

       $request_event = self::select('event_id')->where('request_to','=',$request_to)->get();
       $request_count = [];
        $index = 0;
       foreach ($request_event as $event)
       {
           $event_requests  = self::select('event_id',DB::raw('count(event_id) as total'))
                               ->groupBy('event_id')
                               ->where('event_id','=',$event->event_id)
                               ->get();
           foreach ($event_requests as $request){
               $request_count[$index]['event_id'] = $request->event_id;
               $request_count[$index]['total'] = $request->total;
               $index++;
           }
       }
        
        return array('total_count'=>$total_count,'event_request'=>$request_count);
    }

}
