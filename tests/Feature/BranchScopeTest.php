<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

/** A branch manager must never see another branch's data (item 20) or suppliers (item 21). */
class BranchScopeTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    public function test_branch_manager_list_pages_show_only_own_branch(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $mine = $this->makeCompany($this->makeBranch('Mine'), 'MINE');
        $other = $this->makeCompany($this->makeBranch('Other'), 'OTHR');

        $this->makeProduct($mine, 'Mine Item');
        $this->makeProduct($other, 'Other Item');
        Supplier::create(['name' => 'Mine Supplier', 'branch_id' => $mine->branch_id]);
        Supplier::create(['name' => 'Other Supplier', 'branch_id' => $other->branch_id]);
        foreach ([$mine, $other] as $c) {
            Batch::create(['branch_id' => $c->branch_id, 'company_id' => $c->id, 'batch_no' => 'SB-' . $c->company_code,
                'batch_date' => now()->toDateString(), 'status' => 'Open']);
        }

        $manager = $this->makeUser(3, $mine);
        $this->actingAs($manager);
        $this->get(route('product.index'))->assertSee('Mine Item')->assertDontSee('Other Item');
        $this->get(route('category.index'))->assertSee('MINE')->assertDontSee('OTHR');
        $this->get(route('supplier.index'))->assertSee('Mine Supplier')->assertDontSee('Other Supplier');
        $this->get(route('batch.index'))->assertSee('SB-MINE')->assertDontSee('SB-OTHR');
        $this->get(route('company.index'))->assertSee('MINE')->assertDontSee('OTHR');
        $this->get(route('batch.create'))->assertSee('Mine Supplier')->assertDontSee('Other Supplier');
        $this->get(route('counter'))->assertSee('Mine Item')->assertDontSee('Other Item');
    }

    public function test_branch_manager_supplier_is_saved_to_own_branch_and_others_are_off_limits(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $mine = $this->makeCompany($this->makeBranch('Mine'), 'MINE');
        $other = $this->makeCompany($this->makeBranch('Other'), 'OTHR');
        $manager = $this->makeUser(3, $mine);

        $this->actingAs($manager)->post(route('supplier.store'), ['name' => 'New One', 'branch_id' => $other->branch_id])
            ->assertSessionHasNoErrors();
        $this->assertSame($mine->branch_id, (int) Supplier::where('name', 'New One')->value('branch_id'));

        $theirs = Supplier::create(['name' => 'Theirs', 'branch_id' => $other->branch_id]);
        $this->actingAs($manager)->get(route('supplier.edit', $theirs))->assertForbidden();

        // a stock entry cannot use another branch's supplier
        \App\Models\RunningNumber::create(['code' => 'SB', 'name' => 'stock_batch', 'year' => 2026, 'month' => 11,
            'no_of_digit_behind' => 4, 'running_no' => 1]);
        $this->actingAs($manager)->post(route('batch.store'), [
            'company_id' => $mine->id, 'batch_no' => 'SB1', 'batch_date' => '2026-11-14', 'supplier_id' => $theirs->id,
            'code' => 'SB', 'year' => 2026, 'month' => 11,
        ])->assertSessionHasErrors('supplier_id');
    }
}
