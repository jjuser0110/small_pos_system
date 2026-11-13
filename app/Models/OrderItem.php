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
        'product_unit_id',
        'unit_name',
        'base_qty',
        'single_price',
        'quantity',
        'refunded_quantity',
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

    /** Name as sold, e.g. "Cola (Carton)". */
    public function displayName(): string
    {
        $name = $this->product->product_name ?? '';
        return $this->unit_name ? "$name ({$this->unit_name})" : $name;
    }

    /** Base units per sale unit (1 when sold as the item itself). */
    public function baseQty(): float
    {
        return (float) ($this->base_qty ?: 1);
    }

    public function profit_items()
    {
        return $this->hasMany(OrderItemProfit::class);
    }

    /** Quantity still refundable: sold, minus refunded, minus waiting in pending refunds. */
    public function refundableQuantity(): float
    {
        $pending = RefundItem::where('order_item_id', $this->id)
            ->whereHas('refund', fn ($q) => $q->where('status', 'pending'))
            ->sum('quantity');

        return max(0, round((float) $this->quantity - (float) $this->refunded_quantity - (float) $pending, 4));
    }
}
