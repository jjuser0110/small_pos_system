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
        $shift_closing = ShiftClosing::orderBy('created_at','DESC')->get();

        return view('shift_closing.index')->with('shift_closing',$shift_closing);
    }

    public function view(ShiftClosing $shift_closing)
    {
        return view('shift_closing.view')->with('shift_closing',$shift_closing);
    }

}
