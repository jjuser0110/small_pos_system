<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'company_id',
        'user_id',
        'order_no',
        'order_date',
        'total_product',
        'total_item',
        'total_price',
        'tax_amount',
        'final_total',
        'payment_method',
        'amount_received',
        'change',
    ];
    
    
    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'branch_id');
    }
    
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(\App\Models\OrderItem::class, 'order_id');
    }
}
