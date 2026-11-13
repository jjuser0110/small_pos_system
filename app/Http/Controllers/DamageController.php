<?php

namespace App\Http\Controllers;

use App\Models\Damage;
use App\Models\Product;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Damaged goods. Only the branch manager reports them (choosing the company,
 * then the item); only the superadmin approves, which removes the stock
 * (logged as "damage"). Staff can see the list but not report.
 */
class DamageController extends Controller
{
    private const REPORTER_ROLES = [3];
    private const APPROVER_ROLE = 1;

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Damage::with(['product', 'company', 'requester', 'approver'])->orderByDesc('id');

        match ((int) $user->role_id) {
            1, 2    => null,
            3       => $query->where('branch_id', $user->branch_id),
            5       => $query->where('company_id', $user->workingCompany()?->id),
            default => abort(403),
        };
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $canReport = in_array($user->role_id, self::REPORTER_ROLES);

        return view('damage.index', [
            'damages'    => $query->get(),
            'canReport'  => $canReport,
            'canApprove' => (int) $user->role_id === self::APPROVER_ROLE,
            'companies'  => $canReport ? $this->scopedCompanies() : collect(),
            'products'   => $canReport
                ? Product::where('is_active', 1)->where('branch_id', $user->branch_id)->orderBy('product_name')
                    ->get(['id', 'company_id', 'product_name', 'barcode', 'stock_quantity'])
                : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role_id, self::REPORTER_ROLES)) {
            abort(403);
        }

        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|numeric|min:0.01|max:99999999',
            'reason'     => 'required|string|max:500',
        ]);

        $company = $this->authorizeCompanyId($request->company_id);   // a company in the manager's branch
        $product = Product::findOrFail($request->product_id);
        if ((int) $product->company_id !== (int) $company->id) {
            return back()->withErrors('That item does not belong to the chosen company.')->withInput();
        }
        if ($request->quantity > $product->stock_quantity) {
            return back()->withErrors("Only {$product->stock_quantity} of {$product->product_name} in stock.");
        }

        Damage::create([
            'product_id'       => $product->id,
            'branch_id'        => $product->branch_id,
            'company_id'       => $product->company_id,
            'quantity'         => round($request->quantity, 4),
            'reason'           => $request->reason,
            'status'           => 'pending',
            'requested_by'     => $user->id,
        ]);

        return back()->withSuccess('Damage reported. Waiting for superadmin approval.');
    }

    public function approve(Damage $damage, StockService $stock)
    {
        if ((int) Auth::user()->role_id !== self::APPROVER_ROLE) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($damage, $stock) {
                $damage = Damage::lockForUpdate()->findOrFail($damage->id);
                if ($damage->status !== 'pending') {
                    throw new RuntimeException('This damage report was already processed.');
                }

                $slices = $stock->stockOut(Product::findOrFail($damage->product_id), (float) $damage->quantity,
                    'damage', "Damage #{$damage->id}: {$damage->reason}", $damage);

                $damage->update([
                    'status'      => 'approved',
                    'approved_by' => Auth::id(),
                    'status_at'   => Carbon::now(),
                    // what the damaged stock cost, for the profit report (stock without a cost counts 0)
                    'cost_amount' => round(collect($slices)->sum(fn ($s) => ($s['cost_per_unit'] ?? 0) * $s['quantity']), 2),
                ]);
            });
        } catch (Throwable $e) {
            return back()->withErrors($e->getMessage());
        }

        return back()->withSuccess('Damage approved. Stock removed.');
    }

    public function reject(Request $request, Damage $damage)
    {
        if ((int) Auth::user()->role_id !== self::APPROVER_ROLE) {
            abort(403);
        }

        $updated = Damage::where('id', $damage->id)->where('status', 'pending')->update([
            'status' => 'rejected', 'approved_by' => Auth::id(), 'status_at' => Carbon::now(),
            'reject_reason' => $request->reject_reason,
        ]);

        return $updated ? back()->withSuccess('Damage report rejected.') : back()->withErrors('This damage report was already processed.');
    }
}
