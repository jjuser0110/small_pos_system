<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShiftMethodClosing extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'shift_closing_id',
        'payment_method',
        'amount',
    ];
}
