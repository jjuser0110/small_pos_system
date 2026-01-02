<?php

namespace App\Exports;

use App\Models\Category;
use App\Models\Product;
use App\Models\Uom;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductTemplateExport implements WithHeadings, WithEvents
{

    public function headings(): array
    {
        return [
            'Category',
            'Product Name',
            'Product Code',
            'Barcode',
            'UOM',
            'Selling Price',
            // 'Connected Product',
            // 'Connected Product Quantity',
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
                if ($login_user->role_id == 3){
                    $categories = Category::where('branch_id', $login_user->branch_id)
                        ->get()
                        ->map(fn($category) => $category->category_name . ' (' . $category->company->company_name . ')')
                        ->toArray();
                } elseif ($login_user->role_id == 4) {
                    $categories = Category::where('company_id', $login_user->company_id)
                        ->get()
                        ->map(fn($category) => $category->category_name . ' (' . $category->company->company_name . ')')
                        ->toArray();
                } else {
                    $categories = Category::get()
                        ->map(fn($category) => $category->category_name . ' (' . $category->company->company_name . ')')
                        ->toArray();
                }
                $uoms       = Uom::pluck('uom_name')->toArray();
                // $products   = Product::where('branch_id', $login_user->branch_id)->pluck('product_name')->toArray();

                $dropdownSheet->fromArray(
                    array_merge([['Categories']], array_map(fn($v) => [$v], $categories)),
                    null,
                    'A1'
                );

                $dropdownSheet->fromArray(
                    array_merge([['UOM']], array_map(fn($v) => [$v], $uoms)),
                    null,
                    'B1'
                );

                // $dropdownSheet->fromArray(
                //     array_merge([['Products']], array_map(fn($v) => [$v], $products)),
                //     null,
                //     'C1'
                // );

                /* -------------------------
                 | Apply dropdowns
                 |--------------------------*/
                $this->setDropdown($sheet, 'A', count($categories), 'dropdowns!$A$2:$A$');
                $this->setDropdown($sheet, 'E', count($uoms), 'dropdowns!$B$2:$B$');
                // $this->setDropdown($sheet, 'B', count($products), 'dropdowns!$C$2:$C$');

                if (count($categories) > 0) {
                    $this->addInvalidCellHighlight(
                        $sheet,
                        'A',
                        2,
                        500,
                        '=AND(A2<>"", ISNA(MATCH(A2,dropdowns!$A$2:$A$' . (count($categories) + 1) . ',0)))'
                    );
                }

                if (count($uoms) > 0) {
                    $this->addInvalidCellHighlight(
                        $sheet,
                        'E',
                        2,
                        500,
                        '=AND(E2<>"", ISNA(MATCH(E2,dropdowns!$B$2:$B$' . (count($uoms) + 1) . ',0)))'
                    );
                }

                foreach (range('A', 'F') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
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
