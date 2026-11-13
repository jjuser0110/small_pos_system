<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Damage;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Refund;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

/** Item 23: what each branch / company / shift earned. */
class ProfitReportTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    private Company $company;
    private Product $beer;
    private User $staff;
    private User $boss;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        PaymentMethod::create(['payment_method_name' => 'Cash']);
        $this->company = $this->makeCompany($this->makeBranch('KL'), 'A', 'shopa.test');
        $this->beer = $this->makeProduct($this->company, 'Beer', 12.00);
        $this->stockBatch($this->beer, 10, 5.00);      // costs RM5, sells RM12
        $this->staff = $this->makeUser(5, $this->company, ['shift' => 'AM']);
        $this->boss = $this->makeUser(1);
        $this->travelTo(Carbon::parse('2026-11-08 12:00'));
    }

    private function sellAsStaff(int $qty): int
    {
        $this->actingAs($this->staff);
        for ($i = 0; $i < $qty; $i++) {
            $this->postJson('http://shopa.test/cart/add', ['id' => $this->beer->id])->assertOk();
        }
        return $this->postJson('http://shopa.test/cart/placeorder', ['payment_method' => 'Cash', 'amount_received' => $qty * 12])
            ->assertOk()->json('order_id');
    }

    private function report(array $query = [])
    {
        return $this->actingAs($this->boss)->get(route('profit_report.index', $query + ['date_from' => '2026-11-01', 'date_to' => '2026-11-30']));
    }

    public function test_sales_capital_profit_damage_and_net(): void
    {
        $orderId = $this->sellAsStaff(3);                         // sales 36, capital 15
        $item = \App\Models\Order::find($orderId)->items->first();
        $this->post('http://shopa.test' . route('refund.store', [], false), [
            'order_id' => $orderId, 'qty' => [$item->id => 1], 'reason' => 'x',
        ]);
        $this->actingAs($this->boss)->post(route('refund.approve', Refund::first()));   // net sales 24, capital 10

        $damage = Damage::create(['product_id' => $this->beer->id, 'branch_id' => $this->company->branch_id,
            'company_id' => $this->company->id, 'quantity' => 1, 'reason' => 'broken', 'status' => 'pending',
            'requested_by' => $this->boss->id]);
        $this->actingAs($this->boss)->post(route('damage.approve', $damage));   // damage cost 5
        $this->assertEquals(5.00, $damage->fresh()->cost_amount);

        $res = $this->report()->assertOk();
        $overall = $res->viewData('overall');
        $this->assertEquals(36.00, $overall->sales);
        $this->assertEquals(12.00, $overall->refunds);
        $this->assertEquals(24.00, $overall->net_sales);
        $this->assertEquals(10.00, $overall->cost);
        $this->assertEquals(14.00, $overall->gross);
        $this->assertEquals(5.00, $overall->damage);
        $this->assertEquals(9.00, $overall->net);
        $this->assertEquals(58.3, $overall->margin);
        $this->assertEquals(0, $overall->uncosted_sales);

        $row = $res->viewData('rows')->first();
        $this->assertSame('AM', $row->shift);
        $res->assertSee('RM 14.00')->assertSee('Beer');
    }

    public function test_sales_without_cost_are_flagged(): void
    {
        $this->beer->update(['stock_quantity' => 0]);
        \App\Models\BatchItem::where('product_id', $this->beer->id)->update(['balance' => 0]);
        $this->sellAsStaff(2);                                    // sold into negative: no cost known

        $overall = $this->report()->viewData('overall');
        $this->assertEquals(24.00, $overall->uncosted_sales);
        $this->report()->assertSee('no cost recorded');
    }

    public function test_filters_and_who_can_open(): void
    {
        $this->sellAsStaff(1);
        $this->assertEquals(0, $this->report(['shift' => 'PM'])->viewData('overall')->sales);

        $this->actingAs($this->makeUser(3, $this->company))->get(route('profit_report.index'))->assertForbidden();
        $this->actingAs($this->makeUser(2))->get(route('profit_report.index'))->assertOk();
    }
}
