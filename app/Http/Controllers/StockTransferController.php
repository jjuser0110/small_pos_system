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
 * The branch manager sends stock from company A to company B in one transfer
 * (with its own transfer no). Each line moves A's item to B's item - the two may
 * be named differently. Company A staff confirm it was sent
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
        $query = StockTransfer::with(['fromCompany', 'toCompany', 'items.fromProduct', 'items.toProduct', 'requester', 'senderConfirmer', 'approver'])
            ->orderByDesc('id');

        if ($user->role_id == 3) {
            $query->where(fn ($q) => $q->where('from_branch_id', $user->branch_id)->orWhere('to_branch_id', $user->branch_id));
        } elseif ($user->role_id == 5) {
            $working = $user->workingCompany()?->id;
            $query->where(fn ($q) => $q->where('to_company_id', $working)->orWhere('from_company_id', $working));
        } elseif (!in_array($user->role_id, [1, 2])) {
            abort(403);
        }
        // a picked company sees transfers it sends or receives
        if ($user->role_id != 5 && ($picked = $this->pickedCompanyId())) {
            $query->where(fn ($q) => $q->where('from_company_id', $picked)->orWhere('to_company_id', $picked));
        }

        if ($request->filled('status')) {
            $request->status === 'pending'
                ? $query->whereIn('status', [StockTransfer::PENDING_SENDER, StockTransfer::PENDING_RECEIVER])
                : $query->where('status', $request->status);
        }

        $canSend = in_array($user->role_id, self::SENDER_ROLES);
        // a branch manager moves stock between companies of their own branch
        $companies = $canSend ? $this->transferCompanies() : collect();

        return view('stock_transfer.index', [
            'transfers'     => $query->get(),
            'canSend'       => $canSend,
            'nextNumber'    => $canSend ? StockTransfer::nextNumber() : null,
            'fromCompanies' => $companies,
            'toCompanies'   => $companies,
            'products'      => $canSend
                ? Product::where('is_active', 1)->whereIn('company_id', $companies->pluck('id'))->orderBy('product_name')
                    ->get(['id', 'company_id', 'product_name', 'barcode', 'stock_quantity'])
                : collect(),
        ]);
    }

    /** Both ends of a transfer: every company the user may use, ignoring the top bar pick. */
    private function transferCompanies()
    {
        $user = Auth::user();
        return $user->role_id == 3
            ? \App\Models\Company::where('branch_id', $user->branch_id)->get()
            : \App\Models\Company::all();
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!in_array($user->role_id, self::SENDER_ROLES)) {
            abort(403);
        }

        $request->validate([
            'transfer_no'             => 'nullable|string|max:50|unique:stock_transfers,transfer_no',
            'from_company_id'         => 'required|exists:companies,id',
            'to_company_id'           => 'required|exists:companies,id|different:from_company_id',
            'items'                   => 'required|array|min:1|max:200',
            'items.*.from_product_id' => 'required|exists:products,id',
            'items.*.to_product_id'   => 'required|exists:products,id',
            'items.*.quantity'        => 'required|numeric|min:0.01|max:99999999',
            'reason'                  => 'nullable|string|max:500',
        ], [
            'transfer_no.unique'      => 'This transfer no is already used.',
            'to_company_id.different' => 'Choose a different company to receive the stock.',
            'items.required'          => 'Add at least one item to transfer.',
        ]);

        $fromCompany = $this->authorizeCompanyId($request->from_company_id);
        $toCompany = $this->authorizeCompanyId($request->to_company_id);

        // every line: A's item -> B's item, both inside the sender's scope
        $lines = [];
        $needed = [];
        foreach (array_values($request->items) as $i => $line) {
            $from = Product::findOrFail($line['from_product_id']);
            $to = Product::findOrFail($line['to_product_id']);
            $this->authorizeRecord($from);
            $this->authorizeRecord($to);
            $n = $i + 1;
            if ($from->company_id != $fromCompany->id) {
                return back()->withErrors("Line $n: {$from->product_name} is not in {$fromCompany->company_code}.")->withInput();
            }
            if ($to->company_id != $toCompany->id) {
                return back()->withErrors("Line $n: {$to->product_name} is not in {$toCompany->company_code}.")->withInput();
            }
            $qty = round((float) $line['quantity'], 4);
            $needed[$from->id] = ($needed[$from->id] ?? 0) + $qty;
            $lines[] = ['from' => $from, 'to' => $to, 'quantity' => $qty];
        }

        // enough stock for each item (the same item may be on several lines)
        foreach ($lines as $line) {
            $from = $line['from'];
            if ($needed[$from->id] - $from->stock_quantity > 0.00001) {
                return back()->withErrors("Only {$from->stock_quantity} of {$from->product_name} in stock.")->withInput();
            }
        }

        $transfer = DB::transaction(function () use ($request, $fromCompany, $toCompany, $lines, $user) {
            $transfer = StockTransfer::create([
                'transfer_no'     => trim((string) $request->transfer_no) ?: null,
                'from_branch_id'  => $fromCompany->branch_id,
                'from_company_id' => $fromCompany->id,
                'to_branch_id'    => $toCompany->branch_id,
                'to_company_id'   => $toCompany->id,
                'reason'          => $request->reason,
                'status'          => StockTransfer::PENDING_SENDER,
                'requested_by'    => $user->id,
            ]);
            if (!$transfer->transfer_no) {
                $transfer->update(['transfer_no' => 'TRF' . str_pad($transfer->id, 5, '0', STR_PAD_LEFT)]);
            }
            foreach ($lines as $line) {
                $transfer->items()->create([
                    'from_product_id' => $line['from']->id,
                    'to_product_id'   => $line['to']->id,
                    'quantity'        => $line['quantity'],
                ]);
            }
            return $transfer;
        });

        return redirect()->route('stock_transfer.index')
            ->withSuccess("Transfer {$transfer->transfer_no} created. The sending company's staff must confirm it, then the receiving company's staff.");
    }

    /** How much of each sending item the transfer needs, keyed by product id. */
    private function neededStock(StockTransfer $transfer): array
    {
        $needed = [];
        foreach ($transfer->items as $line) {
            $needed[$line->from_product_id] = ($needed[$line->from_product_id] ?? 0) + (float) $line->quantity;
        }
        return $needed;
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
                    foreach ($this->neededStock($transfer) as $productId => $qty) {
                        $from = Product::findOrFail($productId);
                        if ($qty - $from->stock_quantity > 0.00001) {
                            throw new RuntimeException("Only {$from->stock_quantity} of {$from->product_name} in stock now.");
                        }
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

                // step 2: received - now the stock moves, line by line
                $label = "Transfer {$transfer->transfer_no}";
                $outDesc = "$label to {$transfer->toCompany->company_code}";
                $inDesc = "$label from {$transfer->fromCompany->company_code}";

                // into B: one completed stock entry carrying A's cost, so B's profit stays right
                $batch = Batch::create([
                    'branch_id'     => $transfer->to_branch_id,
                    'company_id'    => $transfer->to_company_id,
                    'batch_no'      => $transfer->transfer_no,
                    'batch_date'    => Carbon::today(),
                    'status'        => 'Completed',
                    'total_product' => $transfer->items->pluck('to_product_id')->unique()->count(),
                    'total_item'    => 0,
                    'total_cost'    => 0,
                ]);

                $totalQty = 0;
                $totalCost = 0;
                foreach ($transfer->items as $line) {
                    $from = Product::findOrFail($line->from_product_id);
                    $to = Product::findOrFail($line->to_product_id);
                    $qty = (float) $line->quantity;

                    // out of A, oldest batch first (fails if A no longer has enough)
                    $slices = $stock->stockOut($from, $qty, 'transfer_out', $outDesc, $transfer);
                    foreach ($slices as $slice) {
                        $stock->stockIn($to, (float) $slice['quantity'], 'transfer_in', $inDesc, $transfer,
                            $slice['cost_per_unit'], $slice['cost_per_unit'] !== null ? $batch : null,
                            $slice['batch_item']?->expiry_date);
                        $totalCost += ($slice['cost_per_unit'] ?? 0) * $slice['quantity'];
                    }
                    $totalQty += $qty;
                }
                $batch->update(['total_item' => $totalQty, 'total_cost' => round($totalCost, 2)]);

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

        if ($updated) {
            \App\Support\ActivityLogger::log('rejected', $stock_transfer, ['status' => [$stock_transfer->status, 'rejected']],
                'Rejected transfer' . ($request->reject_reason ? ': ' . $request->reject_reason : ''));
        }
        return $updated
            ? back()->withSuccess('Transfer rejected.')
            : back()->withErrors('This transfer was already processed.');
    }
}
