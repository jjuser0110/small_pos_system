<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class BranchManagerController extends Controller
{
    const MANAGER_ROLE = 3;

    public function index(Request $request)
    {
        $branch_manager = User::where('role_id',self::MANAGER_ROLE)->get();

        return view('branch_manager.index')->with('branch_manager',$branch_manager);
    }

    public function create()
    {
        $branch = Branch::all();
        return view('branch_manager.create')->with('branch',$branch);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'username'  => 'required|unique:users,username,NULL,id,deleted_at,NULL',
            'password'  => 'required|string',
            'branch_id' => 'required|exists:branches,id',
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                        ->withErrors($validator)
                        ->withInput($request->except('password'));
        }

        User::create([
            'name'      => $request->name,
            'username'  => $request->username,
            'password'  => Hash::make($request->password),
            'role_id'   => self::MANAGER_ROLE,
            'branch_id' => $request->branch_id,
        ]);

        return redirect()->route('branch_manager.index')->withSuccess('Data saved');
    }

    public function edit(User $branch_manager)
    {
        $this->ensureBranchManager($branch_manager);

        $branch = Branch::all();
        return view('branch_manager.create')->with('branch',$branch)->with('branch_manager',$branch_manager);
    }

    public function update(Request $request, User $branch_manager)
    {
        $this->ensureBranchManager($branch_manager);

        $request->validate([
            'name'      => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
            'is_active' => 'nullable|in:0,1',
        ]);

        $data = [
            'name'      => $request->name,
            'branch_id' => $request->branch_id,
        ];
        if ($request->filled('is_active')) {
            $data['is_active'] = (int) $request->is_active;
        }
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $branch_manager->update($data);
        return redirect()->route('branch_manager.index')->withSuccess('Data updated');
    }

    public function destroy(User $branch_manager)
    {
        $this->ensureBranchManager($branch_manager);

        $branch_manager->delete();

        return redirect()->route('branch_manager.index')->withSuccess('Data deleted');
    }

    /** These routes may only touch branch manager accounts, never admins or other roles. */
    private function ensureBranchManager(User $user)
    {
        if ($user->role_id != self::MANAGER_ROLE) {
            abort(403, 'Access denied');
        }
    }
}
