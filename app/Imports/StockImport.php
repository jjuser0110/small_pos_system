<?php

namespace App\Imports;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class StockImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    protected $batch;

    public function __construct(Batch $batch)
    {
        $this->batch = $batch;
    }

     public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
// dd($row);
            /* -------------------------
            | Skip row if both product AND barcode are empty
            |--------------------------*/
            if (empty(trim($row['product'] ?? '')) && empty(trim($row['barcode'] ?? ''))) {
                continue;
            }

            /* -------------------------
             | Find product (name or barcode)
             |--------------------------*/
            $product = Product::where('company_id', $this->batch->company_id)
                ->where('branch_id', $this->batch->branch_id)
                ->where(function ($q) use ($row) {
                    $q->where('product_name', $row['product'])
                      ->orWhere('barcode', $row['barcode']);
                })
                ->first();

            if (!$product) {
                // silently skip or log error
                continue;
            }

            $quantity   = (float) ($row['quantity'] ?? 0);
            $singleCost = isset($row['single_cost']) ? (float) $row['single_cost'] : 0;
            $totalCost  = 0;

            // Skip invalid quantity
            if ($quantity <= 0) {
                continue;
            }

            // If singleCost is not provided but totalCost is numeric, calculate singleCost
            if ($singleCost <= 0) {
                // total_cost might be a formula string — ignore formulas
                if (isset($row['total_cost']) && is_numeric($row['total_cost'])) {
                    $singleCost = (float) $row['total_cost'] / $quantity;
                }
            }

            // Always calculate totalCost in PHP
            $totalCost = $quantity * $singleCost;

            // Skip if both costs are zero
            if ($singleCost <= 0 && $totalCost <= 0) {
                continue;
            }

            /* -------------------------
             | Create batch item (same as addBatchItem)
             |--------------------------*/
            BatchItem::create([
                'batch_id'      => $this->batch->id,
                'product_id'    => $product->id,
                'branch_id'     => $product->branch_id,
                'company_id'    => $product->company_id,
                'category_id'   => $product->category_id,
                'quantity'      => $quantity,
                'cost_per_unit' => round($singleCost, 2),
                'total_cost'    => round($totalCost, 2),
                'balance'       => $quantity,
            ]);
        }
    }
}
