<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StockAdjustment;
use App\Models\Product;
use App\Models\Company;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $login_user = Auth::user();
        if ($login_user->role_id == 5) {
            abort(403);
        }

        $query = StockAdjustment::with(['product', 'company', 'targetCompany', 'requester', 'approver'])
            ->orderBy('id', 'DESC');

        if ($login_user->role_id == 3) {
            $query->where('branch_id', $login_user->branch_id);
        } elseif ($login_user->role_id == 4) {
            $query->where('company_id', $login_user->company_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('stock_adjustment.index')->with('adjustments', $query->get());
    }

    public function store(Request $request)
    {
        $login_user = Auth::user();
        if ($login_user->role_id == 5) {
            abort(403);
        }

        $request->validate([
            'product_id'        => 'required|exists:products,id',
            'type'              => 'required|in:adjust_in,adjust_out,transfer',
            'quantity'          => 'required|numeric|min:0.01|max:99999999',
            'target_company_id' => 'required_if:type,transfer|nullable|exists:companies,id',
            'reason'            => 'required|string|max:500',
        ]);

        $product = Product::findOrFail($request->product_id);

        // can only request for products inside own scope
        if (($login_user->role_id == 3 && $product->branch_id != $login_user->branch_id)
            || ($login_user->role_id == 4 && $product->company_id != $login_user->company_id)) {
            abort(403);
        }

        $target = null;
        if ($request->type == 'transfer') {
            if ($request->target_company_id == $product->company_id) {
                return redirect()->back()->withErrors('Target company must be different from the current company');
            }
            $target = Company::findOrFail($request->target_company_id);
        }

        if ($request->type != 'adjust_in' && $request->quantity > $product->stock_quantity) {
            return redirect()->back()->withErrors('Quantity is more than the current stock (' . $product->stock_quantity . ')');
        }

        StockAdjustment::create([
            'product_id'        => $product->id,
            'branch_id'         => $product->branch_id,
            'company_id'        => $product->company_id,
            'type'              => $request->type,
            'quantity'          => round($request->quantity, 2),
            'target_company_id' => $target->id ?? null,
            'target_branch_id'  => $target->branch_id ?? null,
            'reason'            => $request->reason,
            'status'            => 'pending',
            'requested_by'      => $login_user->id,
        ]);

        return redirect()->route('stock_adjustment.index')->withSuccess('Request submitted, waiting for approval');
    }

    public function approve(StockAdjustment $stock_adjustment)
    {
        $login_user = Auth::user();
        if (!$this->canApprove($stock_adjustment, $login_user)) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($stock_adjustment, $login_user) {
                $adj = StockAdjustment::lockForUpdate()->find($stock_adjustment->id);
                if ($adj->status != 'pending') {
                    throw new Exception('This request was already processed');
                }

                $product = Product::lockForUpdate()->find($adj->product_id);
                $qty     = (float) $adj->quantity;
                $before  = (float) $product->stock_quantity;

                if ($adj->type == 'adjust_in') {
                    $this->move($product, 'adjust_in', $qty, $before, $before + $qty, 'Adjustment #' . $adj->id . ': ' . $adj->reason);

                } else {
                    if ($qty > $before) {
                        throw new Exception('Not enough stock now (current ' . $before . ')');
                    }

                    if ($adj->type == 'adjust_out') {
                        $this->move($product, 'adjust_out', $qty, $before, $before - $qty, 'Adjustment #' . $adj->id . ': ' . $adj->reason);

                    } else { // transfer
                        $target_product = Product::where('company_id', $adj->target_company_id)
                            ->where(function ($q) use ($product) {
                                if ($product->barcode) {
                                    $q->where('barcode', $product->barcode);
                                } else {
                                    $q->where('product_name', $product->product_name);
                                }
                            })
                            ->lockForUpdate()
                            ->first();

                        if (!$target_product) {
                            throw new Exception('No matching product in the target company. Please create it there first (same barcode / name).');
                        }

                        $tbefore = (float) $target_product->stock_quantity;
                        $note = 'Transfer #' . $adj->id . ': ' . $adj->reason;
                        $this->move($product, 'transfer_out', $qty, $before, $before - $qty, $note);
                        $this->move($target_product, 'transfer_in', $qty, $tbefore, $tbefore + $qty, $note);
                    }
                }

                $adj->update([
                    'status'      => 'approved',
                    'approved_by' => $login_user->id,
                    'status_at'   => Carbon::now(),
                ]);
            });
        } catch (Exception $e) {
            return redirect()->back()->withErrors($e->getMessage());
        }

        return redirect()->back()->withSuccess('Request approved, stock updated');
    }

    public function reject(Request $request, StockAdjustment $stock_adjustment)
    {
        $login_user = Auth::user();
        if (!$this->canApprove($stock_adjustment, $login_user)) {
            abort(403);
        }

        $updated = StockAdjustment::where('id', $stock_adjustment->id)
            ->where('status', 'pending')
            ->update([
                'status'        => 'rejected',
                'approved_by'   => $login_user->id,
                'status_at'     => Carbon::now(),
                'reject_reason' => $request->reject_reason,
            ]);

        if (!$updated) {
            return redirect()->back()->withErrors('This request was already processed');
        }
        return redirect()->back()->withSuccess('Request rejected');
    }

    private function canApprove(StockAdjustment $adj, $user)
    {
        return in_array($user->role_id, [1, 2])
            || ($user->role_id == 3 && $adj->branch_id == $user->branch_id);
    }

    /** Writes the stock log and updates the product stock. */
    private function move(Product $product, $type, $qty, $before, $after, $description)
    {
        $product->stockLogs()->create([
            'branch_id'    => $product->branch_id,
            'company_id'   => $product->company_id,
            'category_id'  => $product->category_id,
            'product_id'   => $product->id,
            'type'         => $type,
            'description'  => $description,
            'before_stock' => $before,
            'quantity'     => $qty,
            'after_stock'  => $after,
        ]);
        $product->update(['stock_quantity' => $after]);
    }
}