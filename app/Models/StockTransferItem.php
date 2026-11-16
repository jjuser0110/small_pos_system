<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One line of a stock transfer: company A item -> company B item x quantity. */
class StockTransferItem extends Model
{
    protected $fillable = ['stock_transfer_id', 'from_product_id', 'to_product_id', 'quantity'];

    public function transfer()    { return $this->belongsTo(StockTransfer::class, 'stock_transfer_id'); }
    public function fromProduct() { return $this->belongsTo(Product::class, 'from_product_id')->withTrashed(); }
    public function toProduct()   { return $this->belongsTo(Product::class, 'to_product_id')->withTrashed(); }
}
