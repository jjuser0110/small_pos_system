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
        'stock_quantity',
        'is_active',
        'arrangement',
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

    public function stock_logs()
    {
        return $this->hasMany('App\Models\StockLog');
    }
}
