<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItemProfit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'order_item_id',
        'branch_id',
        'company_id',
        'category_id',
        'product_id',
        'batch_id',
        'batch_item_id',
        'cost_price',
        'selling_price',
        'earning',
        'quantity',
        'total_cost_price',
        'total_selling_price',
        'total_earning',
    ];

    public function order()
    {
        return $this->belongsTo(\App\Models\Order::class, 'order_id');
    }

    public function order_item()
    {
        return $this->belongsTo(\App\Models\OrderItem::class, 'order_item_id');
    }

    public function batch()
    {
        return $this->belongsTo(\App\Models\Batch::class, 'batch_id');
    }

    public function batch_item()
    {
        return $this->belongsTo(\App\Models\BatchrItem::class, 'batch_item_id');
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
    
    public function stock_logs()
    {
        return $this->morphMany('App\Models\StockLog', 'content');
    }
}
