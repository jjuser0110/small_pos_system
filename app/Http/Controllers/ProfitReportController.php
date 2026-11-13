<?php

namespace App\Http\Controllers;

use App\Models\BatchItem;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Damage;
use App\Models\OrderItemProfit;
use App\Models\ShiftClosing;
use App\Support\ShiftWindow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * For the boss: what each branch, company and shift (AM / PM) earned.
 *
 *   net sales    = sales - refunds
 *   capital      = cost of the goods sold (FIFO cost of what was sold, after refunds)
 *   gross profit = net sales - capital
 *   net profit   = gross profit - cost of approved damage
 *
 * Sales of stock that had no cost (e.g. initial stock) are shown as "sales
 * without cost": their profit is overstated because their capital is unknown.
 * Advances are cash paid to staff (repaid from salary), so they are shown as
 * cash, not as a cost.
 */
class ProfitReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->date_from ?: Carbon::now()->startOfMonth()->toDateString();
        $to = $request->date_to ?: Carbon::now()->toDateString();

        $shifts = ShiftClosing::query()
            ->whereBetween('work_date', [$from, $to])
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->when(in_array($request->shift, ShiftWindow::shifts(), true), fn ($q) => $q->where('shift', $request->shift))
            ->get(['id', 'branch_id', 'company_id', 'shift', 'total_order_count', 'total_order_amount', 'refund_amount', 'advance_amount']);

        // cost and costed sales per shift, from the per-unit profit rows of its (not voided) orders
        $costs = OrderItemProfit::query()
            ->join('orders', 'orders.id', '=', 'order_item_profits.order_id')
            ->whereIn('orders.shift_closing_id', $shifts->pluck('id'))
            ->where('orders.status', '!=', 'Voided')
            ->groupBy('orders.shift_closing_id')
            ->selectRaw('orders.shift_closing_id as shift_id, SUM(order_item_profits.total_cost_price) as cost, SUM(order_item_profits.total_selling_price) as costed_sales')
            ->get()->keyBy('shift_id');

        $damage = Damage::where('status', 'approved')
            ->whereBetween(DB::raw('DATE(status_at)'), [$from, $to])
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->groupBy('company_id')
            ->selectRaw('company_id, SUM(cost_amount) as cost')
            ->pluck('cost', 'company_id');

        $companies = Company::with('branch')->get()->keyBy('id');
        $rows = $this->rows($shifts, $costs);
        $byCompany = $this->companyTotals($rows, $damage, $companies);
        $byBranch = $byCompany->groupBy(fn ($c) => $c->branch_id)->map(fn ($list, $branchId) => $this->sum($list, [
            'branch_id' => $branchId, 'branch' => $list->first()->branch,
        ]));
        $overall = $this->sum($byCompany, []);

        return view('profit_report.index', [
            'date_from'  => $from,
            'date_to'    => $to,
            'branches'   => Branch::orderBy('branch_name')->get(),
            'companies'  => Company::orderBy('company_name')->get(),
            'rows'       => $rows,
            'byCompany'  => $byCompany,
            'byBranch'   => $byBranch,
            'overall'    => $overall,
            'topItems'   => $this->items($shifts, 'desc'),
            'lowItems'   => $this->items($shifts, 'asc'),
            'stockValue' => $this->stockValue($request),
        ]);
    }

    /** One row per branch + company + shift (AM / PM) over the period. */
    private function rows(Collection $shifts, Collection $costs): Collection
    {
        return $shifts->groupBy(fn ($s) => $s->company_id . '|' . $s->shift)->map(function ($list) use ($costs) {
            $sales = $list->sum('total_order_amount');
            $refunds = $list->sum('refund_amount');
            $cost = $list->sum(fn ($s) => (float) ($costs[$s->id]->cost ?? 0));
            $costedSales = $list->sum(fn ($s) => (float) ($costs[$s->id]->costed_sales ?? 0));
            $netSales = $sales - $refunds;

            return (object) [
                'branch_id'      => $list->first()->branch_id,
                'company_id'     => $list->first()->company_id,
                'shift'          => $list->first()->shift ?? '-',
                'shifts'         => $list->count(),
                'orders'         => $list->sum('total_order_count'),
                'sales'          => $sales,
                'refunds'        => $refunds,
                'net_sales'      => $netSales,
                'cost'           => $cost,
                'gross'          => $netSales - $cost,
                'uncosted_sales' => max(0, $netSales - $costedSales),
                'advances'       => $list->sum('advance_amount'),
                'damage'         => 0,
            ];
        })->sortBy(['branch_id', 'company_id', 'shift'])->values();
    }

    private function companyTotals(Collection $rows, Collection $damage, Collection $companies): Collection
    {
        $ids = $rows->pluck('company_id')->merge($damage->keys())->unique();

        return $ids->map(function ($companyId) use ($rows, $damage, $companies) {
            $company = $companies[$companyId] ?? null;
            $total = $this->sum($rows->where('company_id', $companyId), [
                'company_id' => $companyId,
                'company'    => $company,
                'branch_id'  => $company?->branch_id,
                'branch'     => $company?->branch,
                'shiftRows'  => $rows->where('company_id', $companyId)->values(),
            ]);
            $total->damage = (float) ($damage[$companyId] ?? 0);
            $total->net = $total->gross - $total->damage;
            return $total;
        })->sortBy(fn ($c) => ($c->branch->branch_name ?? '') . ' ' . ($c->company->company_name ?? ''))->values();
    }

    private function sum(Collection $list, array $extra): object
    {
        $t = (object) array_merge([
            'shifts'   => $list->sum('shifts'),   'orders'  => $list->sum('orders'),
            'sales'    => $list->sum('sales'),    'refunds' => $list->sum('refunds'),
            'net_sales'=> $list->sum('net_sales'),'cost'    => $list->sum('cost'),
            'gross'    => $list->sum('gross'),    'uncosted_sales' => $list->sum('uncosted_sales'),
            'advances' => $list->sum('advances'), 'damage'  => $list->sum('damage'),
        ], $extra);
        $t->net = $t->gross - $t->damage;
        $t->margin = $t->net_sales > 0 ? round($t->gross / $t->net_sales * 100, 1) : null;
        return $t;
    }

    /** Items by profit earned in these shifts (best first, or worst first). */
    private function items(Collection $shifts, string $direction): Collection
    {
        return OrderItemProfit::query()
            ->join('orders', 'orders.id', '=', 'order_item_profits.order_id')
            ->whereIn('orders.shift_closing_id', $shifts->pluck('id'))
            ->where('orders.status', '!=', 'Voided')
            ->groupBy('order_item_profits.product_id')
            ->selectRaw('order_item_profits.product_id, SUM(order_item_profits.quantity) as qty, SUM(order_item_profits.total_selling_price) as sales, SUM(order_item_profits.total_earning) as profit')
            ->orderBy('profit', $direction)
            ->with('product.company')
            ->limit($direction === 'desc' ? 10 : 5)
            ->get();
    }

    /** What the stock on hand cost, per company (completed stock entries only). */
    private function stockValue(Request $request): Collection
    {
        return BatchItem::query()
            ->where('balance', '>', 0)
            ->whereHas('batch', fn ($q) => $q->where('status', 'Completed'))
            ->when($request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->filled('company_id'), fn ($q) => $q->where('company_id', $request->company_id))
            ->groupBy('company_id')
            ->selectRaw('company_id, SUM(balance * cost_per_unit) as value')
            ->with('company.branch')
            ->get()
            ->sortByDesc('value')->values();
    }
}
