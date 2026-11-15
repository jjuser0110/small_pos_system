<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Company;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ReceiptSetting;
use App\Models\Refund;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

/** Holes found in the security review. */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = $this->makeCompany($this->makeBranch(), 'A', 'shopa.test');
        $this->travelTo(Carbon::parse('2026-11-08 12:00'));
    }

    public function test_public_signup_and_password_reset_pages_are_gone(): void
    {
        $this->assertFalse(Route::has('register'));
        $this->assertFalse(Route::has('password.request'));
        $this->get('/register')->assertNotFound();
        $this->get('/password/reset')->assertNotFound();
    }

    public function test_account_without_a_known_role_cannot_log_in(): void
    {
        $odd = $this->makeUser(0, null, ['username' => 'norole']);
        $this->post('/login', ['username' => 'norole', 'password' => 'secret-test'])->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_link_actions_from_another_website_are_refused(): void
    {
        $boss = $this->makeUser(1);
        $item = $this->makeProduct($this->company, 'Victim Item');

        // a delete link clicked on another website
        $this->actingAs($boss)->withHeaders(['Sec-Fetch-Site' => 'cross-site'])
            ->get(route('product.destroy', $item))->assertForbidden();
        $this->assertNotNull(Product::find($item->id));

        // the same link inside FsMart works
        $this->actingAs($boss)->withHeaders(['Sec-Fetch-Site' => 'same-origin'])
            ->get(route('product.destroy', $item))->assertRedirect();
        $this->assertNull(Product::find($item->id));
    }

    public function test_pages_send_security_headers(): void
    {
        $res = $this->get('/login');
        $res->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $res->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_receipt_header_cannot_inject_script(): void
    {
        ReceiptSetting::create(['header' => "FSMART\n<script>alert(1)</script>", 'footer' => 'Thanks']);
        PaymentMethod::create(['payment_method_name' => 'Cash']);
        $boss = $this->makeUser(1);
        $item = $this->makeProduct($this->company, 'Cola', 2);
        $this->stockBatch($item, 5, 1);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($boss)->postJson('/cart/add', ['id' => $item->id]);
        $order = $this->actingAs($boss)->postJson('/cart/placeorder', ['payment_method' => 'Cash', 'amount_received' => 2])->json('order_id');

        $this->actingAs($boss)->get(route('receipt', $order))->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    public function test_completed_entry_adjustment_cannot_run_on_an_open_entry(): void
    {
        $boss = $this->makeUser(1);
        $item = $this->makeProduct($this->company, 'Cola');
        $batch = Batch::create(['branch_id' => $this->company->branch_id, 'company_id' => $this->company->id,
            'batch_no' => 'SB1', 'batch_date' => now()->toDateString(), 'status' => 'Open']);
        $row = BatchItem::create(['batch_id' => $batch->id, 'branch_id' => $item->branch_id, 'company_id' => $item->company_id,
            'category_id' => $item->category_id, 'product_id' => $item->id, 'quantity' => 10, 'balance' => 10,
            'cost_per_unit' => 1, 'total_cost' => 10]);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        $this->actingAs($boss)->post(route('batch.updateBatchItem', $row), ['quantity' => 50, 'cost_per_unit' => 1, 'total_cost' => 50])
            ->assertSessionHasErrors();
        $this->assertEquals(0, $item->fresh()->stock_quantity, 'stock must not be added before the entry is completed');
    }

    public function test_refund_after_discount_returns_what_was_paid(): void
    {
        PaymentMethod::create(['payment_method_name' => 'Cash']);
        $item = $this->makeProduct($this->company, 'Beer', 10);
        $this->stockBatch($item, 5, 4);
        $staff = $this->makeUser(5, $this->company, ['shift' => 'AM']);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($staff);
        $this->postJson('http://shopa.test/cart/add', ['id' => $item->id]);
        $this->postJson('http://shopa.test/cart/add', ['id' => $item->id]);
        $order = Order::find($this->postJson('http://shopa.test/cart/placeorder', ['payment_method' => 'Cash', 'amount_received' => 20])->json('order_id'));

        $this->actingAs($this->makeUser(3, $this->company))->post(route('order.discount', $order), ['discount' => 4]);   // paid 16 for 2

        $this->actingAs($staff)->post('http://shopa.test' . route('refund.store', [], false), [
            'order_id' => $order->id, 'qty' => [$order->items->first()->id => 1], 'reason' => 'x',
        ]);
        $this->assertEquals(8.00, Refund::first()->amount, 'half of what was paid, not the full RM10 price');
    }

    public function test_staff_passwords_need_at_least_six_characters(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($this->makeUser(1))->post(route('company_staff.store'), [
            'name' => 'New', 'username' => 'newstaff', 'password' => '123', 'company_id' => $this->company->id, 'shift' => 'AM',
        ])->assertSessionHasErrors('password');
        $this->assertNull(User::where('username', 'newstaff')->first());
    }

    public function test_dead_routes_are_gone(): void
    {
        foreach (['order.create', 'order.edit', 'order.destroy', 'receipt_setting.destroy', 'role.index'] as $name) {
            $this->assertFalse(Route::has($name), "$name should not exist");
        }
    }
}
