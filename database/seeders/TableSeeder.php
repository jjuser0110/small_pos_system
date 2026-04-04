<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Table;

class TableSeeder extends Seeder
{
    public function run(): void
    {
        Table::truncate();
        $tables = [
            'T1', 'T2', 'T3',
            'T4',
            'T5', 'T6',
            'T7', 'T8',
            'T9', 'T10',
        ];

        foreach ($tables as $name) {
            Table::create([
                'table_name' => $name,
                'type'       => 0,
                'total'      => 0,
            ]);
        }
    }
}