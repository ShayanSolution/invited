<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ContactList extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'contactlists';
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'contact_list',
        'list_name'
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }
    
    public static function CreateList($request){
        $request = $request->all();
        $contactList = json_decode($request['contact_list']);
        foreach($contactList as $key => $contact){
            $contact->phone = preg_replace('/\s+/', '', trim($contact->phone));
            $contact->phone = preg_replace('/^92|^092/', '', trim($contact->phone));
            $phone = $contact->phone;
            $contact->phone." first index".$phone[0]."<br>";
            if($phone[0]!=0){
                 $phone='0'.$phone;
            }
           // $phone[0] = $phone[0] != 0 ?  0 : $phone[0];
            $contact->phone = $phone;
            $contactList[$key] = $contact;
        }
        $request['contact_list'] = json_encode($contactList);
        return self::create($request)->id;
    }
    public static function UpdateList($request){
        $request = $request->all();
        return self::where('id',$request['list_id'])->update(['contact_list'=>$request['contact_list'],'list_name'=>$request['list_name'] ]);
    }

    public static function getList($id){
     return  self::where('id',$id)->get();
    }

    public static function getUserList($id){
        return  self::where('id',$id)->first();
    }

    public static function getUserContactLists($user_id){
        return  self::where('user_id',$user_id)->orderBy('list_name')->get();
    }

    public static function getUserListCount($user_id){

        $lists = ContactList::where('user_id',$user_id)->get();
        $user_list = [];
        $index = 0;
        foreach ($lists as $list){
            $users = json_decode($list->contact_list);
            foreach ($users as $user){
                $user_list[$index]['phone'] = $user->phone;
                $index++;
            }

        }
        return $user_list;
    }

    public static function deleteList($request){
        $id = $request['list_id'];
        $list = self::find($id);
        if($list){
            return $list->delete();
        }
    }

    public static function generateErrorResponse($validator){
        $response = null;
        if ($validator->fails()) {
            $response = $validator->errors()->toArray();
            $response['error'] = $validator->errors()->toArray();
            $response['code'] = 500;
            $response['message'] = 'Error occured';
        }
        else{
            $response['code'] = 200;
            $response['message'] = 'operation completed successfully';
        }
        return $response;
    }
}
