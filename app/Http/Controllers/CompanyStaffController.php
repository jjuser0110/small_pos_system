<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Support\ShiftWindow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CompanyStaffController extends Controller
{
    const STAFF_ROLE = 5;

    public function index(Request $request)
    {
        $login_user = Auth::user();
        $query = User::with('company')->where('role_id', self::STAFF_ROLE);
        if ($login_user->role_id == 3) {
            $query->where('branch_id', $login_user->branch_id);
        }

        return view('company_staff.index')->with('company_staff', $query->get());
    }

    public function create()
    {
        return view('company_staff.create')->with('company', $this->scopedCompanies());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'       => 'required|string|max:255',
            'username'   => 'required|unique:users,username,NULL,id,deleted_at,NULL',
            'password'   => 'required|string',
            'company_id' => ['required', Rule::in($this->scopedCompanies()->pluck('id')->all())],
            'shift'      => ['required', Rule::in(ShiftWindow::shifts())],
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                        ->withErrors($validator)
                        ->withInput($request->except('password'));
        }

        $company = Company::findOrFail($request->company_id);

        // only whitelisted fields; role and branch are always set by the server
        User::create([
            'name'       => $request->name,
            'username'   => $request->username,
            'password'   => Hash::make($request->password),
            'role_id'    => self::STAFF_ROLE,
            'company_id' => $company->id,
            'branch_id'  => $company->branch_id,
            'shift'      => $request->shift,
        ]);

        return redirect()->route('company_staff.index')->withSuccess('Data saved');
    }

    public function edit(User $company_staff)
    {
        $this->authorizeStaff($company_staff);

        return view('company_staff.create')->with('company', $this->scopedCompanies())->with('company_staff', $company_staff);
    }

    public function update(Request $request, User $company_staff)
    {
        $this->authorizeStaff($company_staff);

        $request->validate([
            'name'       => 'required|string|max:255',
            'company_id' => ['required', Rule::in($this->scopedCompanies()->pluck('id')->all())],
            'shift'      => ['required', Rule::in(ShiftWindow::shifts())],
            'is_active'  => 'nullable|in:0,1',
        ]);

        $company = Company::findOrFail($request->company_id);

        $data = [
            'name'       => $request->name,
            'company_id' => $company->id,
            'branch_id'  => $company->branch_id,
            'shift'      => $request->shift,
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

    /** The target must be a staff account inside the logged-in user's scope. */
    private function authorizeStaff(User $staff)
    {
        if ($staff->role_id != self::STAFF_ROLE || !$this->userCanAccess($staff->branch_id, $staff->company_id)) {
            abort(403, 'Access denied');
        }
    }
}
