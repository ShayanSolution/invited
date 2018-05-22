<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeetingType extends Model
{
    protected $fillable = [
        'name',
        'status',
    ];

    public function subscription()
    {
        return $this->hasMany('App\Models\Subscription');
    }

    public function session()
    {
        return $this->hasMany('App\Models\Session');
    }
}
