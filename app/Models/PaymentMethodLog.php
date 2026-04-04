<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethodLog extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'content_id',
        'content_type',
        'payment_method_id',
        'type',
        'prev_amount',
        'amount',
        'total',
        'created_by_id',
    ];
    
    public function content()
    {
        return $this->morphTo();
    }

    public function payment_method()
    {
        return $this->belongsTo('App\Models\PaymentMethod','payment_method_id');
    }

    public function created_by()
    {
        return $this->belongsTo('App\Models\User','created_by_id');
    }


}
