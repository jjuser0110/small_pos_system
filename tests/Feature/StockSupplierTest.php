<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

/** The supplier is chosen when stock comes in, not on the product. */
class StockSupplierTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_adding_stock_requires_a_supplier_and_saves_it(): void
    {
        $company = $this->makeCompany($this->makeBranch());
        $supplier = Supplier::create(['name' => 'Carlsberg Sdn Bhd']);
        $admin = $this->makeUser(1);
        $entry = ['company_id' => $company->id, 'batch_no' => 'SB2026110001', 'batch_date' => '2026-11-09',
                  'code' => 'SB', 'year' => 2026, 'month' => 11];
        \App\Models\RunningNumber::create(['code' => 'SB', 'name' => 'stock_batch', 'year' => 2026, 'month' => 11,
                  'no_of_digit_behind' => 4, 'running_no' => 1]);

        $this->actingAs($admin)->post(route('batch.store'), $entry)->assertSessionHasErrors('supplier_id');
        $this->assertSame(0, Batch::count());

        $this->actingAs($admin)->post(route('batch.store'), $entry + ['supplier_id' => $supplier->id])->assertSessionHasNoErrors();
        $this->assertSame($supplier->id, Batch::first()->supplier_id);

        $this->actingAs($admin)->get(route('batch.index'))->assertOk()->assertSee('Carlsberg Sdn Bhd');
    }

    public function test_product_form_has_no_supplier_and_ignores_one_posted(): void
    {
        $company = $this->makeCompany($this->makeBranch());
        $existing = $this->makeProduct($company);
        $supplier = Supplier::create(['name' => 'Old Supplier']);
        $admin = $this->makeUser(1);

        $this->actingAs($admin)->get(route('product.create'))->assertOk()->assertDontSee('name="supplier_id"', false);

        $this->actingAs($admin)->post(route('product.store'), [
            'category_id' => $existing->category_id, 'product_name' => 'New Item', 'uom' => $existing->uom,
            'selling_price' => 5, 'initial' => 0, 'supplier_id' => $supplier->id,
        ])->assertSessionHasNoErrors();

        $this->assertNull(Product::where('product_name', 'New Item')->value('supplier_id'));
    }
}
