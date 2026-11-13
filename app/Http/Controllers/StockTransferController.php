<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * The branch manager sends stock from company A's item to company B's item
 * (the two may be named differently). Company A staff confirm it was sent
 * correctly, then company B staff confirm it was received - only then does
 * stock leave A and arrive at B (at A's cost). The superadmin may confirm
 * either step.
 */
class StockTransferController extends Controller
{
    private const SENDER_ROLES = [1, 2, 3];

    public function index(Request $request)
    {
        $user = Auth::user();
        $query = StockTransfer::with(['fromCompany', 'toCompany', 'fromProduct', 'toProduct', 'requester', 'senderConfirmer', 'approver'])
            ->orderByDesc('id');

        if ($user->role_id == 3) {
            $query->where(fn ($q) => $q->where('from_branch_id', $user->branch_id)->orWhere('to_branch_id', $user->branch_id));
        } elseif ($user->role_id == 5) {
            $working = $user->workingCompany()?->id;
            $query->where(fn ($q) => $q->where('to_company_id', $working)->orWhere('from_company_id', $working));
        } elseif (!in_array($user->role_id, [1, 2])) {
            abort(403);
        }

        if ($request->filled('status')) {
            $request->status === 'pending'
                ? $query->whereIn('status', [StockTransfer::PENDING_SENDER, StockTransfer::PENDING_RECEIVER])
                : $query->where('status', $request->status);
        }

        $canSend = in_array($user->role_id, self::SENDER_ROLES);
        // a branch manager moves stock between companies of their own branch
        $companies = $canSend ? $this->scopedCompanies() : collect();

        return view('stock_transfer.index', [
            'transfers'     => $query->get(),
            'canSend'       => $canSend,
            'fromCompanies' => $companies,
            'toCompanies'   => $companies,
            'products'      => $canSend
                ? Product::where('is_active', 1)->whereIn('company_id', $companies->pluck('id'))->orderBy('product_name')
                    ->get(['id', 'company_id', 'product_name', 'barcode', 'stock_quantity'])
                : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role_id, self::SENDER_ROLES)) {
            abort(403);
        }

        $request->validate([
            'from_product_id' => 'required|exists:products,id',
            'to_product_id'   => 'required|exists:products,id|different:from_product_id',
            'quantity'        => 'required|numeric|min:0.01|max:99999999',
            'reason'          => 'nullable|string|max:500',
        ]);

        $from = Product::findOrFail($request->from_product_id);
        $to = Product::findOrFail($request->to_product_id);

        // both sides must be inside the sender's scope (branch manager: own branch)
        $this->authorizeRecord($from);
        $this->authorizeRecord($to);
        if ($from->company_id == $to->company_id) {
            return back()->withErrors('Choose an item in a different company to receive the stock.')->withInput();
        }
        if ($request->quantity > $from->stock_quantity) {
            return back()->withErrors("Only {$from->stock_quantity} of {$from->product_name} in stock.")->withInput();
        }

        StockTransfer::create([
            'from_branch_id'  => $from->branch_id,
            'from_company_id' => $from->company_id,
            'from_product_id' => $from->id,
            'to_branch_id'    => $to->branch_id,
            'to_company_id'   => $to->company_id,
            'to_product_id'   => $to->id,
            'quantity'        => round($request->quantity, 4),
            'reason'          => $request->reason,
            'status'          => StockTransfer::PENDING_SENDER,
            'requested_by'    => $user->id,
        ]);

        return redirect()->route('stock_transfer.index')
            ->withSuccess('Transfer created. The sending company\'s staff must confirm it, then the receiving company\'s staff.');
    }

    /** Confirm the step the transfer is waiting for. */
    public function approve(StockTransfer $stock_transfer, StockService $stock)
    {
        if (!$stock_transfer->canBeDecidedBy(Auth::user())) {
            abort(403);
        }

        try {
            $message = DB::transaction(function () use ($stock_transfer, $stock) {
                $transfer = StockTransfer::lockForUpdate()->findOrFail($stock_transfer->id);

                if ($transfer->status === StockTransfer::PENDING_SENDER) {
                    // step 1: the sending company confirms what is going out is correct
                    $from = Product::findOrFail($transfer->from_product_id);
                    if ($transfer->quantity - $from->stock_quantity > 0.00001) {
                        throw new RuntimeException("Only {$from->stock_quantity} of {$from->product_name} in stock now.");
                    }
                    $transfer->update([
                        'status'              => StockTransfer::PENDING_RECEIVER,
                        'sender_confirmed_by' => Auth::id(),
                        'sender_confirmed_at' => Carbon::now(),
                    ]);
                    return 'Confirmed as sent. Waiting for ' . ($transfer->toCompany->company_code ?? 'the receiving company') . ' to confirm it is received.';
                }

                if ($transfer->status !== StockTransfer::PENDING_RECEIVER) {
                    throw new RuntimeException('This transfer was already processed.');
                }

                // step 2: received - now the stock moves
                $from = Product::findOrFail($transfer->from_product_id);
                $to = Product::findOrFail($transfer->to_product_id);
                $qty = (float) $transfer->quantity;
                $label = "Transfer #{$transfer->id}";

                // out of A, oldest batch first (fails if A no longer has enough)
                $slices = $stock->stockOut($from, $qty, 'transfer_out', "$label to {$transfer->toCompany->company_code}", $transfer);

                // into B: a completed stock entry carrying A's cost, so B's profit stays right
                $batch = Batch::create([
                    'branch_id'     => $to->branch_id,
                    'company_id'    => $to->company_id,
                    'batch_no'      => 'TRF' . str_pad($transfer->id, 5, '0', STR_PAD_LEFT),
                    'batch_date'    => Carbon::today(),
                    'status'        => 'Completed',
                    'total_product' => 1,
                    'total_item'    => $qty,
                    'total_cost'    => round(collect($slices)->sum(fn ($s) => ($s['cost_per_unit'] ?? 0) * $s['quantity']), 2),
                ]);

                $inDesc = "$label from {$transfer->fromCompany->company_code}";
                foreach ($slices as $slice) {
                    $stock->stockIn($to, (float) $slice['quantity'], 'transfer_in', $inDesc, $transfer,
                        $slice['cost_per_unit'], $slice['cost_per_unit'] !== null ? $batch : null);
                }

                $transfer->update([
                    'status'            => StockTransfer::APPROVED,
                    'approved_by'       => Auth::id(),
                    'status_at'         => Carbon::now(),
                    'received_batch_id' => $batch->id,
                ]);
                return 'Transfer received. Stock added.';
            });
        } catch (Throwable $e) {
            return back()->withErrors($e->getMessage());
        }

        return back()->withSuccess($message);
    }

    public function reject(Request $request, StockTransfer $stock_transfer)
    {
        if (!$stock_transfer->canBeDecidedBy(Auth::user())) {
            abort(403);
        }

        $updated = StockTransfer::where('id', $stock_transfer->id)
            ->whereIn('status', [StockTransfer::PENDING_SENDER, StockTransfer::PENDING_RECEIVER])
            ->update([
                'status'        => StockTransfer::REJECTED,
                'approved_by'   => Auth::id(),
                'status_at'     => Carbon::now(),
                'reject_reason' => $request->reject_reason,
            ]);

        return $updated
            ? back()->withSuccess('Transfer rejected.')
            : back()->withErrors('This transfer was already processed.');
    }
}
