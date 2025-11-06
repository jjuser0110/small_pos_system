<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FollowUpExport implements FromCollection, WithHeadings, WithStyles
{
    protected $date;

    public function __construct($date)
    {
        $this->date = $date;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $date = $this->date;
        $customers = User::where('role_id', 3)->get();

        $customers->each(function ($customer) use ($date) {
            $customer->today_follow_up = $customer->follow_ups()
                ->whereDate('date', $date)
                ->orderBy('created_at', 'desc')
                ->first();
        });

        return $customers->map(function ($customer) {
            return [
                'Code' => $customer->code,
                'Remarks' => $customer->today_follow_up->remarks ?? '',
                'Staff Handle' => $customer->today_follow_up->staff_handle ?? '',
            ];
        });

    }

    public function headings(): array
    {
        return [
            [$this->date],
            [
                'Code',
                'Remarks',
                'Staff Handle',
            ]
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow(); // Get the last row number
        for ($row = 2; $row <= $highestRow; $row++) {
            for ($col = 'A'; $col <= 'C'; $col++) {
                $sheet->getStyle("{$col}{$row}")->applyFromArray([
                    'borders' => [
                        'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK],
                        'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK],
                        'left' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK],
                        'right' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK],
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);
            }
        }
    }
}
