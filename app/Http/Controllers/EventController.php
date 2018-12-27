<?php

namespace App\Http\Controllers;


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
        //check platform
        $user_id = $request['user_id'];
        $user_platform = User::where('id',$user_id)->first();
        $message = "would like to invite you on";
        Log::info("Before Send User Notification");
        $this->sendUserNotification($request,$event_id,$list_id,$message);
        if($event_id){
            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                    'message' => 'Event Created Successfully',
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
            foreach ($user_list as $list) {
                foreach (json_decode($list->contact_list) as $user_detail) {
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
                            //check user platform
                            $platform = $user->platform;
                            $event_request = $eventRequest->getUserEventRequests($event_id, $user_id);
                            //don't send notification to rejected user
                            if ($event_request->confirmed != 0) {
                                if ($platform == 'ios' || is_null($platform)) {
                                    //send notification to ios user list
                                    Log::info("Request Cycle with Queues Begins");
                                    $message = $created_user->firstName.': '.$event->title.'('.$created_user->phone.')';
                                    $job = new SendPushNotification($device_token, $environment, $created_user, $event_id, $user, $message);
                                    dispatch($job);
                                    Log::info('Request Cycle with Queues Ends');
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
                                    $message_title = $created_user->firstName.': '.$event->title.'('.$created_user->phone.')';
                                    //send data message payload
                                    $this->sendNotificationToAndoidUsers($device_token,$request_status,$message_title);

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
        //accepted event requests
        $accepted_requests = RequestsEvent::acceptedEventRequest($event_id);
        $accepted_requests_count = count($accepted_requests);
        $event_detail = Event::getEventByID($event_id);
        Log::info("================= Accept Request API Before Acceptance =========================");
        Log::info("Event maxi invited ".$event_detail->max_invited);
        Log::info("Request Confirmed ".$accepted_requests_count);
        if($event_detail->max_invited == $accepted_requests_count){
            return JsonResponse::generateResponse(
                [
                    'status' => 'closed',
                    'message' => 'Event has been closed'
                ], 200
            );
        }

        $id = $request['request_to'];
        $accepted = RequestsEvent::acceptRequest($event_id,$id);
        if($accepted['update']){
            $created_by = RequestsEvent::createdByRequest($event_id,$id);
            $accepted_user = User::where('id',$id)->first();
            $this->sendRequestNotification($created_by->created_by,$event_id,$accepted_user,$request_status = "confirmed");
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
                        //send notification to ios user list
                        Log::info("device_token: ".$device_token);
                        Log::info("Request Cycle with Queues Begins");
                        $job = new SendCloseEventNotification($device_token, $event_detail->title,$platform);
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
        $rejected = RequestsEvent::rejectRequest($event_id,$id);
        if($rejected){
            $created_by = RequestsEvent::createdByRequest($event_id,$id);
            $rejected_user = User::where('id',$id)->first();
            $this->sendRequestNotification($created_by->created_by,$event_id,$rejected_user,$request_status = "rejected");
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

    public function sendRequestNotification($id,$event_id,$accepted_user,$request_status=null){
        Log::info("================= Send Notification to event createer =========================");

        $notification_user = User::where('id',$id)->first();

        if($accepted_user){

            if(!empty($accepted_user->firstName)){
                $user_name = $accepted_user->firstName;
            }else{
                $user_name = $accepted_user->phone;
            }

            if(!empty($notification_user->device_token)){
                Log::info("Device token: ".$notification_user->device_token);
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
                            'status' => $request_status
                        ))
                    ));
                    PushNotification::app('invitedIOS')->to($notification_user->device_token)->send($message);
                }
                else{

                    $this->sendNotificationToAndoidUsers($notification_user->device_token,$request_status,$user_name,$event_id);
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
            'event_address' => 'required',
            'event_time' => 'required',
            'payment_method' => 'required',
            'list_id' => 'required',
            'longitude' => 'required',
            'latitude' => 'required',
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
        $event_detail = Event::getEventByID($request['event_id']);
        if($event_detail){
            $event_list_id = $event_detail->list_id;
            $notification_usres_list = ContactList::getUserList($event_list_id);
            foreach (json_decode($notification_usres_list->contact_list) as $list){
                $list->phone = str_replace('(', '', trim($list->phone));
                $list->phone = str_replace(')', '', trim($list->phone));
                $list->phone = str_replace('-', '', trim($list->phone));
                $phone = substr($list->phone, -9);//get last 9 digit of phone number.

                $notification_user = User::where('phone', 'like', '%'.$phone)->first();
                if($notification_user){
                    $user_device_token = $notification_user->device_token;
                    $user_id = $notification_user->id;
                    $platform = $notification_user->platform;
                    $event_request = $eventRequest->getUserEventRequests($request['event_id'],$user_id);
                    //don't send notification to request rejected user.
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
                                    'status' => 'cancelled'
                                ))
                            ));
                            PushNotification::app('invitedIOS')->to($user_device_token)->send($message);
                        } else {
                            $this->sendNotificationToAndoidUsers($user_device_token,$request_status = "deleted",$event_detail->title . "  has been cancelled. ");
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

    public function getDownload()
    {
        //PDF file is stored under project/public/download/info.pdf
        $file= base_path(). "/invited api calls.postman_collection.json";
        return response()->download($file);
    }

    public function sendNotificationToAndoidUsers($device_token,$request_status,$user_name,$event_id){
        Log::info("Request status received => ".$request_status);
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);
        $dataBuilder = new PayloadDataBuilder();
        $event = Event::where('id',$event_id)->first();
        if($request_status == 'confirmed'){
            //$notificationBuilder = new PayloadNotificationBuilder('Accepted');
            //$notificationBuilder->setBody($user_name.' accepted your request')->setSound('default');
//            $dataBuilder->addData(['code' => '3','Title' => 'Accepted','Body' => $user_name.' confirmed your request.']);
            $dataBuilder->addData(['code' => '3','Title' => 'Accepted','Body' => 'Congratulations!'. $user_name.' replied with YES to:'.$event->title.'.']);
            Log::info("Event Accepted:");
        }
        elseif($request_status == 'rejected'){
            //$notificationBuilder = new PayloadNotificationBuilder('Canceled');
            //$notificationBuilder->setBody($user_name.' canceled your request')->setSound('default');
            $dataBuilder->addData(['code' => '4','Title' => 'Canceled', 'Body' => $user_name.' replied with NO to:'.$event->title.'.']);
            Log::info("Event Rjected:");
        }
        elseif($request_status == 'deleted'){
            //$notificationBuilder = new PayloadNotificationBuilder('Deleted');
            //$notificationBuilder->setBody($user_name)->setSound('default');
            $dataBuilder->addData(['code' => '5','Title' => 'Deleted','Body' =>$user_name]);
            Log::info("Event Deleted:");
        }
        elseif($request_status == 'Updated'){
            //$notificationBuilder = new PayloadNotificationBuilder('Updated');
            //$notificationBuilder->setBody($user_name)->setSound('default');
            $dataBuilder->addData(['code' => '2','Title'=>'Updated','Body'=>$user_name]);
            Log::info("Event Updated:");
        }
        else{
            //$notificationBuilder = new PayloadNotificationBuilder('Event Created');
            //$notificationBuilder->setBody(' Event Created Successfully ')->setSound('default');
            $dataBuilder->addData(['code' => '1','Title'=>'Event Created','Body'=>$user_name]);
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
        $contactList = $data['contact_list']['contact_list'];
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
        $requests = RequestsEvent::acceptedRequestUsers($event_id, $created_by);
        $contact_list = [];
        foreach($requests as $request){
            //$contact_list[] = ['name'=>$request->firstName?$request->firstName:$request->lastName, 'phone'=>$request->phone];
            $contact_list[] = ['phone'=>$request->phone];
        }
        $acceptedPeopelCount = count($contact_list);
        //dd($contact_list);
        $filteredContacts = [];
        foreach($contact_list as $item){
            $isFound = false;

            foreach ($contactListPersonName as $contact){
                if(substr($contact['phone'], -9) == substr($item['phone'], -9)){
                    $filteredContacts[] = ['name'=>$contact['name'],'phone'=>$item['phone']];
                    $isFound = true;
                }
            }
            if(!$isFound){
                $filteredContacts[]['phone'] = $item['phone'];
            }

        }

        // dd($contactListPersonName, $contact_list,$filteredContacts);

        $view = view('sendReport.template', compact('data', 'listCount', 'acceptedPeopelCount', 'contact_list', 'filteredContacts', 'contactListPersonName'));

        //Create PDF
        $pdfName = storage_path("/pdf/".time().'_EventReport.pdf');
        /*$pdf = PDF::loadHTML($view)->setPaper('a4', 'potrait')->setWarnings(false)->save('EventReport.pdf');*/
        $pdf = PDF::loadHTML($view)->setPaper('a4', 'potrait')->setWarnings(false)->save($pdfName);
        //dd($pdf);

        $sendMail = Mail::send('emails.welcome', $data, function ($message) use($pdf, $emailAddress, $eventName, $pdfName){
            $message->from('notification@shayansolutions.com', 'Invited');
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


}
