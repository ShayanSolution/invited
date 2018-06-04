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
    
    public static function CreateList($request){
        $request = $request->all();
        return self::create($request)->id;
    }
    public static function UpdateList($request){
        $request = $request->all();
        return self::where('id',$request['list_id'])->update(['contact_list'=>$request['contact_list'] ]);
    }

    public static function getList($user_id,$id){
     return  self::where('user_id',$user_id)->where('id',$id)->get();
    }

    public static function getUserContactLists($user_id){
        return  self::where('user_id',$user_id)->get();
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
}
