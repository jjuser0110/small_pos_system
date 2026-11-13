<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BatchItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'batch_id',
        'branch_id',
        'company_id',
        'category_id',
        'product_id',
        'entry_unit_id',
        'entry_unit',
        'entry_qty',
        'entry_cost',
        'quantity',
        'total_cost',
        'cost_per_unit',
        'balance'
    ];

    public function batch()
    {
        return $this->belongsTo('App\Models\Batch');
    }

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

    public function product()
    {
        return $this->belongsTo('App\Models\Product');
    }

    /** "10 Carton" when keyed in by a sell option, otherwise the base quantity. */
    public function entryLabel(): string
    {
        $qty = rtrim(rtrim(number_format((float) ($this->entry_qty ?? $this->quantity), 2, '.', ''), '0'), '.');
        return $this->entry_unit ? "$qty {$this->entry_unit}" : $qty;
    }

    public function stock_logs()
    {
        return $this->morphMany('App\Models\StockLog', 'content');
    }

    public function profit_items()
    {
        return $this->hasMany('App\Models\OrderItemProfit');
    }
}
