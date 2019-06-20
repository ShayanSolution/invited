<?php

namespace App\Http\Controllers;

use App\Contact;
use App\Helpers\General;
use Illuminate\Http\Request;
use App\ContactList;
use Illuminate\Support\Facades\Validator;
use App\Helpers\JsonResponse;

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
        $contacts = ContactList::CreateContacts($request, $list);
        if($list){
            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                    'messages' => 'List Created Successfully',
                ],200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to create list'
                ], 500
            );
        }
    }

    public function UpdateUserContactList(Request $request){
        $this->validate($request,[
            'list_id' => 'required',
            'contact_list' => 'required',
            'list_name' => 'required',
        ]);
//        $list = ContactList::UpdateList($request);
        $list = ContactList::updateListAndContacts($request);
        if($list){
            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                    'messages' => 'List Updated Successfully',
                ],200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to update list'
                ], 500
            );
        }
    }
    public function UpdateUserContactListImage(Request $request){
        $this->validate($request,[
            'list_id' => 'required',
        ]);
        $list = ContactList::UpdateListImage($request);
        if($list){
            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                    'messages' => 'Group Image Updated Successfully',
                ],200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to Update Group Image'
                ], 500
            );
        }
    }

    public function DeleteUserContactListImage(Request $request){
        $validator = Validator::make($request->all(), [
            'list_id' => 'required',
        ]);
        $response = ContactList::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }
        $deleteListImage = ContactList::where('id',$request->list_id)->first();
        if($deleteListImage){
            $deleteImagePath = $deleteListImage->group_image;
            $ImageName = basename($deleteImagePath);
            $deleteImageFullPath = 'storage/images/'.$ImageName;
            unlink($deleteImageFullPath);

            $data['group_image'] = null;

            $deleteListImage->update($data);

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Group image removed successfully'
                ], 200
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find group'
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
               $user_contact_list[$index]['group_image'] = $list->group_image;
               if(isset($list->is_active)){
                   if ($list->is_active == 1){
                        $user_contact_list[$index]['is_active'] = "Active";
                   }
                   if ($list->is_active == 0) {
                       $user_contact_list[$index]['is_active'] = "In Active";
                   }
               } else {
                   $user_contact_list[$index]['is_active'] = "";
               }
               $user_contact_list[$index]['contacts'] = $list->contact;
               $index++;
           }
           $users = json_decode($list->contact_list);
           return JsonResponse::generateResponse(
               [
                   'user_contact_list' => $user_contact_list,
               ], 200
           );
       }else{
           return JsonResponse::generateResponse(
               [
                   'status' => 'error',
                   'message' => 'Unable to find list'
               ], 500
           );
       }

    }

    public function DeleteUserContactList(Request $request){
        $this->validate($request,[
            'list_id' => 'required',
        ]);

        $list = ContactList::deleteList($request);
        if($list){
            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                    'message' => 'List Deleted Successfully',
                ], 200
            );
        }else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'Unable to Delete list',
                ], 500
            );
        }
    }

    public function importCsv(Request $request){
        $this->validate($request,[
            'file' => 'required|mimes:csv,txt',
        ]);
        if($request->hasfile('file')) {
            $file = $request->file('file');
            $directory = storage_path('files');

            $path = $file->move($directory, time() . "-" . $file->getClientOriginalName());
            $csvFile = fopen($path->getPathname(), 'r');
            $fileData = [];
            while (!feof($csvFile)){
                $fileData[] = fgetcsv($csvFile);
            }
            fclose($csvFile);
            $csvData = General::setArrayKeys($fileData,0,true);

            $contactList = ContactList::create([
                'list_name'=> basename($file->getClientOriginalName(),'.csv'),
                'user_id' => $request->input('user_id', 0)
            ]);
            foreach ($csvData as $data){
                $data['contact_list_id'] = $contactList->id;
                Contact::create($data);
            }
            return JsonResponse::generateResponse(
                [
                    'status' => 'success',
                    'message' => 'List has been imported successfully',
                ], 200
            );
        } else{
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'List unable to Import',
                ], 500
            );
        }

    }

    public function getAllLists(Request $request){
        $roleId = $request->role_id;
        $userId = $request->user_id;

        if ($roleId == 1){
            $lists = ContactList::select('id', 'list_name',\DB::raw('(select count(*) from contacts where contacts.contact_list_id=contactlists.id) as listcount'))->whereNull('deleted_at')->get();

            return response()->json(
                [
                    'lists' => $lists,
                ], 200
            );
        } else {
            $lists = ContactList::select('id', 'list_name', \DB::raw('(select count(*) from contacts where contacts.contact_list_id=contactlists.id) as listcount'))->whereNull('deleted_at')->where('user_id', $userId)->get();
            return response()->json(
                [
                    'lists' => $lists,
                ], 200
            );
        }
    }

    public function exportList(Request $request){
        $listsId = $request->list_id;
        $lists = ContactList::where('id',$listsId)->first();
        $listName = $lists->list_name;
        if ($lists){
            $exportData = Contact::select('name','phone')->where('contact_list_id', $listsId)->get();
            return response()->json(
                [
                    'exportData' => $exportData,
                    'listName' => $listName
                ], 200
            );
        } else {
            return JsonResponse::generateResponse(
                [
                    'status' => 'error',
                    'message' => 'List not found',
                ], 500
            );
        }
    }
}
