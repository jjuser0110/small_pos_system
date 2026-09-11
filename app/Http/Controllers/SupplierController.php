<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Supplier;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $supplier = Supplier::all();

        return view('supplier.index')->with('supplier',$supplier);
    }

    public function create()
    {
        return view('supplier.create');
    }

    public function store(Request $request)
    {
        $supplier = Supplier::create($request->all());

        return redirect()->route('supplier.index')->withSuccess('Data saved');
    }

    public function edit(Supplier $supplier)
    {
        return view('supplier.create')->with('supplier',$supplier);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($request->all());
        return redirect()->route('supplier.index')->withSuccess('Data updated');
    }

    public function destroy(Supplier $supplier)
    {
        if($supplier->products()->count()>0){
            return redirect()->route('supplier.index')->withErrors('UOM has related items. You can not delete this.');
        }
        $supplier->delete();

        return redirect()->route('supplier.index')->withSuccess('Data deleted');
    }

}
