<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * One occurrence of a staff shift (e.g. "PM shift starting 8 Nov") with its
 * grace window: login allowed from start - grace, logout forced at end + grace.
 */
final class ShiftWindow
{
    public function __construct(
        public readonly string $shift,
        public readonly string $workDate,   // Y-m-d of the day the shift starts
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly Carbon $loginFrom,
        public readonly Carbon $logoutAt,
    ) {
    }

    public static function shifts(): array
    {
        return array_keys(config('fsmart.shifts'));
    }

    /** The occurrence of $shift that starts on $workDate. */
    public static function forDate(string $shift, string $workDate): ?self
    {
        $def = config("fsmart.shifts.$shift");
        if (!$def) {
            return null;
        }
        $grace = (int) config('fsmart.grace_minutes', 15);

        $start = Carbon::parse("$workDate {$def['start']}");
        $end = Carbon::parse("$workDate {$def['end']}");
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay(); // runs past midnight
        }

        return new self($shift, $workDate, $start, $end,
            $start->copy()->subMinutes($grace), $end->copy()->addMinutes($grace));
    }

    /** The occurrence of $shift whose login window contains $now, if any. */
    public static function current(?string $shift, ?Carbon $now = null): ?self
    {
        if (!$shift) {
            return null;
        }
        $now = $now ? $now->copy() : Carbon::now();

        // a shift that started yesterday may still be running (PM), so check both days
        foreach ([$now->copy()->subDay(), $now] as $day) {
            $window = self::forDate($shift, $day->toDateString());
            if ($window && $window->contains($now)) {
                return $window;
            }
        }

        return null;
    }

    /** The next occurrence's login time, for "you can log in from ..." messages. */
    public static function nextLoginFrom(string $shift, ?Carbon $now = null): ?Carbon
    {
        $now = $now ? $now->copy() : Carbon::now();
        foreach ([$now, $now->copy()->addDay()] as $day) {
            $window = self::forDate($shift, $day->toDateString());
            if ($window && $window->loginFrom->greaterThan($now)) {
                return $window->loginFrom;
            }
        }

        return null;
    }

    public function contains(Carbon $moment): bool
    {
        return $moment->greaterThanOrEqualTo($this->loginFrom) && $moment->lessThan($this->logoutAt);
    }

    public function label(): string
    {
        return $this->shift . ' ' . $this->start->format('H:i') . '–' . $this->end->format('H:i');
    }
}
