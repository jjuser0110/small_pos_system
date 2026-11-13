<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Damage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'branch_id', 'company_id', 'shift_closing_id', 'quantity', 'cost_amount', 'reason',
        'status', 'requested_by', 'approved_by', 'status_at', 'reject_reason',
    ];

    public function product()   { return $this->belongsTo(Product::class)->withTrashed(); }
    public function company()   { return $this->belongsTo(Company::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver()  { return $this->belongsTo(User::class, 'approved_by'); }

    public static function pendingFor(?User $user)
    {
        $query = self::where('status', 'pending');
        return (int) ($user->role_id ?? 0) === 1 ? $query : $query->whereRaw('1 = 0');
    }
}
