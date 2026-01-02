<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Uom;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ProductImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    protected $branch_id;
    protected $company_id;
    protected bool $skip = false;

    public function __construct($branch_id, $company_id)
    {
        $this->branch_id = $branch_id;
        $this->company_id = $company_id;
    }

    public function model(array $row)
    {
        if (collect($row)->every(fn ($v) => trim((string)$v) === '')) {
            return null;
        }

        $categoryRaw = $row['category'] ?? null;
        $uomRaw      = $row['uom'] ?? null;
        $nameRaw     = $row['product_name'] ?? null;
        $priceRaw   = $row['selling_price'] ?? null;

        if (!$categoryRaw || !$uomRaw || !$nameRaw || $priceRaw === null) {
            return null;
        }

        if (preg_match('/^(.*)\s\((.*)\)$/', $categoryRaw, $matches)) {
            $categoryName = trim($matches[1]);
        } else {
            $categoryName = trim($categoryRaw);
        }

        $category = Category::firstOrCreate(
            [
                'category_name' => $categoryName,
                'company_id'    => $this->company_id,
            ],
            [
                'branch_id' => $this->branch_id,
            ]
        );

        $uom = Uom::firstOrCreate(
            ['uom_name' => trim($uomRaw)],
            ['uom_unit' => strtolower(trim($uomRaw))]
        );

        return new Product([
            'branch_id'     => $this->branch_id,
            'company_id'    => $this->company_id,
            'category_id'   => $category->id,
            'product_name'  => trim($nameRaw),
            'product_code'  => trim($row['product_code'] ?? ''),
            'barcode'       => trim($row['barcode'] ?? ''),
            'uom'           => $uom->id,
            'selling_price' => $priceRaw,
        ]);
    }

    public function rules(): array
    {
        return [];
    }
}
