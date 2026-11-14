<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\OrderItem;
use App\Models\ShiftClosing;
use App\Support\ShiftWindow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Per-shift record: orders, items sold, sales, refunds, advances, net, and the
 * Cash / QR split. One row per staff shift (opened at login).
 */
class ShiftClosingController extends Controller
{
    public function index(Request $request)
    {
        [$shifts, $filters] = $this->filtered($request);

        return view('shift_closing.index', array_merge($filters, [
            'shift_closing' => $shifts,
            'totals'        => $this->totals($shifts),
            'companies'     => $this->companiesForFilter(),
        ]));
    }

    public function print(Request $request)
    {
        [$shifts, $filters] = $this->filtered($request);

        return view('shift_closing.print', array_merge($filters, [
            'shift_closing' => $shifts,
            'totals'        => $this->totals($shifts),
            'login_user'    => Auth::user(),
        ]));
    }

    public function view(ShiftClosing $shift_closing)
    {
        // staff may view their own shifts; managers within their scope
        $this->authorizeRecord($shift_closing, 'user_id');

        // items sold in this shift (refunded quantities taken off)
        $items = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('shift_closing_id', $shift_closing->id)->where('status', '!=', 'Voided'))
            // cartons and tins are listed separately, never added together
            ->select('product_id', 'unit_name',
                DB::raw('SUM(quantity - refunded_quantity) as qty'),
                DB::raw('SUM((quantity - refunded_quantity) * single_price) as amount'))
            ->groupBy('product_id', 'unit_name')
            ->with('product:id,product_name')
            ->orderByDesc('qty')
            ->get();

        $shift_closing->load(['user', 'items', 'details.category', 'details.product']);

        return view('shift_closing.view', compact('shift_closing', 'items'));
    }

    /** Shift records in the user's scope, filtered by date, company and AM/PM. */
    private function filtered(Request $request): array
    {
        $date_from = $request->date_from ?: Carbon::now()->toDateString();
        $date_to   = $request->date_to ?: Carbon::now()->toDateString();

        $login_user = Auth::user();
        $query = ShiftClosing::with(['user', 'items'])
            // every shift has a work date (old ones were filled in), so the index on it is used
            ->whereBetween('work_date', [$date_from, $date_to])
            ->orderByDesc('work_date')->orderByDesc('id');

        match ((int) $login_user->role_id) {
            1, 2    => null,
            3       => $query->where('branch_id', $login_user->branch_id),
            5       => $query->where('user_id', $login_user->id),
            default => abort(403),
        };

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('shift') && in_array($request->shift, ShiftWindow::shifts(), true)) {
            $query->where('shift', $request->shift);
        }

        $shifts = $query->get();
        $companyNames = Company::whereIn('id', $shifts->pluck('company_id')->unique())->pluck('company_code', 'id');
        $shifts->each(fn ($s) => $s->company_code = $companyNames[$s->company_id] ?? '');

        return [$shifts, [
            'date_from'  => $date_from,
            'date_to'    => $date_to,
            'company_id' => $request->company_id,
            'shift'      => $request->shift,
        ]];
    }

    private function totals($shifts): array
    {
        $methods = $shifts->flatMap->items->groupBy('payment_method')->map->sum('amount');

        return [
            'orders'   => $shifts->sum('total_order_count'),
            'items'    => $shifts->sum('total_item_count'),
            'sales'    => $shifts->sum('total_order_amount'),
            'refunds'  => $shifts->sum('refund_amount'),
            'advances' => $shifts->sum('advance_amount'),
            'net'      => $shifts->sum(fn ($s) => $s->netAmount()),
            'methods'  => $methods,
        ];
    }

    private function companiesForFilter()
    {
        $user = Auth::user();
        return in_array($user->role_id, [1, 2, 3]) ? $this->scopedCompanies() : collect();
    }
}
