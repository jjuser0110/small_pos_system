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
            $order = Order::where('branch_id',$login_user->branch_id)->get();
        }else if($login_user->role_id == 4){
            $order = Order::where('company_id',$login_user->company_id)->get();
        }else{
            $order = Order::all();
        }

        return view('order.index')->with('order',$order);
    }

    public function view(Order $order)
    {
        return view('order.view')->with('order',$order);
    }

    public function update(Request $request, Order $order)
    {
        $order->update($request->all());
        return redirect()->route('order.index')->withSuccess('Data updated');
    }

    public function destroy(Order $order)
    {
        $order->delete();

        return redirect()->route('order.index')->withSuccess('Data deleted');
    }

}
