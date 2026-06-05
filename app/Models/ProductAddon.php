<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAddon extends Model
{
    protected $fillable = [
        'product_id',
        'addon_name',
        'addon_price',
        'is_active',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}