<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Cart;
use App\Models\Company;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

/** Only the superadmin adjusts a completed stock entry; deleted items never crash a page. */
class StockAdjustAndDeletedItemTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    private Company $a;
    private Product $beer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->a = $this->makeCompany($this->makeBranch(), 'A', 'shopa.test');
        $this->beer = $this->makeProduct($this->a, 'Tiger Beer');
    }

    private function openEntryWith(Product $product): BatchItem
    {
        $batch = Batch::create([
            'branch_id' => $product->branch_id, 'company_id' => $product->company_id,
            'batch_no' => 'OPEN1', 'batch_date' => now()->toDateString(), 'status' => 'Open',
        ]);
        return BatchItem::create([
            'batch_id' => $batch->id, 'branch_id' => $product->branch_id, 'company_id' => $product->company_id,
            'category_id' => $product->category_id, 'product_id' => $product->id,
            'quantity' => 5, 'balance' => 5, 'cost_per_unit' => 2, 'total_cost' => 10,
        ]);
    }

    public function test_only_superadmin_can_adjust_a_completed_stock_entry(): void
    {
        $row = $this->stockBatch($this->beer, 10, 3.00);
        $manager = $this->makeUser(3, $this->a);
        $data = ['quantity' => 8, 'cost_per_unit' => 3, 'total_cost' => 24];

        $this->actingAs($manager)->get(route('batch.edit', $row->batch_id))
            ->assertOk()->assertDontSee('editModal' . $row->id, false);
        $this->actingAs($manager)->post(route('batch.updateBatchItem', $row), $data)->assertForbidden();
        $this->actingAs($this->makeUser(2))->post(route('batch.updateBatchItem', $row), $data)->assertForbidden();
        $this->assertEquals(10, $row->fresh()->quantity);

        $boss = $this->makeUser(1);
        $this->actingAs($boss)->get(route('batch.edit', $row->batch_id))->assertSee('editModal' . $row->id, false);
        $this->actingAs($boss)->post(route('batch.updateBatchItem', $row), $data)->assertSessionHasNoErrors();
        $this->assertEquals(8, $row->fresh()->quantity);
    }

    public function test_item_on_an_open_stock_entry_cannot_be_deleted(): void
    {
        $this->openEntryWith($this->beer);

        $this->actingAs($this->makeUser(1))->get(route('product.destroy', $this->beer))->assertSessionHasErrors();
        $this->assertFalse($this->beer->fresh()->trashed());
    }

    public function test_stock_entry_with_an_already_deleted_item_still_opens_and_cannot_complete(): void
    {
        $row = $this->openEntryWith($this->beer);
        $this->beer->delete();   // e.g. deleted before this check existed
        $boss = $this->makeUser(1);

        $this->actingAs($boss)->get(route('batch.edit', $row->batch_id))->assertOk()->assertSee('Tiger Beer')->assertSee('deleted');
        $this->actingAs($boss)->get(route('batch.complete', $row->batch_id))->assertSessionHasErrors();
        $this->assertSame('Open', $row->batch->fresh()->status);
    }

    public function test_history_pages_still_show_a_deleted_item(): void
    {
        $row = $this->stockBatch($this->beer, 4, 2.00);
        \App\Models\Product::whereKey($this->beer->id)->update(['deleted_at' => now()]);
        $boss = $this->makeUser(1);

        $this->actingAs($boss)->get(route('batch.edit', $row->batch_id))->assertOk()->assertSee('Tiger Beer');
        $this->assertSame('Tiger Beer', $row->fresh()->product->product_name);
    }

    public function test_cart_drops_a_deleted_item(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-08 12:00'));
        $staff = $this->makeUser(5, $this->a, ['shift' => 'AM']);
        Cart::create(['user_id' => $staff->id, 'product_id' => $this->beer->id, 'quantity' => 1, 'single_price' => 10, 'total_price' => 10]);
        $this->beer->delete();

        $this->actingAs($staff)->get('http://shopa.test/cart/load')->assertOk()->assertJsonCount(0);
        $this->assertSame(0, Cart::count());
    }
}
