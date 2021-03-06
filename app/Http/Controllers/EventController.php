<?php

namespace App\Http\Controllers;


use App\Contact;
use App\Models\NonUser;
use App\Models\Notification;
use App\Models\NotificationStatus;
use Illuminate\Http\Request;
use App\Models\Event;
use App\ContactList;
use App\Models\User;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use App\Models\RequestsEvent;
use Illuminate\Support\Facades\Mail;
use Log;
use App\Jobs\SendPushNotification;
use App\Jobs\SendCloseEventNotification;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;
use LaravelFCM\Facades\FCM;
use Auth;
use App\Helpers\JsonResponse;
use Barryvdh\DomPDF\Facade as PDF;


//use Barryvdh\DomPDF\PDF;



class EventController extends Controller
{
    public function CreateEvent(Request $request){

        Log::info("================= Create Event API =========================");
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'payment_method' => 'required',
//            'event_address' => 'required',
            'title' => 'required',
//            'event_time' => 'required',
            'list_id' => 'required',
            'max_invited' => 'required',
        ]);
        $response = Event::generateErrorResponse($validator);
        if($response['code'] == 500){
            Log::info("Event error generated".$response['code']);
            return $response;
        }
        //list id
        $list_id = $request['list_id'];
        //$user_list = ContactList::getList($list_id);
        $user_list = ContactList::getList($list_id);
        if(empty($user_list->first())){
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to create event'
                ], 500
            );
        }
        //create event
        $event_id = Event::CreateEvent($request);
        //non users entry in table
        //@todo refactor contact list to fetch from contacts table
