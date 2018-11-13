<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Request;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use App\Models\Profile;
use App\Helpers\General;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable, SoftDeletes, HasApiTokens;

    const ADMIN_ROLE = 'ADMIN_USER';
    const BASIC_ROLE = 'BASIC_USER';
    protected $dates = ['deleted_at'];
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uid',
        'firstName',
        'lastName',
        'middleName',
        'email',
        'password',
        'address',
        'zipCode',
        'username',
        'city',
        'state',
        'country',
        'phone',
        'mobile',
        'role_id',
        'isActive',
        'profileImage',
        'device_token',
        'environment',
        'confirmation_code',
        'confirmation_code',
        'confirmed',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'firstName'=>'string',
        'lastName'=> 'string',
        'cnic_no'=> 'string',
        'middleName'=> 'string',
        'username'=> 'string',
        'dob'=> 'dob',
        'address'=> 'string',
        'zipCode'=> 'string',
        'phone'=> 'string',
        'mobile'=> 'string',
        'city'=> 'string',
        'state'=> 'string',
        'country'=> 'string',
        'platform'=> 'string',
        'profileImage' => 'string',
        'deleted_at'=> 'timestamp'
    ];

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return (isset($this->role) ? $this->role : self::BASIC_ROLE) == self::ADMIN_ROLE;
    }

    public function profile()
    {
        return $this->hasOne('App\Models\Profile');
    }

    public function event()
    {
        return $this->hasOne('App\Models\Event');
    }

    public function transaction()
    {
        return $this->hasMany('App\Models\Transaction');
    }

    public function list()
    {
        return $this->hasMany('App\ContactList', 'user_id');
    }

    public function tutor()
    {
        return $this->hasMany('App\Models\Sessions', 'tutor_id');
    }

    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }

    public function AauthAcessToken(){
        return $this->hasMany('App\Models\OauthAccessToken');
    }

    public function findForPassport($username) {
        $request  = Request::all();
        if(isset($request['username']) && !empty($request['username'])){
            //$user = self::where('phone', $username)->where('confirmed','=',1)->first();
            $user = self::where('phone', $username)->first();
            if(empty($user)){
                $user = self::where('email', $username)->first();
            }
        }
        if(!$user){
            return $user;
        }
        return $user;
    }

    public function scopeUpdatePassword($query, $password){

        return $query->update(['password'=>Hash::make($password)]);
    }
    
    public function findBookedUser($tutor_id){
        $user = User::select('users.*')
                ->select('users.*','programmes.name as p_name','subjects.name as s_name'
                    ,'programmes.id as p_id','subjects.id as s_id','profiles.is_group',
                    'profiles.is_home as t_is_home')
                ->leftjoin('profiles','profiles.user_id','=','users.id')
                ->leftjoin('programmes','programmes.id','=','profiles.programme_id')
                ->leftjoin('subjects','subjects.id','=','profiles.subject_id')
                ->where('users.role_id','=',2)
                ->where('users.id','=',$tutor_id)
                ->first();
        return $user;
    }

    public function userProfile($user_id){

       return Self::select('users.*','programmes.name as p_name','subjects.name as s_name','genders.name as g_name','rating')
                    ->leftjoin('profiles','profiles.user_id','=','users.id')
                    ->leftjoin('programmes','programmes.id','=','profiles.programme_id')
                    ->leftjoin('subjects','subjects.id','=','profiles.subject_id')
                    ->leftjoin('genders','genders.id','=','users.gender_id')
                    ->leftjoin('ratings','ratings.user_id','=','users.id')
                    ->where('users.id', $user_id)
                    ->first();
    }


    
    public static function updateProfileImage($tutor_id,$file_name,$role_id){
        User::where('id','=',$tutor_id)->where('role_id','=',$role_id)-> update(['profileImage'=>$file_name]);
    }
    
    public static function updateUserProfile($tutor_id,$update_array,$role_id){
        User::where('id','=',$tutor_id)->where('role_id','=',$role_id)-> update($update_array);
    }
    

    
    public static function updateUserActiveStatus($id){
        $user = Self::where('id',$id)->first();
        if($user->is_active == 0){
            $is_active = 1;
        }else{
            $is_active = 0;
        }
        self::where('id',$id)->update(['is_active'=>$is_active]);
    }

    public static function getTutors(){
        $tutors = self::select('users.*','profiles.is_deserving')
            ->join('profiles','profiles.user_id','=','users.id')
            ->where('role_id', Config::get('user-constants.TUTOR_ROLE_ID'))
            ->get();
        $tutor_detail=[];
        $index = 0;
        foreach ($tutors as $tutor){
            $tutor_detail[$index]['id'] = $tutor->id;
            $tutor_detail[$index]['firstName'] = $tutor->firstName;
            $tutor_detail[$index]['lastName'] = $tutor->lastName;
            $tutor_detail[$index]['username'] = $tutor->username;
            $tutor_detail[$index]['email'] = $tutor->email;
            $tutor_detail[$index]['city'] = $tutor->city;
            $tutor_detail[$index]['country'] = $tutor->country;

            if($tutor->is_active == '1'){
                $tutor_detail[$index]['is_active'] = 'Yes';
            }else{
                $tutor_detail[$index]['is_active'] = 'No';
            }
            $index++;
        }
        return $tutor_detail;
    }
    
    public static function registerUser($request){
        
        $email = explode("@",$request['email']);
        $first_name = $email[0];
        $user = User::create([
            'email' => $request['email'],
            'phone' => $request['phone'],
            'password' => Hash::make($request['password']),
            'uid' => md5(microtime()),
            //'device_token' => $request->device_token,
            'firstName' => $first_name
        ]);
        $user_id = $user['id'];
        $user->profile()->create(['user_id'=>$user_id]);
        return $user_id;
    }

    public static function updateToken($request){

        return self::where('id',$request['user_id'])->update($request->except('user_id'));
       //return self::where('id',$request['user_id'])->update(['device_token'=>$request['device_token'],'platform'=>$request['platform']]);
    }

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

    protected function castAttribute($key, $value)
    {
        if ($this->getCastType($key) == 'array' && is_null($value)) {
            return [];
        }
        if (is_null($value)) {
            return '';
        }

        return parent::castAttribute($key, $value);
    }

    public static function findByPhoneNumber($phone){
        $phoneWithoutCode = substr($phone,-9);
        return self::where('phone','like' ,'%'.$phoneWithoutCode)->first();
    }
}
