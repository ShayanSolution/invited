<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Profile;
use App\Models\Session;
use App\Models\User;
use App\Models\Programme;
use App\Models\Subject;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Http\Request;
use App\Transformers\UserTransformer;
use Davibennun\LaravelPushNotification\Facades\PushNotification;
use Illuminate\Queue\Queue;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Log;
use Illuminate\Support\Facades\Config;
use App\Jobs\SendPushNotification;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Instance of UserRepository
     *
     * @var UserRepository
     */
    private $userRepository;

    /**
     * Instanceof UserTransformer
     *
     * @var UserTransformer
     */
    private $userTransformer;

    /**
     * Constructor
     *
     * @param UserRepository $userRepository
     * @param UserTransformer $userTransformer
     */
    public function __construct(UserRepository $userRepository, UserTransformer $userTransformer)
    {
        $this->userRepository = $userRepository;
        $this->userTransformer = $userTransformer;

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $users = $this->userRepository->findBy($request->all());

        return $this->respondWithCollection($users, $this->userTransformer);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function show($id)
    {
        $user = $this->userRepository->findOne($id);

        if (!$user instanceof User) {
            return $this->sendNotFoundResponse("The user with id {$id} doesn't exist");
        }

        // Authorization
        $this->authorize('show', $user);

        return $this->respondWithItem($user, $this->userTransformer);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function store(Request $request)
    {
        // Validation
        $validatorResponse = $this->validateRequest($request, $this->storeRequestValidationRules($request));

        // Send failed response if validation fails
        if ($validatorResponse !== true) {
            return $this->sendInvalidFieldResponse($validatorResponse);
        }

        $user = $this->userRepository->save($request->all());

        if (!$user instanceof User) {
            return $this->sendCustomResponse(500, 'Error occurred on creating User');
        }

        return $this->setStatusCode(201)->respondWithItem($user, $this->userTransformer);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        // Validation
        $validatorResponse = $this->validateRequest($request, $this->updateRequestValidationRules($request));

        // Send failed response if validation fails
        if ($validatorResponse !== true) {
            return $this->sendInvalidFieldResponse($validatorResponse);
        }

        $user = $this->userRepository->findOne($id);

        if (!$user instanceof User) {
            return $this->sendNotFoundResponse("The user with id {$id} doesn't exist");
        }

        // Authorization
        $this->authorize('update', $user);


        $user = $this->userRepository->update($user, $request->all());

        return $this->respondWithItem($user, $this->userTransformer);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function destroy($id)
    {
        $user = $this->userRepository->findOne($id);

        if (!$user instanceof User) {
            return $this->sendNotFoundResponse("The user with id {$id} doesn't exist");
        }

        // Authorization
        $this->authorize('destroy', $user);

        $this->userRepository->delete($user);

        return response()->json(null, 204);
    }

    /**
     * Store Request Validation Rules
     *
     * @param Request $request
     * @return array
     */
    private function storeRequestValidationRules(Request $request)
    {
        $rules = [
            'email'                 => 'email|required|unique:users',
            'firstName'             => 'required|max:100',
            'middleName'            => 'max:50',
            'lastName'              => 'required|max:100',
            'username'              => 'max:50',
            'address'               => 'max:255',
            'zipCode'               => 'max:10',
            'phone'                 => 'max:20',
            'mobile'                => 'max:20',
            'city'                  => 'max:100',
            'state'                 => 'max:100',
            'country'               => 'max:100',
            'password'              => 'min:5'
        ];

        $requestUser = $request->user();

        // Only admin user can set admin role.
        if ($requestUser instanceof User && $requestUser->role === User::ADMIN_ROLE) {
            $rules['role'] = 'in:BASIC_USER,ADMIN_USER';
        } else {
            $rules['role'] = 'in:BASIC_USER';
        }

        return $rules;
    }

    /**
     * Update Request validation Rules
     *
     * @param Request $request
     * @return array
     */
    private function updateRequestValidationRules(Request $request)
    {
        $userId = $request->segment(2);
        $rules = [
            'email'                 => 'email|unique:users,email,'. $userId,
            'firstName'             => 'max:100',
            'middleName'            => 'max:50',
            'lastName'              => 'max:100',
            'username'              => 'max:50',
            'address'               => 'max:255',
            'zipCode'               => 'max:10',
            'phone'                 => 'max:20',
            'mobile'                => 'max:20',
            'city'                  => 'max:100',
            'state'                 => 'max:100',
            'country'               => 'max:100',
            'password'              => 'min:5'
        ];

        $requestUser = $request->user();

        // Only admin user can update admin role.
        if ($requestUser instanceof User && $requestUser->role === User::ADMIN_ROLE) {
            $rules['role'] = 'in:BASIC_USER,ADMIN_USER';
        } else {
            $rules['role'] = 'in:BASIC_USER';
        }

        return $rules;
    }

    public function getDashboardTotalOfPieCharts(){
        $users = User::all();
        $invoice = Invoice::all();
        $sessions = Session::all();
        return [
            'users' => count($users),
            'students' => count($users->where('role_id', 3)),
            'tutors' => count($users->where('role_id', 3)),
            'sessions' => count($sessions),
            'earning' => $invoice->sum('total_cost'),
        ];
    }

    public function getStudents()
    {
       return $students =  User::getStudents();
        
    }

    public function getUserProfile(Request $request){
        $user_id = $request->all();
        $this->validate($request,[
            'user_id' => 'required'
        ]);
        $user_id = $user_id['user_id'];
        $user = new User();
        $user = $user->userProfile($user_id);
        if($user){
            $profile = array(
                'Full Name'=>$user->firstName.' '.$user->lastName,
                'Email'=>$user->email,
                'Phone Number'=>$user->phone,
                'Father Name'=>$user->fatherName,
                'Qualification'=>$user->qualification,
                'Expert Class'=>$user->p_name,
                'Expert Subjects'=>$user->s_name,
                'Gender'=>$user->g_name,
                'Rating'=>$user->rating,
                'Experience'=>$user->experience,
                'Address'=>$user->address,
                'User CNIC'=>$user->cnic_no,
                'Profile_Image' => URL::to('/images').'/'.$user->profileImage,
            );
            return $profile;
        }
        else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find profile'
                ], 422
            );
        }

    }

    public function postTutorProfile(Request $request){
        $data = $request->all();
        $this->validate($request,[
            'subject_id' => 'required',
            'class_id' => 'required',
            'is_home' => 'required',
            'is_group' => 'required',
            'call_student' => 'required',
        ]);
        $user = new User();
        $users = $user->getTutorProfile($data);
        if($users){
            return response()->json(['data' => $users]);
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find tutor'
                ], 422
            );
        }

    }
    
    public function tutorSessionInfo(Request $request){
        $data = $request->all();
        $this->validate($request,[
            'student_id' => 'required',
            'tutor_id' => 'required',
            'subject_id' => 'required',
            'class_id' => 'required',
        ]);
        $student_id = $data['student_id'];
        $programme_id = $data['class_id'];
        $subject_id = $data['subject_id'];
        $tutors_ids = json_decode($data['tutor_id']);
        $device_token_array = array();
        $class = Programme::find($programme_id);
        $subject = Subject::find($subject_id);
        //update class and subjects for students
        Profile::where('user_id',$student_id)->update(['programme_id'=>$programme_id,'subject_id'=>$subject_id]);
        $student = User::select('users.*')
                    ->select('users.*','profiles.is_group')
                    ->leftjoin('profiles','profiles.user_id','=','users.id')
                    ->leftjoin('programmes','programmes.id','=','profiles.programme_id')
                    ->leftjoin('subjects','subjects.id','=','profiles.subject_id')
                    ->where('users.role_id','=',3)
                    ->where('users.id','=',$student_id)
                    ->first();

        if($student){
            $user_age = Carbon::parse($student->dob)->age;
            for($j=0;$j<count($tutors_ids);$j++){
                //get tutor device token to send notification
                $user = User::where('id','=',$tutors_ids[$j])->select('users.*','device_token as token')->first();
                if(!empty($user->token)){
                    $device_token_array[] = $user->token;
                    //notification message
                    $message = PushNotification::Message(
                        $student->firstName.' '.$student->lastName.' wants a session with you',
                        array(
                            'badge' => 1,
                            'sound' => 'example.aiff',
                            'actionLocKey' => 'Action button title!',
                            'locKey' => 'localized key',
                            'locArgs' => array(
                                'localized args',
                                'localized args',
                            ),
                            'launchImage' => 'image.jpg',
                            'custom' => array('custom_data' => array(
                                'Student_Name' => $student->firstName." ".$student->lastName,
                                'Student_id' => $student->id,
                                'Class_Name' => isset($class->name)?$class->name:'',
                                'Subject_Name' => isset($subject->name)?$subject->name:'',
                                'Class_id' => $programme_id,
                                'Subject_id' => $subject_id,
                                'IS_Group' => $student->is_group,
                                'Longitude' => $student->longitude,
                                'Latitude' => $student->latitude,
                                'Datetime' => Carbon::now()->toDateTimeString(),
                                'Age' => $user_age>0?$user_age:'',
                                'Profile_Image' => !empty($student->profileImage)?URL::to('/images').'/'.$student->profileImage:'',
                            ))
                        ));

                    //send student info to tutor
//                    Log::info("Request Cycle with Queues Begins");
//                    $job = new SendPushNotification($user->token,$user,$class,$subject,$user_age,$programme_id,$subject_id);
//                    dispatch($job);
//                    Log::info('Request Cycle with Queues Ends');
                    PushNotification::app('appNameIOS')->to($user->token)->send($message);
                }
            }

            return [
                'status' => 'success',
                'messages' => 'Notification sent successfully',
                //'device-tokens' => print_r($device_token_array)
            ];

        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to find tutor'
                ], 422
            );
        }

    }

    

    public function updateStudentProfile(Request $request){
        $this->validate($request,[
            'firstName' => 'regex:/^[a-zA-Z]+$/u|max:255',
            'lastName' => 'regex:/^[a-zA-Z]+$/u|max:255',
            'email' => 'email',
            'student_id' => 'Required|numeric',
            'gender_id' => 'numeric',
            'mobile' => 'numeric',
            'profileImage' => 'mimes:jpeg,jpg,png,gif|max:10000',
        ]);
        $data = $request->all();
        $student_id = isset($data['student_id'])?$data['student_id']:'';
        $update_array = $this->getUpdatedValues($data);
        $user = User::where('id','=',$student_id)->first();
        $role_id = Config::get('user-constants.STUDENT_ROLE_ID');
        if($user){
            //upload file and update user profile image
            if(isset($data['profileImage'])){
                $file = $request->file('profileImage');
                $file_name = $file->getClientOriginalName();
                $destinationPath = base_path().'/public/images';
                $file->move($destinationPath,$file->getClientOriginalName());
                User::updateProfileImage($student_id,$file_name,$role_id);
            }
            //update student profile
            User::updateUserProfile($student_id,$update_array,$role_id);
            //get student profile image
            $student_info = User::where('id','=',$student_id)->first();
            return [
                'status' => 'success',
                'messages' => 'Student profile updated successfully!',
                'Profile_Image' => !empty($student_info->profileImage)?URL::to('/images').'/'.$student_info->profileImage:'',
            ];
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to update user'
                ], 422
            );
        }
    }


    public function updateTutorProfile(Request $request){
        $data = $request->all();
        $tutor_id = isset($data['tutor_id'])?$data['tutor_id']:'';
        $role_id = Config::get('user-constants.TUTOR_ROLE_ID');
        //build array to be updated.
        $update_array = $this->getUpdatedValues($data);
        $user = User::where('id','=',$tutor_id)->first();
        if($user){
            //upload file
            if(isset($data['profileImage'])){
                $file = $request->file('profileImage');
                $file_name = $file->getClientOriginalName();
                $destinationPath = base_path().'/public/images';
                $file->move($destinationPath,$file_name);
                User::updateProfileImage($tutor_id,$file_name,$role_id);
            }
            //update tutor profile
            User::updateUserProfile($tutor_id,$update_array,$role_id);
            $tutor_profile = Profile::where('user_id','=',$tutor_id)->first();
            if($tutor_profile){
                $update_profile_values = $this->getProfileUpdatedValues($data);
                Profile::updateUserProfile($update_profile_values);
            }else{
                Profile::createUserProfile($data);
            }
            //get student profile image
            $tutor_info = User::where('id','=',$tutor_id)->first();
            return [
                'status' => 'success',
                'messages' => 'Tutor profile updated successfully!',
                'Profile_Image' => !empty($user->profileImage)?URL::to('/images').'/'.$tutor_info->profileImage:'',

            ];
        }else{
            return response()->json(
                [
                    'status' => 'error',
                    'message' => 'Unable to update user'
                ], 422
            );
        }
    }

    public function getUpdatedValues($data){
        $update_array = array();
        $firstName = isset($data['firstName'])?$data['firstName']:'';
        $lastName = isset($data['lastName'])?$data['lastName']:'';
        $email = isset($data['email'])?$data['email']:'';
        $fatherName = isset($data['fatherName'])?$data['fatherName']:'';
        $mobile = isset($data['mobile'])?$data['mobile']:'';
        $gender_id = isset($data['gender_id'])?$data['gender_id']:'';
        $address = isset($data['address'])?$data['address']:'';
        $cnic_no = isset($data['cnic_no'])?$data['cnic_no']:'';
        $experience = isset($data['experience'])?$data['experience']:'';
        $qualification = isset($data['qualification'])?$data['qualification']:'';
        

        if(!empty($firstName)){$update_array['firstName'] = $firstName;}
        if(!empty($lastName)){$update_array['lastName'] = $lastName;}
        if(!empty($email)){$update_array['email'] = $email;}
        if(!empty($fatherName)){$update_array['fatherName'] = $fatherName;}
        if(!empty($mobile)){$update_array['mobile'] = $mobile;}
        if(!empty($gender_id)){$update_array['gender_id'] = $gender_id;}
        if(!empty($address)){$update_array['address'] = $address;}
        if(!empty($cnic_no)){$update_array['cnic_no'] = $cnic_no;}
        if(!empty($experience)){$update_array['experience'] = $experience;}
        if(!empty($qualification)){$update_array['qualification'] = $qualification;}
        

        return $update_array;
    }
    
    public function getProfileUpdatedValues($data){
        $update_array = array();        
        $is_home = isset($data['is_home'])?$data['is_home']:'';
        $is_group = isset($data['is_group'])?$data['is_group']:'';
        $is_mentor = isset($data['is_mentor'])?$data['is_mentor']:'';
        $programme_id = isset($data['programme_id'])?$data['programme_id']:'';
        $subject_id = isset($data['subject_id'])?$data['subject_id']:'';
        $tutor_id = isset($data['tutor_id'])?$data['tutor_id']:'';
        $student_id = isset($data['student_id'])?$data['student_id']:'';
        $one_on_one = isset($data['one_on_one'])?$data['one_on_one']:'';
        $call_tutor = isset($data['call_tutor'])?$data['call_tutor']:'';
        $call_student = isset($data['call_student'])?$data['call_student']:'';

        if(!empty($subject_id)){$update_array['subject_id'] = $subject_id;}
        if(!empty($programme_id)){$update_array['programme_id'] = $programme_id;}
        if(!empty($is_home)){$update_array['is_home'] = $is_home;}
        if(!empty($is_group)){$update_array['is_group'] = $is_group;}
        if(!empty($is_mentor)){$update_array['is_mentor'] = $is_mentor;}
        if(!empty($tutor_id)){$update_array['user_id'] = $tutor_id;}
        if(!empty($student_id)){$update_array['user_id'] = $student_id;}
        if(!empty($one_on_one)){$update_array['one_on_one'] = $one_on_one;}
        if(!empty($call_tutor)){$update_array['call_tutor'] = $call_tutor;}
        if(!empty($call_student)){$update_array['call_student'] = $call_student;}
        return $update_array;
        
    }

    public function getTutors(){
        return User::getTutors();
    }

    public function updateUserActiveStatus($id){
        //update student deserving status
        User::updateUserActiveStatus($id);
        $students =  User::getStudents();
        return $students;
    }
    
    public function removeUser($id){
        $id = User::find($id); $id ->delete();
        //User::withTrashed()->where('id', $id)->restore();
        return User::getStudents();
    }

    public function userProfile($id){
        $user = new User();
        return $user->userProfile($id);
        
    }

    public function sendUserNotification(Request $request){
        $request = $request->all();
        $id = $request['user_id'];
        $user = User::where('id',$id)->first();
        $device_token = $user->device_token;
        $message = PushNotification::Message('Hello World, i`m a push message',array(
            'badge' => 1,
            'sound' => 'example.aiff',

            'actionLocKey' => 'Action button title!',
            'locKey' => 'localized key',
            'locArgs' => array(
                'localized args',
                'localized args',
            ),
            'launchImage' => 'image.jpg',

            'custom' => array('custom data' => array(
                'we' => 'want', 'send to app'
            ))
        ));
        PushNotification::app('appNameIOS')->to($device_token)->send($message);
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard
     */
    protected function guard()
    {
        return Auth::guard('api');
    }

    public function logoutApi(Request $request)
    {
        if (Auth::check()) {
            Auth::user()->AauthAcessToken()->delete();
        }
    }

    public function updateUserDeviceToken(Request $request){

        $this->validate($request,[
            'user_id' => 'required',
            'device_token' => 'required',
        ]);
        
        $token = User::updateToken($request);
        if($token){
            
            return response()->json(
                [
                    'status' => 'Device token accepted successfully',
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
