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

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $order = Order::where('branch_id',$login_user->branch_id)->orderBy('created_at','DESC')->get();
        }else if($login_user->role_id == 4){
            $order = Order::where('company_id',$login_user->company_id)->orderBy('created_at','DESC')->get();
        }else{
            $order = Order::orderBy('created_at','DESC')->get();
        }

        return view('order.index')->with('order',$order);
    }

    public function view(Order $order)
    {
        return view('order.view')->with('order',$order);
    }

}
