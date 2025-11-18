<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'branch_id',
        'company_id',
        'category_id',
        'product_id',
        'single_price',
        'quantity',
        'total_price',
    ];

    public function order()
    {
        return $this->belongsTo(\App\Models\Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }
    
    public function category()
    {
        return $this->belongsTo(\App\Models\Category::class, 'category_id');
    }
    
    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'branch_id');
    }
    
    public function company()
    {
        return $this->belongsTo(\App\Models\Company::class, 'company_id');
    }
}
