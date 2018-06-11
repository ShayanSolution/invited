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
use Illuminate\Support\Facades\Validator;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Facades\FCM;

class EventController extends Controller
{
    public function CreateEvent(Request $request){

       $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'payment_method' => 'required',
            'event_address' => 'required',
            'title' => 'required',
            'event_time' => 'required',
            'list_id' => 'required',
        ]);
        $response = Event::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

        //list id
        $list_id = $request['list_id'];
        //$user_list = ContactList::getList($list_id);
        $user_list = ContactList::getList($list_id);
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
        //check platform
        $user_id = $request['user_id'];
        $user_platform = User::where('id',$user_id)->first();
        $message = "would like to invite you on";
        $this->sendUserNotification($request,$event_id,$list_id,$message);

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
        //$list = ContactList::getList($user_id,$list_id);
        $list = ContactList::getList($list_id);
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

    public function sendUserNotification(Request $request,$event_id,$list_id,$message){

        $request = $request->all();
        $created_by = $request['user_id'];
        $created_user = User::where('id',$created_by)->first();
        //get list against user id.
        //$user_list = ContactList::getList($created_by,$list_id);
        $user_list = ContactList::getList($list_id);
        $eventRequest = new RequestsEvent();
        if(!empty($user_list->first())) {
            foreach ($user_list as $list) {
                foreach (json_decode($list->contact_list) as $user_detail) {
                    $phone = $user_detail->phone;
                    $user = User::where('phone', $phone)->first();
                    //create event request
                    if(!empty($user)){
                        $request = RequestsEvent::CreateRequestEvent($created_by, $user, $event_id);
                        $device_token = $user->device_token;
                        $user_id = $user->id;
                        if (!empty($device_token)) {
                            //check user platform
                            $platform = $user->platform;
                            $event_request = $eventRequest->getUserEventRequests($event_id, $user_id);
                            //don't send notification to rejected user
                            if ($event_request->confirmed != 0) {
                                if ($platform == 'ios' || is_null($platform)) {
                                    //send notification to ios user list
                                    //Log::info("Request Cycle with Queues Begins");
                                    $job = new SendPushNotification($device_token, $created_user, $event_id, $user, $message);
                                    dispatch($job);
                                    //Log::info('Request Cycle with Queues Ends');
                                }
                                else {
                                $this->sendNotificationToAndoidUsers($device_token);

                            }
                        }
                      }
                    }
                }
            }
        }
    }

    public function getUserEvents(Request $request){
      $validator = Validator::make($request->all(), [
            'user_id' => 'required'
        ]);
        $response = Event::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

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
        $validator = Validator::make($request->all(), [
            'request_to' => 'required'
        ]);
        $response = Event::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

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
        $validator = Validator::make($request->all(), [
            'event_id' => 'required',
            'request_to' => 'required'
        ]);
        $response = Event::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

        $event_id = $request['event_id'];
        $id = $request['request_to'];
        $accepted = RequestsEvent::acceptRequest($event_id,$id);
        if($accepted){
            $created_by = RequestsEvent::createdByRequest($event_id,$id);
            $accepted_user = User::where('id',$id)->first();

            $this->sendRequestNotification($created_by->created_by,$event_id,$accepted_user,$request_status = "accepted");
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
       $validator = Validator::make($request->all(), [
            'event_id' => 'required',
            'request_to' => 'required'
        ]);
        $response = Event::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

        $event_id = $request['event_id'];
        $id = $request['request_to'];
        $rejected = RequestsEvent::rejectRequest($event_id,$id);
        if($rejected){
            $created_by = RequestsEvent::createdByRequest($event_id,$id);
            $rejected_user = User::where('id',$id)->first();
            $this->sendRequestNotification($created_by->created_by,$event_id,$rejected_user,$request_status = "rejected");
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

    public function sendRequestNotification($id,$event_id,$accepted_user,$request_status=null){

        $notification_user = User::where('id',$id)->first();

        if($accepted_user){

            if(!empty($accepted_user->firstName)){
                $user_name = $accepted_user->firstName;
            }else{
                $user_name = $accepted_user->phone;
            }

            if(!empty($notification_user->device_token)){
                $platform = $notification_user->platform;
                if($platform == 'ios' || is_null($platform)) {
                    $message = PushNotification::Message($user_name . " $request_status your request ", array(
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
                            'event_id' => $event_id,
                            'status' => 'confirmed'
                        ))
                    ));
                    PushNotification::app('invitedIOS')->to($notification_user->device_token)->send($message);
                }else{
                    $this->sendNotificationToAndoidUsers($accepted_user->device_token);
                }
            }
        }
    }

    public function receivedRequest(Request $request){
        $validator = Validator::make($request->all(), [
            'created_by' => 'required',
        ]);
        $response = Event::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

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
        $validator = Validator::make($request->all(), [
            'event_id' => 'required',
            'title' => 'required',
            'event_address' => 'required',
            'event_time' => 'required',
            'payment_method' => 'required',
            'list_id' => 'required',
            'longitude' => 'required',
            'latitude' => 'required',
            'user_id' => 'required',
        ]);
        $response = Event::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

        $event =Event::updateEvent($request);
        $event_id = $request['event_id'];
        $list_id = $request['list_id'];

        if($event){
            $user_id = $request['user_id'];
            $message = "updated the event";
            $user_platform = User::where('id',$user_id)->first();
            if($user_platform->platform=='ios' || is_null($user_platform->platform)){
                //create event request and send notifications to user list.
                $this->sendUserNotification($request,$event_id,$list_id,$message);
            }
            else{
                //send firebase notification on android.
            }
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
        $eventRequest = new RequestsEvent();
        $event_detail = Event::getEventByID($request['event_id']);
        $event_list_id = $event_detail->list_id;
        $notification_usres_list = ContactList::getUserList($event_list_id);
        foreach (json_decode($notification_usres_list->contact_list) as $list){
            $notification_user = User::where('phone',$list->phone)->first();
            if($notification_user){
                $user_device_token = $notification_user->device_token;
                $user_id = $notification_user->id;
                $platform = $notification_user->platform;
                $event_request = $eventRequest->getUserEventRequests($request['event_id'],$user_id);
                //don't send notification to request rejected user.
                if ($event_request->confirmed != 0) {
                    if ($platform == 'ios' || is_null($platform)) {
                        $message = PushNotification::Message($event_detail->title . "  has been cancelled. ", array(
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
                                'event_id' => $event_detail->id,
                                'status' => 'cancelled'
                            ))
                        ));
                        PushNotification::app('invitedIOS')->to($user_device_token)->send($message);
                    } else {
                        $this->sendNotificationToAndoidUsers($user_device_token);
                    }
                }
            }
        }
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
                    'status' => 'error',
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
    
    public function sendNotificationToAndoidUsers($device_token){
dd($device_token);
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);

        $notificationBuilder = new PayloadNotificationBuilder('my title');
        $notificationBuilder->setBody('Hello world')
            ->setSound('default');

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData(['a_data' => 'my_data']);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        $token = "f-9wrGC6i6g:APA91bG6ZtVrbL_BhVTXOT3WiGATM4rI9SuHYn32jheelqumbGmTGOcYqzB8He9CHjk6uj5N3NE3TOqMtoRgSDQh2TtmmnKai1NBHoPpx3EBYsFKpcht5m_6VWwq5vX4M2YDOpJWWXhQ";

        $downstreamResponse = FCM::sendTo($device_token, $option, $notification, $data);
        
    }
}
