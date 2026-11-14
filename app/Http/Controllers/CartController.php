<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Models\RunningNumber;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemProfit;
use App\Models\BatchItem;
use App\Models\ShiftClosing;
use App\Models\ShiftClosingDetail;
use App\Models\ShiftMethodClosing;
use App\Models\PaymentMethod;
use App\Services\StockService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;

class CartController extends Controller
{
    /** Cart lines for the counter and checkout. */
    public function load()
    {
        $cart = Cart::with(['product', 'unit'])
            ->where('user_id', auth()->id())
            ->orderBy('id')
            ->get()
            ->map(fn ($line) => [
                'id'              => $line->id,
                'product_id'      => $line->product_id,
                'product_unit_id' => $line->product_unit_id,
                'product_name'    => ($line->product->product_name ?? '') . ($line->unit ? " ({$line->unit->name})" : ''),
                'single_price'    => $line->single_price,
                'quantity'        => $line->quantity,
                'total_price'     => $line->total_price,
                'stock'           => $line->product->stock_quantity ?? 0,
            ]);

        return response()->json($cart);
    }

    /** Add one of an item, or of one of its sell options (unit_id), to the cart. */
    public function add(Request $request)
    {
        $product = Product::find($request->id);
        if (!$product || !$product->is_active) {
            return response()->json(['status' => 'error', 'message' => 'Item not found'], 404);
        }
        $user = auth()->user();
        if ($user->isStaff() && (int) $product->company_id !== (int) $user->workingCompany()?->id) {
            return response()->json(['status' => 'error', 'message' => 'This item belongs to another company.'], 422);
        }
        $otherCompany = Cart::where('user_id', $user->id)
            ->whereHas('product', fn ($q) => $q->where('company_id', '!=', $product->company_id))->exists();
        if ($otherCompany) {
            return response()->json(['status' => 'error', 'message' => 'One sale can only have items of one company. Checkout or empty the cart first.'], 422);
        }

        $unit = null;
        if ($request->filled('unit_id')) {
            $unit = ProductUnit::where('id', $request->unit_id)->where('product_id', $product->id)->where('is_active', true)->first();
            if (!$unit) {
                return response()->json(['status' => 'error', 'message' => 'Sell option not found'], 404);
            }
        }
        $price = $unit ? (float) $unit->price : (float) $product->selling_price;

        $line = Cart::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->where('product_unit_id', $unit?->id)
            ->first();

        if ($line) {
            $qty = $line->quantity + 1;
            $line->update(['quantity' => $qty, 'total_price' => round($qty * $line->single_price, 2)]);
        } else {
            Cart::create([
                'user_id'         => auth()->id(),
                'product_id'      => $product->id,
                'product_unit_id' => $unit?->id,
                'quantity'        => 1,
                'single_price'    => $price,
                'total_price'     => $price,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function update(Request $request)
    {
        $request->validate(['line_id' => 'required|integer', 'quantity' => 'required|numeric|min:1|max:99999']);

        $line = Cart::where('user_id', auth()->id())->where('id', $request->line_id)->first();
        if (!$line) {
            return response()->json(['status' => 'error', 'message' => 'Cart line not found'], 404);
        }

        $qty = (float) $request->quantity;
        $line->update(['quantity' => $qty, 'total_price' => round($line->single_price * $qty, 2)]);

        return response()->json(['status' => 'ok']);
    }

    public function remove(Request $request)
    {
        Cart::where('user_id', auth()->id())->where('id', $request->line_id)->delete();

        return response()->json(['status' => 'ok']);
    }

    public function place(Request $request)
    {
        $methods = PaymentMethod::pluck('payment_method_name')->all();
        $request->validate([
            'payment_method'  => ['required', \Illuminate\Validation\Rule::in($methods)],
            'amount_received' => 'required|numeric|min:0',
        ]);

        try {
            $user = Auth::user();
            $order = null;

            DB::transaction(function () use ($user, $request, &$order) {
                $carts = Cart::where('user_id', $user->id)->get();
                if ($carts->isEmpty()) {
                    throw new \RuntimeException('Cart is empty.');
                }

                // one sale belongs to one company: staff sell for the company whose domain they
                // logged in on; managers at the counter sell for the company the items belong to
                $companyIds = Product::whereIn('id', $carts->pluck('product_id'))->pluck('company_id')->unique();
                if ($companyIds->count() > 1) {
                    throw new \RuntimeException('One sale can only have items of one company.');
                }
                $company = $user->isStaff() ? $user->workingCompany() : \App\Models\Company::find($companyIds->first());
                if (!$company || (int) $companyIds->first() !== (int) $company->id) {
                    throw new \RuntimeException('These items do not belong to the company you are selling for.');
                }

                $shift = ShiftClosing::openOrStart($user, $company);
                $order = $this->createOrder($user, $request, $shift, $company);

                foreach ($carts as $cart) {
                    $this->processItem($order, $cart);
                    $cart->delete();
                }

                $this->finalizeOrder($order, $request, $shift);
            });

            return response()->json([
                'status' => 'success',
                'order_id' => $order->id
            ]);

        } catch (\RuntimeException $e) {
            // e.g. not enough stock: the whole order is rolled back
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['status' => 'error', 'message' => 'Could not place the order. Please try again.'], 500);
        }
    }

    private function createOrder($user, $request, ShiftClosing $shift, \App\Models\Company $company)
    {
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->month;
        $day = $now->day;

        $rn = RunningNumber::firstOrCreate(
            ['name' => 'order', 'year' => $year, 'month' => $month, 'day' => $day],
            ['code' => 'OD', 'no_of_digit_behind' => 4, 'running_no' => 1]
        );

        $order_no = $rn->code .
            $rn->year .
            sprintf('%02d', $rn->month) .
            sprintf('%02d', $rn->day) .
            sprintf('%0'.$rn->no_of_digit_behind.'d', $rn->running_no);

        $order = Order::create([
            'branch_id' => $company->branch_id,
            'company_id' => $company->id,
            'user_id' => $user->id,
            'shift_closing_id' => $shift->id,
            'order_no' => $order_no,
            'order_date' => $now,
        ]);
        $rn->increment('running_no');

        return $order;
    }

    private function processItem($order, $cart)
    {
        $product = Product::findOrFail($cart->product_id);
        $unit = $cart->product_unit_id ? ProductUnit::withTrashed()->find($cart->product_unit_id) : null;
        $baseQty = $unit ? (float) $unit->base_qty : 1.0;          // e.g. Carton = 24 tins

        $item = OrderItem::create([
            'order_id'        => $order->id,
            'branch_id'       => $order->branch_id,
            'company_id'      => $order->company_id,
            'category_id'     => $product->category_id,
            'product_id'      => $product->id,
            'product_unit_id' => $unit?->id,
            'unit_name'       => $unit?->name,
            'base_qty'        => $baseQty,
            'single_price'    => $cart->single_price,
            'quantity'        => $cart->quantity,
            'total_price'     => $cart->total_price,
        ]);

        // stock is in base units; the counter may sell into negative stock
        $slices = app(StockService::class)->stockOut($product, (float) $cart->quantity * $baseQty,
            'stock_out', 'Order ' . $order->order_no, $item, true);

        // one profit row per batch slice, per base unit (unbatched stock has no known cost: no profit row)
        $sellPerBase = (float) $item->single_price / $baseQty;
        foreach ($slices as $slice) {
            if (!$slice['batch_item']) {
                continue;
            }
            $cost = $slice['cost_per_unit'];
            $qty  = $slice['quantity'];

            OrderItemProfit::create([
                'order_id'            => $item->order_id,
                'branch_id'           => $item->branch_id,
                'company_id'          => $item->company_id,
                'order_item_id'       => $item->id,
                'category_id'         => $item->category_id,
                'product_id'          => $item->product_id,
                'batch_id'            => $slice['batch_item']->batch_id,
                'batch_item_id'       => $slice['batch_item']->id,
                'cost_price'          => $cost,
                'selling_price'       => round($sellPerBase, 4),
                'earning'             => round($sellPerBase - $cost, 4),
                'quantity'            => $qty,
                'total_cost_price'    => round($cost * $qty, 2),
                'total_selling_price' => round($sellPerBase * $qty, 2),
                'total_earning'       => round(($sellPerBase - $cost) * $qty, 2),
            ]);
        }
    }

    private function finalizeOrder($order, $request, ShiftClosing $shift)
    {
        $order->load('items.category');
        $totalProduct = $order->items->count();
        $totalQty     = $order->items->sum('quantity');
        $totalPrice   = round($order->items->sum('total_price'), 2);
        $received     = (float) $request->amount_received;

        if ($received + 0.001 < $totalPrice) {
            throw new \RuntimeException('Amount received is less than the total.');
        }

        $order->update([
            'total_product'   => $totalProduct,
            'total_item'      => $totalQty,
            'total_price'     => $totalPrice,
            'tax_amount'      => 0,
            'final_total'     => $totalPrice,
            'payment_method'  => $request->payment_method,
            'amount_received' => $received,
            'change'          => round($received - $totalPrice, 2),
        ]);

        // one more order on this shift
        $shift->update([
            'total_order_count'  => $shift->total_order_count + 1,
            'total_item_count'   => $shift->total_item_count + $totalQty,
            'total_order_amount' => round($shift->total_order_amount + $totalPrice, 2),
            'first_sale_time'    => $shift->first_sale_time ?? Carbon::now(),
        ]);

        foreach ($order->items as $item) {
            $detail = ShiftClosingDetail::firstOrNew([
                'shift_closing_id' => $shift->id,
                'category_id'      => $item->category_id,
                'product_id'       => null,
            ]);
            $detail->amount = round(($detail->amount ?? 0) + $item->total_price, 2);
            $detail->save();
        }

        $method = ShiftMethodClosing::firstOrNew([
            'shift_closing_id' => $shift->id,
            'payment_method'   => $request->payment_method,
        ]);
        $method->amount = round(($method->amount ?? 0) + $totalPrice, 2);
        $method->save();
    }

    public function empty_cart()
    {
        Cart::where('user_id', auth()->id())->delete();
        return redirect()->back();
    }
}
