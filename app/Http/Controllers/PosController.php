<?php

namespace App\Http\Controllers;

use App\Models\Table;
use App\Models\Category;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use Carbon\Carbon;


class PosController extends Controller
{
    // ── TABLES ────────────────────────────────────────────

    // GET /api/tables
    public function getTables()
    {
        $tables = Table::where('type', 0)
            ->orderBy('id')
            ->get();

        return response()->json($tables);
    }

    // PUT /api/tables/{id}/pay  — reset table total to 0
    public function payTable(Request $request, $id)
    {
        $table = Table::where('type', 0)->findOrFail($id);

        DB::transaction(function () use ($request, $table, $id) {
            $this->createOrder($request, $id);

            // Clear cart + reset table total
            Cart::where('table_id', $id)->delete();
            $table->update(['total' => 0]);
        });

        return response()->json(['success' => true]);
    }

    // ── DABAO ─────────────────────────────────────────────

    // GET /api/dabao
    public function getDabao()
    {
        $dabao = Table::where('type', 1)
            ->where('total', '>', 0)
            ->orderBy('id')
            ->get();

        return response()->json($dabao);
    }

    // POST /api/dabao
    public function createDabao(Request $request)
    {
        $dabao = Table::create([
            'table_name' => $request->table_name ?? null,
            'type'       => 1,
            'total'      => 0,
        ]);

        return response()->json($dabao, 201);
    }

    // PUT /api/dabao/{id}
    public function updateDabao(Request $request, $id)
    {
        $dabao = Table::where('type', 1)->findOrFail($id);
        $dabao->update([
            'table_name' => $request->table_name ?? $dabao->table_name,
            'total'      => $request->total      ?? $dabao->total,
        ]);

        return response()->json($dabao);
    }

    // PUT /api/dabao/{id}/pay  — reset dabao total to 0 (hides it)
    public function payDabao(Request $request, $id)
    {
        $dabao = Table::where('type', 1)->findOrFail($id);

        DB::transaction(function () use ($request, $dabao, $id) {
            $this->createOrder($request, $id);

            // Clear cart + hide dabao slot
            Cart::where('table_id', $id)->delete();
            $dabao->update(['total' => 0, 'table_name' => null]);
        });

        return response()->json(['success' => true]);
    }

    // ── MENU (Categories + Products) ──────────────────────

    // GET /api/menu
    // Returns all active categories with their active products
    public function getMenu()
    {
        $categories = Category::with([
            'products' => function ($q) {
                $q->where('is_active', 1)
                  ->with(['addons' => function ($addon) {
                      $addon->where('is_active', 1);
                  }])
                  ->orderBy('arrangement')
                  ->orderBy('product_name');
            }
        ])
        ->orderBy('arrangement')
        ->get();
        $categories->each(function ($cat) {
            $cat->products->each(function ($product) use ($cat) {
                $product->has_stock = $cat->has_stock;
            });
        });
    
        return response()->json($categories);
    }

    // ── CART ──────────────────────────────────────────────

    // GET /api/cart/{tableId}
    // Load existing cart for a table/dabao
    public function getCart($tableId)
    {
        $cart = Cart::with('product')
            ->where('table_id', $tableId)
            ->get();

        return response()->json($cart);
    }

    // POST /api/cart
    // Add or increment an item in the cart
    public function addToCart(Request $request)
    {
        $request->validate([
            'table_id'   => 'required|integer',
            'product_id' => 'required|integer',
            'quantity'   => 'required|integer|min:1',
        ]);
    
        $product     = Product::findOrFail($request->product_id);
        $addons      = $request->input('addons', []);   // array from frontend
        $unitPrice   = $request->input('unit_price')
                        ?? $product->selling_price;
    
        // Normalise addons to a sorted JSON string for comparison
        $addonsJson  = collect($addons)
                        ->sortBy('id')
                        ->values()
                        ->toJson();
    
        // Match on table + product + EXACT addon combo
        $cartItem = Cart::where('table_id',   $request->table_id)
                        ->where('product_id', $request->product_id)
                        ->where('addons',     $addonsJson)
                        ->first();
    
        if ($cartItem) {
            // Same product, same add-ons → increment
            $cartItem->quantity   += $request->quantity;
            $cartItem->total_price = $cartItem->quantity * $cartItem->single_price;
            $cartItem->save();
        } else {
            // New row — different add-ons OR first time
            $cartItem = Cart::create([
                'user_id'      => Auth::id() ?? 1,
                'table_id'     => $request->table_id,
                'product_id'   => $request->product_id,
                'quantity'     => $request->quantity,
                'single_price' => $unitPrice,
                'total_price'  => $request->quantity * $unitPrice,
                'addons'       => $addonsJson,
            ]);
        }
    
        $this->syncTableTotal($request->table_id);
    
        return response()->json($cartItem->load('product'));
    }

