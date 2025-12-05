<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\ShiftClosing;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ShiftClosingController extends Controller
{
    public function index(Request $request)
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $shift_closing = ShiftClosing::where('branch_id',$login_user->branch_id)->orderBy('created_at','DESC')->get();
        }else if($login_user->role_id == 4){
            $shift_closing = ShiftClosing::where('company_id',$login_user->company_id)->orderBy('created_at','DESC')->get();
        }else{
            $shift_closing = ShiftClosing::orderBy('created_at','DESC')->get();
        }
        

        return view('shift_closing.index')->with('shift_closing',$shift_closing);
    }

    public function view(ShiftClosing $shift_closing)
    {
        return view('shift_closing.view')->with('shift_closing',$shift_closing);
    }

}
