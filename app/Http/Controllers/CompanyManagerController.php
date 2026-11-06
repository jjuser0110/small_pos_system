<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CompanyManagerController extends Controller
{
    const MANAGER_ROLE = 4;

    public function index(Request $request)
    {
        $login_user = Auth::user();
        if ($login_user->role_id == 3) {
            $company_manager = User::where('branch_id',$login_user->branch_id)->where('role_id',self::MANAGER_ROLE)->get();
        } else {
            $company_manager = User::where('role_id',self::MANAGER_ROLE)->get();
        }

        return view('company_manager.index')->with('company_manager',$company_manager);
    }

    public function create()
    {
        return view('company_manager.create')->with('company',$this->allowedCompanies());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'username'   => 'required|unique:users,username,NULL,id,deleted_at,NULL',
            'password'   => 'required|string',
            'company_id' => ['required', Rule::in($this->allowedCompanies()->pluck('id')->all())],
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                        ->withErrors($validator)
                        ->withInput($request->except('password'));
        }

        $company = Company::findOrFail($request->company_id);

        User::create([
            'name'       => $request->name,
            'username'   => $request->username,
            'password'   => Hash::make($request->password),
            'role_id'    => self::MANAGER_ROLE,
            'company_id' => $company->id,
            'branch_id'  => $company->branch_id,
        ]);

        return redirect()->route('company_manager.index')->withSuccess('Data saved');
    }

    public function edit(User $company_manager)
    {
        $this->authorizeManager($company_manager);

        return view('company_manager.create')->with('company',$this->allowedCompanies())->with('company_manager',$company_manager);
    }

    public function update(Request $request, User $company_manager)
    {
        $this->authorizeManager($company_manager);

        $request->validate([
            'name'       => 'required|string|max:255',
            'company_id' => ['required', Rule::in($this->allowedCompanies()->pluck('id')->all())],
            'is_active'  => 'nullable|in:0,1',
        ]);

        $company = Company::findOrFail($request->company_id);

        $data = [
            'name'       => $request->name,
            'company_id' => $company->id,
            'branch_id'  => $company->branch_id,
        ];
        if ($request->filled('is_active')) {
            $data['is_active'] = (int) $request->is_active;
        }
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $company_manager->update($data);
        return redirect()->route('company_manager.index')->withSuccess('Data updated');
    }

    public function destroy(User $company_manager)
    {
        $this->authorizeManager($company_manager);

        $company_manager->delete();

        return redirect()->route('company_manager.index')->withSuccess('Data deleted');
    }

    /** Companies the logged-in user may assign managers to. */
    private function allowedCompanies()
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            return Company::where('branch_id',$login_user->branch_id)->get();
        }
        return Company::all();
    }

    /** The target must be a company manager inside the logged-in user's scope. */
    private function authorizeManager(User $manager)
    {
        $login_user = Auth::user();

        $allowed = $manager->role_id == self::MANAGER_ROLE
            && match ((int) $login_user->role_id) {
                1, 2    => true,
                3       => $manager->branch_id == $login_user->branch_id,
                default => false,
            };

        if (!$allowed) {
            abort(403, 'Access denied');
        }
    }
}
