<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Refund;
use App\Models\RefundItem;
use App\Models\ShiftClosing;
use App\Models\ShiftMethodClosing;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * Counter refunds. The counter picks items and quantities from orders sold in
 * the current shift; the branch manager (or superadmin) approves. On approval
 * the stock goes back onto the batches it was sold from and the amount is
 * taken off that shift's sales.
 */
class RefundController extends Controller
{
    /** Refund requests: staff see their own, branch manager their branch, superadmin/admin all. */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Refund::with(['order', 'items.product', 'company', 'requester', 'approver'])->orderByDesc('id');

        match ((int) $user->role_id) {
            1, 2    => null,
            3       => $query->where('branch_id', $user->branch_id),
            5       => $query->where('requested_by', $user->id),
            default => abort(403),
        };

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return view('refund.index', ['refunds' => $query->get()]);
    }

    /** Counter: orders of my current shift with what can still be refunded. */
    public function create()
    {
        $user = Auth::user();
        $shift = ShiftClosing::openFor($user);

        $orders = $shift
            ? Order::with('items.product')->where('shift_closing_id', $shift->id)->where('status', 'Active')->orderByDesc('id')->get()
            : collect();

        return view('refund.create', compact('orders', 'shift'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'qty'      => 'required|array',
            'qty.*'    => 'nullable|numeric|min:0',
            'reason'   => 'required|string|max:500',
        ]);

        $order = Order::with('items')->findOrFail($request->order_id);
        $shift = ShiftClosing::openFor($user);

        // only orders sold in the shift that is running now ("within the working time")
        if (!$shift || (int) $order->shift_closing_id !== (int) $shift->id) {
            return back()->withErrors('Only orders from your current shift can be refunded.');
        }
        if ($order->status !== 'Active') {
            return back()->withErrors('This order is ' . strtolower($order->status) . '.');
        }

        $lines = [];
        foreach ($order->items as $item) {
            $qty = round((float) ($request->qty[$item->id] ?? 0), 4);
            if ($qty <= 0) {
                continue;
            }
            if ($qty > $item->refundableQuantity()) {
                return back()->withErrors("Only {$item->refundableQuantity()} of " . ($item->product->product_name ?? 'this item') . ' can still be refunded.');
            }
            $lines[] = [$item, $qty];
        }
        if (!$lines) {
            return back()->withErrors('Enter a quantity for at least one item.');
        }

        DB::transaction(function () use ($order, $shift, $lines, $user, $request) {
            $refund = Refund::create([
                'order_id'         => $order->id,
                'shift_closing_id' => $shift->id,
                'branch_id'        => $order->branch_id,
                'company_id'       => $order->company_id,
                'amount'           => 0,
                'reason'           => $request->reason,
                'status'           => 'pending',
                'requested_by'     => $user->id,
            ]);

            $total = 0;
            foreach ($lines as [$item, $qty]) {
                $amount = round($item->single_price * $qty, 2);
                $total += $amount;
                RefundItem::create([
                    'refund_id'     => $refund->id,
                    'order_item_id' => $item->id,
                    'product_id'    => $item->product_id,
                    'quantity'      => $qty,
                    'single_price'  => $item->single_price,
                    'amount'        => $amount,
                ]);
            }
            $refund->update(['amount' => round($total, 2)]);
        });

        return redirect()->route('refund.index')->withSuccess('Refund request sent. Waiting for the superadmin to approve.');
    }

    public function approve(Refund $refund, StockService $stock)
    {
        if (!$refund->canBeDecidedBy(Auth::user())) {
            abort(403);
        }

        try {
            DB::transaction(function () use ($refund, $stock) {
                $refund = Refund::lockForUpdate()->findOrFail($refund->id);
                if ($refund->status !== 'pending') {
                    throw new RuntimeException('This refund was already processed.');
                }
                $order = Order::lockForUpdate()->findOrFail($refund->order_id);
                if ($order->status !== 'Active') {
                    throw new RuntimeException('This order is ' . strtolower($order->status) . '.');
                }

                foreach ($refund->items as $line) {
                    $item = OrderItem::with('category')->lockForUpdate()->findOrFail($line->order_item_id);
                    $left = (float) $item->quantity - (float) $item->refunded_quantity;
                    if ($line->quantity - $left > 0.00001) {
                        throw new RuntimeException('Refund is more than what is left on the order.');
                    }

                    // refunded sale units back to stock in base units (e.g. 1 carton = 24 tins)
                    $stock->returnSale($item, (float) $line->quantity * $item->baseQty(), 'refund_in', "Refund #{$refund->id} ({$order->order_no})", $refund);
                    $item->update(['refunded_quantity' => round($item->refunded_quantity + $line->quantity, 4)]);
                }

                // take the money off the shift it was sold in
                if ($shift = ShiftClosing::lockForUpdate()->find($refund->shift_closing_id)) {
                    $shift->update([
                        'refund_amount'    => round($shift->refund_amount + $refund->amount, 2),
                        'total_item_count' => max(0, $shift->total_item_count - $refund->items->sum('quantity')),
                    ]);
                    ShiftMethodClosing::where('shift_closing_id', $shift->id)
                        ->where('payment_method', $order->payment_method)
                        ->decrement('amount', (float) $refund->amount);
                    foreach ($refund->items as $line) {
                        \App\Models\ShiftClosingDetail::where('shift_closing_id', $shift->id)
                            ->where('category_id', OrderItem::find($line->order_item_id)?->category_id)
                            ->decrement('amount', (float) $line->amount);
                    }
                }

                $fullyRefunded = $order->items()->get()->every(fn ($i) => (float) $i->refunded_quantity >= (float) $i->quantity - 0.00001);
                if ($fullyRefunded) {
                    $order->update(['status' => 'Refunded']);
                }

                $refund->update(['status' => 'approved', 'approved_by' => Auth::id(), 'status_at' => Carbon::now()]);
            });
        } catch (Throwable $e) {
            return back()->withErrors($e->getMessage());
        }

        return back()->withSuccess('Refund approved. Stock returned and amount taken off the shift.');
    }

    public function reject(Request $request, Refund $refund)
    {
        if (!$refund->canBeDecidedBy(Auth::user())) {
            abort(403);
        }

        $updated = Refund::where('id', $refund->id)->where('status', 'pending')->update([
            'status' => 'rejected', 'approved_by' => Auth::id(), 'status_at' => Carbon::now(),
            'reject_reason' => $request->reject_reason,
        ]);

        return $updated ? back()->withSuccess('Refund rejected.') : back()->withErrors('This refund was already processed.');
    }
}
