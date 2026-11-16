<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Company;
use App\Models\Product;
use App\Support\BarcodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

/** Stock expiry dates and the expiring list, item average cost + margin, generated barcodes. */
class ExpiryMarginBarcodeTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    private Company $mine;
    private Company $other;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->mine = $this->makeCompany($this->makeBranch('Mine'), 'MINE');
        $this->other = $this->makeCompany($this->makeBranch('Other'), 'OTHR');
    }

    public function test_expiry_date_saved_on_open_and_completed_rows(): void
    {
        $boss = $this->makeUser(1);
        $item = $this->makeProduct($this->mine, 'Milk');
        $batch = Batch::create(['branch_id' => $item->branch_id, 'company_id' => $item->company_id, 'batch_no' => 'OPEN1', 'batch_date' => now()->toDateString(), 'status' => 'Open']);
        $row = BatchItem::create(['batch_id' => $batch->id, 'branch_id' => $item->branch_id, 'company_id' => $item->company_id,
            'category_id' => $item->category_id, 'product_id' => $item->id, 'quantity' => 1, 'balance' => 1, 'cost_per_unit' => 1, 'total_cost' => 1]);

        $this->actingAs($boss)->postJson(route('batch.updateOpenItem', $row), [
            'entry_qty' => 5, 'entry_cost' => 2, 'expiry_date' => '2027-01-31', 'changed' => 'expiry',
        ])->assertOk();
        $this->assertSame('2027-01-31', substr($row->fresh()->expiry_date, 0, 10));
        $this->assertEquals(5, $row->fresh()->quantity);

        $done = $this->stockBatch($item, 3, 1, 'DONE1');
        $this->actingAs($boss)->postJson(route('batch.updateExpiry', $done), ['expiry_date' => '2027-02-01'])->assertOk();
        $this->assertSame('2027-02-01', substr($done->fresh()->expiry_date, 0, 10));
        $this->assertEquals(3, $done->fresh()->balance, 'expiry does not touch stock');

        // a branch manager cannot change another branch's row
        $this->actingAs($this->makeUser(3, $this->other))->postJson(route('batch.updateExpiry', $done), ['expiry_date' => '2027-03-01'])->assertForbidden();
    }

    public function test_expiring_list_and_home_card(): void
    {
        $expired = $this->stockBatch($this->makeProduct($this->mine, 'Old Bread'), 2, 1, 'B1');
        $expired->update(['expiry_date' => now()->subDays(3)->toDateString()]);
        $soon = $this->stockBatch($this->makeProduct($this->mine, 'Soon Yogurt'), 2, 1, 'B2');
        $soon->update(['expiry_date' => now()->addDays(10)->toDateString()]);
        $later = $this->stockBatch($this->makeProduct($this->mine, 'Later Rice'), 2, 1, 'B3');
        $later->update(['expiry_date' => now()->addDays(200)->toDateString()]);
        $sold = $this->stockBatch($this->makeProduct($this->mine, 'Sold Cheese'), 2, 1, 'B4');
        $sold->update(['expiry_date' => now()->addDays(5)->toDateString(), 'balance' => 0]);
        $theirs = $this->stockBatch($this->makeProduct($this->other, 'Their Juice'), 2, 1, 'B5');
        $theirs->update(['expiry_date' => now()->addDays(5)->toDateString()]);

        $manager = $this->makeUser(3, $this->mine);
        $this->actingAs($manager)->get(route('expiring_stock.index'))->assertOk()
            ->assertSee('Old Bread')->assertSee('Soon Yogurt')
            ->assertDontSee('Later Rice')->assertDontSee('Sold Cheese')->assertDontSee('Their Juice');

        $this->actingAs($manager)->get(route('home'))->assertSee('1 stock row already expired')->assertSee('1 expiring within 30 days');
    }

    public function test_average_cost_and_margin(): void
    {
        $boss = $this->makeUser(1);
        $item = $this->makeProduct($this->mine, 'Cola');
        $this->assertNull($item->averageCost());

        $this->stockBatch($item, 10, 2.00, 'A1');
        $this->stockBatch($item, 10, 3.00, 'A2');
        $this->assertEquals(2.50, $item->averageCost());

        $this->actingAs($boss)->get(route('product.edit', $item))->assertViewHas('averageCost', 2.5);

        $this->actingAs($boss)->post(route('product.update', $item), [
            'category_id' => $item->category_id, 'product_name' => 'Cola', 'selling_price' => 3.13, 'margin' => 25,
        ])->assertSessionHasNoErrors();
        $this->assertEquals(25, $item->fresh()->margin);
    }

    public function test_generated_barcode_is_unique_in_store_ean13(): void
    {
        $code = $this->actingAs($this->makeUser(3, $this->mine))->getJson(route('product.newBarcode'))->assertOk()->json('barcode');

        $this->assertMatchesRegularExpression('/^20\d{11}$/', $code);
        $this->assertSame((int) substr($code, -1), BarcodeGenerator::checkDigit(substr($code, 0, 12)));
        $this->assertSame(0, Product::where('barcode', $code)->count());
    }

    public function test_barcode_used_by_another_item_of_the_company_is_refused(): void
    {
        $boss = $this->makeUser(1);
        $cola = $this->makeProduct($this->mine, 'Cola');
        $cola->update(['barcode' => '2000000000008']);
        $cola->allUnits()->create(['name' => 'Carton', 'base_qty' => 24, 'price' => 50, 'barcode' => '2000000000015', 'is_active' => true]);
        $beer = $this->makeProduct($this->mine, 'Beer');

        $form = fn (array $extra) => array_merge(['category_id' => $beer->category_id, 'product_name' => 'Beer', 'selling_price' => 5], $extra);

        $this->actingAs($boss)->post(route('product.update', $beer), $form(['barcode' => '2000000000008']))->assertSessionHasErrors('barcode');
        $this->actingAs($boss)->post(route('product.update', $beer), $form([
            'units' => [['name' => 'Box', 'base_qty' => 6, 'price' => 20, 'barcode' => '2000000000015']],
        ]))->assertSessionHasErrors('barcode');

        // saving Cola with its own barcodes is fine, and another company may use the same code
        $this->actingAs($boss)->post(route('product.update', $cola), array_merge(['category_id' => $cola->category_id, 'product_name' => 'Cola', 'selling_price' => 2, 'barcode' => '2000000000008'], [
            'units' => [['id' => $cola->allUnits()->first()->id, 'name' => 'Carton', 'base_qty' => 24, 'price' => 50, 'barcode' => '2000000000015']],
        ]))->assertSessionHasNoErrors();
        $theirs = $this->makeProduct($this->other, 'Cola');
        $this->actingAs($boss)->post(route('product.update', $theirs), ['category_id' => $theirs->category_id, 'product_name' => 'Cola', 'selling_price' => 2, 'barcode' => '2000000000008'])
            ->assertSessionHasNoErrors();
    }
}
