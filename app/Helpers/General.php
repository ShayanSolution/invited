<?php
namespace App\Helpers;
/**
 * Created by PhpStorm.
 * User: shayan.solutions
 * Date: 07/11/2018
 * Time: 9:14 PM
 */
class General
{
    public static function sanitizePhoneNumber($number){


        if (preg_match('/^[+,0]?(92)/', $number)){
            return $number;
        }else{
            $number = ltrim($number, '0');
            return '+92'.$number;
        }
    }

    public static function generateRandomCode($digits = 4){
        return rand(pow(10, $digits-1), pow(10, $digits)-1);
    }
}
