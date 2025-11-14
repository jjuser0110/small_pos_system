<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Uom;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UomController extends Controller
{
    public function index(Request $request)
    {
        $uom = Uom::all();

        return view('uom.index')->with('uom',$uom);
    }

    public function create()
    {
        return view('uom.create');
    }

    public function store(Request $request)
    {
        $uom = Uom::create($request->all());

        return redirect()->route('uom.index')->withSuccess('Data saved');
    }

    public function edit(Uom $uom)
    {
        return view('uom.create')->with('uom',$uom);
    }

    public function update(Request $request, Uom $uom)
    {
        $uom->update($request->all());
        return redirect()->route('uom.index')->withSuccess('Data updated');
    }

    public function destroy(Uom $uom)
    {
        if($uom->products()->count()>0){
            return redirect()->route('uom.index')->withErrors('UOM has related items. You can not delete this.');
        }
        $uom->delete();

        return redirect()->route('uom.index')->withSuccess('Data deleted');
    }

}
