<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use App\Models\RunningNumber;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemProfit;
use App\Models\BatchItem;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CartController extends Controller
{
    public function load()
    {
        $cart = Cart::with('product')
            ->where('user_id', auth()->id())
            ->get();
        foreach($cart as $ca){
            $ca->product_name = $ca->product->product_name;
            $ca->stock = $ca->product->stock_quantity;
        }

        return response()->json($cart);
    }

    public function add(Request $request)
    {
        $product = Product::find($request->id);
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
        }

        $item = Cart::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();

        if ($item) {
            $newQty = $item->quantity + 1;
            $item->update([
                'quantity' => $newQty,
                'total_price' => $newQty * $item->single_price,
            ]);
        } else {
            Cart::create([
                'user_id' => auth()->id(),
                'product_id' => $product->id,
                'quantity' => 1,
                'single_price' => $product->selling_price,
                'total_price' => $product->selling_price,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }

    public function update(Request $request)
    {
        $item = Cart::where('user_id', auth()->id())
            ->where('product_id', $request->id)
            ->first();

        if (!$item) return response()->json(['status' => 'error'], 404);

        $item->update([
            'quantity' => $request->quantity,
            'total_price' => $item->single_price * $request->quantity,
        ]);

        return response()->json(['status' => 'ok']);
    }

    public function remove(Request $request)
    {
        Cart::where('user_id', auth()->id())
            ->where('product_id', $request->id)
            ->delete();

        return response()->json(['status' => 'ok']);
    }

    public function place(Request $request)
    {
        // dd($request->all());
        $user = Auth::user();

        $order = $this->createOrder($user, $request);

        $carts = Cart::where('user_id', $user->id)->get();

        foreach ($carts as $cart) {
            $this->processItem($order, $cart);
            $cart->delete();
        }

        $this->finalizeOrder($order, $request);

        return response()->json(['status' => 'success']);
    }

    private function createOrder($user, $request)
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

        return Order::create([
            'branch_id' => $user->branch_id,
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'order_no' => $order_no,
            'order_date' => $now,
        ]);
    }

    private function processItem($order, $cart)
    {
        $product = Product::find($cart->product_id);

        // Create order item
        $item = OrderItem::create([
            'order_id' => $order->id,
            'branch_id' => $order->branch_id,
            'company_id' => $order->company_id,
            'category_id' => $product->category_id,
            'product_id' => $product->id,
            'single_price' => $cart->single_price,
            'quantity' => $cart->quantity,
            'total_price' => $cart->total_price,
        ]);

        // FIFO deduction logic (clean function)
        $this->deductStockFIFO($product, $item, $cart->quantity);
    }

    private function deductStockFIFO($product, $orderItem, $qty_needed)
    {
        while ($qty_needed > 0) {

            $batch = BatchItem::where('product_id', $product->id)
                ->where('balance', '>', 0)
                ->orderBy('created_at', 'ASC')
                ->first();

            if (!$batch) break;

            $take = min($qty_needed, $batch->balance);

            // Create order profit entry
            $order_item_profit = $this->createProfitEntry($orderItem, $batch, $take);

            // Update batch
            $batch->update(['balance' => $batch->balance - $take]);

            // Update stock
            $before = $product->stock_quantity;
            $after = $before - $take;
            $product->update(['stock_quantity' => $after]);

            // Stock log
            $order_item_profit->stock_logs()->create([
                'branch_id' => $orderItem->branch_id,
                'company_id' => $orderItem->company_id,
                'category_id' => $orderItem->category_id,
                'product_id' => $product->id,
                'type' => 'stock_out',
                'description' => $batch->batch->batch_no ?? '',
                'before_stock' => $before,
                'quantity' => $take,
                'after_stock' => $after,
            ]);

            // reduce remaining needed quantity
            $qty_needed -= $take;
        }
    }

    private function createProfitEntry($orderItem, $batch, $qty)
    {
        $cost = $batch->cost_per_unit;
        $sell = $orderItem->single_price;

        return OrderItemProfit::create([
            'order_id' => $orderItem->order_id,
            'branch_id' => $orderItem->branch_id,
            'company_id' => $orderItem->company_id,
            'order_item_id' => $orderItem->id,
            'category_id' => $orderItem->category_id,
            'product_id' => $orderItem->product_id,
            'batch_id' => $batch->batch_id,
            'batch_item_id' => $batch->id,
            'cost_price' => $cost,
            'selling_price' => $sell,
            'earning' => round($sell - $cost, 2),
            'quantity' => $qty,
            'total_cost_price' => round($cost * $qty, 2),
            'total_selling_price' => round($sell * $qty, 2),
            'total_earning' => round(($sell - $cost) * $qty, 2),
        ]);
    }

    private function finalizeOrder($order, $request)
    {
        $order->update([
            'total_product' => $order->items->count(),
            'total_item' => $order->items->sum('quantity'),
            'total_price' => $order->items->sum('total_price'),
            'tax_amount' => 0,
            'final_total' => $order->items->sum('total_price'),
            'payment_method' => $request->payment_method,
            'amount_received' => $request->amount_received,
            'change' => $request->change,
        ]);
    }
}
