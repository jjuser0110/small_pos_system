<?php

namespace App\Http\Middleware;

use App\Support\Preferences;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/** Use the user's chosen language for this request. */
class ApplyPreferences
{
    public function handle(Request $request, Closure $next)
    {
        App::setLocale(Preferences::locale());
        \Carbon\Carbon::setLocale(Preferences::locale() === 'zh_CN' ? 'zh_CN' : Preferences::locale());

        return $next($request);
    }
}
