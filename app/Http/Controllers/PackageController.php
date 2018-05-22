<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PackageController extends Controller
{
    /**
     *
     * retufn hourly package rate
     */
    public function packageCost(Request $request){
        $this->validate($request,[
            'category_id' => 'required',
            'is_group' => 'required',
            'is_group' => 'required',
            'group_count' => 'required',
        ]);

        $request = $request->all();
        $category_id = $request['category_id'];
        $is_group = $request['is_group'];
        $group_count = $request['group_count'];
        $package = Package::where('category_id',$category_id)->where('is_active',1)->first();
        if($is_group && !empty($package)){
            if ($group_count == 2) {
                $hourly_rate = $package->hourly_rate + $package->extra_percentage_for_group_of_two;
                return response()->json(
                    [
                        'hourly_rate' => $hourly_rate
                    ]
                );
            }else if($group_count == 3){
                $hourly_rate = $package->hourly_rate + $package->extra_percentage_for_group_of_three;
                return response()->json(
                    [
                        'hourly_rate' => $hourly_rate
                    ]
                );
            }else if($group_count == 4){
                $hourly_rate = $package->hourly_rate + $package->extra_percentage_for_group_of_four;
                return response()->json(
                    [
                        'hourly_rate' => $hourly_rate
                    ]
                );
            }else{
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => 'Unable to find user hourly rate'
                    ], 422
                );
            }

        }else if(!empty($package)){
            $hourly_rate = $package->hourly_rate;
            return response()->json(
                [
                    'hourly_rate' => $hourly_rate
                ]
            );
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find user hourly rate'
                ], 422
            );
        }
    }

    public function getPackageCategories(){
        //Mail::raw('Raw string email', function($msg) { $msg->to(['dev2@shayansolutions.com']); $msg->from(['dev2@shayansolutions.com']); });
        $categories = Category::where('status',1)->get();
        if($categories){
            $package_categories = [];
            foreach ($categories as $category){
                $package_categories[] = [
                    'id'=>$category->id,
                    'name'=>$category->name,
                ];
            }
            return response()->json(
                [
                    'data' => $package_categories
                ]
            );

        }else{

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find user session'
                ], 422
            );
        }

    }
}
