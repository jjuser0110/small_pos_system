<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Bank;

class BankSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Bank::truncate();
        $data = [
            [
                'bank_name'=>'Maybank',
                'short_name'=>'MBB',
            ],
            [
                'bank_name'=>'CIMB Bank',
                'short_name'=>'CIMB',
            ],
            [
                'bank_name'=>'Public Bank',
                'short_name'=>'PBB',
            ],
            [
                'bank_name'=>'RHB Bank',
                'short_name'=>'RHB',
            ],
            [
                'bank_name'=>'Hong Leong Bank',
                'short_name'=>'HLB',
            ],
            [
                'bank_name'=>'AmBank',
                'short_name'=>'AMB',
            ],
            [
                'bank_name'=>'UOB Bank',
                'short_name'=>'UOB',
            ],
            [
                'bank_name'=>'Bank Rakyat',
                'short_name'=>'BANK RAKYAT',
            ],
            [
                'bank_name'=>'OCBC Bank',
                'short_name'=>'OCBC',
            ],
            [
                'bank_name'=>'HSBC Bank',
                'short_name'=>'HSBC',
            ],
            [
                'bank_name'=>'Bank Islam',
                'short_name'=>'ISLAM',
            ],
            [
                'bank_name'=>'Affin Bank',
                'short_name'=>'AFFIN',
            ],
            [
                'bank_name'=>'Alliance Bank',
                'short_name'=>'ALLIANCE',
            ],
            [
                'bank_name'=>'Standard Charted Bank',
                'short_name'=>'SCB',
            ],
            [
                'bank_name'=>'MBSB Bank',
                'short_name'=>'MBSB',
            ],
            [
                'bank_name'=>'Citibank',
                'short_name'=>'CITI',
            ],
            [
                'bank_name'=>'Bank Simpanan Nasional (BSN)',
                'short_name'=>'BSN',
            ],
            [
                'bank_name'=>'Bank Muamalat',
                'short_name'=>'MUAMALAT',
            ],
            [
                'bank_name'=>'Agrobank',
                'short_name'=>'AGRO',
            ],
            [
                'bank_name'=>'TNG',
                'short_name'=>'TNG',
            ],
        ];
        Bank::insert($data);
    }
}
