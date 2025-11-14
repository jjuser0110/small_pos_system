<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Branch;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $branch = Branch::all();

        return view('branch.index')->with('branch',$branch);
    }

    public function create()
    {
        return view('branch.create');
    }

    public function store(Request $request)
    {
        $branch = Branch::create($request->all());

        return redirect()->route('branch.index')->withSuccess('Data saved');
    }

    public function edit(Branch $branch)
    {
        return view('branch.create')->with('branch',$branch);
    }

    public function update(Request $request, Branch $branch)
    {
        $branch->update($request->all());
        return redirect()->route('branch.index')->withSuccess('Data updated');
    }

    public function destroy(Branch $branch)
    {
        if($branch->companies()->count()>0){
            return redirect()->route('branch.index')->withErrors('Branch has related items. You can not delete this.');
        }
        $branch->delete();

        return redirect()->route('branch.index')->withSuccess('Data deleted');
    }

}
