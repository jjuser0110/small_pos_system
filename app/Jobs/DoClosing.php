<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Mail;
use App\Models\User;
use App\Models\BankAccount;
use App\Models\PackageInvoice;
use App\Models\BankClosing;
use App\Models\UserClosing;
use App\Models\PackageInvoiceClosing;
use App\Models\DailyReport;
use App\Models\Bonus;
use App\Models\Shareholder;
use App\Models\ShareholderClosing;
use Illuminate\Support\Facades\Log;

class DoClosing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function handle()
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        if (DailyReport::where('daily_date', $yesterday)->exists()) {
            return;
        }

        $user = User::where('role_id', 3)->get();
        $bank_account = BankAccount::all();
        $shareholders = Shareholder::all();
        $package_invoices = PackageInvoice::where('is_paid',0)->get();
        $yesterday = Carbon::now()->format('Y-m-d');

        foreach ($user as $u) {
            UserClosing::create([
                'user_id' => $u->id,
                'closing_date' => $yesterday,
                'amount' => $u->wallet,
                'created_at' => Carbon::now(),
            ]);
        }

        foreach ($bank_account as $bank) {
            BankClosing::create([
                'bank_account_id' => $bank->id,
                'closing_date' => $yesterday,
                'amount' => $bank->amount,
                'created_at' => Carbon::now(),
            ]);
        }

        foreach ($shareholders as $shareholder) {
            ShareholderClosing::create([
                'shareholder_id' => $shareholder->id,
                'closing_date' => $yesterday,
                'amount' => $shareholder->amount,
                'created_at' => Carbon::now(),
            ]);
        }

        foreach ($package_invoices as $package_invoice) {
            PackageInvoiceClosing::create([
                'package_invoice_id' => $package_invoice->id,
                'closing_date' => $yesterday,
                'amount' => $package_invoice->amount,
                'created_at' => Carbon::now(),
            ]);
        }

        $user_closing_total = UserClosing::where('closing_date', $yesterday)->sum('amount');
        $bank_closing_total = BankClosing::where('closing_date', $yesterday)->sum('amount');
        $shareholder_closing_total = ShareholderClosing::where('closing_date', $yesterday)->sum('amount');
        $package_invoice_closing_total = PackageInvoiceClosing::where('closing_date', $yesterday)->sum('amount');
        $bonus = Bonus::where('bonus_date', $yesterday)->sum('bonus_amount');
        $bonus = -$bonus;

        $total = ($bank_closing_total + $shareholder_closing_total) - $user_closing_total + $package_invoice_closing_total + $bonus;

        DailyReport::create([
            'daily_date' => $yesterday,
            'bank_total' => $bank_closing_total,
            'shareholder_total' => $shareholder_closing_total,
            'customer_total' => $user_closing_total,
            'package_havent_pay' => $package_invoice_closing_total,
            'bonus' => $bonus,
            'invoice_total' => $total,
        ]);
    }
}
