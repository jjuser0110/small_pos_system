<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Uom extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'branch_id',
        'uom_name',
        'uom_unit',
    ];

    public function products()
    {
        return $this->hasMany('App\Models\Product','uom');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    /** Units a branch may use: its own, plus shared ones (no branch). */
    public function scopeForBranch($query, $branchId)
    {
        return $query->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
    }
}
