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
use App\Models\ShiftClosing;
use App\Models\ShiftClosingDetail;
use App\Models\ShiftMethodClosing;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;

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

    public function convertBox(Request $request)
    {
        $product = Product::findOrFail($request->box_id);

        // BOX MUST HAVE STOCK
        if ($product->stock_quantity <= 0) {
            return response()->json(['error' => 'Stock not enough'], 422);
        }

        // Get FIRST BATCH ITEM for this box
        $batch = BatchItem::where('product_id', $product->id)
            ->whereHas('batch', function ($query) {
                $query->where('status', 'Completed');
            })
            ->where('balance', '>', 0)
            ->orderBy('created_at', 'ASC')
            ->first();

        if (!$batch) {
            return response()->json(['error' => 'No batch found for this product'], 422);
        }

        // IDENTIFY CONNECTED PRODUCT (bottle)
        $connected_product = Product::find($product->connected_product_id);
        if (!$connected_product) {
            return response()->json(['error' => 'Connected product not found'], 422);
        }

        $bottle_qty = $product->connected_product_quantity;

        /** -----------------------------------
         *  1) STOCK LOG — OUT (BOX)
         * ----------------------------------- */
        $batch->stock_logs()->create([
            'branch_id'     => $product->branch_id,
            'company_id'    => $product->company_id,
            'category_id'   => $product->category_id,
            'product_id'    => $product->id,
            'type'          => 'convert_out',
            'description'   => $batch->batch->batch_no ?? '',
            'before_stock'  => $product->stock_quantity,
            'quantity'      => 1,
            'after_stock'   => $product->stock_quantity - 1,
        ]);

        // Reduce BOX batch balance
        $batch->update([
            'balance'       => $batch->balance - 1,
            'quantity'      => $batch->quantity - 1,
            'total_cost'    => round($batch->cost_per_unit * ($batch->quantity - 1), 2),
        ]);

        // Reduce BOX stock
        $product->update([
            'stock_quantity' => $product->stock_quantity - 1
        ]);

        /** -----------------------------------
         *  2) CREATE NEW BATCH FOR BOTTLES
         * ----------------------------------- */
        $total_cost     = $batch->cost_per_unit;
        $cost_per_unit  = round($total_cost / $bottle_qty, 2);

        $newBatch = BatchItem::create([
            'batch_id'      => $batch->batch_id,
            'branch_id'     => $batch->branch_id,
            'company_id'    => $batch->company_id,
            'category_id'   => $batch->category_id,
            'product_id'    => $connected_product->id,
            'quantity'      => $bottle_qty,
            'balance'       => $bottle_qty,
            'total_cost'    => $total_cost,
            'cost_per_unit' => $cost_per_unit,
        ]);

        /** -----------------------------------
         *  3) STOCK LOG — IN (BOTTLES)
         * ----------------------------------- */
        $newBatch->stock_logs()->create([
            'branch_id'     => $connected_product->branch_id,
            'company_id'    => $connected_product->company_id,
            'category_id'   => $connected_product->category_id,
            'product_id'    => $connected_product->id,
            'type'          => 'convert_in',
            'description'   => $batch->batch->batch_no ?? '',
            'before_stock'  => $connected_product->stock_quantity,
            'quantity'      => $bottle_qty,
            'after_stock'   => $connected_product->stock_quantity + $bottle_qty,
        ]);

        // Increase bottle stock
        $connected_product->update([
            'stock_quantity' => $connected_product->stock_quantity + $bottle_qty
        ]);

        return response()->json([
            'success' => true,
            'message' => "1 box converted into {$bottle_qty} bottles",
            'new_bottle_stock' => $connected_product->stock_quantity,
            'new_box_stock' => $product->stock_quantity,
        ]);
    }

    public function place(Request $request)
    {
        try {
            $user = Auth::user();
            $order = null;

            DB::transaction(function () use ($user, $request, &$order) {
                $order = $this->createOrder($user, $request);

                $carts = Cart::where('user_id', $user->id)->get();

                foreach ($carts as $cart) {
                    $this->processItem($order, $cart);
                    $cart->delete();
                }

                $this->finalizeOrder($order, $request);
            });

            return response()->json([
                'status' => 'success',
                'order_id' => $order->id
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
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

        $order = Order::create([
            'branch_id' => $user->branch_id,
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'order_no' => $order_no,
            'order_date' => $now,
        ]);
        $rn->increment('running_no');

        return $order;
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
        if (!$product->category->special) {
            $this->deductStockFIFO($product, $item, $cart->quantity);
        }
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
        $totalProduct = $order->items->count();
        $totalQty     = $order->items->sum('quantity');
        $totalPrice   = $order->items->sum('total_price');

        $order->update([
            'total_product'   => $totalProduct,
            'total_item'      => $totalQty,
            'total_price'     => $totalPrice,
            'tax_amount'      => 0,
            'final_total'     => $totalPrice,
            'payment_method'  => $request->payment_method,
            'amount_received' => $request->amount_received,
            'change'          => $request->change,
        ]);

        $user  = Auth::user();
        $shift = ShiftClosing::where('user_id', $user->id)
            ->whereNull('closing_time')
            ->first();

        if ($shift) {
            $shift->update([
                'total_order_count'  => $shift->total_order_count + $totalProduct,
                'total_order_amount' => round($shift->total_order_amount + $totalPrice, 2),
            ]);

        } else {
            $shift = ShiftClosing::create([
                'user_id'            => $user->id,
                'branch_id'          => $user->branch_id,
                'company_id'         => $user->company_id,
                'total_order_count'  => $totalProduct,
                'total_order_amount' => $totalPrice,
                'first_sale_time'    => Carbon::now(),
            ]);
        }

        foreach ($order->items as $item) {
            $checkShiftClosingDetail = ShiftClosingDetail::where('shift_closing_id', $shift->id)
                ->where(function ($query) use ($item) {
                    if ($item->category->special === 1) {
                        $query->where('product_id', $item->product_id);
                    } else {
                        $query->whereNull('product_id');
                    }
                })
                ->where('category_id', $item->category_id)
                ->first();

            if (isset($checkShiftClosingDetail)) {
                $checkShiftClosingDetail->update([
                    'amount' => round($checkShiftClosingDetail->amount + $item->total_price, 2),
                ]);
            } else {
                ShiftClosingDetail::create([
                    'shift_closing_id' => $shift->id,
                    'category_id'      => $item->category_id,
                    'product_id'       => $item->category->special === 1 ? $item->product_id : null,
                    'amount'           => $item->total_price,
                ]);
            }
        }

        $checkShiftMethod = ShiftMethodClosing::where('shift_closing_id', $shift->id)->where('payment_method',$request->payment_method)->first();
        if(isset($checkShiftMethod)){
            $checkShiftMethod->update([
                'amount' => round($checkShiftMethod->amount + $totalPrice, 2),
            ]);
        }else{
            ShiftMethodClosing::create([
                'shift_closing_id'=>$shift->id,
                'payment_method'=>$request->payment_method,
                'amount'=>$totalPrice
            ]);
        }

    }

    public function empty_cart()
    {
        Cart::where('user_id', auth()->id())->delete();
        return redirect()->back();
    }

    public function addSpecial(Request $request)
    {
        $product = Product::find($request->id);
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product not found'], 404);
        }

        $item = Cart::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->where('single_price', $request->amount)
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
                'single_price' => $request->amount,
                'total_price' => $request->amount,
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
