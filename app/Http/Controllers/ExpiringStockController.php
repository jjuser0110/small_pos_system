<?php

namespace App\Http\Controllers;

use App\Models\BatchItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Stock that has expired or expires soon (rows with something left in completed stock entries). */
class ExpiringStockController extends Controller
{
    public const DEFAULT_DAYS = 30;

    public function index(Request $request)
    {
        $days = (int) $request->input('days', self::DEFAULT_DAYS);
        $days = max(1, min($days, 365));

        $rows = self::query()
            ->whereDate('expiry_date', '<=', Carbon::today()->addDays($days))
            ->with(['product.uom_dt', 'company', 'batch'])
            ->orderBy('expiry_date')
            ->get();

        return view('expiring_stock.index', ['rows' => $rows, 'days' => $days]);
    }

    /** Counts for the Home card: [expired, expiring within DEFAULT_DAYS]. */
    public static function counts(): array
    {
        $today = Carbon::today();
        return [
            (clone self::query())->whereDate('expiry_date', '<', $today)->count(),
            (clone self::query())->whereDate('expiry_date', '>=', $today)
                ->whereDate('expiry_date', '<=', $today->copy()->addDays(self::DEFAULT_DAYS))->count(),
        ];
    }

    /** Rows with stock left and an expiry date, in the user's branch / picked company. */
    private static function query()
    {
        $user = Auth::user();
        $query = BatchItem::query()
            ->where('balance', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereHas('batch', fn ($q) => $q->where('status', 'Completed'))
            ->when($user->role_id == 3, fn ($q) => $q->where('branch_id', $user->branch_id));

        if ($picked = \App\Support\CompanyPick::id()) {
            $query->where('company_id', $picked);
        }
        return $query;
    }
}
