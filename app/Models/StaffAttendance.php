<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class StaffAttendance extends Model
{
    protected $fillable = [
        'user_id', 
        'branch_id', 
        'company_id', 
        'work_date', 
        'clock_in', 
        'clock_out',
    ];

    public function user()
    {
        return $this->belongsTo('App\Models\User', 'user_id');
    }

    /**
     * Overnight shifts (e.g. 22:00-06:00): a login at 01:00 still belongs
     * to the day the shift started.
     */
    public static function workDateFor($user)
    {
        $now = Carbon::now();

        if ($user->shift_start && $user->shift_end) {
            $start = Carbon::parse($user->shift_start)->format('H:i:s');
            $end   = Carbon::parse($user->shift_end)->format('H:i:s');

            if ($start > $end && $now->format('H:i:s') <= $end) {
                return $now->copy()->subDay()->toDateString();
            }
        }

        return $now->toDateString();
    }

    /** First login of the work day. Later logins the same day change nothing. */
    public static function clockIn($user)
    {
        return self::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => self::workDateFor($user)],
            [
                'branch_id'  => $user->branch_id,
                'company_id' => $user->company_id,
                'clock_in'   => Carbon::now(),
            ]
        );
    }

    /**
     * Closing button. If there is no record yet (e.g. logged in before this
     * feature existed), create one using $fallbackClockIn.
     * Closing twice in a day keeps the latest closing time.
     */
    public static function clockOut($user, $fallbackClockIn = null)
    {
        $record = self::firstOrCreate(
            ['user_id' => $user->id, 'work_date' => self::workDateFor($user)],
            [
                'branch_id'  => $user->branch_id,
                'company_id' => $user->company_id,
                'clock_in'   => $fallbackClockIn ?? Carbon::now(),
            ]
        );

        $record->update(['clock_out' => Carbon::now()]);
        return $record;
    }
}