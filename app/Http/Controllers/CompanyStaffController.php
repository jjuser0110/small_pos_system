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

class CompanyStaffController extends Controller
{
    const STAFF_ROLE = 5;

    public function index(Request $request)
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $company_staff = User::where('branch_id',$login_user->branch_id)->where('role_id',self::STAFF_ROLE)->get();
        }else if($login_user->role_id == 4){
            $company_staff = User::where('company_id',$login_user->company_id)->where('role_id',self::STAFF_ROLE)->get();
        }else{
            $company_staff = User::where('role_id',self::STAFF_ROLE)->get();
        }

        return view('company_staff.index')->with('company_staff',$company_staff);
    }

    public function create()
    {
        return view('company_staff.create')->with('company',$this->allowedCompanies());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255',
            'username'    => 'required|unique:users,username,NULL,id,deleted_at,NULL',
            'password'    => 'required|string',
            'company_id'  => ['required', Rule::in($this->allowedCompanies()->pluck('id')->all())],
            'shift'       => 'required|in:AM,PM',
            'shift_start' => 'required|date_format:H:i',
            'shift_end'   => 'required|date_format:H:i|different:shift_start',
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                        ->withErrors($validator)
                        ->withInput($request->except('password'));
        }

        $company = Company::findOrFail($request->company_id);

        // only whitelisted fields; role and branch are always set by the server
        User::create([
            'name'        => $request->name,
            'username'    => $request->username,
            'password'    => Hash::make($request->password),
            'role_id'     => self::STAFF_ROLE,
            'company_id'  => $company->id,
            'branch_id'   => $company->branch_id,
            'shift'       => $request->shift,
            'shift_start' => $request->shift_start,
            'shift_end'   => $request->shift_end,
        ]);

        return redirect()->route('company_staff.index')->withSuccess('Data saved');
    }

    public function edit(User $company_staff)
    {
        $this->authorizeStaff($company_staff);

        return view('company_staff.create')->with('company',$this->allowedCompanies())->with('company_staff',$company_staff);
    }

    public function update(Request $request, User $company_staff)
    {
        $this->authorizeStaff($company_staff);

        $request->validate([
            'name'        => 'required|string|max:255',
            'company_id'  => ['required', Rule::in($this->allowedCompanies()->pluck('id')->all())],
            'shift'       => 'required|in:AM,PM',
            'shift_start' => 'required|date_format:H:i',
            'shift_end'   => 'required|date_format:H:i|different:shift_start',
            'is_active'   => 'nullable|in:0,1',
        ]);

        $company = Company::findOrFail($request->company_id);

        $data = [
            'name'        => $request->name,
            'company_id'  => $company->id,
            'branch_id'   => $company->branch_id,
            'shift'       => $request->shift,
            'shift_start' => $request->shift_start,
            'shift_end'   => $request->shift_end,
        ];
        if ($request->filled('is_active')) {
            $data['is_active'] = (int) $request->is_active;
        }
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $company_staff->update($data);
        return redirect()->route('company_staff.index')->withSuccess('Data updated');
    }

    public function destroy(User $company_staff)
    {
        $this->authorizeStaff($company_staff);

        $company_staff->delete();

        return redirect()->route('company_staff.index')->withSuccess('Data deleted');
    }

    /** Companies the logged-in user may assign staff to. */
    private function allowedCompanies()
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            return Company::where('branch_id',$login_user->branch_id)->get();
        }else if($login_user->role_id == 4){
            return Company::where('id',$login_user->company_id)->get();
        }
        return Company::all();
    }

    /** The target must be a staff account inside the logged-in user's scope. */
    private function authorizeStaff(User $staff)
    {
        $login_user = Auth::user();

        $allowed = $staff->role_id == self::STAFF_ROLE
            && match ((int) $login_user->role_id) {
                1, 2    => true,
                3       => $staff->branch_id == $login_user->branch_id,
                4       => $staff->company_id == $login_user->company_id,
                default => false,
            };

        if (!$allowed) {
            abort(403, 'Access denied');
        }
    }
}
