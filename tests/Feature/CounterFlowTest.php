<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Damage;
use App\Models\Order;
use App\Models\OrderItemProfit;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Refund;
use App\Models\ShiftClosing;
use App\Models\StaffBorrow;
use App\Models\StockLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

class CounterFlowTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    private const HOST = 'http://shopa.test';

    private Company $company;
    private Product $beer;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        PaymentMethod::create(['payment_method_name' => 'Cash']);
        PaymentMethod::create(['payment_method_name' => 'QR']);

        $this->company = $this->makeCompany($this->makeBranch(), 'A', 'shopa.test');
        $this->beer = $this->makeProduct($this->company, 'Beer', 12.00);
        $this->stockBatch($this->beer, 10, 5.00);
        $this->staff = $this->makeUser(5, $this->company, ['shift' => 'AM']);

        $this->travelTo(Carbon::parse('2026-11-08 12:00'));
        $this->post(self::HOST . '/login', ['username' => $this->staff->username, 'password' => 'secret-test']);
    }

    private function sell(int $qty, string $method = 'Cash', ?float $received = null): Order
    {
        for ($i = 0; $i < $qty; $i++) {
            $this->postJson(self::HOST . '/cart/add', ['id' => $this->beer->id])->assertOk();
        }
        $res = $this->postJson(self::HOST . '/cart/placeorder', [
            'payment_method' => $method, 'amount_received' => $received ?? $qty * 12.00,
        ]);
        $res->assertOk()->assertJson(['status' => 'success']);

        return Order::findOrFail($res->json('order_id'));
    }

    private function shift(): ShiftClosing
    {
        return ShiftClosing::openFor($this->staff)->fresh();
    }

    // ---- selling ------------------------------------------------------

    public function test_cash_and_qr_orders_update_shift_totals(): void
    {
        $this->sell(2, 'Cash', 50);
        $this->sell(1, 'QR');

        $shift = $this->shift();
        $this->assertEquals(2, $shift->total_order_count, 'counts orders, not product lines');
        $this->assertEquals(3, $shift->total_item_count);
        $this->assertEquals(36.00, $shift->total_order_amount);
        $this->assertEquals(24.00, $shift->items()->where('payment_method', 'Cash')->value('amount'));
        $this->assertEquals(12.00, $shift->items()->where('payment_method', 'QR')->value('amount'));
        $this->assertEquals(7, $this->beer->fresh()->stock_quantity);
        $this->assertEquals(26.00, Order::where('payment_method', 'Cash')->value('change'));
    }

    public function test_unknown_payment_method_is_rejected(): void
    {
        $this->postJson(self::HOST . '/cart/add', ['id' => $this->beer->id]);
        $this->postJson(self::HOST . '/cart/placeorder', ['payment_method' => 'cash-ish', 'amount_received' => 12])
            ->assertStatus(422);
        $this->assertSame(0, Order::count());
    }

    public function test_counter_can_sell_into_negative_stock(): void
    {
        $this->beer->update(['stock_quantity' => 1]);
        \App\Models\BatchItem::where('product_id', $this->beer->id)->update(['balance' => 1]);

        $order = $this->sell(3);

        $this->assertEquals(-2, $this->beer->fresh()->stock_quantity, 'the counter never blocks a sale');
        // the 1 in stock was costed; the 2 sold without stock have no cost, so no profit row
        $this->assertEquals(1, OrderItemProfit::where('order_item_id', $order->items->first()->id)->sum('quantity'));
    }

    public function test_sell_option_moves_base_units_and_refunds_back(): void
    {
        $carton = $this->beer->allUnits()->create(['name' => 'Carton', 'base_qty' => 6, 'price' => 60.00, 'barcode' => 'CTN6']);
        $this->stockBatch($this->beer, 20, 5.00, 'SB2');   // stock now 30

        $this->postJson(self::HOST . '/cart/add', ['id' => $this->beer->id, 'unit_id' => $carton->id])->assertOk();
        $this->postJson(self::HOST . '/cart/add', ['id' => $this->beer->id, 'unit_id' => $carton->id])->assertOk();
        $this->postJson(self::HOST . '/cart/add', ['id' => $this->beer->id])->assertOk();   // and 1 single

        $lines = $this->getJson(self::HOST . '/cart/load')->json();
        $this->assertCount(2, $lines, 'carton and single are separate cart lines');
        $this->assertSame('Beer (Carton)', $lines[0]['product_name']);

        $res = $this->postJson(self::HOST . '/cart/placeorder', ['payment_method' => 'Cash', 'amount_received' => 132]);
        $res->assertOk();
        $order = Order::find($res->json('order_id'));

        $this->assertEquals(132.00, $order->final_total);          // 2 x 60 + 12
        $this->assertEquals(30 - 12 - 1, $this->beer->fresh()->stock_quantity);
        $cartonLine = $order->items->firstWhere('product_unit_id', $carton->id);
        $this->assertSame('Carton', $cartonLine->unit_name);
        $this->assertEquals(12, OrderItemProfit::where('order_item_id', $cartonLine->id)->sum('quantity'), 'profit rows in base units');
        $this->assertEquals(120.00, OrderItemProfit::where('order_item_id', $cartonLine->id)->sum('total_selling_price'));

        // refund 1 carton: 6 base units come back
        $this->post(self::HOST . route('refund.store', [], false), [
            'order_id' => $order->id, 'qty' => [$cartonLine->id => 1], 'reason' => 'damaged box',
        ])->assertSessionHasNoErrors();
        $this->actingAs($this->makeUser(1))->post(route('refund.approve', Refund::first()))->assertSessionHasNoErrors();

        $this->assertEquals(17 + 6, $this->beer->fresh()->stock_quantity);
        $this->assertEquals(60.00, Refund::first()->amount);
    }

    // ---- refund -------------------------------------------------------

    public function test_refund_needs_superadmin_then_returns_stock_and_cuts_shift(): void
    {
        $order = $this->sell(3);
        $item = $order->items->first();

        $this->post(self::HOST . route('refund.store', [], false), [
            'order_id' => $order->id, 'qty' => [$item->id => 2], 'reason' => 'flat beer',
        ])->assertSessionHasNoErrors();

        $refund = Refund::first();
        $this->assertSame('pending', $refund->status);
        $this->assertEquals(24.00, $refund->amount);
        $this->assertEquals(7, $this->beer->fresh()->stock_quantity, 'nothing returned before approval');

        // staff cannot approve their own refund
        $this->post(self::HOST . route('refund.approve', $refund, false))->assertForbidden();

        // branch manager can no longer approve refunds; the superadmin does
        $this->actingAs($this->makeUser(3, $this->company))->post(route('refund.approve', $refund))->assertForbidden();
        $this->actingAs($this->makeUser(1))->post(route('refund.approve', $refund))->assertSessionHasNoErrors();

        $this->assertSame('approved', $refund->fresh()->status);
        $this->assertEquals(9, $this->beer->fresh()->stock_quantity);
        $this->assertEquals(2, $item->fresh()->refunded_quantity);
        $this->assertEquals(1, OrderItemProfit::where('order_item_id', $item->id)->sum('quantity'), 'profit reduced to the 1 kept');

        $shift = ShiftClosing::find($order->shift_closing_id);
        $this->assertEquals(36.00, $shift->total_order_amount);
        $this->assertEquals(24.00, $shift->refund_amount);
        $this->assertEquals(12.00, $shift->netAmount());
        $this->assertEquals(12.00, $shift->items()->where('payment_method', 'Cash')->value('amount'));
        $this->assertSame(1, StockLog::where('type', 'refund_in')->count());
    }

    public function test_cannot_refund_more_than_sold_or_an_order_from_another_shift(): void
    {
        $order = $this->sell(1);
        $item = $order->items->first();

        $this->post(self::HOST . route('refund.store', [], false), [
            'order_id' => $order->id, 'qty' => [$item->id => 2], 'reason' => 'x',
        ])->assertSessionHasErrors();

        $order->update(['shift_closing_id' => 999999]); // pretend it was an earlier shift
        $this->post(self::HOST . route('refund.store', [], false), [
            'order_id' => $order->id, 'qty' => [$item->id => 1], 'reason' => 'x',
        ])->assertSessionHasErrors();

        $this->assertSame(0, Refund::count());
    }

    public function test_full_refund_marks_order_refunded(): void
    {
        $order = $this->sell(1);
        $this->post(self::HOST . route('refund.store', [], false), [
            'order_id' => $order->id, 'qty' => [$order->items->first()->id => 1], 'reason' => 'x',
        ]);
        $this->actingAs($this->makeUser(1))->post(route('refund.approve', Refund::first()));

        $this->assertSame('Refunded', $order->fresh()->status);
    }

    // ---- damage -------------------------------------------------------

    public function test_damage_is_reported_by_branch_manager_and_removed_when_superadmin_approves(): void
    {
        // staff can no longer report damage
        $this->post(self::HOST . route('damage.store', [], false), [
            'company_id' => $this->company->id, 'product_id' => $this->beer->id, 'quantity' => 2, 'reason' => 'dropped',
        ])->assertForbidden();

        $manager = $this->makeUser(3, $this->company);
        $this->actingAs($manager)->post(route('damage.store'), [
            'company_id' => $this->company->id, 'product_id' => $this->beer->id, 'quantity' => 2, 'reason' => 'dropped',
        ])->assertSessionHasNoErrors();
        $damage = Damage::first();
        $this->assertEquals(10, $this->beer->fresh()->stock_quantity);

        $this->actingAs($this->makeUser(3, $this->company))->post(route('damage.approve', $damage))->assertForbidden();
        $this->actingAs($this->makeUser(1))->post(route('damage.approve', $damage))->assertSessionHasNoErrors();

        $this->assertSame('approved', $damage->fresh()->status);
        $this->assertEquals(8, $this->beer->fresh()->stock_quantity);
        $this->assertSame(1, StockLog::where('type', 'damage')->count());
    }

    // ---- advance ------------------------------------------------------

    public function test_advance_is_cut_from_the_shift_it_was_requested_in(): void
    {
        $this->sell(5);
        $requestShift = $this->shift();

        $this->post(self::HOST . route('staff_borrow.store', [], false), ['amount' => 20, 'reason' => 'transport'])
            ->assertSessionHasNoErrors();
        $advance = StaffBorrow::first();
        $this->assertEquals($requestShift->id, $advance->shift_closing_id);

        // approved the next day, during another shift
        $this->travelTo(Carbon::parse('2026-11-09 12:00'));
        $this->actingAs($this->makeUser(3, $this->company))->post(route('staff_borrow.approve', $advance))->assertForbidden();
        $this->actingAs($this->makeUser(1))->post(route('staff_borrow.approve', $advance))->assertSessionHasNoErrors();

        $requestShift->refresh();
        $this->assertEquals(20.00, $requestShift->advance_amount);
        $this->assertEquals(40.00, $requestShift->netAmount());
    }

    // ---- void ---------------------------------------------------------

    public function test_void_after_partial_refund_returns_only_the_rest(): void
    {
        $order = $this->sell(3);
        $item = $order->items->first();
        $this->post(self::HOST . route('refund.store', [], false), ['order_id' => $order->id, 'qty' => [$item->id => 1], 'reason' => 'x']);
        $this->actingAs($this->makeUser(1))->post(route('refund.approve', Refund::first()));

        $manager = $this->makeUser(3, $this->company);
        $this->actingAs($manager)->post(route('order.void', $order), ['voided_reason' => 'mistake'])->assertSessionHasNoErrors();

        $this->assertEquals(10, $this->beer->fresh()->stock_quantity, 'all 3 back: 1 by refund, 2 by void');
        $shift = ShiftClosing::find($order->shift_closing_id);
        $this->assertEquals(0, $shift->total_order_count);
        $this->assertEquals(0, $shift->total_order_amount);
        $this->assertEquals(0, $shift->refund_amount);
        $this->assertEquals(0, $shift->items()->where('payment_method', 'Cash')->value('amount'));
    }

    public function test_damage_item_must_belong_to_the_chosen_company(): void
    {
        $other = $this->makeCompany($this->company->branch, 'Z', 'shopz.test');
        $manager = $this->makeUser(3, $this->company);

        $this->actingAs($manager)->post(route('damage.store'), [
            'company_id' => $other->id, 'product_id' => $this->beer->id, 'quantity' => 1, 'reason' => 'x',
        ])->assertSessionHasErrors();
        $this->assertSame(0, Damage::count());
    }

    public function test_counter_has_no_damage_button_for_staff(): void
    {
        $this->get(self::HOST . '/counter')->assertOk()->assertDontSee(route('damage.index'), false);
    }
}
