<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'name',
        'status',
        'programme_id',
    ];

    public function programme()
    {
        return $this->belongsTo('App\Models\Programme');
    }

}
