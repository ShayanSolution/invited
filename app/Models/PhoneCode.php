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

    public function createPhoneCode($phone,$code){
       return  self::create(['phone'=>$phone,'code'=>$code])->id;
    }

    public function getPhoneNumber($phone){
        return  self::where('phone','=',$phone)->first();
    }

    public function updatePhoneCode($phone,$code){
        return self::where('phone',$phone)->update(['code'=>$code]);
    }
    
    public function verifyPhoneCode($request){
       $request = $request->all();
       $phone = $request['phone'];
       $code = $request['code'];
       $phone_code =  self::where('phone','=',$phone)->where('code','=',$code)->first();

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
