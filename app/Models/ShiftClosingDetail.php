<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftClosingDetail extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'shift_closing_id',
        'category_id',
        'product_id',
        'amount',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
