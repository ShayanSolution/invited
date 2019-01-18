<?php

namespace App\Models;

use App\ContactList;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Models\RequestsEvent;
use Log;
use Auth;


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
        'event_date',
        'event_only_time',
        'title',
        'event_address',
        'list_id',
        'max_invited',
    ];

    protected $appends = ['who_will_pay', 'event_type', 'list_name','list_count'];
    protected $casts = [
        'title'=>'string',
        'event_address'=> 'string',
        'max_invited'=> 'integer',
    ];

    public function getListNameAttribute(){
        if(is_null($this->contactList)){
            return '';
        }
        return $this->contactList->list_name;
    }

    public function getWhoWillPayAttribute()
    {
        if(Auth::user()->id == $this->user_id){
            //You->1
            //Invitee-> 2
            //shared ->3
            if($this->payment_method == 1){
                return 'You';
            }elseif($this->payment_method == 2){
                return 'Invitee';
            }else{
                return 'Shared';
            }
        }else{
            //Inviter->1
            //You->2
            //shared->3
            if($this->payment_method == 1){
                return 'Inviter';
            }elseif($this->payment_method == 2){
                return 'You';
            }else{
                return 'Shared';
            }
        }
    }

    public function getEventTypeAttribute()
    {
        if(Auth::user()->id == $this->user_id){
            return 'Sent by me.';
        }else{
            return 'Accepted by me.';
        }
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public static function CreateEvent($request){
        $request = $request->all();
        //dd($request);
        //$request['event_date'] = empty($request['event_date']) ? null : $request['event_date'];
        //$request['event_only_time'] = empty($request['event_only_time']) ? null : $request['event_only_time'];
        $event = self::create($request);
        return $event->id;
    }

    public function scopeGetEventDetails($query)
    {
        return $query->with('owner', 'acceptedRequests.invitee', 'contactList')
            ->withCount(['requests', 'acceptedRequests'])
            ->latest('updated_at');
    }

    public function getEventTimeAttribute(){
        //date is null and time exists
        if(is_null($this->event_date) && !is_null($this->event_only_time)){
            return $this->event_only_time;
        }
        //date exists and time is null
        else if(!is_null($this->event_date) && is_null($this->event_only_time)){
            return $this->event_date;
        }
        //date exists and time exists
        else if(!is_null($this->event_date) && !is_null($this->event_only_time)){
            return $this->event_date.' '.$this->event_only_time;
        }
        //date is null and time is null
        else if(is_null($this->event_date) && is_null($this->event_only_time)){
            return '';
        }
    }

    public function setEventDateAttribute($value){
        if(empty($value)){
            $this->attributes['event_date'] = null;
        }
        else{
            $this->attributes['event_date'] = $value;
        }
    }


    public function setEventOnlyTimeAttribute($value){
        if(empty($value)){
            $this->attributes['event_only_time'] = null;
        }
        else{
            $this->attributes['event_only_time'] = $value;
        }
    }

    public static function getEvents($id){
        $events = self::with('contactList')->select('events.*','contactlists.list_name', 'contactlists.deleted_at')
                   ->join('contactlists','contactlists.id','=','events.list_id')
                   ->where('events.user_id',$id)
                   ->orderBy('events.updated_at','desc')
                   ->get();

        $user_events = [];
        $index=0;
        foreach($events as $event){
            $user_events[$index]['id'] = $event->id;
            $user_events[$index]['title'] = $event->title;
            $user_events[$index]['event_address'] = $event->event_address;
            $user_events[$index]['event_time'] = $event->event_time;
            $user_events[$index]['canceled_at'] = $event->canceled_at;
            $user_events[$index]['is_created_by_admin'] = $event->is_created_by_admin;
            $user_events[$index]['event_created_time'] = date('Y-m-d H:i:s', strtotime($event->created_at));
            $user_events[$index]['event_update_time'] = date('Y-m-d H:i:s', strtotime($event->updated_at));
            $user_events[$index]['longitude'] = $event->longitude;
            $user_events[$index]['latitude'] = $event->latitude;
            $user_events[$index]['payment_method'] = $event->payment_method;
            $user_events[$index]['user_id'] = $event->user_id;
            $user_events[$index]['list_id'] = $event->list_id;
            $user_events[$index]['list_name'] = $event->list_name;
            $user_events[$index]['deleted_at'] = $event->deleted_at;
            $user_list = ContactList::getUserList($event->list_id);
            $user_events[$index]['list_count'] = count(json_decode($user_list->contact_list));
            if($event->max_invited == ''){
                $user_events[$index]['max_invited'] = 0;
            }else{
                $user_events[$index]['max_invited'] = $event->max_invited;
            }

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
        $data = [
            'title'=>$request['title'],
            'event_address'=>$request['event_address'],
            'payment_method'=>$request['payment_method'],
            'list_id'=>$request['list_id'],
            'longitude'=>$request['longitude'],
            'latitude'=>$request['latitude'],
            'max_invited'=>$request['max_invited'],
        ];
        if (!empty($request->input('event_date'))){
            $data['event_date'] = $request->input('event_date');
        } else {
            $data['event_date'] = null;
        }
        if (!empty($request->input('event_only_time'))){
            $data['event_only_time'] = $request->input('event_only_time');
        } else {
            $data['event_only_time'] = null;
        }

        $id = $request['event_id'];
        self::where('id',$id)->update($data);
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

    public static function cancelEvent($request){
        $id = $request['event_id'];
        $event = self::find($id);
        if($event){
            self::where('id',$id)->update([
                'canceled_at'=>Carbon::now(),
            ]);
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

    public function owner()
    {
        return $this->belongsTo('App\Models\User', 'user_id', 'id')->select('id', 'firstName', 'lastName', 'username', 'email', 'phone', 'profileImage');
    }

    public function requests()
    {
        return $this->hasMany('App\Models\RequestsEvent', 'event_id');
    }

    public function acceptedRequests()
    {
        return $this->hasMany('App\Models\RequestsEvent', 'event_id')->where('confirmed', 1)->latest('updated_at');
    }

    public function rejectRequests()
    {
        return $this->hasMany('App\Models\RequestsEvent', 'event_id')->where('confirmed', 0)->latest('updated_at');
    }

    public function pendingRequests()
    {
        return $this->hasMany('App\Models\RequestsEvent', 'event_id')->where('confirmed', 2)->latest('updated_at');
    }

    public function contactList()
    {
        return $this->belongsTo('App\ContactList', 'list_id', 'id')->withTrashed();
    }

    protected function contactListCount()
    {
        $contactList = $this->belongsTo('App\ContactList', 'list_id', 'id')->withTrashed();
        $contactList = $contactList->get()->first();
        if(!empty($contactList)){
            return count(json_decode($contactList->contact_list));
        }
        else
        {
            return 0;
        }

    }

    public function getListCountAttribute(){
        return $this->contactListCount();
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
