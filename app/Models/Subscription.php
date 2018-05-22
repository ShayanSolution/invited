<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'name',
        'cost_hourly',
        'group_costing',
        'status',
        'meeting_type_id',
    ];

    protected $casts = [
      'group_costing' => 'array'
    ];

    public function meetingType()
    {
        return $this->belongsTo('App\Models\MeetingType');
    }

    public function invoice()
    {
        return $this->hasMany('App\Models\Invoice');
    }
}
