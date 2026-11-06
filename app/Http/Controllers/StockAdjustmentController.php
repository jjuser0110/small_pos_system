<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Manual stock correction. Only the superadmin may do it; it applies at once
 * and is kept as a record plus a stock log row. (Transfers between companies
 * live in StockTransferController.)
 */
class StockAdjustmentController extends Controller
{
    private const ADJUSTER_ROLE = 1;

    public function index(Request $request)
    {
        $login_user = Auth::user();

        $query = StockAdjustment::with(['product', 'company', 'requester'])->orderBy('id', 'DESC');
        if ($login_user->role_id == 3) {
            $query->where('branch_id', $login_user->branch_id);
        }

        return view('stock_adjustment.index')->with('adjustments', $query->get());
    }

    public function store(Request $request, StockService $stock)
    {
        $login_user = Auth::user();
        if ($login_user->role_id != self::ADJUSTER_ROLE) {
            abort(403, 'Only the superadmin can adjust stock.');
        }

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'type'       => 'required|in:adjust_in,adjust_out',
            'quantity'   => 'required|numeric|min:0.01|max:99999999',
            'reason'     => 'required|string|max:500',
        ]);

        $product = Product::findOrFail($request->product_id);
        $qty = round((float) $request->quantity, 4);

        try {
            DB::transaction(function () use ($request, $product, $qty, $login_user, $stock) {
                $adjustment = StockAdjustment::create([
                    'product_id'   => $product->id,
                    'branch_id'    => $product->branch_id,
                    'company_id'   => $product->company_id,
                    'type'         => $request->type,
                    'quantity'     => $qty,
                    'reason'       => $request->reason,
                    'status'       => 'approved',
                    'requested_by' => $login_user->id,
                    'approved_by'  => $login_user->id,
                    'status_at'    => Carbon::now(),
                ]);

                $note = 'Adjustment #' . $adjustment->id . ': ' . $request->reason;
                if ($request->type === 'adjust_in') {
                    $stock->stockIn($product, $qty, 'adjust_in', $note, $adjustment);
                } else {
                    $stock->stockOut($product, $qty, 'adjust_out', $note, $adjustment);
                }
            });
        } catch (Throwable $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

        return redirect()->back()->withSuccess('Stock adjusted and logged.');
    }
}
