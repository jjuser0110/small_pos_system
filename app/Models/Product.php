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
        'margin',
        'uom',
        'initial',
        'stock_quantity',
        'is_active',
        'arrangement',
        'connected_product_id',
        'connected_product_quantity',
        'supplier_id',
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

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    /** Sell options (Carton, bundle...), stock always counted in this item's base unit. */
    public function units()
    {
        return $this->hasMany(ProductUnit::class)->where('is_active', true)->orderBy('base_qty');
    }

    public function allUnits()
    {
        return $this->hasMany(ProductUnit::class)->orderBy('base_qty');
    }

    /**
     * Average cost per base unit of the stock on hand (completed stock entries,
     * weighted by what is left). With nothing left, the average of everything
     * ever received. Null when the item has no costed stock at all.
     */
    public function averageCost(): ?float
    {
        $rows = BatchItem::where('product_id', $this->id)
            ->whereHas('batch', fn ($q) => $q->where('status', 'Completed'));

        $onHand = (clone $rows)->where('balance', '>', 0)
            ->selectRaw('SUM(balance * cost_per_unit) as cost, SUM(balance) as qty')->first();
        if ($onHand && (float) $onHand->qty > 0) {
            return round((float) $onHand->cost / (float) $onHand->qty, 4);
        }

        $all = $rows->selectRaw('SUM(total_cost) as cost, SUM(quantity) as qty')->first();
        return $all && (float) $all->qty > 0 ? round((float) $all->cost / (float) $all->qty, 4) : null;
    }
}
