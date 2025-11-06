<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Bank;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class BankController extends Controller
{
    public function index(Request $request)
    {
        $bank = Bank::all();

        return view('bank.index')->with('bank',$bank);
    }

    public function create()
    {
        return view('bank.create');
    }

    public function store(Request $request)
    {
        $bank = Bank::create($request->all());

        return redirect()->route('bank.index')->withSuccess('Data saved');
    }

    public function edit(Bank $bank)
    {
        return view('bank.create')->with('bank',$bank);
    }

    public function update(Request $request, Bank $bank)
    {
        $bank->update($request->all());
        return redirect()->route('bank.index')->withSuccess('Data updated');
    }

    public function destroy(Bank $bank)
    {
        $bank->delete();

        return redirect()->route('bank.index')->withSuccess('Data deleted');
    }

}
