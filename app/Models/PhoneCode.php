<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneCode extends Model
{
    protected $fillable = [
        'phone',
        'code'
    ];
    protected $table = 'phone_codes';

    public function scopeVerified($q){
        return $q->where('verified',1);
    }

    public function createPhoneCode($phone,$code){
       return  self::create(['phone'=>$phone,'code'=>$code])->id;
    }

    public static function getPhoneNumber($phone){
        $phoneWithoutCode = substr($phone,-10);
        return  self::where('phone','like','%'.$phoneWithoutCode)->verified()->first();
    }

    public function updatePhoneCode($phone,$code){
        return self::where('phone',$phone)->update(['code'=>$code]);
    }
    
    public function verifyPhoneCode($request){

       $phone = $request['phone'];
        $phoneWithoutCode = substr($phone,-10);
       $code = $request['code'];
       $phone_code =  self::where('phone','like','%'.$phoneWithoutCode)->where('code','=',$code)->first();

       if($phone_code) {
           $new_code = $this->generateRandomCode();
           self::where('phone','=',$phone_code->phone)->where('code','=',$phone_code->code)->update(['verified'=>1,'code'=>$new_code]);
           return $new_code;
       }else{
           return false;
       }
    }

    public function generateRandomCode($digits = 4){
        return rand(pow(10, $digits-1), pow(10, $digits)-1);
    }
}
