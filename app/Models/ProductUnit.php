<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** A way to sell an item other than its base unit, e.g. Carton = 24 tins. */
class ProductUnit extends Model
{
    use SoftDeletes;

    protected $fillable = ['product_id', 'name', 'base_qty', 'price', 'barcode', 'is_active'];

    protected $casts = ['base_qty' => 'float', 'price' => 'float', 'is_active' => 'boolean'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
