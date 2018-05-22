<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Profile extends Model
{
    protected $fillable = [
        'name',
        'is_mentor',
        'is_deserving',
        'meeting_type_id',
        'user_id',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function meetingType()
    {
        return $this->belongsTo('App\Models\MeetingType');
    }
    
    public static function createUserProfile($data){
        $tutor_id = isset($data['tutor_id'])?$data['tutor_id']:'';
        $programme_id = isset($data['programme_id'])?$data['programme_id']:'';
        $subject_id = isset($data['subject_id'])?$data['subject_id']:'';
        
        $tutor_profile = new Profile();
        $tutor_profile->programme_id = $programme_id;
        $tutor_profile->subject_id = $subject_id;
        $tutor_profile->user_id = $tutor_id;
        $tutor_profile->is_home = 0;
        $tutor_profile->is_group = 0;
        $tutor_profile->save();
    }
    
    public static function updateUserProfile($update_profile_values){
        Profile::where('user_id','=',$update_profile_values['user_id'])->update($update_profile_values);
    }
    
    public static function registerUserProfile($tutor_id){
        $profile = Self::create([
            'is_mentor' => 0,
            'is_deserving' => 0,
            'is_home' => 0,
            'is_group' => 0,
            'meeting_type_id' => 0,
            'user_id'=>$tutor_id,
            'programme_id'=>0,
            'subject_id'=>0,
        ])->id;
        
        return $profile;
    }

    public static function updateStudentGroup($student_id,$group){
        self::where('user_id',$student_id)->update(['is_group'=>$group]);
    }

    public static function updateDerserveStatus($student_id){
        $result = Self::where('user_id',$student_id)->first();
        if($result->is_deserving == 0){
            $deserving_status = 1;
        }else{
            $deserving_status = 0;
        }
        self::where('user_id',$student_id)->update(['is_deserving'=>$deserving_status]);

    }
}
