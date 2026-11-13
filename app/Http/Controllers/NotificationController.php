<?php

namespace App\Http\Controllers;

use App\Models\StockTransfer;
use Illuminate\Support\Facades\Auth;

/**
 * Things waiting for the logged-in user to act on. Used for the counter
 * banner (polled) and the Home page alerts.
 */
class NotificationController extends Controller
{
    public static function itemsFor($user): array
    {
        $items = [];

        $transfers = StockTransfer::pendingFor($user)->count();
        if ($transfers > 0) {
            $items[] = [
                'key'   => 'transfers',
                'count' => $transfers,
                'text'  => $transfers . ' stock transfer' . ($transfers > 1 ? 's' : '') . ' waiting for you to confirm',
                'url'   => route('stock_transfer.index', ['status' => 'pending']),
            ];
        }

        $more = [
            ['refunds',  \App\Models\Refund::pendingFor($user)->count(),      'refund',  'to approve', 'refund.index'],
            ['damages',  \App\Models\Damage::pendingFor($user)->count(),      'damage report', 'to approve', 'damage.index'],
            ['advances', \App\Models\StaffBorrow::pendingFor($user)->count(), 'advance request', 'to approve', 'staff_borrow.index'],
        ];
        foreach ($more as [$key, $count, $noun, $what, $route]) {
            if ($count > 0) {
                $items[] = [
                    'key'   => $key,
                    'count' => $count,
                    'text'  => "$count $noun" . ($count > 1 ? 's' : '') . " $what",
                    'url'   => route($route, ['status' => 'pending']),
                ];
            }
        }

        return $items;
    }

    public function index()
    {
        return response()->json(['items' => self::itemsFor(Auth::user())]);
    }
}
