<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockTransfer extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'from_branch_id', 'from_company_id', 'from_product_id',
        'to_branch_id', 'to_company_id', 'to_product_id',
        'quantity', 'reason', 'status',
        'requested_by', 'approved_by', 'status_at', 'reject_reason',
        'received_batch_id',
    ];

    public function fromCompany() { return $this->belongsTo(Company::class, 'from_company_id'); }
    public function toCompany()   { return $this->belongsTo(Company::class, 'to_company_id'); }
    public function fromProduct() { return $this->belongsTo(Product::class, 'from_product_id')->withTrashed(); }
    public function toProduct()   { return $this->belongsTo(Product::class, 'to_product_id')->withTrashed(); }
    public function requester()   { return $this->belongsTo(User::class, 'requested_by'); }
    public function approver()    { return $this->belongsTo(User::class, 'approved_by'); }

    /** Receiving side: staff of the target company, or the branch manager of its branch. */
    public function canBeDecidedBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }
        return match ((int) $user->role_id) {
            5       => $user->company_id == $this->to_company_id,
            3       => $user->branch_id == $this->to_branch_id,
            default => false,
        };
    }

    /** Pending transfers waiting for this user to accept. */
    public static function pendingFor(?User $user)
    {
        $query = self::where('status', 'pending');

        return match ((int) ($user->role_id ?? 0)) {
            5       => $query->where('to_company_id', $user->company_id),
            3       => $query->where('to_branch_id', $user->branch_id),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
