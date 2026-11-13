<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Stock sent from company A's item to company B's item. Two confirmations:
 *   pending_sender   -> company A staff (working at A) confirm it was sent correctly
 *   pending_receiver -> company B staff (working at B) confirm it was received; stock moves
 *   approved / rejected
 * The superadmin may confirm either step so a transfer never gets stuck.
 */
class StockTransfer extends Model
{
    use SoftDeletes;

    public const PENDING_SENDER = 'pending_sender';
    public const PENDING_RECEIVER = 'pending_receiver';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $fillable = [
        'from_branch_id', 'from_company_id', 'from_product_id',
        'to_branch_id', 'to_company_id', 'to_product_id',
        'quantity', 'reason', 'status',
        'requested_by', 'sender_confirmed_by', 'sender_confirmed_at',
        'approved_by', 'status_at', 'reject_reason',
        'received_batch_id',
    ];

    public function fromCompany()     { return $this->belongsTo(Company::class, 'from_company_id'); }
    public function toCompany()       { return $this->belongsTo(Company::class, 'to_company_id'); }
    public function fromProduct()     { return $this->belongsTo(Product::class, 'from_product_id')->withTrashed(); }
    public function toProduct()       { return $this->belongsTo(Product::class, 'to_product_id')->withTrashed(); }
    public function requester()       { return $this->belongsTo(User::class, 'requested_by'); }
    public function senderConfirmer() { return $this->belongsTo(User::class, 'sender_confirmed_by'); }
    public function approver()        { return $this->belongsTo(User::class, 'approved_by'); }

    public function isPending(): bool
    {
        return in_array($this->status, [self::PENDING_SENDER, self::PENDING_RECEIVER], true);
    }

    /** Can this user act on the step the transfer is waiting for? */
    public function canBeDecidedBy(?User $user): bool
    {
        if (!$user || !$this->isPending()) {
            return false;
        }
        if ((int) $user->role_id === 1) {
            return true;
        }
        if (!$user->isStaff()) {
            return false;
        }
        $working = $user->workingCompany()?->id;
        return $this->status === self::PENDING_SENDER
            ? (int) $working === (int) $this->from_company_id
            : (int) $working === (int) $this->to_company_id;
    }

    /** Label of the step waiting, for buttons and lists. */
    public function stepLabel(): string
    {
        return match ($this->status) {
            self::PENDING_SENDER   => 'Waiting: ' . ($this->fromCompany->company_code ?? 'sender') . ' confirms sent',
            self::PENDING_RECEIVER => 'Waiting: ' . ($this->toCompany->company_code ?? 'receiver') . ' confirms received',
            self::APPROVED         => 'Received',
            self::REJECTED         => 'Rejected',
            default                => ucfirst((string) $this->status),
        };
    }

    /** Transfers waiting for this user to confirm. */
    public static function pendingFor(?User $user)
    {
        $query = self::query();
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }
        if ((int) $user->role_id === 1) {
            return $query->whereIn('status', [self::PENDING_SENDER, self::PENDING_RECEIVER]);
        }
        if ($user->isStaff() && ($working = $user->workingCompany())) {
            return $query->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('status', self::PENDING_SENDER)->where('from_company_id', $working->id))
                ->orWhere(fn ($q) => $q->where('status', self::PENDING_RECEIVER)->where('to_company_id', $working->id)));
        }
        return $query->whereRaw('1 = 0');
    }
}
