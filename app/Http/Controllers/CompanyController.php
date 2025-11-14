<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\Branch;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $company = Company::all();

        return view('company.index')->with('company',$company);
    }

    public function create()
    {
        $branch = Branch::all();
        return view('company.create')->with('branch',$branch);
    }

    public function store(Request $request)
    {
        $company = Company::create($request->all());

        return redirect()->route('company.index')->withSuccess('Data saved');
    }

    public function edit(Company $company)
    {
        $branch = Branch::all();
        return view('company.create')->with('company',$company)->with('branch',$branch);
    }

    public function update(Request $request, Company $company)
    {
        $company->update($request->all());
        return redirect()->route('company.index')->withSuccess('Data updated');
    }

    public function destroy(Company $company)
    {
        if($company->categories()->count()>0 || $company->users()->count()>0){
            return redirect()->route('company.index')->withErrors('Company has related items. You can not delete this.');
        }
        $company->delete();

        return redirect()->route('company.index')->withSuccess('Data deleted');
    }

}