//        $listContactUser = $user_list['0']['contact_list'];
//        $listDecode = json_decode($listContactUser);
//        $contactListPersonName = [];
//        foreach ($listDecode as $value) {
//                $contactListPersonName[] = [$value->phone];
//        }
        $contactOfList = Contact::select('phone')->where('contact_list_id',$list_id)->get();
        $contactListPersonName = $contactOfList->toArray();
        $onlyNonUsers = [];
        foreach ($contactListPersonName as $key => $value){
            foreach ($value as $phone){
                $phoneMatch = substr($phone, -9);
                $filterNonUsers = User::where('phone',  'like', '%'.$phoneMatch)->first();
                if($filterNonUsers == null){
                    $onlyNonUsers [] = $phone;
                    $nonUser = new NonUser();
                    $nonUser->event_id = $event_id;
                    $nonUser->phone = $phone;
                    $nonUser->save();
                }
            }
        }
        //Create comma string
        $allNonUsers = implode(',',$onlyNonUsers);
        //check platform
        $user_id = $request['user_id'];
        $user_platform = User::where('id',$user_id)->first();
        $message = "Created";
        Log::info("Before Send User Notification");
        $this->sendUserNotification($request,$event_id,$list_id,$message);
        if($event_id){
            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                    'message' => 'Event Created Successfully',
                    'non_users' => $allNonUsers,
                ],200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to create event'
                ], 500
            );
        }
    }

    //this function not in use
    public function getUserContactList(Request $request){
        $request = $request->all();
        $user_id = $request['user_id'];
        $list_id = $request['list_id'];
        //$list = ContactList::getList($user_id,$list_id);
        $list = ContactList::getList($list_id);
        if($list){
            $users = json_decode($list->contact_list);
            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                    'user_contact_list' => $users,
                ], 200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable find contact list'
                ], 500
            );
        }

    }

    public function sendUserNotification(Request $request,$event_id,$list_id,$message){

        $request = $request->all();
        $created_by = $request['user_id'];
        $created_user = User::where('id',$created_by)->first();
        $event = Event::where('id',$event_id)->first();
        //get list against user id.
        //$user_list = ContactList::getList($created_by,$list_id);
        Log::info("Getting List ID => ".$list_id);
        $user_list = ContactList::getList($list_id);
        $eventRequest = new RequestsEvent();
        if(!empty($user_list->first())) {
            //Save notification
            if(!empty($created_user->firstName)){
                $senderName = $created_user->firstName. " ". $created_user->lastName;
            }else{
                $senderName = $created_user->phone;
            }
            if(!empty($created_user->profileImage)){
                $senderImage = $created_user->profileImage;
            } else{
                $senderImage = '';
            }
            $saveNotificationId = Notification::saveNotification($event->title,$event_id,$list_id,$senderName,$senderImage,$created_by);
            foreach ($user_list as $list) {
                foreach (json_decode($list->contact) as $user_detail) {
                    $user_detail->phone = str_replace('(', '', trim($user_detail->phone));
                    $user_detail->phone = str_replace(')', '', trim($user_detail->phone));
                    $user_detail->phone = str_replace('-', '', trim($user_detail->phone));
                    $phone = substr($user_detail->phone, -9);//get last 9 digit of phone number.

                    $user = User::where('phone', 'like', '%'.$phone)->first();


                    //create event request
                    if(!empty($user)){
                        Log::info("sending notification to  => ".$user->device_token);
                        $request = RequestsEvent::CreateRequestEvent($created_by, $user, $event_id);
                        $device_token = $user->device_token;
                        $user_id = $user->id;
                        $environment = $user->environment;
                        //dd($user->toArray(), $device_token, $environment);
                        if (!empty($device_token)) {
                            // Save create notifications
                            $saveNotification = NotificationStatus::saveNotificationStatus($saveNotificationId,$user_id,$message);
                            //check user platform
                            $platform = $user->platform;
                            $event_request = $eventRequest->getUserEventRequests($event_id, $user_id);
                            //don't send notification to rejected user
                            if ($event_request->confirmed != 0) {
                                if ($platform == 'ios' || is_null($platform)) {
                                    //send notification to ios user list
                                    Log::info("Request Cycle with Queues Begins and noti Id id: ". $saveNotification);
//                                    $message = $created_user->firstName.': '.$event->title.'('.$created_user->phone.')';
                                    $job = new SendPushNotification($device_token, $environment, $created_user, $event_id, $user, $message, $saveNotification);
                                    dispatch($job);
                                    Log::info('Request Cycle with Queues Ends Checked by sarmad');
                                }
                                else {
                                    Log::info("Before Sending Push notification to {$user->email} device token =>".$device_token);
                                    if(!empty($created_user->firstName)){
                                        $user_name = $created_user->firstName;
                                    }else{
                                        $user_name = $created_user->phone;
                                    }
                                    if($message == 'updated the event'){
                                        $request_status = 'Updated';
                                    }else{
                                        $request_status = 'created';
                                    }
//                                    $message_title = $user_name.' '.$message.' '. $event->title.'.';
                                    $message_title = $created_user->firstName.' '.$created_user->lastName.': '.$event->title.' ('.$created_user->phone.')';
                                    //send data message payload
                                    $this->sendNotificationToAndoidUsers($device_token,$request_status,$message_title,$event_id,$saveNotification);

                                }
                            }
                        }
                    }
                    else
                    {
                        Log::info("No push notification sending to phone number $phone as phone number not found in database");
                    }
                }
            }
        }
        else
        {
            Log::info("I am getting empty user list");
        }
    }

    public function sendCreateEventDataMessage($device_token){
        Log::info('Send data message in background');
        $optionBuiler = new OptionsBuilder();
        $optionBuiler->setTimeToLive(60*20);
        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData(['a_data' => 'my_test_data']);
        $data = $dataBuilder->build();
        $option = $optionBuiler->build();
        FCM::sendTo($device_token, $option, null, $data);
        Log::info('Now sending data message and notification in forground');
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

            return JsonResponse::generateResponse(
                [
                    'total_events'=>$total_events,
                    'totl_invited'=>count($user_list),
                    'user_events' => $events
                ], 200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to find event list.'
                ], 500
            );
        }
    }

    public function getEventRequests(Request $request){

        Log::info("================= Get Event Request API =========================");
        Log::info("Request received =>".print_r($request->all(),true));
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

            Log::info("Response received =>".print_r($total_count['event_request'],true));
            return JsonResponse::generateResponse(
                [
                    'event_requests' => $total_count['event_request'],
                ], 200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable find total count'
                ], 500
            );
        }
    }

    public function acceptRequest(Request $request){
        $validator = Validator::make($request->all(), [
            'event_id' => 'required',
            'request_to' => 'required'
        ]);

        $isEventPresent = Event::find($request->event_id);
        if(!$isEventPresent){
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Event has been deleted'
                ], 200
            );
        }

        $response = Event::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

        $event_id = $request['event_id'];
        $id = $request['request_to'];

        // Event Expire
        $currentDate = date("Y-m-d");
        $currentDateMidNight = strtotime("today midnight");
        $eventDate = $isEventPresent->event_date;
        $eventExpireDate = strtotime($eventDate. ' + 1 days');

        $currentTime = strtotime("now");
        $eventTime = $isEventPresent->event_only_time;
        $eventExpireTime = strtotime($eventTime);

        // Expire on date and time
        if(!$eventDate == null && !$eventTime == null){
            if($currentDate == $eventDate){
               if($currentTime >= $eventExpireTime){
                   RequestsEvent::eventExpire($event_id, $id);
                   return JsonResponse::generateResponse(
                       [
                           'status' => 'error',
                           'message' => 'Event has been expired'
                       ], 200
                   );
               }
            }
        }
        // Expire only date
        if($currentDateMidNight >= $eventExpireDate){
            RequestsEvent::eventExpire($event_id, $id);
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Event has been expired'
                ], 200
            );
        }

        //accepted event requests
        $accepted_requests = RequestsEvent::acceptedEventRequest($event_id);
        $accepted_requests_count = count($accepted_requests);
        $event_detail = Event::getEventByID($event_id);
        Log::info("================= Accept Request API Before Acceptance =========================");
        Log::info("Event maxi invited ".$event_detail->max_invited);
        Log::info("Request Confirmed ".$accepted_requests_count);

        if($event_detail->max_invited == $accepted_requests_count){
            RequestsEvent::acceptRequestLimitEqual($event_id, $id);
            return JsonResponse::generateResponse(
                [
                    'status' => 'closed',
                    'message' => 'Event has been closed'
                ], 200
            );
        }
        if($event_detail->canceled_at != null){
            return JsonResponse::generateResponse(
                [
                    'status' => 'cancelled',
                    'message' => 'Event has been cancelled'
                ], 200
            );
        }
        $accepted = RequestsEvent::acceptRequest($event_id,$id);
        if($accepted['update']){
            $created_by = RequestsEvent::createdByRequest($event_id,$id);
            $accepted_user = User::where('id',$id)->first();

            //Save Notification
            if(!empty($accepted_user->firstName)){
                $user_name = $accepted_user->firstName." ".$accepted_user->lastName;
            }else{
                $user_name = $accepted_user->phone;
            }
            if(!empty($accepted_user->profileImage)){
                $senderImage = $accepted_user->profileImage;
            } else{
                $senderImage = '';
            }
            $message = 'Congratulations! ' . $user_name . ' replied with YES to: ' . $event_detail->title . '.';
            $saveNotificationId = Notification::saveNotification($message,$event_id,$event_detail->list_id,$user_name,$senderImage, $id);
            $saveNotification = NotificationStatus::saveNotificationStatus($saveNotificationId,$event_detail->user_id,"Accept Event");
            //

            $this->sendRequestNotification($created_by->created_by,$event_id,$accepted_user,$request_status = "YES", $saveNotification);
            Log::info("Notification users ids for closed events: ".print_r($accepted['notification_users'],true));
            //if event has been closed, send notification remaining users for closed events
            if(!empty($accepted['notification_users'])){
                $users = $accepted['notification_users'];
                for($j=0;$j<count($users);$j++){
                    $user_id = $users[$j];
                    //select user
                    $user = User::where('id',$user_id)->first();
                    if($user){
                        $device_token = $user->device_token;
                        $platform = $user->platform;
                        $environment = $user->environment;
                        //send notification to ios user list
                        Log::info("device_token: ".$device_token. "-----". $environment);
                        Log::info("Request Cycle with Queues Begins");
                        //Save Notification
                        $senderUser = User::where('id',$event_detail->user_id)->first();
                        if(!empty($senderUser->firstName)){
                            $user_name = $senderUser->firstName." ".$senderUser->lastName;
                        }else{
                            $user_name = $senderUser->phone;
                        }
                        if(!empty($senderUser->profileImage)){
                            $senderImage = $senderUser->profileImage;
                        } else{
                            $senderImage = '';
                        }
                        $message = "Too late. ".$event_detail->title . "  has been closed ";
                        $saveNotificationId = Notification::saveNotification($message,$event_id,$event_detail->list_id,$user_name,$senderImage, $event_detail->user_id);
                        $saveNotification = NotificationStatus::saveNotificationStatus($saveNotificationId,$user_id,"Closed Event");
                        //
                        $job = new SendCloseEventNotification($device_token, $event_detail->title,$platform,$environment,$saveNotification);
                        dispatch($job);
                        Log::info('Request Cycle with Queues Ends now');
                    }
                }
            }
            return JsonResponse::generateResponse(
                [
                    'status' => 'Request accepted successfully'
                ], 200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to accept'
                ], 500
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

        $checkEvent = Event::find($request->event_id);
        if($checkEvent->canceled_at != null){
            return JsonResponse::generateResponse(
                [
                    'status' => 'cancelled',
                    'message' => 'Event has been cancelled'
                ], 200
            );
        }
        // Event Expire
        $currentDate = date("Y-m-d");
        $currentDateMidNight = strtotime("today midnight");
        $eventDate = $checkEvent->event_date;
        $eventExpireDate = strtotime($eventDate. ' + 1 days');

        $currentTime = strtotime("now");
        $eventTime = $checkEvent->event_only_time;
        $eventExpireTime = strtotime($eventTime);

        // Expire on date and time
        if(!$eventDate == null && !$eventTime == null) {
            if ($currentDate == $eventDate) {
                if ($currentTime >= $eventExpireTime) {
                    RequestsEvent::eventExpire($event_id, $id);
                    return JsonResponse::generateResponse(
                        [
                            'status' => 'error',
                            'message' => 'Event has been expired'
                        ], 200
                    );
                }
            }
        }

        // Expire only date
        if($currentDateMidNight >= $eventExpireDate){
        RequestsEvent::eventExpire($event_id, $id);
        	return JsonResponse::generateResponse(
        		[
        			'status' => 'error',
        			'message' => 'Event has been expired'
        		], 200
        	);
        }

        $rejected = RequestsEvent::rejectRequest($event_id,$id);
        if($rejected){
            $created_by = RequestsEvent::createdByRequest($event_id,$id);
            $rejected_user = User::where('id',$id)->first();

            //Save Notification
            if(!empty($rejected_user->firstName)){
                $user_name = $rejected_user->firstName." ".$rejected_user->lastName;
            }else{
                $user_name = $rejected_user->phone;
            }
            if(!empty($rejected_user->profileImage)){
                $senderImage = $rejected_user->profileImage;
            } else{
                $senderImage = '';
            }
            $message = $user_name . ' replied with NO to: ' . $checkEvent->title . '.';
            $saveNotificationId = Notification::saveNotification($message,$event_id,$checkEvent->list_id,$user_name,$senderImage,$id);
            $saveNotification = NotificationStatus::saveNotificationStatus($saveNotificationId,$checkEvent->user_id,"Reject Event");
            //

            $this->sendRequestNotification($created_by->created_by,$event_id,$rejected_user,$request_status = "NO", $saveNotification);
            return JsonResponse::generateResponse(
                [
                    'status' => 'Request rejected successfully',
                ], 200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to reject request'
                ], 422
            );
        }
    }

    public function sendRequestNotification($id,$event_id,$accepted_user,$request_status=null, $saveNotification){
        Log::info("================= Send Notification to event createer =========================");
        $event = Event::where('id',$event_id)->first();
        $notification_user = User::where('id',$id)->first();

        if($accepted_user){

            if(!empty($accepted_user->firstName)){
                $user_name = $accepted_user->firstName." ".$accepted_user->lastName;
            }else{
                $user_name = $accepted_user->phone;
            }

            if(!empty($notification_user->device_token)){
                Log::info("Device token: ".$notification_user->device_token);
                Log::info("Device token: ".$notification_user->environment);
                $platform = $notification_user->platform;
//                $environment = $notification_user->environment;
                if($platform == 'ios' || is_null($platform)) {
                    if ($request_status == "YES") {
                        $message = PushNotification::Message('Congratulations! ' . $user_name . ' replied with ' . $request_status . ' to: ' . $event->title . '.', array(
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
                                'notification_id' => $saveNotification,
                                'status' => $request_status
                            ))
                        ));
                    } else {
                        $message = PushNotification::Message($user_name.' replied with '.$request_status.' to: '.$event->title.'.', array(
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
                                'notification_id' => $saveNotification,
                                'status' => $request_status
                            ))
                        ));
                    }