    // PUT /api/cart/{cartId}
    // Update quantity of a cart item (pass quantity=0 to remove)
    public function updateCart(Request $request, $cartId)
    {
        $cartItem = Cart::findOrFail($cartId);
        $tableId  = $cartItem->table_id;

        if ($request->quantity <= 0) {
            $cartItem->delete();
        } else {
            $cartItem->update([
                'quantity'    => $request->quantity,
                'total_price' => $request->quantity * $cartItem->single_price,
            ]);
        }

        $this->syncTableTotal($tableId);

        return response()->json(['success' => true]);
    }

    // DELETE /api/cart/{cartId}
    public function removeFromCart($cartId)
    {
        $cartItem = Cart::findOrFail($cartId);
        $tableId  = $cartItem->table_id;
        $cartItem->delete();

        $this->syncTableTotal($tableId);

        return response()->json(['success' => true]);
    }

    // ── HELPERS ───────────────────────────────────────────

    // Keep table.total in sync with sum of cart items
    private function syncTableTotal($tableId)
    {
        $total = Cart::where('table_id', $tableId)->sum('total_price');
        Table::where('id', $tableId)->update(['total' => $total]);
    }

    private function createOrder(Request $request, $tableId)
    {
        $cartItems = Cart::with('product.category')->where('table_id', $tableId)->get();

        if ($cartItems->isEmpty()) return;

        $user       = Auth::user();
        $totalPrice = $cartItems->sum('total_price');
        $finalTotal = $request->final_total   ?? $totalPrice;
        $received   = $request->amount_received ?? $finalTotal;
        $change     = $received - $finalTotal;

        // Generate order number: ORD-20250404-0001
        $todayCount = Order::whereDate('order_date', Carbon::today())->count() + 1;
        $orderNo    = 'ORD-' . Carbon::today()->format('Ymd') . '-' . str_pad($todayCount, 4, '0', STR_PAD_LEFT);

        $order = Order::create([
            'branch_id'       => $user->branch_id   ?? null,
            'company_id'      => $user->company_id  ?? null,
            'user_id'         => $user->id          ?? null,
            'table_id'        => $tableId,
            'order_no'        => $orderNo,
            'order_date'      => Carbon::now(),
            'total_product'   => $cartItems->unique('product_id')->count(),
            'total_item'      => $cartItems->sum('quantity'),
            'total_price'     => $totalPrice,
            'final_total'     => $finalTotal,
            'payment_method'  => $request->payment_method  ?? 'cash',
            'amount_received' => $received,
            'change'          => max(0, $change),
            'status'          => 'paid',
        ]);

        // Create order items from cart
        foreach ($cartItems as $cart) {
            OrderItem::create([
                'order_id'     => $order->id,
                'branch_id'    => $user->branch_id  ?? null,
                'company_id'   => $user->company_id ?? null,
                'category_id'  => $cart->product->category_id ?? null,
                'product_id'   => $cart->product_id,
                'single_price' => $cart->single_price,
                'quantity'     => $cart->quantity,
                'total_price'  => $cart->total_price,
            ]);

            if (
                $cart->product &&
                $cart->product->category &&
                $cart->product->category->has_stock == 1
            ) {
                $cart->product->decrement('stock_quantity', $cart->quantity);
                $product = Product::find($cart->product_id);
                $product->stockLogs()->create([
                    'branch_id'     => $user->branch_id,
                    'company_id'    => $user->company_id,
                    'category_id'   => $product->category_id,
                    'product_id'    => $product->id,
                    'type'          => 'sales',
                    'description'   => $orderNo ?? '',
                    'before_stock'  => $product->stock_quantity,
                    'quantity'      => 1,
                    'after_stock'   => $product->stock_quantity - 1,
                ]);
            }
        }

        $paymentMethod = PaymentMethod::where('payment_method_name', $request->payment_method)->first();
        if ($paymentMethod) {
            $prev_amount = $paymentMethod->amount ?? 0;
            $amount = $finalTotal;
            $total = round($prev_amount + $amount, 2);
            $paymentMethod->payment_method_logs()->create([
                'payment_method_id' => $paymentMethod->id,
                'type' => 'sale',
                'remarks' => $order->order_no,
                'prev_amount' => $prev_amount,
                'amount' => $amount,
                'total' => $total,
                'created_by_id' => $user->id ?? null,
            ]);
            $paymentMethod->update(['amount' => $total]);
        }
    }

    public function getPaymentMethods()
    {
        $methods = PaymentMethod::where('is_active', 1)
            ->orderBy('payment_method_name')
            ->get()
            ->map(function ($pm) {
                $pm->image_full_url = $pm->image_url
                    ? asset($pm->image_url)
                    : null;
                return $pm;
            });

        return response()->json($methods);
    }
}