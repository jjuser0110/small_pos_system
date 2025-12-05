<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\User;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class BranchManagerController extends Controller
{
    public function index(Request $request)
    {
        $branch_manager = User::where('role_id',3)->get();

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
            'username' => 'required|unique:users,username,NULL,id,deleted_at,NULL',
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                        ->withErrors($validator);
        }
        $request->merge(['password' => Hash::make($request->password),'role_id'=>3]);
        $branch_manager = User::create($request->all());

        return redirect()->route('branch_manager.index')->withSuccess('Data saved');
    }

    public function edit(User $branch_manager)
    {
        $branch = Branch::all();
        return view('branch_manager.create')->with('branch',$branch)->with('branch_manager',$branch_manager);
    }

    public function update(Request $request, User $branch_manager)
    {
        if($request->password !=null){
            $request->merge(['password' => Hash::make($request->password)]);
        }else{
            $request->request->remove('password');
        }

        $branch_manager->update($request->all());
        return redirect()->route('branch_manager.index')->withSuccess('Data updated');
    }

    public function destroy(User $branch_manager)
    {
        $branch_manager->delete();

        return redirect()->route('branch_manager.index')->withSuccess('Data deleted');
    }

}
