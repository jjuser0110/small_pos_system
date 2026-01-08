<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\BatchItem;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemProfit;
use App\Models\Product;
use App\Models\ShiftClosing;
use App\Models\ShiftMethodClosing;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use DB;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $date_from = $request->date_from
            ? Carbon::parse($request->date_from)
            : Carbon::now()->startOfDay();

        $date_to = $request->date_to
            ? Carbon::parse($request->date_to)
            : Carbon::now()->endOfDay();

        $login_user = Auth::user();

        if ($login_user->role_id == 3) {
            $branches = Branch::where('id', $login_user->branch_id)->get();
            $companies = Company::where('branch_id', $login_user->branch_id)->get();
        } elseif ($login_user->role_id == 4) {
            $branches = Branch::where('id', $login_user->branch_id)->get();
            $companies = Company::where('id', $login_user->company_id)->get();
        } elseif ($login_user->role_id == 5) {
            return redirect('home')->withErrors('Access Denied');
        } else {
            $branches = Branch::all();
            $companies = Company::all();
        }

        $query = Order::query();

        // Filter by role
        if ($login_user->role_id == 3) {
            $query->where('branch_id', $login_user->branch_id);
        } elseif ($login_user->role_id == 4) {
            $query->where('company_id', $login_user->company_id);
        }

        // Apply date range filter
        $query->whereBetween('created_at', [$date_from, $date_to]);

        // Sort and get results
        $order = $query
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->whereIn('branch_id', $request->branch_id);
            })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                $q->whereIn('company_id', $request->company_id);
            })
            ->with('profit_items')
            ->withSum('profit_items', 'total_earning')
            ->orderBy('created_at', 'DESC')
            ->get();

        $total_profit = $order->where('status', 'Active')->sum('profit_items_sum_total_earning');

        // Format for input fields
        $date_from_input = $date_from->format('Y-m-d\TH:i');
        $date_to_input   = $date_to->format('Y-m-d\TH:i');

        $activeOrders = $order->where('status', 'Active');

        $categoryTotals = OrderItem::query()
            ->when($login_user->role_id == 3, function ($q) use ($login_user) {
                $q->where('branch_id', $login_user->branch_id);
            })
            ->when($login_user->role_id == 4, function ($q) use ($login_user) {
                $q->where('company_id', $login_user->company_id);
            })
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->whereIn('branch_id', $request->branch_id);
            })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                $q->whereIn('company_id', $request->company_id);
            })
            ->whereHas('order', function ($q) use ($date_from, $date_to) {
                $q->where('status', 'Active')
                ->whereBetween('created_at', [$date_from, $date_to]);
            })
            ->select(
                'category_id',
                'branch_id',
                'company_id',
                DB::raw('SUM(total_price) as total_amount'),
                DB::raw('COUNT(*) as order_item_count')
            )
            ->with([
                'category:id,category_name',
                'branch:id,branch_name',
                'company:id,company_name',
            ])
            ->groupBy('category_id', 'branch_id', 'company_id')
            ->get();

        $categoryProfits = OrderItemProfit::query()
            ->when($login_user->role_id == 3, function ($q) use ($login_user) {
                $q->where('branch_id', $login_user->branch_id);
            })
            ->when($login_user->role_id == 4, function ($q) use ($login_user) {
                $q->where('company_id', $login_user->company_id);
            })
            ->when($request->filled('branch_id'), function ($q) use ($request) {
                $q->whereIn('branch_id', $request->branch_id);
            })
            ->when($request->filled('company_id'), function ($q) use ($request) {
                $q->whereIn('company_id', $request->company_id);
            })
            ->whereHas('order', function ($q) use ($date_from, $date_to) {
                $q->where('status', 'Active')
                ->whereBetween('created_at', [$date_from, $date_to]);
            })
            ->select(
                'category_id',
                'branch_id',
                'company_id',
                DB::raw('SUM(total_earning) as total_amount')
            )
            ->with([
                'category:id,category_name',
                'branch:id,branch_name',
                'company:id,company_name',
            ])
            ->groupBy('category_id', 'branch_id', 'company_id')
            ->get();

        return view('order.index', [
            'order' => $order,
            'activeOrderCount' => $activeOrders->count(),
            'activeOrderTotal' => $activeOrders->sum('final_total'),
            'categoryTotals' => $categoryTotals,
            'categoryProfits' => $categoryProfits,
            'total_profit' => $total_profit,
            'date_from' => $date_from_input,
            'date_to' => $date_to_input,
            'branches' => $branches,
            'companies' => $companies,
        ]);

    }

    public function view(Order $order)
    {
        return view('order.view')->with('order',$order);
    }

    public function void(Request $request, Order $order)
    {
        if (!(auth()->user()->role_id == 1 || auth()->user()->role_id == 2 || auth()->user()->role_id == 3)) {
            return back()->withErrors('Access denied');
        }

        $request->validate([
            'voided_reason' => 'required|string',
        ]);

        if ($order->status === 'Voided') {
            return back()->with('error', 'Order already voided.');
        } elseif ($order->status === 'Refunded') {
            abort(403);
        }

        $order->load(['items.profit_items']);

        DB::transaction(function () use ($order, $request) {

            foreach ($order->items as $item) {
                if ($item->profit_items->isEmpty()) {
                    $product = Product::lockForUpdate()->find($item->product_id);
                    if (!$product) {
                        continue;
                    }
                    $before = $product->stock_quantity;
                    $after  = $before + $item->quantity;

                    $product->update([
                        'stock_quantity' => $after
                    ]);

                    // Stock log
                    $product->stockLogs()->create([
                        'branch_id'     => $item->branch_id,
                        'company_id'    => $item->company_id,
                        'category_id'   => $item->category_id,
                        'product_id'    => $item->product_id,
                        'type'          => 'stock_in',
                        'description'   => 'VOID ORDER ' . $order->order_no,
                        'before_stock'  => $before,
                        'quantity'      => $item->quantity,
                        'after_stock'   => $after,
                    ]);

                    continue;
                }

                foreach ($item->profit_items as $profit_item) {
                    $product = Product::lockForUpdate()->find($profit_item->product_id);
                    if (!$product) {
                        continue;
                    }
                    $before = $product->stock_quantity;
                    $after = $before + $profit_item->quantity;
                    // Restore product stock
                    $product->update([
                        'stock_quantity' => $after
                    ]);

                    // Restore batch balance
                    BatchItem::where('id', $profit_item->batch_item_id)
                        ->increment('balance', $profit_item->quantity);

                    // Stock log (reverse)
                    $profit_item->stock_logs()->create([
                        'branch_id'     => $profit_item->branch_id,
                        'company_id'    => $profit_item->company_id,
                        'category_id'   => $profit_item->category_id,
                        'product_id'    => $profit_item->product_id,
                        'type'          => 'stock_in',
                        'description'   => 'VOID ORDER ' . $order->order_no,
                        'before_stock'  => $before,
                        'quantity'      => $profit_item->quantity,
                        'after_stock'   => $after,
                    ]);

                    $profit_item->delete();
                }
            }

            // Reverse shift closing
            $shift = ShiftClosing::where('user_id', $order->user_id)
                ->whereNull('closing_time')
                ->first();

            if ($shift) {
                $shift->decrement('total_order_count', $order->total_product);
                $shift->decrement('total_order_amount', $order->total_price);

                ShiftMethodClosing::where('shift_closing_id', $shift->id)
                    ->where('payment_method', $order->payment_method)
                    ->decrement('amount', $order->final_total);
            }

            // Mark order as voided
            $order->update([
                'status'            => 'Voided',
                'voided_at'         => Carbon::now(),
                'voided_by'         => auth()->id(),
                'voided_reason'     => $request->voided_reason,
            ]);
        });

        return back()->with('success', 'Order voided successfully.');
    }

    public function discount(Request $request, Order $order)
    {
        $order->update([
            'discount' => $request->discount,
            'amount_received' => $order->final_total + $order->change - $request->discount,
        ]);

        return back()->with('success', 'Discount added.');
    }
}
