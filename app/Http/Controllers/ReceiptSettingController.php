<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Browsershot\Browsershot;
use Illuminate\Http\Request;
use App\Models\Uom;
use App\Models\ReceiptSetting;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class ReceiptSettingController extends Controller
{
    public function index(Request $request)
    {
        $receipt_setting = ReceiptSetting::find(1);

        return view('receipt_setting.index')->with('receipt_setting',$receipt_setting);
    }

    public function store(Request $request)
    {
        $receipt_setting = ReceiptSetting::find(1);
        if(isset($receipt_setting)){
            $receipt_setting->update($request->all());
        }else{
            ReceiptSetting::create($request->all());
        }

        return redirect()->route('receipt_setting.index')->withSuccess('Data saved');
    }

    public function forPos()
    {
        $receipt_setting = ReceiptSetting::find(1);

        return response()->json([
            'header' => $receipt_setting->header ?? 'WILDFIRE',
            'footer' => $receipt_setting->footer ?? '',
        ]);
    }
}
