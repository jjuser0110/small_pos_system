<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftClosing extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'total_order_count',
        'total_order_amount',
        'first_sale_time',
        'closing_time',
    ];

    public function items()
    {
        return $this->hasMany(\App\Models\ShiftMethodClosing::class, 'shift_closing_id');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}