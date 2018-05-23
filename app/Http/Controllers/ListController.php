<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ContactList;

class ListController extends Controller
{
    public function CreateUserContactList(Request $request){
        $this->validate($request,[
            'user_id' => 'required',
            'contact_list' => 'required',
        ]);
        $list = ContactList::CreateList($request);
        if($list){
            return [
                'status' => 'success',
                'messages' => 'List Created Successfully',
            ];
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to create list'
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
                   'message' => 'Unable find list'
               ], 422
           );
       }

    }
}
