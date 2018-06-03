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
use Illuminate\Http\Response;


class EventController extends Controller
{
    public function CreateEvent(Request $request){

        $this->validate($request,[
            'user_id' => 'required',
            'payment_method' => 'required',
            'event_address' => 'required',
            'title' => 'required',
            'event_time' => 'required',
            'list_id' => 'required',
        ]);

        //list id
        $list_id = $request['list_id'];
        $user_list = ContactList::getList($request['user_id'],$list_id);
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
        $this->sendUserNotification($request,$event_id,$list_id);
        
        if($event_id){
            return [
                'status' => 'success',
                'message' => 'Event Created Successfully',
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
        $list_id = $request['list_id'];
        $list = ContactList::getList($user_id,$list_id);
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

    public function sendUserNotification(Request $request,$event_id,$list_id){

        $request = $request->all();
        $created_by = $request['user_id'];
        $created_user = User::where('id',$created_by)->first();
        //get list against user id.
        $user_list = ContactList::getList($created_by,$list_id);

        if(!empty($user_list->first())) {
            foreach ($user_list as $list) {
                foreach (json_decode($list->contact_list) as $user_detail) {
                    $phone = trim(preg_replace('/[\s\t\n\r\s]+/', ' ', $user_detail->phone));
                    $user = User::where('phone', $phone)->first();
                    dd($phone);
                    //create event request
                    if(!empty($user)){
                        $request = RequestsEvent::CreateRequestEvent($created_by, $user, $event_id);
                        $device_token = $user->device_token;
                        if (!empty($device_token)) {
                            //send notification to user list
                            //Log::info("Request Cycle with Queues Begins");
                            $job = new SendPushNotification($device_token, $created_user, $event_id, $user);
                            dispatch($job);
                            // Log::info('Request Cycle with Queues Ends');
                        }
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
        $user_list = ContactList::getUserListCount($user_id);
        $total_events = count($events);
        if($events){

            return response()->json(
                [
                    'total_events'=>$total_events,
                    'totl_invited'=>count($user_list),
                    'user_events' => $events
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
    
    public function acceptRequest(Request $request){

        $this->validate($request,[
            'event_id' => 'required',
            'request_to' => 'required'
        ]);
        $event_id = $request['event_id'];
        $id = $request['request_to'];
        $accepted = RequestsEvent::acceptRequest($event_id,$id);
        if($accepted){
            $created_by = RequestsEvent::createdByRequest($event_id,$id);
            $this->sendRequestNotification($created_by->created_by,$event_id,$request_status = "accepted");
            return response()->json(
                [
                    'status' => 'Request accepted successfully',
                ], 200
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to accept'
                ], 422
            );
        }
    }

    public function rejectRequest(Request $request){

        $this->validate($request,[
            'event_id' => 'required',
            'request_to' => 'required'
        ]);
        $event_id = $request['event_id'];
        $id = $request['request_to'];
        $accepted = RequestsEvent::rejectRequest($event_id,$id);
        if($accepted){
            $created_by = RequestsEvent::createdByRequest($event_id,$id);
            $this->sendRequestNotification($created_by->created_by,$event_id,$request_status = "accepted");
            return response()->json(
                [
                    'status' => 'Request rejected successfully',
                ], 200
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to reject request'
                ], 422
            );
        }
    }

    public function sendRequestNotification($id,$event_id,$request_status=null){

        $request_acctepted_user = User::where('id',$id)->first();

        if($request_acctepted_user){

            if(!empty($request_acctepted_user->firstName)){
                $user_name = $request_acctepted_user->firstName;
            }else{
                $user_name = $request_acctepted_user->phone;
            }

            if(!empty($request_acctepted_user->device_token)){
                $message = PushNotification::Message($user_name." $request_status your request "  ,array(
                    'badge' => 1,
                    'sound' => 'example.aiff',

                    'actionLocKey' => 'Action button title!',
                    'locKey' => 'localized key',
                    'locArgs' => array(
                        'localized args',
                        'localized args',
                    ),
                    'launchImage' => 'image.jpg',

                    'custom' => array('custom_data' => array(
                        'accepted_user' => $user_name,
                        'event_id' => $event_id
                    ))
                ));
                PushNotification::app('invitedIOS')->to($request_acctepted_user->device_token)->send($message);
            }
        }
    }

    public function receivedRequest(Request $request){

        $this->validate($request,[
            'created_by' => 'required',
        ]);

        $id = $request['created_by'];
        $requests = RequestsEvent::receivedRequest($id);

        if($requests){

            return response()->json(
                [
                    'Received Requests' => $requests,
                ], 200
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to reject request'
                ], 422
            );
        }

    }

    public function updateUserEvent(Request $request){

        $this->validate($request,[
            'event_id' => 'required',
            'title' => 'required',
            'event_address' => 'required',
            'event_time' => 'required',
            'payment_method' => 'required'
        ]);

        $event =Event::updateEvent($request);
        if($event){
            return response()->json(
                [
                    'success' => 'Event Updated Successfully',
                ], 200
            );
        }else{
            return response()->json(
                [
                    'error' => 'Unable to update event',
                ], 200
            );
        }

    }

    public function deleteEvent(Request $request){

        $this->validate($request,[
            'event_id' => 'required',
        ]);

        $event =Event::deleteEvent($request);
        if($event){
            return response()->json(
                [
                    'message' => 'Event Deleted Successfully',
                ], 200
            );
        }else{
            return response()->json(
                [
                    'message' => 'Unable to Delete event',
                ], 400
            );
        }

    }

    public function getDownload()
    {
        //PDF file is stored under project/public/download/info.pdf
        $file= base_path(). "/invited api calls.postman_collection.json";
        return response()->download($file);
    }
}
