<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Event;
use App\ContactList;
use App\Models\User;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use App\Models\RequestsEvent;
use Log;
use App\Jobs\SendPushNotification;

class EventController extends Controller
{
    public function CreateEvent(Request $request){

        $this->validate($request,[
            'user_id' => 'required',
            'payment_method' => 'required',
            'event_address' => 'required',
            'title' => 'required',
            'event_time' => 'required'
        ]);
        
        $user_list = ContactList::getList($request['user_id']);
        if(empty($user_list->first())){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to create event'
                ], 422
            );

        }
        //create event
        $event_id = Event::CreateEvent($request);
        //create event request and send notifications to user list.
        $this->sendUserNotification($request,$event_id);
        
        if($event_id){
            return [
                'status' => 'success',
                'messages' => 'Event Created Successfully',
            ];
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to create event'
                ], 422
            );
        }
    }

    public function getUserContactList(Request $request){
        $request = $request->all();
        $user_id = $request['user_id'];
        $list = ContactList::getList($user_id);
        if($list){
            $users = json_decode($list->contact_list);
            return response()->json(
                [
                    'user_contact_list' => $users,
                ], 200
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable find contact list'
                ], 422
            );
        }

    }

    public function sendUserNotification(Request $request,$event_id){

        $request = $request->all();
        $created_by = $request['user_id'];
        //get list against user id.
        $user_list = ContactList::getList($created_by);

        if(!empty($user_list->first())) {
            foreach ($user_list as $list) {
                foreach (json_decode($list->contact_list) as $user_detail) {
                    $id = $user_detail->user_id;
                    $user = User::where('id', $id)->first();
                    //create event request
                    $request = RequestsEvent::CreateRequestEvent($created_by, $user, $event_id);
                    $device_token = $user->device_token;
                    if (!empty($device_token)) {
                        //send notification to user list
                        Log::info("Request Cycle with Queues Begins");
                        $job = new SendPushNotification($device_token, $user);
                        dispatch($job);
                        Log::info('Request Cycle with Queues Ends');
                    }
                }
            }
        }
    }

    public function getUserEvents(Request $request){
        $this->validate($request,[
            'user_id' => 'required'
        ]);
        $request = $request->all();
        $user_id = $request['user_id'];
        $events = Event::getEvents($user_id);
        if($events){

            return response()->json(
                [
                    'User Event' => $events,
                ], 200
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable find event list'
                ], 422
            );
        }
    }

    public function getEventRequests(Request $request){

        $this->validate($request,[
            'request_to' => 'required'
        ]);
        $request = $request->all();
        $request_to  = $request['request_to'];
        $total_count  = RequestsEvent::getEventRequest($request_to);

        if($total_count){

            return response()->json(
                [
                    'total-count' => $total_count['total_count'],
                    'event_requests' => $total_count['event_request'],
                ], 200
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable find total count'
                ], 422
            );
        }
    }

}
