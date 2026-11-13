<?php

namespace App\Models;

use App\Support\ShiftWindow;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per staff member per shift day: first login (clock_in) and last
 * logout (clock_out, with the reason: closing button, normal logout, or auto
 * logout at shift end + grace).
 */
class StaffAttendance extends Model
{
    protected $fillable = [
        'user_id',
        'branch_id',
        'company_id',
        'work_date',
        'shift',
        'clock_in',
        'clock_out',
        'clock_out_reason',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    /** The company the staff member logged in at that day. */
    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id');
    }

    /** The day the staff member's current shift started (PM after midnight = yesterday). */
    public static function workDateFor($user): string
    {
        return $user->currentShiftWindow()?->workDate ?? Carbon::now()->toDateString();
    }

    /** Start (or resume) today's attendance at login. */
    public static function clockIn($user)
    {
        self::closeStale($user);

        $record = self::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => self::workDateFor($user)],
            [
                // the company the staff member logged in at (the login domain)
                'branch_id'  => $user->workingCompany()?->branch_id ?? $user->branch_id,
                'company_id' => $user->workingCompany()?->id ?? $user->company_id,
                'shift'      => $user->shift,
                'clock_in'   => Carbon::now(),
            ]
        );

        // logging in again during the same shift: still working
        if ($record->clock_out) {
            $record->update(['clock_out' => null, 'clock_out_reason' => null]);
        }

        return $record;
    }

    /**
     * Record leaving. $reason: 'closing', 'logout' or 'auto'. The latest leave
     * time of the day is kept. Creates the row if the login was never recorded.
     */
    public static function clockOut($user, string $reason = 'closing', $fallbackClockIn = null, ?Carbon $at = null)
    {
        $record = self::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => self::workDateFor($user)],
            [
                // the company the staff member logged in at (the login domain)
                'branch_id'  => $user->workingCompany()?->branch_id ?? $user->branch_id,
                'company_id' => $user->workingCompany()?->id ?? $user->company_id,
                'shift'      => $user->shift,
                'clock_in'   => $fallbackClockIn ?? Carbon::now(),
            ]
        );

        $record->update(['clock_out' => $at ?? Carbon::now(), 'clock_out_reason' => $reason]);
        return $record;
    }

    /**
     * Rows left open after the shift window ended (browser closed, no logout)
     * are closed at the shift's forced logout time.
     */
    public static function closeStale($user): void
    {
        $open = self::where('user_id', $user->id)->whereNull('clock_out')->get();

        foreach ($open as $row) {
            $window = ShiftWindow::forDate($row->shift ?? $user->shift ?? 'AM', Carbon::parse($row->work_date)->toDateString());
            if ($window && Carbon::now()->greaterThanOrEqualTo($window->logoutAt)) {
                $row->update(['clock_out' => $window->logoutAt, 'clock_out_reason' => 'auto']);
            }
        }
    }

    /** Minutes worked, or null if still open. */
    public function minutesWorked(): ?int
    {
        if (!$this->clock_out) {
            return null;
        }
        return (int) Carbon::parse($this->clock_in)->diffInMinutes(Carbon::parse($this->clock_out));
    }
}
