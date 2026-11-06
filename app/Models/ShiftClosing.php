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
    public static function openOrStart($user): self
    {
        $window = $user->currentShiftWindow();
        $workDate = $window?->workDate ?? now()->toDateString();

        $open = self::openFor($user);
        if ($open && $open->work_date && $open->work_date != $workDate) {
            $previous = \App\Support\ShiftWindow::forDate($open->shift ?? $user->shift ?? 'AM', \Carbon\Carbon::parse($open->work_date)->toDateString());
            $open->update(['closing_time' => $previous?->logoutAt ?? now()]);
            $open = null;
        }

        return $open ?? self::create([
            'user_id'    => $user->id,
            'branch_id'  => $user->branch_id,
            'company_id' => $user->company_id,
            'shift'      => $user->shift,
            'work_date'  => $workDate,
        ]);
    }
}
