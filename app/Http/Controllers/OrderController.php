<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Order;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
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
        $order = $query->orderBy('created_at', 'DESC')->get();

        // Format for input fields
        $date_from_input = $date_from->format('Y-m-d\TH:i');
        $date_to_input   = $date_to->format('Y-m-d\TH:i');

        return view('order.index')
            ->with('order', $order)
            ->with('date_from', $date_from_input)
            ->with('date_to', $date_to_input);

    }

    public function view(Order $order)
    {
        return view('order.view')->with('order',$order);
    }

}
