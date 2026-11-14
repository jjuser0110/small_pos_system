<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Company;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ShiftClosing;
use App\Models\ShiftClosingDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

/** Calculation fixes from the full review. */
class ReviewFixesTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    private const HOST = 'http://shopa.test';

    private Company $company;
    private Product $cola;
    private User $staff;
    private User $boss;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        PaymentMethod::create(['payment_method_name' => 'Cash']);
        $this->company = $this->makeCompany($this->makeBranch(), 'A', 'shopa.test');
        $this->cola = $this->makeProduct($this->company, 'Cola', 2.50);
        $this->cola->allUnits()->create(['name' => 'Carton', 'base_qty' => 24, 'price' => 50]);
        $this->stockBatch($this->cola, 100, 1.50);
        $this->staff = $this->makeUser(5, $this->company, ['shift' => 'AM']);
        $this->boss = $this->makeUser(1);
        $this->travelTo(Carbon::parse('2026-11-08 12:00'));
    }

    /** Staff sells: [ [unit_id|null, times], ... ] */
    private function sell(array $lines, float $received): Order
    {
        $this->actingAs($this->staff);
        foreach ($lines as [$unitId, $times]) {
            for ($i = 0; $i < $times; $i++) {
                $this->postJson(self::HOST . '/cart/add', ['id' => $this->cola->id, 'unit_id' => $unitId])->assertOk();
            }
        }
        $id = $this->postJson(self::HOST . '/cart/placeorder', ['payment_method' => 'Cash', 'amount_received' => $received])
            ->assertOk()->json('order_id');
        return Order::find($id);
    }

    private function carton(): int
    {
        return $this->cola->allUnits()->first()->id;
    }

    public function test_discount_reduces_order_shift_and_payment_method(): void
    {
        $order = $this->sell([[null, 4]], 10);                 // 4 tins = RM10
        $manager = $this->makeUser(3, $this->company);

        $this->actingAs($manager)->post(route('order.discount', $order), ['discount' => 2])->assertSessionHasNoErrors();
        $order->refresh();
        $shift = ShiftClosing::find($order->shift_closing_id);
        $this->assertEquals(8.00, $order->final_total);
        $this->assertEquals(8.00, $shift->total_order_amount);
        $this->assertEquals(8.00, $shift->items()->value('amount'));

        // changing the discount adjusts by the difference only
        $this->actingAs($manager)->post(route('order.discount', $order), ['discount' => 1]);
        $this->assertEquals(9.00, $shift->fresh()->total_order_amount);

        // more than the order is refused
        $this->actingAs($manager)->post(route('order.discount', $order), ['discount' => 50])->assertSessionHasErrors();

        // void after a discount takes back exactly what was kept
        $this->actingAs($manager)->post(route('order.void', $order), ['voided_reason' => 'x']);
        $this->assertEquals(0, $shift->fresh()->total_order_amount);
        $this->assertEquals(0, $shift->items()->value('amount'));
    }

    public function test_refund_reduces_items_and_category_totals(): void
    {
        $order = $this->sell([[null, 3]], 7.50);
        $item = $order->items->first();
        $this->post(self::HOST . route('refund.store', [], false), ['order_id' => $order->id, 'qty' => [$item->id => 1], 'reason' => 'x']);
        $this->actingAs($this->boss)->post(route('refund.approve', Refund::first()));

        $shift = ShiftClosing::find($order->shift_closing_id);
        $this->assertEquals(2, $shift->total_item_count);
        $this->assertEquals(5.00, ShiftClosingDetail::where('shift_closing_id', $shift->id)->value('amount'));
        $this->assertEquals($shift->netAmount(), ShiftClosingDetail::where('shift_closing_id', $shift->id)->sum('amount'));
    }

    public function test_reports_list_cartons_and_tins_separately_net_of_refunds(): void
    {
        $order = $this->sell([[$this->carton(), 2], [null, 3]], 107.50);
        $tins = $order->items->firstWhere('product_unit_id', null);
        $this->post(self::HOST . route('refund.store', [], false), ['order_id' => $order->id, 'qty' => [$tins->id => 1], 'reason' => 'x']);
        $this->actingAs($this->boss)->post(route('refund.approve', Refund::first()));

        $report = $this->actingAs($this->boss)->get(route('report.index', ['date_from' => '2026-11-08T00:00', 'date_to' => '2026-11-08T23:59']));
        $rows = $report->viewData('itemTotals')->mapWithKeys(fn ($r) => [($r->unit_name ?? 'Tin') => [(float) $r->total_quantity, (float) $r->total_amount]]);
        $this->assertSame(['Carton' => [2.0, 100.0], 'Tin' => [2.0, 5.0]], $rows->sortKeys()->all());

        $view = $this->actingAs($this->boss)->get(route('shift_closing.view', $order->shift_closing_id));
        $items = $view->viewData('items')->mapWithKeys(fn ($r) => [($r->unit_name ?? 'Tin') => (float) $r->qty]);
        $this->assertSame(['Carton' => 2.0, 'Tin' => 2.0], $items->sortKeys()->all());
        $view->assertSee('Cola (Carton)');
    }

    public function test_order_list_total_is_net_of_refunds(): void
    {
        $order = $this->sell([[null, 4]], 10);
        $this->post(self::HOST . route('refund.store', [], false), ['order_id' => $order->id, 'qty' => [$order->items->first()->id => 4], 'reason' => 'x']);
        $this->actingAs($this->boss)->post(route('refund.approve', Refund::first()));   // fully refunded
        $this->sell([[null, 2]], 5);

        $res = $this->actingAs($this->boss)->get(route('order.index', ['date_from' => '2026-11-08T00:00', 'date_to' => '2026-11-08T23:59']));
        $this->assertEquals(5.00, $res->viewData('activeOrderTotal'));
    }

    public function test_staff_cannot_sell_another_companys_item_and_cart_is_one_company(): void
    {
        $other = $this->makeCompany($this->company->branch, 'B', 'shopb.test');
        $otherItem = $this->makeProduct($other, 'Other Cola');

        $this->actingAs($this->staff)->postJson(self::HOST . '/cart/add', ['id' => $otherItem->id])->assertStatus(422);

        // a manager selling at the counter cannot mix companies in one sale
        $manager = $this->makeUser(3, $this->company);
        $this->actingAs($manager)->postJson('/cart/add', ['id' => $this->cola->id])->assertOk();
        $this->actingAs($manager)->postJson('/cart/add', ['id' => $otherItem->id])->assertStatus(422);
    }

    public function test_adjusting_a_completed_row_drops_the_old_carton_label(): void
    {
        $batch = Batch::create(['branch_id' => $this->company->branch_id, 'company_id' => $this->company->id,
            'batch_no' => 'SBX', 'batch_date' => now()->toDateString(), 'status' => 'Open']);
        $this->actingAs($this->boss)->postJson(route('batch.addBatchItem', $batch), ['product_id' => $this->cola->id, 'unit_id' => $this->carton()]);
        $row = BatchItem::where('batch_id', $batch->id)->first();
        $this->actingAs($this->boss)->postJson(route('batch.updateOpenItem', $row), ['unit_id' => $this->carton(), 'entry_qty' => 2, 'entry_cost' => 36, 'changed' => 'qty']);
        $this->actingAs($this->boss)->get(route('batch.complete', $batch));

        $this->actingAs($this->boss)->post(route('batch.updateBatchItem', $row), ['quantity' => 40, 'cost_per_unit' => 1.5, 'total_cost' => 60]);

        $row->refresh();
        $this->assertEquals(40, $row->quantity);
        $this->assertSame('40', $row->entryLabel(), 'no longer "2 Carton"');
    }

    public function test_adding_a_stock_row_returns_the_row_html_and_remove_needs_no_reload(): void
    {
        $batch = Batch::create(['branch_id' => $this->company->branch_id, 'company_id' => $this->company->id,
            'batch_no' => 'SBY', 'batch_date' => now()->toDateString(), 'status' => 'Open']);

        $res = $this->actingAs($this->boss)->postJson(route('batch.addBatchItem', $batch), ['product_id' => $this->cola->id])->assertOk();
        $this->assertStringContainsString('data-id="' . $res->json('row.id') . '"', $res->json('html'));

        $this->actingAs($this->boss)->getJson(route('batch.destroyItem', $res->json('row.id')))->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(0, BatchItem::where('batch_id', $batch->id)->count());
    }
}
