<?php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockTemplateExport implements WithHeadings, WithEvents
{
    protected $batch;

    public function __construct($batch)
    {
        $this->batch = $batch;
    }


    public function headings(): array
    {
        return [
            'Product',
            'Barcode',
            'Quantity',
            'Single Cost',
            'Total Cost',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {

                $sheet = $event->sheet->getDelegate();
                $spreadsheet = $sheet->getParent();
                $login_user = Auth::user();

                /* -------------------------
                 | Create hidden dropdown sheet
                 |--------------------------*/
                $dropdownSheet = new Worksheet($spreadsheet, 'dropdowns');
                $spreadsheet->addSheet($dropdownSheet);
                $dropdownSheet->setSheetState(Worksheet::SHEETSTATE_HIDDEN);

                /* -------------------------
                 | Fill dropdown data
                 |--------------------------*/
                $products = Product::where('branch_id', $this->batch->branch_id)->where('company_id', $this->batch->company_id)->pluck('product_name')->toArray();

                $dropdownSheet->fromArray(
                    array_merge([['Products']], array_map(fn($v) => [$v], $products)),
                    null,
                    'A1'
                );

                /* -------------------------
                 | Apply dropdowns
                 |--------------------------*/
                $this->setDropdown($sheet, 'A', count($products), 'dropdowns!$A$2:$A$');

                if (count($products) > 0) {
                    $this->addInvalidCellHighlight(
                        $sheet,
                        'A',
                        2,
                        500,
                        '=AND(A2<>"", ISNA(MATCH(A2,dropdowns!$A$2:$A$' . (count($products) + 1) . ',0)))'
                    );
                }

                foreach (range('A', 'E') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                /* -------------------------
                | Set formulas for calculations
                |--------------------------*/
                foreach (range(2, 500) as $row) {
                    // Total Cost (E) = Quantity * Single Cost
                    // Only calculates if D or C changed
                    $sheet->setCellValue(
                        'E' . $row,
                        '=IF(AND(ISNUMBER(C' . $row . '),ISNUMBER(D' . $row . ')),C' . $row . '*D' . $row . ',0)'
                    );

                    // Single Cost (D) = Total Cost / Quantity
                    // Only updates if Quantity > 0
                    $sheet->setCellValue(
                        'D' . $row,
                        '=IF(C' . $row . '=0,0,E' . $row . '/C' . $row . ')'
                    );
                }

                // Format columns D & E as currency
                $sheet->getStyle('D2:E500')->getNumberFormat()->setFormatCode('#,##0.00');
            }
        ];
    }

    private function setDropdown($sheet, $column, $count, $formulaBase)
    {
        for ($row = 2; $row <= 500; $row++) {
            $validation = new DataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Invalid selection');
            $validation->setError('Please select a value from the dropdown list.');
            $validation->setFormula1($formulaBase . ($count + 1));

            $sheet->getCell($column . $row)->setDataValidation(clone $validation);
        }
    }

    private function addInvalidCellHighlight(Worksheet $sheet, string $column, int $startRow, int $endRow, string $formula)
    {
        $conditional = new Conditional();
        $conditional->setConditionType(Conditional::CONDITION_EXPRESSION);
        $conditional->setOperatorType(Conditional::OPERATOR_NONE);
        $conditional->setConditions($formula);
        $conditional->getStyle()->getFill()->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('FF9999'); // light red

        $sheet->getStyle("{$column}{$startRow}:{$column}{$endRow}")
            ->setConditionalStyles([$conditional]);
    }
}
