<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;

class ReportController extends Controller
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

        $order = $query->get();

        $date_from_input = $date_from->format('Y-m-d\TH:i');
        $date_to_input   = $date_to->format('Y-m-d\TH:i');

        $activeOrders = $order->where('status', 'Active');

        $itemTotals = OrderItem::query()
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
                'product_id',
                'branch_id',
                'company_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_price) as total_amount'),
            )
            ->with([
                'product:id,product_name',
                'branch:id,branch_name',
                'company:id,company_name',
            ])
            ->groupBy('product_id', 'branch_id', 'company_id')
            ->orderByDesc('total_quantity')
            ->get();

        return view('report.index', compact('branches', 'companies', 'date_from', 'date_to', 'itemTotals'));
    }
}
