<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Event;
use Log;


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

    protected $casts = [
        'deleted_at'=>'timestamp',
    ];
    
    public function scopeGetEventIds($query){
        return $query->pluck('event_id')->toArray();
    }
    
    
    public static function CreateRequestEvent($created_by,$user,$event_id){
        $request = RequestsEvent::where('request_to',$user->id)->where('event_id',$event_id)->first();
        if(empty($request->id)){
            return  RequestsEvent::create(['created_by' => $created_by, 'request_to'=>$user->id,'event_id'=>$event_id,'confirmed'=>2])->id;
        }
    }

    public function contact_list(){
        return $this->hasMany('App\Contact', 'contact_list_id', 'list_id');
    }

    public static function getEventRequest($request_to){
       $total_count =  self::where('request_to',$request_to)->count();

       $request_event = self::select('event_id','created_by')->where('request_to','=',$request_to)->orderBy('created_at','desc')->get();
       $request_count = [];
       $index = 0;

       foreach ($request_event as $event)
       {
           //@todo refactor join to get data from contacts with contactlists
           $event_requests  = self::with('contact_list')->select('requests.event_id','requests.created_by',DB::raw('count(requests.event_id) as total'),'confirmed', 'contactlists.list_name', 'contactlists.contact_list')
                               ->join('events','events.id','=','requests.event_id')
                               ->join('contactlists','contactlists.id','=','events.list_id')
                               ->groupBy('event_id')
                               ->where('event_id','=',$event->event_id)
                               ->where('request_to','=',$request_to)
                                ->orderBy('requests.updated_at','desc')
                               ->get();
           foreach ($event_requests as $request){
               $created_by = User::where('id',$request->created_by)->first();
               if($created_by){
                   $contact_list = json_decode($request->contact_list);
                   $event = Event::getEventByID($request->event_id);
                   $request_count[$index]['event_id'] = $request->event_id;
                   $request_count[$index]['total'] = $request->total;
                   $request_count[$index]['create_by'] = $created_by->email;
                   $request_count[$index]['phone'] = $created_by->phone;
                   $request_count[$index]['invited_by'] = $created_by->firstName.' '.$created_by->lastName;
                   $request_count[$index]['profileImage'] = $created_by->profileImage;
                   $request_count[$index]['mobile'] = $created_by->mobile;
                   $request_count[$index]['address'] = $event->event_address;
                   $request_count[$index]['event_time'] = $event->event_time;
                   $request_count[$index]['created_at'] = date('Y-m-d H:i:s', strtotime($event->created_at));
                   $request_count[$index]['updated_at'] = date('Y-m-d H:i:s', strtotime($event->updated_at));
                   $request_count[$index]['event_title'] = $event->title;
                   $request_count[$index]['longitude'] = $event->longitude;
                   $request_count[$index]['latitude'] = $event->latitude;
                   $request_count[$index]['payment_method'] = $event->payment_method;
                   $request_count[$index]['confirmed'] = $request->confirmed;
                   $request_count[$index]['request_created_by'] = $request->created_by;
                   $request_count[$index]['list_name'] = $request->list_name;
                   $request_count[$index]['contact_list'] = $contact_list;
                   $request_count[$index]['total_invited'] = count($contact_list);
                   $index++;
               }
           }
       }
        
        return array('event_request'=>$request_count);
    }

    public static function acceptRequest($event_id,$request_to){
        $update = self::where('event_id',$event_id)->where('request_to',$request_to)->update(['confirmed'=>1]);
        $accepted_requests = RequestsEvent::acceptedEventRequest($event_id);
        $accepted_requests_count = count($accepted_requests);
        $event_detail = Event::getEventByID($event_id);
        Log::info("================= Accept Request API After Acceptance =========================");
        $notification_users = [];
        if($event_detail->max_invited == $accepted_requests_count){
            Log::info("Event maxi invited ".$event_detail->max_invited);
            Log::info("Request Confirmed ".$accepted_requests_count);
            //send notification only to pending request users
            $not_accepted_event_request =   self::where('event_id',$event_id)->where('confirmed','!=',1)->where('confirmed','!=',0)->get();
            foreach ( $not_accepted_event_request as $request) {
                Log::info("Request id to update ".$request->id);
                self::where('id',$request->id)->update(['confirmed'=>3]);
                $notification_users[] = $request->request_to;
            }
            
        }
        return array('update'=>$update,'notification_users'=>$notification_users);
    }

    public static function acceptRequestLimitEqual($event_id,$request_to){
        $update = self::where('event_id',$event_id)->where('request_to',$request_to)->update(['confirmed'=>3]);

        return array('update'=>$update);
    }
    public static function eventExpire($event_id,$request_to){
        $update = self::where('event_id',$event_id)->where('request_to',$request_to)->update(['confirmed'=>3]);

        return array('update'=>$update);
    }

    public static function acceptedEventRequest($event_id){


        return  self::where('event_id',$event_id)->where('confirmed',1)->get();

    }

    public static function notAcceptedEventRequest($event_id){
        return  self::where('event_id',$event_id)->where('confirmed',1)->get();
    }

    public static function createdByRequest($event_id,$request_to){

        return  self::where('event_id',$event_id)->where('request_to',$request_to)->first();

    }

    public static function rejectRequest($event_id,$request_to){

        return  self::where('event_id',$event_id)->where('request_to',$request_to)->update(['confirmed'=>0]);

    }

    public static function receivedRequest($created_by){

        return self::select('requests.*','users.firstName','users.lastName','users.phone',
            'events.title','events.event_time', 'events.event_address', 'events.latitude AS event_latitude', 'events.longitude AS event_longitude', 'events.payment_method',
            'contactlists.list_name', 'contactlists.contact_list', DB::raw('(CASE WHEN requests.request_to = ' . $created_by . ' THEN 1 ELSE 0 END) AS event_accepted'))
                ->join('users','users.id','=','requests.created_by')
                ->join('events','events.id','=','requests.event_id')
                ->join('contactlists','contactlists.id','=','events.list_id')
                ->where(function ($query) use ($created_by) {
                    $query ->where('created_by',$created_by);
                    $query ->orWhere('request_to',$created_by);
                })
//                ->where('created_by',$created_by)
                ->where('requests.confirmed',1)
                ->orderBy('requests.updated_at','desc')
                ->groupBy('requests.event_id')
                ->get();

    }

    public static function eventAcceptedByMe($request_to){
        
        
        $eventIds = self::where(['request_to'=>$request_to, 'confirmed'=>1])->groupBy('event_id')->pluck('event_id')->toArray();
        $events = Event::whereIn('id', $eventIds)
                    ->with('owner', 'acceptedRequests.invitee', 'contactList')
                    ->withCount(['requests', 'acceptedRequests'])
                    ->latest('updated_at')->get();
        return $events;
    }

    public static function eventSentByMe($created_by){
        $eventIds = self::where('created_by',$created_by)->whereIn('confirmed',[1,0])->groupBy('event_id', 'request_to')->pluck('event_id')->toArray();
        $events = Event::whereIn('id', $eventIds)
                    ->with('owner', 'acceptedRequests.invitee', 'rejectRequests.invitee', 'pendingRequests.invitee', 'contactList')
                    ->withCount(['requests', 'acceptedRequests', 'rejectRequests', 'pendingRequests'])
                    ->latest('updated_at')->get();
        return $events;
    }


    public static function acceptedRequestUsers($event_id, $created_by){

        return self::select('users.phone','users.firstName','users.lastName')
            ->join('users','users.id','=','requests.request_to')
            ->where('created_by',$created_by)
            ->where('event_id',$event_id)
            ->where('requests.confirmed',1)
            ->orderBy('requests.updated_at','desc')
            ->get();

    }

    public static function SendRequestAllUsers($event_id, $created_by){

        return self::select('users.phone','users.firstName','users.lastName', 'requests.confirmed')
            ->join('users','users.id','=','requests.request_to')
            ->where('created_by',$created_by)
            ->where('event_id',$event_id)
            ->orderBy('requests.updated_at','desc')
            ->get();

    }

    public static function deleteRequest($id){
        $requests = self::where('event_id',$id);
        $requests->delete();
    }
    
    public function getUserEventRequests($event_id,$user_id){

        return self::where('event_id',$event_id)->where('request_to',$user_id)->first();
    }

    public function getUserEventRequestsAccepted($event_id,$user_id){

        return self::where('event_id',$event_id)->where('request_to',$user_id)->where('confirmed', 1)->first();
    }

    public function owner()
    {
        return $this->belongsTo('App\Models\User', 'created_by', 'id')->select('id', 'firstName', 'lastName', 'username', 'email', 'phone');
    }

    public function invitee()
    {
        return $this->belongsTo('App\Models\User', 'request_to', 'id')->select('id', 'firstName', 'lastName', 'username', 'email', 'phone', 'profileImage');
    }

    public function event()
    {
        return $this->belongsTo('App\Models\Event', 'event_id', 'id');
    }

    protected function castAttribute($key, $value)
    {

        if ($this->getCastType($key) == 'array' && is_null($value)) {
            return [];
        }
        if (is_null($value)) {
            return '';
        }

        return parent::castAttribute($key, $value);
    }

}
