<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * The one place stock quantities change.
 *
 * Stock lives in two forms:
 *  - batch items (from a Completed batch) with a balance and a cost per unit, used
 *    oldest-first (FIFO) so each sale knows its cost;
 *  - "unbatched" stock (initial quantity, manual adjust-in) with no known cost.
 *
 * products.stock_quantity is always the total. Every change writes a stock_logs row
 * whose `content` is the record that caused it (order item, transfer, damage, ...).
 */
class StockService
{
    private const EPS = 0.00001;

    /**
     * Take $qty out of $product, oldest completed batch first, then unbatched stock.
     *
     * @return array<int, array{batch_item: ?BatchItem, quantity: float, cost_per_unit: ?float}>
     *
     * With $allowNegative (counter sales) a shortfall is taken anyway and stock
     * goes below zero; otherwise (transfers, damage, adjustments) it is refused.
     *
     * @throws RuntimeException when there is not enough stock and negative is not allowed
     */
    public function stockOut(Product $product, float $qty, string $type, string $description, Model $source, bool $allowNegative = false): array
    {
        $this->assertPositive($qty);

        return DB::transaction(function () use ($product, $qty, $type, $description, $source, $allowNegative) {
            $product = Product::lockForUpdate()->findOrFail($product->id);
            $stock = (float) $product->stock_quantity;

            if (!$allowNegative && $qty - $stock > self::EPS) {
                throw new RuntimeException("Not enough stock for {$product->product_name} (have {$stock}, need {$qty}).");
            }

            $remaining = $qty;
            $slices = [];

            $batchItems = BatchItem::where('product_id', $product->id)
                ->where('balance', '>', 0)
                ->whereHas('batch', fn ($q) => $q->where('status', 'Completed'))
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($batchItems as $batchItem) {
                if ($remaining <= self::EPS) {
                    break;
                }
                $take = min($remaining, (float) $batchItem->balance);
                $batchItem->update(['balance' => $this->round($batchItem->balance - $take)]);

                $slices[] = ['batch_item' => $batchItem, 'quantity' => $take, 'cost_per_unit' => (float) $batchItem->cost_per_unit];
                $stock = $this->log($product, $source, $type, $this->describe($description, $batchItem), $stock, -$take);
                $remaining -= $take;
            }

            if ($remaining > self::EPS) {
                $slices[] = ['batch_item' => null, 'quantity' => $this->round($remaining), 'cost_per_unit' => null];
                $stock = $this->log($product, $source, $type, trim($description . ' (no batch)'), $stock, -$remaining);
            }

            $product->update(['stock_quantity' => $this->round($stock)]);

            return $slices;
        });
    }

    /**
     * Add $qty to $product. With a $batch and a cost the stock goes into a new batch
     * item (so later sales know its cost); otherwise it is unbatched stock.
     */
    public function stockIn(Product $product, float $qty, string $type, string $description, Model $source, ?float $costPerUnit = null, ?Batch $batch = null): ?BatchItem
    {
        $this->assertPositive($qty);

        return DB::transaction(function () use ($product, $qty, $type, $description, $source, $costPerUnit, $batch) {
            $product = Product::lockForUpdate()->findOrFail($product->id);

            $batchItem = null;
            if ($batch && $costPerUnit !== null) {
                $batchItem = BatchItem::create([
                    'batch_id'      => $batch->id,
                    'branch_id'     => $product->branch_id,
                    'company_id'    => $product->company_id,
                    'category_id'   => $product->category_id,
                    'product_id'    => $product->id,
                    'quantity'      => $qty,
                    'balance'       => $qty,
                    'cost_per_unit' => round($costPerUnit, 4),
                    'total_cost'    => round($costPerUnit * $qty, 2),
                ]);
            }

            $after = $this->log($product, $source, $type, $this->describe($description, $batchItem), (float) $product->stock_quantity, $qty);
            $product->update(['stock_quantity' => $this->round($after)]);

            return $batchItem;
        });
    }

    /**
     * Put $qty of a sold order item back into stock (refund / void): back onto the
     * batch items it was sold from, reducing the matching profit rows; any part sold
     * from unbatched stock goes back as unbatched stock.
     */
    public function returnSale(OrderItem $item, float $qty, string $type, string $description, Model $source): void
    {
        $this->assertPositive($qty);

        DB::transaction(function () use ($item, $qty, $type, $description, $source) {
            $product = Product::lockForUpdate()->findOrFail($item->product_id);
            $stock = (float) $product->stock_quantity;
            $remaining = $qty;

            $profits = $item->profit_items()->orderByDesc('id')->lockForUpdate()->get();
            foreach ($profits as $profit) {
                if ($remaining <= self::EPS) {
                    break;
                }
                $back = min($remaining, (float) $profit->quantity);

                $batchItem = BatchItem::lockForUpdate()->find($profit->batch_item_id);
                if ($batchItem) {
                    $batchItem->update(['balance' => $this->round($batchItem->balance + $back)]);
                }

                $left = $this->round($profit->quantity - $back);
                if ($left <= self::EPS) {
                    $profit->delete();
                } else {
                    $profit->update([
                        'quantity'            => $left,
                        'total_cost_price'    => round($profit->cost_price * $left, 2),
                        'total_selling_price' => round($profit->selling_price * $left, 2),
                        'total_earning'       => round(($profit->selling_price - $profit->cost_price) * $left, 2),
                    ]);
                }

                $stock = $this->log($product, $source, $type, $this->describe($description, $batchItem), $stock, $back);
                $remaining -= $back;
            }

            if ($remaining > self::EPS) {
                $stock = $this->log($product, $source, $type, trim($description . ' (no batch)'), $stock, $remaining);
            }

            $product->update(['stock_quantity' => $this->round($stock)]);
        });
    }

    /** Write one stock log row and return the stock after the change. */
    private function log(Product $product, Model $source, string $type, string $description, float $before, float $delta): float
    {
        $after = $this->round($before + $delta);

        StockLog::create([
            'content_type' => $source->getMorphClass(),
            'content_id'   => $source->getKey(),
            'branch_id'    => $product->branch_id,
            'company_id'   => $product->company_id,
            'category_id'  => $product->category_id,
            'product_id'   => $product->id,
            'type'         => $type,
            'description'  => $description,
            'before_stock' => $this->round($before),
            'quantity'     => $this->round(abs($delta)),
            'after_stock'  => $after,
        ]);

        return $after;
    }

    private function describe(string $description, ?BatchItem $batchItem): string
    {
        $batchNo = $batchItem?->batch?->batch_no;
        return trim($description . ($batchNo ? " ({$batchNo})" : ''));
    }

    private function assertPositive(float $qty): void
    {
        if ($qty <= 0) {
            throw new InvalidArgumentException('Quantity must be more than zero.');
        }
    }

    private function round(float $value): float
    {
        return round($value, 4);
    }
}
