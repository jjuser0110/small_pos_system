<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Some actions are still plain links (GET): delete buttons, completing a stock
 * entry, emptying the cart, closing a shift. Forms are protected by the CSRF
 * token, but a link could be triggered from another website while a manager is
 * logged in. Browsers tell us where a request came from (Sec-Fetch-Site), so a
 * link-action arriving from another site is refused.
 */
class BlockCrossSiteActions
{
    /** Route names (or name endings) that change data on GET. */
    private const ACTION_SUFFIXES = ['.destroy', '.destroyItem', '.complete'];
    private const ACTION_NAMES = ['empty_cart', 'shift_closing'];

    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('GET') && $request->headers->get('Sec-Fetch-Site') === 'cross-site' && $this->isAction($request)) {
            abort(403, 'This action must be started from inside FsMart.');
        }

        return $next($request);
    }

    private function isAction(Request $request): bool
    {
        $name = (string) optional($request->route())->getName();
        if (in_array($name, self::ACTION_NAMES, true)) {
            return true;
        }
        foreach (self::ACTION_SUFFIXES as $suffix) {
            if (str_ends_with($name, $suffix)) {
                return true;
            }
        }
        return false;
    }
}
