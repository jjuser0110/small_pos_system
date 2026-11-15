<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/** Basic browser protections on every page. */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // pages cannot be shown inside another website's frame (click-jacking)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        // browsers must not guess file types (stops uploaded/served files running as scripts)
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        // do not leak full page addresses to other sites
        $response->headers->set('Referrer-Policy', 'same-origin');

        return $response;
    }
}
