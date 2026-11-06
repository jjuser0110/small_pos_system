<?php

namespace App\Http\Middleware;

use App\Models\StaffAttendance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Staff may only be logged in on their company's domain and inside their
 * shift window (15 min before start to 15 min after end). Anyone outside
 * that is logged out and their attendance is closed as "auto".
 */
class CheckStaffAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->isStaff()) {
            $problem = $user->staffAccessProblem($request->getHost());

            if ($problem) {
                StaffAttendance::closeStale($user);
                $open = StaffAttendance::where('user_id', $user->id)->whereNull('clock_out')->latest('id')->first();
                $open?->update(['clock_out' => now(), 'clock_out_reason' => 'auto']);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->expectsJson()) {
                    return response()->json(['status' => 'error', 'message' => $problem, 'logged_out' => true], 401);
                }
                return redirect()->route('login')->withErrors($problem);
            }
        }

        return $next($request);
    }
}
