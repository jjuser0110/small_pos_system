<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'payment_method_name',
        'image_url',
        'amount',
        'is_active',
    ];

    public function payment_method_logs()
    {
        return $this->morphMany('App\Models\PaymentMethodLog', 'content');
    }
}
