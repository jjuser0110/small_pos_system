<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckShiftTime
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && !Auth::user()->isWithinShift()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/login')->withErrors('Your shift time has ended.');
        }
        return $next($request);
    }
}