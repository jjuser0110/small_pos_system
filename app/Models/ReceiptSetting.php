<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Receipt header and footer. One per branch; the row without a branch is the
 * default used by branches that have not set their own.
 */
class ReceiptSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'header',
        'footer',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    /** The branch's own setting, else the default, else the oldest one. */
    public static function forBranch($branchId): ?self
    {
        return ($branchId ? self::where('branch_id', $branchId)->first() : null)
            ?? self::whereNull('branch_id')->first()
            ?? self::orderBy('id')->first();
    }
}
