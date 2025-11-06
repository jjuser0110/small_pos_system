<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Symfony\Component\Console\Input\InputOption;
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

class DoClosings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daily:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daily Cron';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        if (DailyReport::where('daily_date', $yesterday)->exists()) {
            return;
        }

        $user = User::where('role_id', 3)->get(['id', 'wallet']);
        $bank_account = BankAccount::all(['id', 'amount']);
        $shareholders = Shareholder::all(['id', 'amount']);
        $package_invoices = PackageInvoice::where('is_paid',0)->get();
        // $yesterday = Carbon::now()->format('Y-m-d');

        $userClosings = [];
        foreach ($user as $u) {
            $userClosings[] = [
                'user_id' => $u->id,
                'closing_date' => $yesterday,
                'amount' => $u->wallet,
                'created_at' => Carbon::now(),
            ];
        }

        $bankClosings = [];
        foreach ($bank_account as $bank) {
            $bankClosings[] = [
                'bank_account_id' => $bank->id,
                'closing_date' => $yesterday,
                'amount' => $bank->amount,
                'created_at' => Carbon::now(),
            ];
        }

        $shareholderClosings = [];
        foreach ($shareholders as $shareholder) {
            $shareholderClosings[] = [
                'shareholder_id' => $shareholder->id,
                'closing_date' => $yesterday,
                'amount' => $shareholder->amount,
                'created_at' => Carbon::now(),
            ];
        }

        $packageInvoiceClosing = [];
        foreach ($package_invoices as $package_invoice) {
            $packageInvoiceClosing[] = [
                'package_invoice_id' => $package_invoice->id,
                'closing_date' => $yesterday,
                'amount' => $package_invoice->balance,
                'created_at' => Carbon::now(),
            ];
        }

        UserClosing::insert($userClosings);
        BankClosing::insert($bankClosings);
        ShareholderClosing::insert($shareholderClosings);
        PackageInvoiceClosing::insert($packageInvoiceClosing);

        $user_closing_total = UserClosing::where('closing_date',$yesterday)->sum('amount');
        $bank_closing_total = BankClosing::where('closing_date',$yesterday)->sum('amount');
        $shareholder_closing_total = ShareholderClosing::where('closing_date',$yesterday)->sum('amount');
        $package_invoice_closing_total = PackageInvoiceClosing::where('closing_date',$yesterday)->sum('amount');
        $bonus = Bonus::where('bonus_date', $yesterday)->sum('bonus_amount');
        $bonus = -$bonus;

        $total = ($bank_closing_total + $shareholder_closing_total) - $user_closing_total + $bonus;
        DailyReport::create([
            'daily_date'=>$yesterday,
            'bank_total'=>round($bank_closing_total,2),
            'shareholder_total'=>round($shareholder_closing_total,2),
            'customer_total'=>round($user_closing_total,2),
            'package_havent_pay'=>round($package_invoice_closing_total,2),
            'bonus' => $bonus,
            'total'=>round($total,2),
        ]);
    }
}
