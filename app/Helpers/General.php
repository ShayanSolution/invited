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

        return '+'.$number;
//        if (preg_match('/^[+]([0-9])/', $number)){
//            return $number;
//        }else{
//            $number = ltrim($number, '0');
//            return '+92'.$number;
//        }
    }

    public static function generateRandomCode($digits = 4){
        return rand(pow(10, $digits-1), pow(10, $digits)-1);
    }

    /**
     * @param array $array
     * @param int $keyIndex
     * @param bool $addLeadingZero
     * @description this function is for what
     * @return array
     */
    public static function setArrayKeys($array = [], $keyIndex = 0, $addLeadingZero = false){
        $arrayKeys = $array[$keyIndex];

        $newArray = [];
        foreach ($array as $key => $value){
            if($key != $keyIndex){
                if ($value[1] != '') {
                    if ($addLeadingZero){
                        if (strlen($value[1]) < 11){
                            $value[1] = "0".$value[1];
                        }
                    }
                    $newArray[] = [$arrayKeys[0] => $value[0], $arrayKeys[1] => $value[1]];
                }
            }
        }
        return $newArray;
    }

    /**
     * @param array $data
     * @return array
     * @discription Tang karta tha zero
     */
    public static function excludeEmptyArrayKeys($data = []){
        $filteredData = [];
        foreach ($data as $key => $item){
            if ((!empty($item) && $item !='' && $item !='null' && $item !=null) || $item == '0'){
                $filteredData[$key] = $item;
            } else{
                $filteredData[$key] = null;
            }
        }
        return $filteredData;
    }
}
