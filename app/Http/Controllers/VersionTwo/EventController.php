<?php

namespace App\Http\Controllers\VersionTwo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Auth;
use Log;

//Models
use App\Models\Event;
use App\Models\RequestsEvent;
use App\ContactList;

class EventController extends Controller
{

    public function getUserEvents(){

        $events = Event::where('user_id', Auth::user()->id)->getEventDetails()->get();

        if($events){
            return response()->json(
                [
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


    public function getEventRequests(){

        $eventsIds = RequestsEvent::where('request_to', Auth::user()->id)->getEventIds();

        $events = Event::whereIn('id', $eventsIds)->getEventDetails()->get();

        if($events){
            return response()->json(
                [
                    'event_requests' => $events,
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
