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
use Carbon\Carbon;

class ShiftClosingController extends Controller
{
    public function index(Request $request)
    {
        $date_from = $request->date_from
            ? Carbon::parse($request->date_from)
            : Carbon::now()->startOfDay();

        $date_to = $request->date_to
            ? Carbon::parse($request->date_to)
            : Carbon::now()->endOfDay();

        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $shift_closing = ShiftClosing::where('branch_id',$login_user->branch_id)->whereBetween('created_at', [$date_from, $date_to])->orderBy('created_at','DESC')->get();
        }else if($login_user->role_id == 4){
            $shift_closing = ShiftClosing::where('company_id',$login_user->company_id)->whereBetween('created_at', [$date_from, $date_to])->orderBy('created_at','DESC')->get();
        }else if($login_user->role_id == 5){
            $shift_closing = ShiftClosing::where('user_id',$login_user->id)->whereBetween('created_at', [$date_from, $date_to])->orderBy('created_at','DESC')->get();
        }else{
            $shift_closing = ShiftClosing::orderBy('created_at','DESC')->whereBetween('created_at', [$date_from, $date_to])->get();
        }

        return view('shift_closing.index', [
            'date_from' => $date_from->format('Y-m-d'),
            'date_to'   => $date_to->format('Y-m-d'),
            'shift_closing'      => $shift_closing,
        ]);
    }

    public function view(ShiftClosing $shift_closing)
    {
        return view('shift_closing.view')->with('shift_closing',$shift_closing);
    }

}
