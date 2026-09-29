<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $login_user = Auth::user();

        if ($login_user->role_id == 5) {
            return redirect('home')->withErrors('Access Denied');
        }

        [$date_from, $date_to] = $this->dateRange($request);
        [$branches, $companies] = $this->filterOptions($login_user);

        $itemTotals = $this->itemTotals($request, $login_user, $date_from, $date_to);

        $date_from = $date_from->format('Y-m-d\TH:i');
        $date_to   = $date_to->format('Y-m-d\TH:i');

        return view('report.index', compact('branches', 'companies', 'date_from', 'date_to', 'itemTotals'));
    }

    public function print(Request $request)
    {
        $login_user = Auth::user();

        if ($login_user->role_id == 5) {
            return redirect('home')->withErrors('Access Denied');
        }

        [$date_from, $date_to] = $this->dateRange($request);
        [$branches, $companies] = $this->filterOptions($login_user);

        $itemTotals = $this->itemTotals($request, $login_user, $date_from, $date_to);

        // Names of selected filters, for the report header
        $selectedBranches = $request->filled('branch_id')
            ? $branches->whereIn('id', $request->branch_id)->pluck('branch_name')->implode(', ')
            : 'All';

        $selectedCompanies = $request->filled('company_id')
            ? $companies->whereIn('id', $request->company_id)->pluck('company_code')->implode(', ')
            : 'All';

        $grandQuantity = $itemTotals->sum('total_quantity');
        $grandAmount   = $itemTotals->sum('total_amount');

        return view('report.print', compact(
            'itemTotals', 'date_from', 'date_to',
            'selectedBranches', 'selectedCompanies',
            'grandQuantity', 'grandAmount', 'login_user'
        ));
    }

    private function dateRange(Request $request): array
    {
        $date_from = $request->date_from
            ? Carbon::parse($request->date_from)
            : Carbon::now()->startOfDay();

        $date_to = $request->date_to
            ? Carbon::parse($request->date_to)
            : Carbon::now()->endOfDay();

        return [$date_from, $date_to];
    }

    private function filterOptions($login_user): array
    {
        if ($login_user->role_id == 3) {
            $branches  = Branch::where('id', $login_user->branch_id)->get();
            $companies = Company::where('branch_id', $login_user->branch_id)->get();
        } elseif ($login_user->role_id == 4) {
            $branches  = Branch::where('id', $login_user->branch_id)->get();
            $companies = Company::where('id', $login_user->company_id)->get();
        } else {
            $branches  = Branch::all();
            $companies = Company::all();
        }

        return [$branches, $companies];
    }

    private function itemTotals(Request $request, $login_user, $date_from, $date_to)
    {
        return OrderItem::query()
            ->when($login_user->role_id == 3, fn ($q) => $q->where('branch_id', $login_user->branch_id))
            ->when($login_user->role_id == 4, fn ($q) => $q->where('company_id', $login_user->company_id))
            ->when($request->filled('branch_id'), fn ($q) => $q->whereIn('branch_id', $request->branch_id))
            ->when($request->filled('company_id'), fn ($q) => $q->whereIn('company_id', $request->company_id))
            ->whereHas('order', function ($q) use ($date_from, $date_to) {
                $q->where('status', 'Active')
                  ->whereBetween('created_at', [$date_from, $date_to]);
            })
            ->select(
                'product_id',
                'branch_id',
                'company_id',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('SUM(total_price) as total_amount')
            )
            ->with([
                'product:id,product_name',
                'branch:id,branch_name',
                'company:id,company_name',
            ])
            ->groupBy('product_id', 'branch_id', 'company_id')
            ->orderByDesc('total_quantity')
            ->get();
    }
}