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
use Bouncer;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;

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
        
        return view('home');
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

    public function receipt(Request $request)
    {
        return view('receipt');
    }
}
