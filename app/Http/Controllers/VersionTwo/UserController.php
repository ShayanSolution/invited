<?php

namespace App\Http\Controllers\VersionTwo;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Auth;
//Helpers
use App\Helpers\JsonResponse;
//Models
use App\VTwoModels\User;

class UserController extends Controller
{

    public function getUser(){
        $user = Auth::user();
        if($user){
            return response()->json(
                [
                    'user' => $user,
                ], 200
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find user'
                ], 422
            );
        }
    }

    public function updateUserDeviceToken(Request $request){
        $validator = Validator::make($request->all(), [
            'device_token' => 'required',
            'platform' => 'required',
        ]);
        $response = JsonResponse::generateErrorResponse($validator);
        if($response['code'] == 500){
            return $response;
        }
        $inputs = $request->all();
        $userId = Auth::user()->id;

        $token = User::updateWhere(['id'=> $userId], ['device_token'=>$inputs['device_token'],'platform'=>$inputs['platform']]);
        if($token){
            return response()->json(
                [
                    'status' => 'Device token updated successfully',
                ], 200
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to update device token'
                ], 422
            );
        }
    }

}
