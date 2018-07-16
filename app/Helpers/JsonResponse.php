<?php

namespace App\Helpers;


class JsonResponse
{
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


    public static function generateResponse($data, $code = 200){
        if(!isset($data['message'])){
            if($data['status'] != 'success' || $data['status'] != 'error'){
                $data['message'] = $data['status'];
                if($code == 200){
                    $data['status'] = 'success';
                }else{
                    $data['status'] = 'error';
                }
            }
            if($data['status'] == 'success'){
                $data['message'] = 'Operation completed successfully.';
            }else{
                $data['message'] = 'Error in operation.';
            }
        }
        $data['code'] = $code;
        return response()->json($data);
    }
}
