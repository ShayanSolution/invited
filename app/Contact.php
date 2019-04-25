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
}
