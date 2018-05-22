<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneCode extends Model
{
    protected $fillable = [
        'phone',
        'code'
    ];
}
