<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Subject;


class ProgrammeSubjectController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/get-classes",
     *     summary="Get Classes",
     *     produces={"application/json"},     *
     *     @SWG\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *     @SWG\Response(
     *         response="422",
     *         description="Class not found",
     *     )
     * )
     */
    public function getAllProgrammes() {

        $programmes = Programme::where('status', 1)->get();
        if($programmes){
            return response()->json(
                [
                    'programmes' => $programmes,
                ], 200
            );
        }
        else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Programmes not found'
                ], 422
            );
        }
    }


    public function getProgramme(Request $request) {
        $this->validate($request,[
            'programme' => 'required',
        ]);

        $request = $request->all();
        $programme_id =  $request['programme'];
        $programme = Programme::where('id', $programme_id)->first();

        if($programme){
            return response()->json(
                [
                    'programme' => $programme,
                ], 200
            );
        }else {

            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Programmes not found'
                ], 422
            );
        }
    }

    public function getAllSubjects() {

        $subjects = Subject::where('status', 1)->get();
        if($subjects){
            return response()->json(
                [
                    'subjects' => $subjects,
                ], 200
            );
        }
        else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Programmes not found'
                ], 422
            );
        }
    }

    /**
     * @SWG\Get(
     *     path="/get-class-subjects",
     *     summary="Get Class subjects",
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         description="Class Id",
     *         in="query",
     *         name="class",
     *         required=true,
     *         type="integer",
     *         format="int64",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="successful operation",
     *     ),
     *     @SWG\Response(
     *         response="422",
     *         description="Invalid programme value",
     *     )
     * )
     */
    public function getProgrammeSubjects(Request $request) {
        $this->validate($request,[
            'class' => 'required',
        ]);
        $programme_id = $request['class'];
        $subjects = Subject::where('programme_id', $programme_id)
                    ->where('status',1)
                    ->get();
        if($subjects){
            return response()->json(
                [
                    'subjects' => $subjects,
                ], 200
            );
        }
        else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Subject not found'
                ], 422
            );
        }
    }


    public function getSubjectById(Request $request) {
        $this->validate($request,[
            'subject' => 'required',
        ]);
        $subject_id = $request['subject'];
        $subject = Subject::where('programme_id', $subject_id)->first();
        if($subject){
            return response()->json(
                [
                    'subject' => $subject,
                ], 200
            );
        }
        else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Subject not found'
                ], 422
            );
        }
    }


    public function postSaveProgramme(Request $request){
        $this->validate($request,[
            'name' => 'required',
        ]);

        $request = $request->all();
        if(is_array($request)){
            $name = $request['name'];
        }else{
            $name = $request->name;
        }
        $programme = Programme::where('name', $name)->first();

        if($programme){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Programme already exist!'
                ], 422
            );
        }else{
            Programme::create(['name'=>$name,'status'=>1,'created_at'=>Carbon::now(),'updated_at'=>Carbon::now()]);

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Programme created successfully!'
                ], 200
            );
        }
    }


    public function postSaveProgrammeSubject(Request $request){

        $this->validate($request,[
            'name' => 'required',
            'programme_id' => 'required',
        ]);

        $request = $request->all();
        if(is_array($request)){
            $name = $request['name'];
            $programme_id = $request['programme_id'];
        }else{
            $name = $request->name;
            $programme_id = $request['programme_id'];
        }
        $programme = Subject::where('name', $name)
                    ->where('programme_id',$programme_id)
                    ->first();

        if($programme){
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Subject already exist!'
                ], 422
            );
        }else{
            Subject::create(
                [
                    'name'=>$name,
                    'status'=>1,
                    'programme_id'=>$programme_id,
                    'created_at'=>Carbon::now(),
                    'updated_at'=>Carbon::now()
                ]
            );

            return response()->json(
                [
                    'status' => 'success',
                    'message' => 'Subject created successfully!'
                ], 200
            );
        }
    }
    
}
