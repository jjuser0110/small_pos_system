<?php

namespace App\Exports;

use App\Models\Expense;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExpensesExport implements FromCollection, WithHeadings
{
    protected $from_date;
    protected $to_date;

    public function __construct($from_date, $to_date)
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Expense::with(['expense_type', 'createdBy', 'paidBy', 'content'])
            ->whereBetween('created_at', [$this->from_date, $this->to_date])
            ->get()
            ->map(function ($expense) {
                return [
                    'Type' => $expense->expense_type->name ?? '',
                    'Date' => $expense->expense_date ?? '',
                    'Remarks' => $expense->remarks,
                    'Amount' => $expense->expense_amount,
                    'Created By' => $expense->createdBy->name ?? '',
                    'Paid By' => $expense->paidBy->name ?? '',
                    'Paid At' => $expense->paid_at,
                    'Bank / Shareholder Used' => $this->getContentDetails($expense),
                    'Status' => $expense->paid_at ? 'Paid' : 'Unpaid',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Type',
            'Date',
            'Remarks',
            'Amount',
            'Created By',
            'Paid By',
            'Paid At',
            'Bank / Shareholder Used',
            'Status'
        ];
    }

    private function getContentDetails($expense): string
    {
        if ($expense->content_type === 'App\Models\BankAccount') {
            $bank_name = $expense->content->bank->bank_name ?? '';
            $owner_name = $expense->content->owner_name ?? '';

            return $bank_name . ' (' . $owner_name . ')';
        }

        if ($expense->content_type === 'App\Models\Shareholder') {
            return $expense->content->owner_name ?? '';
        }

        return '';
    }
}
