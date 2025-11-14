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

class CompanyStaffController extends Controller
{
    public function index(Request $request)
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $company_staff = User::where('branch_id',$login_user->branch_id)->where('role_id',5)->get();
        }else if($login_user->role_id == 4){
            $company_staff = User::where('company_id',$login_user->company_id)->where('role_id',5)->get();
        }else{
            $company_staff = User::where('role_id',5)->get();
        }

        return view('company_staff.index')->with('company_staff',$company_staff);
    }

    public function create()
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $company = Company::where('branch_id',$login_user->branch_id)->get();
        }else if($login_user->role_id == 4){
            $company = Company::where('id',$login_user->company_id)->get();
        }else{
            $company = Company::all();
        }
        return view('company_staff.create')->with('company',$company);
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
        $request->merge(['password' => Hash::make($request->password),'role_id'=>5,'branch_id'=>$company->branch_id]);
        $company_staff = User::create($request->all());

        return redirect()->route('company_staff.index')->withSuccess('Data saved');
    }

    public function edit(User $company_staff)
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $company = Company::where('branch_id',$login_user->branch_id)->get();
        }else if($login_user->role_id == 4){
            $company = Company::where('id',$login_user->company_id)->get();
        }else{
            $company = Company::all();
        }
        return view('company_staff.create')->with('company',$company)->with('company_staff',$company_staff);
    }

    public function update(Request $request, User $company_staff)
    {
        if($request->password !=null){
            $request->merge(['password' => Hash::make($request->password)]);
        }else{
            $request->request->remove('password');
        }

        $company_staff->update($request->all());
        return redirect()->route('company_staff.index')->withSuccess('Data updated');
    }

    public function destroy(User $company_staff)
    {
        $company_staff->delete();

        return redirect()->route('company_staff.index')->withSuccess('Data deleted');
    }

}
