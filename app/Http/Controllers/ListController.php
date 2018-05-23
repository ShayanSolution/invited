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

}
