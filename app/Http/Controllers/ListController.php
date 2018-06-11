<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ContactList;
use Illuminate\Support\Facades\Validator;

class ListController extends Controller
{
    public function CreateUserContactList(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'contact_list' => 'required',
            'list_name' => 'required'
        ]);
        $response = ContactList::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }

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

    public function UpdateUserContactList(Request $request){
        $this->validate($request,[
            'list_id' => 'required',
            'contact_list' => 'required',
            'list_name' => 'required',
        ]);
        $list = ContactList::UpdateList($request);
        if($list){
            return [
                'status' => 'success',
                'messages' => 'List Updated Successfully',
            ];
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to update list'
                ], 422
            );
        }
    }

    public function getUserContactList(Request $request){
       $request = $request->all();
       $user_id = $request['user_id'];
       //$list_id = $request['list_id'];
       $user_list = ContactList::getUserContactLists($user_id);
       
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

    public function DeleteUserContactList(Request $request){
        $this->validate($request,[
            'list_id' => 'required',
        ]);

        $list = ContactList::deleteList($request);
        if($list){
            return response()->json(
                [
                    'message' => 'List Deleted Successfully',
                ], 200
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to Delete list',
                ], 400
            );
        }
    }
}
