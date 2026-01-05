<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'company_id',
        'category_id',
        'product_name',
        'product_code',
        'barcode',
        'selling_price',
        'uom',
        'initial',
        'stock_quantity',
        'is_active',
        'arrangement',
        'connected_product_id',
        'connected_product_quantity',
    ];

    public function branch()
    {
        return $this->belongsTo('App\Models\Branch');
    }

    public function company()
    {
        return $this->belongsTo('App\Models\Company');
    }

    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }

    public function uom_dt()
    {
        return $this->belongsTo('App\Models\Uom','uom');
    }

    public function stockLogs()
    {
        return $this->morphMany('App\Models\StockLog', 'content');
    }

    public function stock_logs()
    {
        return $this->hasMany('App\Models\StockLog');
    }

    public function connected_product()
    {
        return $this->belongsTo(Product::class, 'connected_product_id');
    }

    public function used_in_boxes()
    {
        return $this->hasMany(Product::class, 'connected_product_id');
    }
}
