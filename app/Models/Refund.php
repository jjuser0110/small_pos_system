<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_id', 'shift_closing_id', 'branch_id', 'company_id', 'amount', 'reason',
        'status', 'requested_by', 'approved_by', 'status_at', 'reject_reason',
    ];

    public function order()     { return $this->belongsTo(Order::class); }
    public function items()     { return $this->hasMany(RefundItem::class); }
    public function shift()     { return $this->belongsTo(ShiftClosing::class, 'shift_closing_id'); }
    public function company()   { return $this->belongsTo(Company::class); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver()  { return $this->belongsTo(User::class, 'approved_by'); }

    /** Only the superadmin approves or rejects refunds. */
    public function canBeDecidedBy(?User $user): bool
    {
        return $user && (int) $user->role_id === 1;
    }

    public static function pendingFor(?User $user)
    {
        $query = self::where('status', 'pending');
        return (int) ($user->role_id ?? 0) === 1 ? $query : $query->whereRaw('1 = 0');
    }
}
