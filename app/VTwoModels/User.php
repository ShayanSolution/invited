<?php

namespace App\VTwoModels;

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
    
    public static function updateWhere($where, $update){
        return self::where($where)->update($update);
    }

    public static function registerUser($request){

        $email = explode("@",$request['email']);
        $first_name = $email[0];
        $user = User::create([
            'email' => $request['email'],
            'phone' => $request['phone'],
            'password' => Hash::make($request['password']),
            'uid' => md5(microtime()),
            'firstName' => $first_name
        ]);
        $user_id = $user['id'];
        return $user_id;
    }

    public static function findByPhoneNumber($phone){
        $phoneWithoutCode = substr($phone,-10);
        return self::where('phone','like' ,'%'.$phoneWithoutCode)->first();
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
}
