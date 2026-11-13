<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockLog extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'content_id',
        'content_type',
        'branch_id',
        'company_id',
        'category_id',
        'product_id',
        'user_id',
        'type',
        'description',
        'before_stock',
        'quantity',
        'after_stock',
    ];
    
    protected static function booted(): void
    {
        static::creating(function (StockLog $log) {
            $log->user_id ??= auth()->id();
        });
    }

    public function content()
    {
        return $this->morphTo();
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

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function product()
    {
        return $this->belongsTo('App\Models\Product');
    }
}
