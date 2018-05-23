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
    ];
    
    public static function CreateList($request){
        $request = $request->all();
        $user_id = $request['user_id'];
        $user_list = self::where('user_id',$user_id)->first();
        if($user_list){
         return  self::where('user_id',$user_id)->update(['contact_list'=>$request['contact_list']]);
        }else{
            return self::create($request)->id;           
        }
    }
}