//                    PushNotification::app('invitedIOS')->to($notification_user->device_token)->send($message);
                    if($notification_user->environment == 'development') {
                        Log::info(" Environment is Development-----".$notification_user->device_token."---- Before Send and Environment:-----".$notification_user->environment);
                        $response = PushNotification::app('invitedIOSDev')->to($notification_user->device_token)->send($message);
                        Log::info(" Environment is Development-----".$notification_user->device_token."------After Send");
                    } else{
                        Log::info(" Environment is Production-----".$notification_user->device_token."---- Before Send and Environment:-----".$notification_user->environment);
                        $response = PushNotification::app('invitedIOS')->to($notification_user->device_token)->send($message);
                        Log::info(" Environment is Production-----".$notification_user->device_token."------After Send");
                    }
                }
                else{

                    $this->sendNotificationToAndoidUsers($notification_user->device_token,$request_status,$user_name,$event_id,$saveNotification);
                }
            }
        }
    }

    public function receivedRequest(Request $request){
        Log::info("================= Received Reques API =========================");
//        $validator = Validator::make($request->all(), [
//            'created_by' => 'required',
//        ]);
//        $response = Event::generateErrorResponse($validator);
//        if($response['code'] == 500){
//            Log::info("Received Requests Error =>".print_r($response,true));
//            return $response;
//        }

        $id = Auth::user()->id;
//        $requests = RequestsEvent::receivedRequest($id);

        $acceptedByMe = RequestsEvent::eventAcceptedByMe($id);
        $sentByMe = RequestsEvent::eventSentByMe($id);

        $receivedRequest = $acceptedByMe->merge($sentByMe)->sortByDesc('updated_at');
        if(count($receivedRequest) > 0){
//            foreach($requests as $request){
//                $request->contact_list = json_decode($request->contact_list);
//                $request->total_invited = count($request->contact_list);
//            }
            Log::info("Received Requests =>".print_r($receivedRequest,true));

            return JsonResponse::generateResponse(
                [
                    'received_requests'=>$receivedRequest->values()
                ], 200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to find request'
                ], 500
            );
        }

    }


    public function acceptedRequestUsers(Request $request){
        Log::info("================= Received Reques API =========================");
        $validator = Validator::make($request->all(), [
            'event_id' => 'required',
            'created_by' => 'required',
        ]);
        $response = Event::generateErrorResponse($validator);
        if($response['code'] == 500){
            Log::info("Received Requests Error =>".print_r($response,true));
            return $response;
        }

        $created_by = $request['created_by'];
        $event_id = $request['event_id'];
        $requests = RequestsEvent::acceptedRequestUsers($event_id, $created_by);
        $contact_list = [];
        foreach($requests as $request){
            $contact_list[] = $request->phone;
        }

        if($requests){
            Log::info("Received Requests =>".print_r($requests,true));
            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                    'Contact List' => $contact_list,
                    'count' => count($contact_list)
                ], 200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to find request'
                ], 500
            );
        }

    }



    public function updateUserEvent(Request $request){
        Log::info("================= Update Event API =========================");
        Log::info("Request Received =>".print_r($request->all(),true));
        $validator = Validator::make($request->all(), [
            'event_id' => 'required',
            'title' => 'required',
//            'event_address' => 'required',
//            'event_time' => 'required',
            'payment_method' => 'required',
            'list_id' => 'required',
//            'longitude' => 'required',
//            'latitude' => 'required',
            'user_id' => 'required',
            'max_invited' => 'required',
        ]);
        $response = Event::generateErrorResponse($validator);
        if($response['code'] == 500){
            Log::info("Error code ".$response['code']);
            Log::info("Error Message".print_r($response,true));
            return $response;
        }

        Log::info("Request received => ".print_r($request->all(),true));
        $event =Event::updateEvent($request);
        $event_id = $request['event_id'];
        $list_id = $request['list_id'];

        if($event){
            $user_id = $request['user_id'];
            $message = "updated the event";
            $this->sendUserNotification($request,$event_id,$list_id,$message);
            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                    'success' => 'Event Updated Successfully',
                ], 200
            );
        }else{
            Log::info("Unable to update event");
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'error' => 'Unable to update event',
                ], 500
            );
        }

    }

    public function deleteEvent(Request $request){
        $this->validate($request,[
            'event_id' => 'required',
        ]);
        $eventRequest = new RequestsEvent();
        $event_id = $request['event_id'];
        $event_detail = Event::getEventByID($request['event_id']);
        if($event_detail){
            $event_list_id = $event_detail->list_id;
            $notification_usres_list = ContactList::getUserList($event_list_id);
//            dd($notification_usres_list);
            $message = $event_detail->title.' has been deleted.';
            $user = User::where('id',$event_detail->user_id)->first();
            if(!empty($user->firstName)){
                $userName = $user->firstName." ".$user->lastName;
            }else{
                $userName = $user->phone;
            }
            if(!empty($user->profileImage)){
                $senderImage = $user->profileImage;
            } else{
                $senderImage = '';
            }
            $saveNotificationId = Notification::saveNotification($message,$event_id,$event_list_id,$userName,$senderImage,$event_detail->user_id);
            if ($notification_usres_list != null){
                foreach ($notification_usres_list->contact as $list){
                    $list->phone = str_replace('(', '', trim($list->phone));
                    $list->phone = str_replace(')', '', trim($list->phone));
                    $list->phone = str_replace('-', '', trim($list->phone));
                    $phone = substr($list->phone, -9);//get last 9 digit of phone number.

                    $notification_user = User::where('phone', 'like', '%'.$phone)->first();

                    //Save Notification status
                    $saveNotification = NotificationStatus::saveNotificationStatus($saveNotificationId,$notification_user->id,"Deleted Event");

                    if($notification_user){
                        $user_device_token = $notification_user->device_token;
                        $user_id = $notification_user->id;
                        $platform = $notification_user->platform;
                        $event_request = $eventRequest->getUserEventRequests($request['event_id'],$user_id);
                        //don't send notification to request rejected user.
                        if (isset($event_request->confirmed) && $event_request->confirmed != 0) {
                            if ($platform == 'ios' || is_null($platform)) {
                                $message = PushNotification::Message($event_detail->title . "  has been deleted. ", array(
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
                                        'notification_id' => $saveNotification,
                                        'status' => 'cancelled'
                                    ))
                                ));
    //                            PushNotification::app('invitedIOS')->to($user_device_token)->send($message);
                                if($notification_user->environment == 'development') {
                                    Log::info(" Environment is Development(Delete)-----".$notification_user->device_token."---- Before Send and Environment:-----".$notification_user->environment);
                                    $response = PushNotification::app('invitedIOSDev')->to($notification_user->device_token)->send($message);
                                    Log::info(" Environment is Development-----".$notification_user->device_token."------After Send");
                                } else{
                                    Log::info(" Environment is Production(Delete)-----".$notification_user->device_token."---- Before Send and Environment:-----".$notification_user->environment);
                                    $response = PushNotification::app('invitedIOS')->to($notification_user->device_token)->send($message);
                                    Log::info(" Environment is Production-----".$notification_user->device_token."------After Send");
                                }
                            } else {
                                $this->sendNotificationToAndoidUsers($user_device_token,$request_status = "deleted",$event_detail->title . "  has been deleted. ",$event_id,$saveNotification);
                            }
                        }
                    }
                }
            }
            $event =Event::deleteEvent($request);
            if($event){
                return JsonResponse::generateResponse(
                    [
                        'status' => 'success',
                        'message' => 'Event Deleted Successfully',
                    ], 200
                );
            }else{
                return JsonResponse::generateResponse(
                    [
                        'status' => 'error',
                        'message' => 'Unable to Delete event',
                    ], 500
                );
            }
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to Delete event',
                ], 500
            );
        }

    }

    public function cancelEvent(Request $request){
        $this->validate($request,[
            'event_id' => 'required',
        ]);
        $eventRequest = new RequestsEvent();
        $event_id = $request['event_id'];
        $event_detail = Event::getEventByID($request['event_id']);
        if($event_detail){
            $event =Event::cancelEvent($request);
            $event_list_id = $event_detail->list_id;
            $notification_usres_list = ContactList::getUserList($event_list_id);
            $message = $event_detail->title.' has been cancelled.';
            $user = User::where('id',$event_detail->user_id)->first();
            if(!empty($user->firstName)){
                $userName = $user->firstName." ".$user->lastName;
            }else{
                $userName = $user->phone;
            }
            if(!empty($user->profileImage)){
                $senderImage = $user->profileImage;
            } else{
                $senderImage = '';
            }
            $saveNotificationId = Notification::saveNotification($message,$event_id,$event_list_id,$userName,$senderImage,$event_detail->user_id);
            foreach ($notification_usres_list->contact as $list){
                $list->phone = str_replace('(', '', trim($list->phone));
                $list->phone = str_replace(')', '', trim($list->phone));
                $list->phone = str_replace('-', '', trim($list->phone));
                $phone = substr($list->phone, -9);//get last 9 digit of phone number.

                $notification_user = User::where('phone', 'like', '%'.$phone)->first();

                if($notification_user){
                    $user_device_token = $notification_user->device_token;
                    $user_id = $notification_user->id;
                    $platform = $notification_user->platform;
                    $event_request = $eventRequest->getUserEventRequestsAccepted($request['event_id'],$user_id);
                    //Save notification status
                    $saveNotification = NotificationStatus::saveNotificationStatus($saveNotificationId,$user_id,"Cancelled Event");

                    //don't send notification to request rejected user and pending users.
                    if (isset($event_request->confirmed) && $event_request->confirmed != 0) {
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
                                    'notification_id' => $saveNotification,
                                    'status' => 'cancelled'
                                ))
                            ));
//                            PushNotification::app('invitedIOS')->to($user_device_token)->send($message);
                            if($notification_user->environment == 'development') {
                                Log::info(" Environment is Development(Cancel)-----".$notification_user->device_token."---- Before Send and Environment:-----".$notification_user->environment);
                                $response = PushNotification::app('invitedIOSDev')->to($notification_user->device_token)->send($message);
                                Log::info(" Environment is Development-----".$notification_user->device_token."------After Send");
                            } else{
                                Log::info(" Environment is Production(Cancel)-----".$notification_user->device_token."---- Before Send and Environment:-----".$notification_user->environment);
                                $response = PushNotification::app('invitedIOS')->to($notification_user->device_token)->send($message);
                                Log::info(" Environment is Production-----".$notification_user->device_token."------After Send");
                            }
                        } else {
                            $this->sendNotificationToAndoidUsers($user_device_token,$request_status = "cancelled",$event_detail->title . "  has been cancelled. ",$event_id,$saveNotification);
                        }
                    }
                }
            }

            if($event){
                return JsonResponse::generateResponse(
                    [
                        'status' => 'success',
                        'message' => 'Event Cancelled Successfully',
                    ], 200
                );
            }else{
                return JsonResponse::generateResponse(
                    [
                        'status' => 'error',
                        'message' => 'Unable to Cancel event',
                    ], 500
                );
            }
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to Delete event',
                ], 500
            );
        }

    }

    public function getDownload()
    {
        //PDF file is stored under project/public/download/info.pdf
        $file= base_path(). "/invited api calls.postman_collection.json";
        return response()->download($file);
    }

    public function sendNotificationToAndoidUsers($device_token,$request_status,$user_name,$event_id,$saveNotification){
        Log::info("Request status received => ".$request_status);
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);
        $dataBuilder = new PayloadDataBuilder();
        $event = Event::where('id',$event_id)->first();
        $entity = "Message";
        if($request_status == "YES"){
            //$notificationBuilder = new PayloadNotificationBuilder('Accepted');
            //$notificationBuilder->setBody($user_name.' accepted your request')->setSound('default');
//            $dataBuilder->addData(['code' => '3','Title' => 'Accepted','Body' => $user_name.' confirmed your request.']);
            $dataBuilder->addData(['code' => '3','notification_id' => $saveNotification, 'Title' => $entity.' Accepted','Body' => 'Congratulations! '. $user_name.' replied with YES to: '.$event->title.'.']);
            Log::info("Event Accepted:");
        }
        elseif($request_status == "NO"){
            //$notificationBuilder = new PayloadNotificationBuilder('Cancelled');
            //$notificationBuilder->setBody($user_name.' cancelled your request')->setSound('default');
            $dataBuilder->addData(['code' => '4','notification_id' => $saveNotification,'Title' => $entity.' Rejected', 'Body' => $user_name.' replied with NO to: '.$event->title.'.']);
            Log::info("Event Rjected:");
        }
        elseif($request_status == 'deleted'){
            //$notificationBuilder = new PayloadNotificationBuilder('Deleted');
            //$notificationBuilder->setBody($user_name)->setSound('default');
            $dataBuilder->addData(['code' => '5','notification_id' => $saveNotification,'Title' => $entity.' Deleted','Body' =>$user_name]);
            Log::info("Event Deleted:");
        }
        elseif($request_status == 'Updated'){
            //$notificationBuilder = new PayloadNotificationBuilder('Updated');
            //$notificationBuilder->setBody($user_name)->setSound('default');
            $dataBuilder->addData(['code' => '2','notification_id' => $saveNotification,'Title'=>$entity.' Updated','Body'=>$user_name]);
            Log::info("Event Updated:");
        }
        elseif($request_status == 'cancelled'){
            //$notificationBuilder = new PayloadNotificationBuilder('Deleted');
            //$notificationBuilder->setBody($user_name)->setSound('default');
            $dataBuilder->addData(['code' => '6','notification_id' => $saveNotification,'Title' => $entity.' Cancelled','Body' =>$user_name]);
            Log::info("Event Cancelled:");
        }
        else{
            //$notificationBuilder = new PayloadNotificationBuilder('Event Created');
            //$notificationBuilder->setBody(' Event Created Successfully ')->setSound('default');
            $dataBuilder->addData(['code' => '1','notification_id' => $saveNotification,'Title'=>$entity.' Received','Body'=>$user_name]);
            Log::info("Event Created:");
        }

        $option = $optionBuilder->build();
        //$notification = $notificationBuilder->build();
        $data = $dataBuilder->build();
        Log::info("Sending push notification to $device_token");
        //$downstreamResponse = FCM::sendTo($device_token, $option, $notification, $data);
        //send only data message payload
        $downstreamResponse = FCM::sendTo($device_token, $option, null, $data);

    }

    public function SendReport(Request $request){
        $this->validate($request,[
            'event_id' => 'required ',
            'email_address' => 'required | email'
        ]);

        $event_id = $request->event_id;
        $emailAddress = $request->email_address;

        // get Event
        $eventObj = Event::getEventByID($event_id);
        $data = $eventObj->toArray();
        $eventName = $data['title'];

        //get count of contactList
        //@todo refactor contact list to fetch from contacts table
//        $contactList = $data['contact_list']['contact_list'];
//        $list = json_decode($contactList);
        $contactList = Contact::select('name','phone')->where('contact_list_id',$data['list_id'])->get();
        $list = json_decode($contactList);
        $listCount = 0;
        foreach ($list as $value) {
            $listCount ++;
        }
        //dd($list);
        //key value array for match
        $contactListPersonName = [];
        foreach ($list as $value) {
            if(isset($value->name)) {
                $contactListPersonName[] = ['name' => $value->name, 'phone' => $value->phone];
            }else{
                $contactListPersonName[] = ['name' => $value->email, 'phone' => $value->phone];
            }
        }

        //get number of people accepted
        $created_by = $data['user_id'];
        $requests = RequestsEvent::SendRequestAllUsers($event_id, $created_by);
//        dd($requests->toArray());
        $reject_contact_list = [];
        $accept_contact_list = [];
        $pending_contact_list = [];
//        dd($requests->toArray());
        foreach($requests as $request){
            if( $request->confirmed == 0 ) {
                $reject_contact_list[] = ['phone' => $request->phone];
            }elseif($request->confirmed == 1 ) {
                $accept_contact_list[] = ['phone' => $request->phone];
            }
            elseif( $request->confirmed == 2 ) {
                $pending_contact_list[] = ['phone' => $request->phone];
            }
        }
        $acceptedPeopelCount = count($accept_contact_list);
        $rejectPeopelCount = count($reject_contact_list);
        $pendingPeopelCount = count($pending_contact_list);
//        dd("REJECT",$reject_contact_list, "ACCEPT",$accept_contact_list, "pending",$pending_contact_list);
        $rejectFilteredContacts = [];
        foreach($reject_contact_list as $item){
            $isFound = false;

            foreach ($contactListPersonName as $contact){
                if(substr($contact['phone'], -9) == substr($item['phone'], -9)){
                    $rejectFilteredContacts[] = ['name'=>$contact['name'],'phone'=>$item['phone']];
                    $isFound = true;
                }
            }
            if(!$isFound){
                $rejectFilteredContacts[]['phone'] = $item['phone'];
            }

        }

        $acceptFilteredContacts = [];
        foreach($accept_contact_list as $item){
            $isFound = false;

            foreach ($contactListPersonName as $contact){
                if(substr($contact['phone'], -9) == substr($item['phone'], -9)){
                    $acceptFilteredContacts[] = ['name'=>$contact['name'],'phone'=>$item['phone']];
                    $isFound = true;
                }
            }
            if(!$isFound){
                $acceptFilteredContacts[]['phone'] = $item['phone'];
            }

        }

        $pendingFilteredContacts = [];
        foreach($pending_contact_list as $item){
            $isFound = false;

            foreach ($contactListPersonName as $contact){
                if(substr($contact['phone'], -9) == substr($item['phone'], -9)){
                    $pendingFilteredContacts[] = ['name'=>$contact['name'],'phone'=>$item['phone']];
                    $isFound = true;
                }
            }
            if(!$isFound){
                $pendingFilteredContacts[]['phone'] = $item['phone'];
            }

        }

        // dd($contactListPersonName, $contact_list,$filteredContacts);

        $view = view('sendReport.template', compact('data', 'listCount', 'acceptedPeopelCount', 'rejectPeopelCount', 'pendingPeopelCount', 'contact_list', 'rejectFilteredContacts', 'acceptFilteredContacts', 'pendingFilteredContacts', 'contactListPersonName'));

        //Create PDF
        $pdfName = storage_path("/pdf/".time().'_EventReport.pdf');
        /*$pdf = PDF::loadHTML($view)->setPaper('a4', 'potrait')->setWarnings(false)->save('EventReport.pdf');*/
        $pdf = PDF::loadHTML($view)->setPaper('a4', 'potrait')->setWarnings(false)->save($pdfName);
        //dd($pdf);

        $sendMail = Mail::send('emails.welcome', $data, function ($message) use($pdf, $emailAddress, $eventName, $pdfName){
            $message->from(env("DEFAULT_EMAIL_ADDRESS","notification@shayansolutions.com"), 'Notification System');
            $message->to($emailAddress)->subject('Event Report - '. $eventName);
            $message->attach($pdfName, [
                'as' => $eventName.'.pdf',
                'mime' => 'application/pdf',
            ]);
        });

        return JsonResponse::generateResponse(
            [
                'status' => 'success',
                'message' => 'Email has been sent successfully'
            ], 200
        );
        // respone null
        //dd($sendMail);

        //dd("email Send successfully");
    }

    public function getNotifications(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required'
        ]);
        $response = Event::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

        $request = $request->all();
        $user_id = $request['user_id'];
        $notifications = NotificationStatus::with('notification')->where('receiver_id', $user_id)->orderBy('notification_id', 'desc')->get();
        $unReadNotification = $notifications->where('receiver_id', $user_id)->where('read_status', 0);
        $unReadNotification_count = count($unReadNotification);
        foreach ($notifications as $key => $notification){
            if($notification->notification){
                $notifications[$key]->message = $notification->notification->message;
                $notifications[$key]->event_id = $notification->notification->event_id;
                $notifications[$key]->list_id = $notification->notification->list_id;
                $notifications[$key]->sender_id = $notification->notification->sender_id;
                $notifications[$key]->sender_name = $notification->notification->sender_name;
                $notifications[$key]->sender_image = $notification->notification->sender_image;
            }
            unset($notifications[$key]->notification);
        }
        if($notifications){

            return JsonResponse::generateResponse(
                [
                    'unReadNotification_count'=>$unReadNotification_count,
                    'notifications'=>$notifications,
                ], 200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to find notifications.'
                ], 500
            );
        }
    }

    public function readNotification(Request $request){
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required',
        ]);
        $response = Event::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }
        $notification_id = $request['notification_id'];
        $notifications = NotificationStatus::where('id', $notification_id)->update(['read_status' => 1]);

        if($notifications){
            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                    'message' => 'Notification read successfully.'
                ], 200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to find notifications.'
                ], 500
            );
        }
    }


}
