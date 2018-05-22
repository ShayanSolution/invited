<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Programme extends Model
{
    protected $fillable = [
        'name',
        'status',
    ];

    public function subject()
    {
        return $this->hasMany('App\Models\Subject');
    }

    public function session()
    {
        return $this->hasMany('App\Models\Session');
    }
}
