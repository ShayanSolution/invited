<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneCode extends Model
{
    protected $fillable = [
        'phone',
        'code'
    ];

    public function createPhoneCode($phone,$code){
       return  self::create(['phone'=>$phone,'code'=>$code])->id;
    }
}
