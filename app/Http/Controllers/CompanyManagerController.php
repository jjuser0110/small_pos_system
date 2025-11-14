<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class CompanyManagerController extends Controller
{
    public function index(Request $request)
    {
        $company_manager = User::where('role_id',4)->get();

        return view('company_manager.index')->with('company_manager',$company_manager);
    }

    public function create()
    {
        $company = Company::all();
        return view('company_manager.create')->with('company',$company);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:users,username,NULL,id,deleted_at,NULL',
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                        ->withErrors($validator);
        }
        $company = Company::find($request->company_id);
        $request->merge(['password' => Hash::make($request->password),'role_id'=>4,'branch_id'=>$company->branch_id]);
        $company_manager = User::create($request->all());

        return redirect()->route('company_manager.index')->withSuccess('Data saved');
    }

    public function edit(User $company_manager)
    {
        $company = Company::all();
        return view('company_manager.create')->with('company',$company)->with('company_manager',$company_manager);
    }

    public function update(Request $request, User $company_manager)
    {
        if($request->password !=null){
            $request->merge(['password' => Hash::make($request->password)]);
        }else{
            $request->request->remove('password');
        }

        $company_manager->update($request->all());
        return redirect()->route('company_manager.index')->withSuccess('Data updated');
    }

    public function destroy(User $company_manager)
    {
        $company_manager->delete();

        return redirect()->route('company_manager.index')->withSuccess('Data deleted');
    }

}
