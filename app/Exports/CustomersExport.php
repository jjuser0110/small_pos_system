<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return User::with(['last_package_invoice', 'last_invoice'])
            ->where('role_id', 3)
            ->orderBy('code', 'ASC')
            ->get()
            ->map(function ($user) {
                return [
                    'code'              => $user->code,
                    'wallet'            => $user->wallet,
                    'name'              => $user->name,
                    'package_name'      => $user->package->package_name ?? '',
                    'package_price'     => $user->package->amount ?? '',
                    'package_date_from' => $user->last_package_invoice->invoice_date ?? '',
                    'package_date_to'   => $user->last_package_invoice->invoice_date_to ?? '',
                    'remarks'           => $user->last_package_invoice->remarks ?? '',
                    'last_invoice'      => $user->last_invoice->total ?? '',
                    'status'            => $user->is_active == 1 ? 'Active' : 'Inactive',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Code',
            'Wallet',
            'Customer Name',
            'Package Name',
            'Package Price',
            'Package Date From',
            'Package Date To',
            'Remarks',
            'Last Inovice',
            'Status'
        ];
    }
}
