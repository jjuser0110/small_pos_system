<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\StockTransfer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    private Company $a;
    private Company $b;
    private Product $itemA;
    private Product $itemB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $branch = $this->makeBranch();
        $this->a = $this->makeCompany($branch, 'A', 'shopa.test');
        $this->b = $this->makeCompany($branch, 'B', 'shopb.test');
        $this->itemA = $this->makeProduct($this->a, 'Tiger Beer 330ml');
        $this->itemB = $this->makeProduct($this->b, 'Tiger Can');   // same goods, different name
        $this->stockBatch($this->itemA, 10, 3.00);
        $this->travelTo(Carbon::parse('2026-11-08 12:00'));
    }

    private function send(float $qty, $as)
    {
        return $this->actingAs($as)->post(route('stock_transfer.store'), [
            'from_product_id' => $this->itemA->id, 'to_product_id' => $this->itemB->id,
            'quantity' => $qty, 'reason' => 'restock B',
        ]);
    }

    public function test_sending_creates_pending_transfer_without_moving_stock(): void
    {
        $this->send(4, $this->makeUser(3, $this->a))->assertRedirect(route('stock_transfer.index'));

        $t = StockTransfer::first();
        $this->assertSame('pending', $t->status);
        $this->assertEquals(10, $this->itemA->fresh()->stock_quantity);
        $this->assertEquals(0, $this->itemB->fresh()->stock_quantity);
    }

    public function test_staff_cannot_send_a_transfer(): void
    {
        $this->actingAs($this->makeUser(5, $this->a, ['shift' => 'AM']))
            ->post('http://shopa.test' . route('stock_transfer.store', [], false), [
                'from_product_id' => $this->itemA->id, 'to_product_id' => $this->itemB->id, 'quantity' => 1,
            ])->assertForbidden();
        $this->assertSame(0, StockTransfer::count());
    }

    public function test_receiving_staff_accepts_and_stock_moves_with_cost_and_logs(): void
    {
        $this->send(4, $this->makeUser(1));
        $t = StockTransfer::first();
        $staffB = $this->makeUser(5, $this->b, ['shift' => 'AM']);

        $this->actingAs($staffB)->post('http://shopb.test' . route('stock_transfer.approve', $t, false))->assertSessionHasNoErrors();

        $this->assertSame('approved', $t->fresh()->status);
        $this->assertEquals(6, $this->itemA->fresh()->stock_quantity);
        $this->assertEquals(4, $this->itemB->fresh()->stock_quantity);

        // B received a completed stock entry at A's cost, so later sales have a cost
        $received = BatchItem::where('product_id', $this->itemB->id)->first();
        $this->assertEquals(3.00, $received->cost_per_unit);
        $this->assertSame('Completed', Batch::find($t->fresh()->received_batch_id)->status);

        $this->assertSame(1, StockLog::where('type', 'transfer_out')->where('product_id', $this->itemA->id)->count());
        $this->assertSame(1, StockLog::where('type', 'transfer_in')->where('product_id', $this->itemB->id)->count());
    }

    public function test_sender_side_staff_and_other_company_cannot_accept(): void
    {
        $this->send(4, $this->makeUser(1));
        $t = StockTransfer::first();

        $this->actingAs($this->makeUser(5, $this->a, ['shift' => 'AM']))
            ->post('http://shopa.test' . route('stock_transfer.approve', $t, false))->assertForbidden();
        $this->actingAs($this->makeUser(1))->post(route('stock_transfer.approve', $t))->assertForbidden();

        $this->assertSame('pending', $t->fresh()->status);
    }

    public function test_branch_manager_of_receiving_branch_can_accept(): void
    {
        $this->send(2, $this->makeUser(1));
        $this->actingAs($this->makeUser(3, $this->b))->post(route('stock_transfer.approve', StockTransfer::first()));

        $this->assertEquals(2, $this->itemB->fresh()->stock_quantity);
    }

    public function test_accept_fails_cleanly_if_sender_ran_out(): void
    {
        $this->send(8, $this->makeUser(1));
        $this->itemA->update(['stock_quantity' => 5]);  // sold meanwhile
        BatchItem::where('product_id', $this->itemA->id)->update(['balance' => 5]);

        $this->actingAs($this->makeUser(3, $this->b))->post(route('stock_transfer.approve', StockTransfer::first()))
            ->assertSessionHasErrors();

        $this->assertSame('pending', StockTransfer::first()->status);
        $this->assertEquals(5, $this->itemA->fresh()->stock_quantity);
        $this->assertEquals(0, $this->itemB->fresh()->stock_quantity);
    }

    public function test_reject_keeps_record_and_moves_nothing(): void
    {
        $this->send(3, $this->makeUser(1));
        $this->actingAs($this->makeUser(3, $this->b))
            ->post(route('stock_transfer.reject', StockTransfer::first()), ['reject_reason' => 'wrong item']);

        $t = StockTransfer::first();
        $this->assertSame('rejected', $t->status);
        $this->assertSame('wrong item', $t->reject_reason);
        $this->assertEquals(10, $this->itemA->fresh()->stock_quantity);
    }

    public function test_only_superadmin_can_adjust_stock_and_it_is_logged(): void
    {
        $this->actingAs($this->makeUser(3, $this->a))->post(route('stock_adjustment.store'), [
            'product_id' => $this->itemA->id, 'type' => 'adjust_out', 'quantity' => 1, 'reason' => 'x',
        ])->assertForbidden();

        $this->actingAs($this->makeUser(1))->post(route('stock_adjustment.store'), [
            'product_id' => $this->itemA->id, 'type' => 'adjust_out', 'quantity' => 2, 'reason' => 'count fix',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(8, $this->itemA->fresh()->stock_quantity);
        $this->assertSame(1, StockLog::where('type', 'adjust_out')->count());
        $this->assertSame(1, \App\Models\StockAdjustment::where('status', 'approved')->count());
    }
}
