<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

/** Stock entry (items 4, 5, 26): pick or scan to add rows, edit inline, key in by carton. */
class StockEntryTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    private Company $company;
    private Product $cola;
    private Batch $batch;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->company = $this->makeCompany($this->makeBranch());
        $this->cola = $this->makeProduct($this->company, 'Cola', 2.50);
        $this->cola->update(['barcode' => 'COLA1']);
        $this->cola->allUnits()->create(['name' => 'Carton', 'base_qty' => 24, 'price' => 50, 'barcode' => 'COLA24']);
        $this->admin = $this->makeUser(1);
        $this->batch = Batch::create([
            'branch_id' => $this->company->branch_id, 'company_id' => $this->company->id,
            'supplier_id' => Supplier::create(['name' => 'Cola Co'])->id,
            'batch_no' => 'SB1', 'batch_date' => now()->toDateString(), 'status' => 'Open',
        ]);
    }

    private function add(array $data)
    {
        return $this->actingAs($this->admin)->postJson(route('batch.addBatchItem', $this->batch), $data);
    }

    public function test_pick_adds_a_row_and_scanning_again_adds_one(): void
    {
        $this->add(['product_id' => $this->cola->id])->assertOk();
        $this->add(['barcode' => 'COLA1'])->assertOk();

        $rows = BatchItem::where('batch_id', $this->batch->id)->get();
        $this->assertCount(1, $rows);
        $this->assertEquals(2, $rows[0]->entry_qty);
        $this->assertEquals(2, $rows[0]->quantity);
    }

    public function test_scanning_a_carton_barcode_adds_a_carton_row(): void
    {
        $this->add(['barcode' => 'COLA24'])->assertOk()->assertJson(['ok' => true]);

        $row = BatchItem::first();
        $this->assertSame('Carton', $row->entry_unit);
        $this->assertEquals(1, $row->entry_qty);
        $this->assertEquals(24, $row->quantity, 'stored in base units (tins)');
    }

    public function test_unknown_barcode_is_refused(): void
    {
        $this->add(['barcode' => 'NOPE'])->assertStatus(422);
        $this->assertSame(0, BatchItem::count());
    }

    public function test_editing_a_row_by_carton_converts_to_tins(): void
    {
        $this->add(['product_id' => $this->cola->id]);
        $row = BatchItem::first();
        $carton = $this->cola->allUnits()->first();

        // 10 cartons at RM48 each
        $this->actingAs($this->admin)->postJson(route('batch.updateOpenItem', $row), [
            'unit_id' => $carton->id, 'entry_qty' => 10, 'entry_cost' => 48, 'changed' => 'qty',
        ])->assertOk();

        $row->refresh();
        $this->assertEquals(240, $row->quantity);
        $this->assertEquals(240, $row->balance);
        $this->assertEquals(480.00, $row->total_cost);
        $this->assertEquals(2.00, $row->cost_per_unit, 'cost per tin');
        $this->assertSame('10 Carton', $row->entryLabel());
    }

    public function test_editing_the_total_works_out_the_cost_per_unit(): void
    {
        $this->add(['product_id' => $this->cola->id]);
        $row = BatchItem::first();

        $this->actingAs($this->admin)->postJson(route('batch.updateOpenItem', $row), [
            'entry_qty' => 4, 'entry_cost' => 0, 'total_cost' => 10, 'changed' => 'total',
        ])->assertOk();

        $row->refresh();
        $this->assertEquals(2.50, $row->entry_cost);
        $this->assertEquals(10.00, $row->total_cost);
    }

    public function test_completing_adds_base_units_and_invoice_is_kept(): void
    {
        $this->actingAs($this->admin)->post(route('batch.update', $this->batch), ['supplier_invoice' => 'INV-77', 'back' => 'edit'])
            ->assertRedirect(route('batch.edit', $this->batch));
        $this->add(['barcode' => 'COLA24']);
        $row = BatchItem::first();
        $this->actingAs($this->admin)->postJson(route('batch.updateOpenItem', $row), [
            'unit_id' => $row->entry_unit_id, 'entry_qty' => 2, 'entry_cost' => 48, 'changed' => 'qty',
        ]);

        $this->actingAs($this->admin)->get(route('batch.complete', $this->batch));

        $this->assertEquals(48, $this->cola->fresh()->stock_quantity);
        $this->assertSame('INV-77', $this->batch->fresh()->supplier_invoice);
        $this->actingAs($this->admin)->get(route('batch.edit', $this->batch))->assertOk()
            ->assertSee('INV-77')->assertSee('2 Carton');
    }

    public function test_completed_entry_rows_cannot_be_edited_inline(): void
    {
        $this->add(['product_id' => $this->cola->id]);
        $this->batch->update(['status' => 'Completed']);

        $this->actingAs($this->admin)->postJson(route('batch.updateOpenItem', BatchItem::first()), ['entry_qty' => 5])
            ->assertStatus(422);
        $this->add(['product_id' => $this->cola->id])->assertStatus(422);
    }
}
