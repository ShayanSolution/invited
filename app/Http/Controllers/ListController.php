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
       $list_id = $request['list_id'];
       $user_list = ContactList::getList($user_id,$list_id);
       
       if(!empty($user_list->first())){
           $user_contact_list =[];
           $index = 0;
           foreach($user_list as $list){
               $user_contact_list[$index]['id'] = $list->id;
               $user_contact_list[$index]['list_name'] = $list->list_name;
               $user_contact_list[$index]['contacts'] = json_decode($list->contact_list);
               $index++;
           }
           $users = json_decode($list->contact_list);
           return response()->json(
               [
                   'user_contact_list' => $user_contact_list,
               ], 200
           );
       }else{
           return response()->json(
               [
                   'status' => 'error',
                   'message' => 'Unable to find list'
               ], 422
           );
       }

    }
}
