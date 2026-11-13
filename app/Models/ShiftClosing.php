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
        'branch_id',
        'company_id',
        'user_id',
        'shift',
        'work_date',
        'total_order_count',
        'total_item_count',
        'total_order_amount',
        'refund_amount',
        'advance_amount',
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

    public function orders()
    {
        return $this->hasMany(Order::class, 'shift_closing_id');
    }

    /** Sales minus approved refunds and advances: the cash the shift should hand over. */
    public function netAmount(): float
    {
        return round((float) $this->total_order_amount - (float) $this->refund_amount - (float) $this->advance_amount, 2);
    }

    public function details()
    {
        return $this->hasMany(ShiftClosingDetail::class);
    }

    /** The staff member's open shift record, or null. */
    public static function openFor($user): ?self
    {
        return self::where('user_id', $user->id)->whereNull('closing_time')->latest('id')->first();
    }

    /**
     * Open shift record for the user's current shift; created at login so that
     * shifts with no sales still appear in reports. An open record left over
     * from an earlier shift day is closed first.
     */
    public static function openOrStart($user, ?Company $company = null): self
    {
        // staff work for the company of the domain they logged in on
        $company = $company ?? $user->workingCompany();
        $window = $user->currentShiftWindow();
        $workDate = $window?->workDate ?? now()->toDateString();

        $open = self::openFor($user);
        $otherDay = $open && $open->work_date && $open->work_date != $workDate;
        $otherCompany = $open && $company && (int) $open->company_id !== (int) $company->id;
        if ($otherDay || $otherCompany) {
            // an earlier shift day ends at its logout time; switching company ends it now
            $previous = $otherDay
                ? \App\Support\ShiftWindow::forDate($open->shift ?? $user->shift ?? 'AM', \Carbon\Carbon::parse($open->work_date)->toDateString())
                : null;
            $open->update(['closing_time' => $previous?->logoutAt ?? now()]);
            $open = null;
        }

        return $open ?? self::create([
            'user_id'    => $user->id,
            'branch_id'  => $company->branch_id ?? $user->branch_id,
            'company_id' => $company->id ?? $user->company_id,
            'shift'      => $user->shift,
            'work_date'  => $workDate,
        ]);
    }
}
