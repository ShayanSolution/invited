<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    protected $fillable = [
        'rating',
        'review',
        'session_id',
    ];

    public function session()
    {
        return $this->belongsTo('App\Models\Session');
    }
}
