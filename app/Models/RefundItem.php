<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundItem extends Model
{
    protected $fillable = ['refund_id', 'order_item_id', 'product_id', 'quantity', 'single_price', 'amount'];

    public function refund()    { return $this->belongsTo(Refund::class); }
    public function orderItem() { return $this->belongsTo(OrderItem::class); }
    public function product()   { return $this->belongsTo(Product::class)->withTrashed(); }
}
