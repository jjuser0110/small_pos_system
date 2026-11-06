<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Restricts a route to the given role ids.
 *
 * Usage: ->middleware('role:1,2')
 *   1 = Super Admin, 2 = Admin, 3 = Branch Manager,
 *   4 = Company Manager, 5 = Company Staff
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = Auth::user();

        if (!$user || !in_array((int) $user->role_id, array_map('intval', $roles), true)) {
            abort(403, 'Access denied');
        }

        return $next($request);
    }
}
