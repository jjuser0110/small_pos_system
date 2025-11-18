<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $payment_method = PaymentMethod::all();

        return view('payment_method.index')->with('payment_method',$payment_method);
    }

    public function create()
    {
        return view('payment_method.create');
    }

    public function store(Request $request)
    {
        $payment_method = PaymentMethod::create($request->all());

        return redirect()->route('payment_method.index')->withSuccess('Data saved');
    }

    public function edit(PaymentMethod $payment_method)
    {
        return view('payment_method.create')->with('payment_method',$payment_method);
    }

    public function update(Request $request, PaymentMethod $payment_method)
    {
        $payment_method->update($request->all());
        return redirect()->route('payment_method.index')->withSuccess('Data updated');
    }

    public function destroy(PaymentMethod $payment_method)
    {
        $payment_method->delete();

        return redirect()->route('payment_method.index')->withSuccess('Data deleted');
    }

}
