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
use App\Models\StaffAttendance;
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

        $categoryQuery = Category::query();
        $productQuery = Product::with(['category', 'uom_dt', 'units'])->where('is_active', 1);
        if ($login_user->role_id == 3) {
            $categoryQuery->where('branch_id', $login_user->branch_id);
            $productQuery->where('branch_id', $login_user->branch_id);
        } elseif ($login_user->role_id == 5) {
            $company = $login_user->workingCompany();
            $categoryQuery->where('company_id', $company?->id);
            $productQuery->where('company_id', $company?->id);
        }

        // arrangement first (blank arrangement last), then name
        $category = $categoryQuery->orderByRaw('arrangement IS NULL')->orderBy('arrangement')->orderBy('category_name')->get();
        $categoryOrder = $category->pluck('id')->flip();

        $products = $productQuery->orderByRaw('arrangement IS NULL')->orderBy('arrangement')->orderBy('product_name')->get()
            ->sortBy(fn ($p) => $categoryOrder[$p->category_id] ?? PHP_INT_MAX, SORT_REGULAR)
            ->values();

        return view('counter', [
            'category'  => $category,
            'sellables' => $this->sellables($products),
        ]);
    }

    /** One tile per thing the counter can sell: each item, and each of its sell options. */
    private function sellables($products): array
    {
        $tiles = [];
        foreach ($products as $p) {
            $stock = (float) $p->stock_quantity;
            $tiles[] = [
                'key'         => 'p' . $p->id,
                'product_id'  => $p->id,
                'unit_id'     => null,
                'category_id' => $p->category_id,
                'name'        => $p->product_name,
                'unit_label'  => $p->uom_dt->uom_name ?? null,
                'price'       => round((float) $p->selling_price, 2),
                'barcode'     => $p->barcode,
                'stock'       => $stock,
            ];
            foreach ($p->relationLoaded('units') ? $p->units : [] as $unit) {
                $tiles[] = [
                    'key'         => 'u' . $unit->id,
                    'product_id'  => $p->id,
                    'unit_id'     => $unit->id,
                    'category_id' => $p->category_id,
                    'name'        => $p->product_name,
                    'unit_label'  => $unit->name,
                    'price'       => round((float) $unit->price, 2),
                    'barcode'     => $unit->barcode,
                    'stock'       => $unit->base_qty > 0 ? floor($stock / $unit->base_qty) : 0,
                ];
            }
        }
        return $tiles;
    }

    public function checkout(Request $request)
    {
        $payment_method = PaymentMethod::all();
        return view('checkout')->with('payment_method',$payment_method);
    }

    public function receipt(Request $request, $order_id)
    {
        $order = Order::findOrFail($order_id);
        // staff may reprint their own receipts; managers within their scope
        $this->authorizeRecord($order, 'user_id');

        $receipt_setting = ReceiptSetting::find(1);
        return view('receipt')->with('order',$order)->with('receipt_setting',$receipt_setting);
    }

    public function shift_closing(Request $request)
    {
        $user = Auth::user();
        $shift = ShiftClosing::where('user_id', $user->id)
            ->whereNull('closing_time')
            ->first();

        $closed = false;

        if (isset($shift)) {
            $shift->update(['closing_time' => Carbon::now()]);
            $closed = true;
        }

        if ($user->isStaff()) {
            $open = StaffAttendance::where('user_id', $user->id)
                ->where('work_date', StaffAttendance::workDateFor($user))
                ->whereNull('clock_out')
                ->exists();

            if ($open || $closed) {
                StaffAttendance::clockOut($user, 'closing', $shift?->created_at);
                $closed = true;
            }
        }

        if (!$closed) {
            return redirect()->back()->withInfo('Nothing To Close');
        }

        // closed successfully: log out
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withSuccess('Shift closed. You have been logged out.');
    }
}
