<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

/** Round 2 clean-up: categories, item list scope, sell options on the item form, counter order. */
class ItemCleanupTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_money_helper(): void
    {
        $this->assertSame('RM 1,234.50', money(1234.5));
        $this->assertSame('-RM 12.00', money(-12));
        $this->assertSame('RM 0.00', money(null));
    }

    public function test_category_has_no_special_or_has_stock_choice(): void
    {
        $company = $this->makeCompany($this->makeBranch());
        $this->actingAs($this->makeUser(1))->post(route('category.store'), [
            'company_id' => $company->id, 'category_name' => 'Snacks', 'arrangement' => 2,
            'special' => 1, 'has_stock' => 0,      // ignored
        ])->assertSessionHasNoErrors();

        $cat = Category::where('category_name', 'Snacks')->first();
        $this->assertSame(0, (int) $cat->special);
        $this->assertSame(1, (int) $cat->has_stock);
    }

    public function test_branch_manager_item_list_shows_only_own_branch(): void
    {
        $mine = $this->makeCompany($this->makeBranch('Mine'), 'M');
        $other = $this->makeCompany($this->makeBranch('Other'), 'O');
        $this->makeProduct($mine, 'My Beer');
        $this->makeProduct($other, 'Their Beer');   // created just now: used to leak

        $this->actingAs($this->makeUser(3, $mine))->get(route('product.index'))
            ->assertOk()->assertSee('My Beer')->assertDontSee('Their Beer');
    }

    public function test_item_form_saves_sell_options_and_removes_dropped_ones(): void
    {
        $company = $this->makeCompany($this->makeBranch());
        $item = $this->makeProduct($company, 'Cola', 2.50);
        $admin = $this->makeUser(1);
        $base = ['category_id' => $item->category_id, 'product_name' => 'Cola', 'uom' => $item->uom,
                 'selling_price' => 2.50, 'initial' => 0];

        $this->actingAs($admin)->post(route('product.update', $item), $base + ['units' => [
            ['name' => 'Carton', 'base_qty' => 24, 'price' => 50, 'barcode' => 'COLA24'],
            ['name' => '3 for RM10', 'base_qty' => 3, 'price' => 10],
        ]])->assertSessionHasNoErrors();
        $this->assertSame(['3 for RM10', 'Carton'], $item->fresh()->units->pluck('name')->all());

        $carton = $item->allUnits()->where('name', 'Carton')->first();
        $this->actingAs($admin)->post(route('product.update', $item), $base + ['units' => [
            ['id' => $carton->id, 'name' => 'Carton', 'base_qty' => 24, 'price' => 48],
        ]])->assertSessionHasNoErrors();
        $this->assertSame(['Carton'], $item->fresh()->units->pluck('name')->all());
        $this->assertEquals(48, $carton->fresh()->price);

        // the same barcode twice on one item is refused
        $this->actingAs($admin)->post(route('product.update', $item), $base + ['barcode' => 'X1', 'units' => [
            ['name' => 'Carton', 'base_qty' => 24, 'price' => 48, 'barcode' => 'X1'],
        ]])->assertSessionHasErrors('barcode');
    }

    public function test_counter_lists_items_by_arrangement_with_sell_options(): void
    {
        $company = $this->makeCompany($this->makeBranch());
        $b = $this->makeProduct($company, 'Zebra Juice');
        $a = $this->makeProduct($company, 'Apple Juice');
        $b->update(['arrangement' => 1]);
        $a->update(['arrangement' => 2]);
        $b->allUnits()->create(['name' => 'Carton', 'base_qty' => 12, 'price' => 30]);

        $res = $this->actingAs($this->makeUser(1))->get(route('counter'))->assertOk();
        $tiles = collect($res->viewData('sellables'));
        $this->assertSame(['Zebra Juice', 'Zebra Juice', 'Apple Juice'], $tiles->pluck('name')->all());
        $this->assertSame('Carton', $tiles[1]['unit_label']);
    }
}
