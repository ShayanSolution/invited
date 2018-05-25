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
    public static function getList($user_id){
     return  self::where('user_id',$user_id)->get();
    }
}
