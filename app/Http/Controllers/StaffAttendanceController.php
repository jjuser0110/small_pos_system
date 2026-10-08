<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StaffAttendance;
use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $login_user = Auth::user();
        if ($login_user->role_id == 5) {
            abort(403);
        }

        $date_from = $request->input('date_from') ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $date_to   = $request->input('date_to')   ?: Carbon::now()->endOfMonth()->format('Y-m-d');

        $query = StaffAttendance::with('user')
            ->whereBetween('work_date', [$date_from, $date_to])
            ->orderBy('work_date', 'DESC');

        // scope by role
        if ($login_user->role_id == 3) {
            $query->where('branch_id', $login_user->branch_id);
        } elseif ($login_user->role_id == 4) {
            $query->where('company_id', $login_user->company_id);
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);   // roles 1, 2
        }

        $records = $query->get();

        // summary per staff
        $summary = $records->groupBy('user_id')->map(function ($rows) {
            $minutes = $rows->filter(fn ($r) => $r->clock_out)
                ->sum(fn ($r) => Carbon::parse($r->clock_in)->diffInMinutes(Carbon::parse($r->clock_out)));

            return (object) [
                'user'       => $rows->first()->user,
                'days'       => $rows->count(),
                'minutes'    => $minutes,
                'not_closed' => $rows->whereNull('clock_out')->count(),
            ];
        })->sortByDesc('days');

        $branches = in_array($login_user->role_id, [1, 2]) ? Branch::all() : collect();

        return view('staff_attendance.index')
            ->with(compact('records', 'summary', 'date_from', 'date_to', 'branches'));
    }

    public static function formatMinutes($minutes)
    {
        $minutes = (int) $minutes;
        return floor($minutes / 60) . 'h ' . ($minutes % 60) . 'm';
    }
}