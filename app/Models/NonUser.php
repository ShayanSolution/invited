<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NonUser extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'non_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'event_id',
        'phone'
    ];

    public function eventNonuser()
    {
        return $this->belongsTo('App\Models\Event', 'event_id');
    }
}
