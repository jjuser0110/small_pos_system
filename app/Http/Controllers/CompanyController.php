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
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $company = Company::where('branch_id',$login_user->branch_id)->get();
        }else{
            $company = Company::all();
        }

        return view('company.index')->with('company',$company);
    }

    public function create()
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $branch = Branch::where('id',$login_user->branch_id)->get();
        }else{
            $branch = Branch::all();
        }

        return view('company.create')->with('branch',$branch);
    }

    public function store(Request $request)
    {
        $request->merge(['domain' => Company::normalizeDomain($request->domain)]);
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'domain'    => $this->domainRules(),
        ]);
        $this->authorizeBranchId($request->branch_id);

        Company::create($request->only(['branch_id', 'company_name', 'company_code', 'domain']));

        return redirect()->route('company.index')->withSuccess('Data saved');
    }

    public function edit(Company $company)
    {
        $this->authorizeCompany($company);

        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $branch = Branch::where('id',$login_user->branch_id)->get();
        }else{
            $branch = Branch::all();
        }

        return view('company.create')->with('company',$company)->with('branch',$branch);
    }

    public function update(Request $request, Company $company)
    {
        $this->authorizeCompany($company);
        $request->merge(['domain' => Company::normalizeDomain($request->domain)]);
        $request->validate([
            'branch_id' => 'sometimes|required|exists:branches,id',
            'domain'    => $this->domainRules($company),
        ]);
        if ($request->has('branch_id')) {
            $this->authorizeBranchId($request->branch_id);
        }

        $company->update($request->only(['branch_id', 'company_name', 'company_code', 'domain']));
        return redirect()->route('company.index')->withSuccess('Data updated');
    }

    public function destroy(Company $company)
    {
        $this->authorizeCompany($company);

        if($company->categories()->count()>0 || $company->users()->count()>0){
            return redirect()->route('company.index')->withErrors('Company has related items. You can not delete this.');
        }
        $company->delete();

        return redirect()->route('company.index')->withSuccess('Data deleted');
    }

    /** Branch managers may only manage companies in their own branch. */
    private function authorizeCompany(Company $company): void
    {
        if (!$this->userCanAccess($company->branch_id, $company->id)) {
            abort(403, 'Access denied');
        }
    }

    private function authorizeBranchId($branchId): void
    {
        if (!$this->userCanAccess($branchId, null)) {
            abort(403, 'Access denied');
        }
    }

    private function domainRules(?Company $ignore = null): array
    {
        return [
            'nullable', 'string', 'max:191',
            // host names only (underscores allowed for local hosts like small_pos_system.test)
            'regex:/^[a-z0-9]([a-z0-9_-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9_-]*[a-z0-9])?)+$/',
            \Illuminate\Validation\Rule::unique('companies', 'domain')->ignore($ignore?->id)->whereNull('deleted_at'),
        ];
    }
}
