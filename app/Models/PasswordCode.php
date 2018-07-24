<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\General;

class PasswordCode extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'code'
    ];
    protected $table = 'password_codes';

    public function scopeVerifyPasswordCode( $query, $data ) {
        $phone = $data['phone'];
        $phoneWithoutCode = substr($phone,-9);
        $code = $data['code'];
        $passwordCode = $query->where('phone','like','%'.$phoneWithoutCode)->where('code','=',$code)->first();
        if($passwordCode){
            $passwordCode->where('phone','like','%'.$phoneWithoutCode)->where('code','=',$code)->update(['verified'=>1, 'code'=> General::generateRandomCode(4)]);
            return $passwordCode;
        }else{
            return false;
        }
    }

    public function scopeUpdatePasswordCode( $query, $data ) {
        $phone = $data['phone'];
        $phoneWithoutCode = substr($phone,-9);
        $code = General::generateRandomCode(4);
        return $query->where('phone','like','%'.$phoneWithoutCode)->where('code','=',$code)->update(['verified'=>1, 'code'=> $code]);

    }
}
