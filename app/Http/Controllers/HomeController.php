<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Browsershot\Browsershot;
use App\Models\User;
use App\Models\Package;
use App\Models\PackageInvoice;
use App\Models\BankAccount;
use App\Models\DailyReport;
use App\Models\Product;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemProfit;
use App\Models\ReceiptSetting;
use App\Models\ShiftClosing;
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;
use Mike42\Escpos\Printer;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }


    public function index(Request $request)
    {
        $user = Auth::user();
        $shift_data = ShiftClosing::where('user_id',$user->id)->whereNull('closing_time')->first();

        return view('home')->with('shift_data',$shift_data);
    }

    public function change_password(Request $request){
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);


        if ($validator->fails()) {
            $message = "";
            foreach($validator->messages()->messages() as $m){
                foreach($m as $mm){
                    $message .=$mm.'\n';
                }
            }
            return redirect()->back()->withInfo($message);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return redirect()->route('home')->withSuccess('Password changed successfully.');
    }

    public function counter(Request $request)
    {
        $login_user = Auth::user();
        if($login_user->role_id == 3){
            $category = Category::where('branch_id',$login_user->branch_id)->get();
            $products = Product::where('branch_id',$login_user->branch_id)->get();
        }else if($login_user->role_id == 4){
            $category = Category::where('company_id',$login_user->company_id)->get();
            $products = Product::where('company_id',$login_user->company_id)->get();
        }else{
            $category = Category::all();
            $products = Product::all();
        }
        return view('counter')->with('category',$category)->with('products',$products);
    }

    public function checkout(Request $request)
    {
        $payment_method = PaymentMethod::all();
        return view('checkout')->with('payment_method',$payment_method);
    }

    public function receipt(Request $request, $order_id)
    {
        $order = Order::find($order_id);
        $receipt_setting = ReceiptSetting::find(1);
        return view('receipt')->with('order',$order)->with('receipt_setting',$receipt_setting);
    }

    public function shift_closing(Request $request)
    {
        $user = Auth::user();
        $shift = ShiftClosing::where('user_id', $user->id)
            ->whereNull('closing_time')
            ->first();

        if(isset($shift)){
            $shift->update([
                'closing_time'=>Carbon::now()
            ]);
            return redirect()->back()->withInfo("Shift Closed");
        }else{
            return redirect()->back()->withInfo('Nothing To Close');
        }
    }
}
