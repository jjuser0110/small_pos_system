<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PaymentMethod;
use App\Models\ReceiptSetting;
use App\Models\Uom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

/** UOM and receipt settings are per branch and branch managers manage their own. */
class BranchUomReceiptTest extends TestCase
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

    public function test_branch_manager_manages_own_branch_uoms_only(): void
    {
        $manager = $this->makeUser(3, $this->mine);
        Uom::create(['uom_name' => 'Shared Box', 'uom_unit' => 'box']);
        $theirs = Uom::create(['uom_name' => 'Their Crate', 'uom_unit' => 'crate', 'branch_id' => $this->other->branch_id]);

        // created for own branch, whatever branch is posted
        $this->actingAs($manager)->post(route('uom.store'), ['uom_name' => 'Tin', 'uom_unit' => 'tin', 'branch_id' => $this->other->branch_id])
            ->assertSessionHasNoErrors();
        $this->assertSame($this->mine->branch_id, (int) Uom::where('uom_name', 'Tin')->value('branch_id'));

        $this->actingAs($manager)->get(route('uom.index'))
            ->assertSee('Tin')->assertSee('Shared Box')->assertDontSee('Their Crate');

        // another branch's unit and shared units cannot be changed by a branch manager
        $this->actingAs($manager)->get(route('uom.edit', $theirs))->assertForbidden();
        $this->actingAs($manager)->get(route('uom.edit', Uom::where('uom_name', 'Shared Box')->first()))->assertForbidden();

        // the item form only offers own + shared units
        $this->actingAs($manager)->get(route('product.create'))
            ->assertSee('Tin')->assertSee('Shared Box')->assertDontSee('Their Crate');
    }

    public function test_each_branch_has_its_own_receipt_with_default_fallback(): void
    {
        ReceiptSetting::create(['header' => 'DEFAULT HEADER', 'footer' => 'Default footer']);
        $manager = $this->makeUser(3, $this->mine);

        // the manager starts from the default and saves the branch's own
        $this->actingAs($manager)->get(route('receipt_setting.index'))->assertOk()->assertSee('DEFAULT HEADER')
            ->assertSee('uses the default receipt');
        $this->actingAs($manager)->post(route('receipt_setting.store'), ['header' => 'MINE SHOP', 'footer' => 'Mine thanks', 'branch_id' => $this->other->branch_id])
            ->assertSessionHasNoErrors();

        $this->assertSame('MINE SHOP', ReceiptSetting::forBranch($this->mine->branch_id)->header);
        $this->assertSame('DEFAULT HEADER', ReceiptSetting::forBranch($this->other->branch_id)->header, 'other branch untouched');
        $this->assertSame('DEFAULT HEADER', ReceiptSetting::whereNull('branch_id')->value('header'), 'default untouched');

        // receipts print the order's branch header
        PaymentMethod::create(['payment_method_name' => 'Cash']);
        $boss = $this->makeUser(1);
        foreach ([$this->mine, $this->other] as $company) {
            $item = $this->makeProduct($company, 'Cola ' . $company->company_code, 2);
            $this->actingAs($boss)->postJson('/cart/add', ['id' => $item->id])->assertOk();
            $orderId = $this->actingAs($boss)->postJson('/cart/placeorder', ['payment_method' => 'Cash', 'amount_received' => 2])->json('order_id');
            $expected = $company->is($this->mine) ? 'MINE SHOP' : 'DEFAULT HEADER';
            $this->actingAs($boss)->get(route('receipt', $orderId))->assertSee($expected);
        }
    }

    public function test_admin_can_edit_any_branch_receipt_and_the_default(): void
    {
        $boss = $this->makeUser(1);
        $this->actingAs($boss)->post(route('receipt_setting.store'), ['header' => 'GLOBAL', 'footer' => 'f'])->assertSessionHasNoErrors();
        $this->actingAs($boss)->post(route('receipt_setting.store'), ['header' => 'OTHER BRANCH', 'footer' => 'f', 'branch_id' => $this->other->branch_id]);

        $this->assertSame('GLOBAL', ReceiptSetting::whereNull('branch_id')->value('header'));
        $this->assertSame('OTHER BRANCH', ReceiptSetting::forBranch($this->other->branch_id)->header);
        $this->assertSame('GLOBAL', ReceiptSetting::forBranch($this->mine->branch_id)->header);
    }

    public function test_staff_cannot_open_uom_or_receipt_settings(): void
    {
        $staff = $this->makeUser(5, $this->mine, ['shift' => 'AM']);
        $this->withoutMiddleware(\App\Http\Middleware\CheckStaffAccess::class);
        $this->actingAs($staff)->get(route('uom.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('receipt_setting.index'))->assertForbidden();
    }
}
