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
        $login_user = Auth::user();
        $payment_method = $request->payment_method;
        $amount_received = $request->amount_received;
        $change = $request->change;

        $carts = Cart::where('user_id', $login_user->id)->get();

        $year = Carbon::now()->year;
        $month = Carbon::now()->month;
        $day = Carbon::now()->day;

        // find running number
        $check = RunningNumber::where('name', 'order')
            ->where('year', $year)
            ->where('month', $month)
            ->where('day', $day)
            ->first();

        if (!$check) {
            $check = RunningNumber::create([
                'code' => 'OD',
                'name' => 'order',
                'year' => $year,
                'month' => $month, // FIXED
                'day' => $day,
                'no_of_digit_behind' => 4,
                'running_no' => 1,
            ]);
        }

        // generate order number
        $order_no =
            $check->code .
            $check->year .
            sprintf('%02d', $check->month) .
            sprintf('%02d', $check->day) .
            sprintf('%0' . $check->no_of_digit_behind . 'd', $check->running_no);

        // create order
        $order = Order::create([
            'branch_id' => $login_user->branch_id,
            'company_id' => $login_user->company_id,
            'user_id' => $login_user->id,
            'order_no' => $order_no,
            'order_date' => Carbon::now(),
        ]);

        foreach ($carts as $cart) {

            $product = Product::find($cart->product_id);

            // create order item
            $order_item = OrderItem::create([
                'order_id' => $order->id,
                'branch_id' => $order->branch_id,
                'company_id' => $order->company_id,
                'category_id' => $product->category_id,
                'product_id' => $cart->product_id,
                'single_price' => $cart->single_price,
                'quantity' => $cart->quantity,
                'total_price' => $cart->total_price,
            ]);

            //-------------------------------
            // FIRST BATCH CONSUME
            //-------------------------------
            $batch_item = BatchItem::where('product_id', $cart->product_id)
                ->where('balance', '>', 0)
                ->orderBy('created_at', 'ASC')
                ->first();

            $balance = $batch_item->balance;

            if ($cart->quantity > $balance) {
                $new_balance = $cart->quantity - $balance;
                $quantity = $balance;
                $batch_balance = 0;
            } else {
                $new_balance = 0;
                $quantity = $cart->quantity;
                $batch_balance = $balance - $cart->quantity;
            }

            // calculate cost + profit
            $earning = round($order_item->single_price - $batch_item->cost_per_unit, 2);
            $total_cost_price = round($batch_item->cost_per_unit * $quantity, 2);
            $total_selling_price = round($order_item->single_price * $quantity, 2);
            $total_earning = round($total_selling_price - $total_cost_price, 2);

            // create profit
            $orderItemProfit = OrderItemProfit::create([
                'order_id' => $order_item->order_id,
                'branch_id' => $order_item->branch_id,
                'company_id' => $order_item->company_id,
                'order_item_id' => $order_item->id,
                'category_id' => $order_item->category_id,
                'product_id' => $order_item->product_id,
                'batch_id' => $batch_item->batch_id,
                'batch_item_id' => $batch_item->id,
                'cost_price' => $batch_item->cost_per_unit,
                'selling_price' => $order_item->single_price,
                'earning' => $earning,
                'quantity' => $quantity,
                'total_cost_price' => $total_cost_price,
                'total_selling_price' => $total_selling_price,
                'total_earning' => $total_earning,
            ]);

            // update batch balance
            $batch_item->update(['balance' => $batch_balance]);

            // update product stock
            $before_stock = $product->stock_quantity;
            $after_stock = $before_stock - $quantity;
            $product->update(['stock_quantity' => $after_stock]);

            // stock logs
            $orderItemProfit->stock_logs()->create([
                'branch_id' => $orderItemProfit->branch_id,
                'company_id' => $orderItemProfit->company_id,
                'category_id' => $orderItemProfit->category_id,
                'product_id' => $orderItemProfit->product_id,
                'type' => 'stock_out',
                'description' => $batch_item->batch->batch_no ?? '',
                'before_stock' => $before_stock,
                'quantity' => $quantity,
                'after_stock' => $after_stock,
            ]);

            //-------------------------------
            // MULTI-BATCH LOOP
            //-------------------------------
            while ($new_balance > 0) {

                $batch_item = BatchItem::where('product_id', $cart->product_id)
                    ->where('balance', '>', 0)
                    ->orderBy('created_at', 'ASC')
                    ->first();

                if (!$batch_item) break;

                $balance = $batch_item->balance;

                if ($new_balance > $balance) {
                    $quantity = $balance;
                    $new_balance -= $balance;
                    $batch_balance = 0;
                } else {
                    $quantity = $new_balance;
                    $batch_balance = $balance - $new_balance;
                    $new_balance = 0;
                }

                // cost & profit
                $earning = round($order_item->single_price - $batch_item->cost_per_unit, 2);
                $total_cost_price = round($batch_item->cost_per_unit * $quantity, 2);
                $total_selling_price = round($order_item->single_price * $quantity, 2);
                $total_earning = round($total_selling_price - $total_cost_price, 2);

                $orderItemProfit = OrderItemProfit::create([
                    'order_id' => $order_item->order_id,
                    'branch_id' => $order_item->branch_id,
                    'company_id' => $order_item->company_id,
                    'order_item_id' => $order_item->id,
                    'category_id' => $order_item->category_id,
                    'product_id' => $order_item->product_id,
                    'batch_id' => $batch_item->batch_id,
                    'batch_item_id' => $batch_item->id,
                    'cost_price' => $batch_item->cost_per_unit,
                    'selling_price' => $order_item->single_price,
                    'earning' => $earning,
                    'quantity' => $quantity,
                    'total_cost_price' => $total_cost_price,
                    'total_selling_price' => $total_selling_price,
                    'total_earning' => $total_earning,
                ]);

                $batch_item->update(['balance' => $batch_balance]);

                // stock update
                $before_stock = $product->stock_quantity;
                $after_stock = $before_stock - $quantity;
                $product->update(['stock_quantity' => $after_stock]);

                $orderItemProfit->stock_logs()->create([
                    'branch_id' => $orderItemProfit->branch_id,
                    'company_id' => $orderItemProfit->company_id,
                    'category_id' => $orderItemProfit->category_id,
                    'product_id' => $orderItemProfit->product_id,
                    'type' => 'stock_out',
                    'description' => $batch_item->batch->batch_no ?? '',
                    'before_stock' => $before_stock,
                    'quantity' => $quantity,
                    'after_stock' => $after_stock,
                ]);
            }

            // remove cart
            $cart->delete();
        }

        // update final order summary
        $order->update([
            'total_product' => $order->items->count(),
            'total_item' => $order->items->sum('quantity'),
            'total_price' => $order->items->sum('total_price'),
            'tax_amount' => 0,
            'final_total' => $order->items->sum('total_price'),
            'payment_method' => $payment_method,
            'amount_received' => $amount_received,
            'change' => $change,
        ]);

        return response()->json([
            'status' => 'success'
        ]);
    }

}
