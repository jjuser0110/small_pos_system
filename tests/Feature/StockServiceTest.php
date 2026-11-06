<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\StockLog;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    private StockService $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stock = app(StockService::class);
    }

    public function test_stock_out_uses_oldest_completed_batch_first(): void
    {
        $company = $this->makeCompany($this->makeBranch());
        $product = $this->makeProduct($company);
        $old = $this->stockBatch($product, 3, 2.00, 'SB-OLD');
        $this->travel(1)->minutes();
        $new = $this->stockBatch($product, 5, 4.00, 'SB-NEW');

        $slices = $this->stock->stockOut($product, 4, 'stock_out', 'Test', $product);

        $this->assertCount(2, $slices);
        $this->assertSame([3.0, 1.0], array_map(fn ($s) => (float) $s['quantity'], $slices));
        $this->assertSame([2.0, 4.0], array_map(fn ($s) => $s['cost_per_unit'], $slices));
        $this->assertEquals(0, $old->fresh()->balance);
        $this->assertEquals(4, $new->fresh()->balance);
        $this->assertEquals(4, $product->fresh()->stock_quantity);
        $this->assertSame(2, StockLog::where('product_id', $product->id)->count());
    }

    public function test_stock_out_ignores_open_batches(): void
    {
        $company = $this->makeCompany($this->makeBranch());
        $product = $this->makeProduct($company);
        $open = $this->stockBatch($product, 5, 2.00, 'SB-OPEN');
        $open->batch->update(['status' => 'Open']);
        $product->update(['stock_quantity' => 5]); // e.g. initial stock

        $slices = $this->stock->stockOut($product, 2, 'stock_out', 'Test', $product);

        $this->assertNull($slices[0]['batch_item'], 'should be unbatched stock, not the open batch');
        $this->assertEquals(5, $open->fresh()->balance);
    }

    public function test_stock_out_refuses_more_than_available(): void
    {
        $company = $this->makeCompany($this->makeBranch());
        $product = $this->makeProduct($company);
        $this->stockBatch($product, 2, 1.00);

        $this->expectException(RuntimeException::class);
        try {
            $this->stock->stockOut($product, 3, 'stock_out', 'Test', $product);
        } finally {
            $this->assertEquals(2, $product->fresh()->stock_quantity, 'nothing should change');
            $this->assertSame(0, StockLog::count());
        }
    }

    public function test_stock_in_with_batch_creates_costed_batch_item(): void
    {
        $company = $this->makeCompany($this->makeBranch());
        $product = $this->makeProduct($company);
        $batch = Batch::create(['branch_id' => $company->branch_id, 'company_id' => $company->id,
            'batch_no' => 'TRF1', 'batch_date' => now()->toDateString(), 'status' => 'Completed']);

        $item = $this->stock->stockIn($product, 6, 'transfer_in', 'Transfer #1', $product, 2.5, $batch);

        $this->assertInstanceOf(BatchItem::class, $item);
        $this->assertEquals(6, $item->balance);
        $this->assertEquals(2.5, $item->cost_per_unit);
        $this->assertEquals(6, $product->fresh()->stock_quantity);
        $this->assertSame('transfer_in', StockLog::first()->type);
    }
}
