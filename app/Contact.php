<?php

namespace App;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    protected $table = 'contacts';

    protected $fillable = ['name', 'phone', 'contact_list_id', 'deleted_at'];

    protected $appends = array('lastName', 'dob', 'address');

    public function getLastNameAttribute()
    {
        return '';
    }

    public function getDobAttribute()
    {
        return '';
    }

    public function getAddressAttribute()
    {
        return '';
    }
}
