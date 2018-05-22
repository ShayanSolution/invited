<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'name',
        'total_cost',
        'session_id',
        'subscription_id',
        'transaction_id',
    ];

    public function session()
    {
        return $this->belongsTo('App\Models\Session');
    }

    public function subscription()
    {
        return $this->belongsTo('App\Models\Subscription');
    }

    public function transaction()
    {
        return $this->belongsTo('App\Models\Transaction');
    }
}
