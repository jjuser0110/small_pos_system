<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Company;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\StockTransfer;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

/** Item 16: branch manager sends A -> B; A staff confirm sent; B staff confirm received; stock moves. */
class StockTransferTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    private Company $a;
    private Company $b;
    private Product $itemA;
    private Product $itemB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $branch = $this->makeBranch();
        $this->a = $this->makeCompany($branch, 'A', 'shopa.test');
        $this->b = $this->makeCompany($branch, 'B', 'shopb.test');
        $this->itemA = $this->makeProduct($this->a, 'Tiger Beer 330ml');
        $this->itemB = $this->makeProduct($this->b, 'Tiger Can');   // same goods, different name
        $this->stockBatch($this->itemA, 10, 3.00);
        $this->travelTo(Carbon::parse('2026-11-08 12:00'));
    }

    private function send(float $qty, $as)
    {
        return $this->actingAs($as)->post(route('stock_transfer.store'), [
            'from_product_id' => $this->itemA->id, 'to_product_id' => $this->itemB->id,
            'quantity' => $qty, 'reason' => 'restock B',
        ]);
    }

    /** Staff working at a company: the request host is that company's domain. */
    private function asStaffAt(Company $company, string $action, StockTransfer $t, array $data = [])
    {
        $staff = $this->makeUser(5, $this->a, ['shift' => 'AM']);   // home company A, works anywhere in the branch
        return $this->actingAs($staff)->post("http://{$company->domain}" . route("stock_transfer.$action", $t, false), $data);
    }

    public function test_branch_manager_creates_transfer_waiting_for_sender(): void
    {
        $this->send(4, $this->makeUser(3, $this->a))->assertRedirect(route('stock_transfer.index'));

        $t = StockTransfer::first();
        $this->assertSame(StockTransfer::PENDING_SENDER, $t->status);
        $this->assertEquals(10, $this->itemA->fresh()->stock_quantity);
        $this->assertEquals(0, $this->itemB->fresh()->stock_quantity);
    }

    public function test_staff_cannot_create_a_transfer(): void
    {
        $this->actingAs($this->makeUser(5, $this->a, ['shift' => 'AM']))
            ->post('http://shopa.test' . route('stock_transfer.store', [], false), [
                'from_product_id' => $this->itemA->id, 'to_product_id' => $this->itemB->id, 'quantity' => 1,
            ])->assertForbidden();
        $this->assertSame(0, StockTransfer::count());
    }

    public function test_branch_manager_cannot_send_to_another_branch(): void
    {
        $far = $this->makeCompany($this->makeBranch('Far'), 'F', 'far.test');
        $farItem = $this->makeProduct($far, 'Far Beer');

        $this->actingAs($this->makeUser(3, $this->a))->post(route('stock_transfer.store'), [
            'from_product_id' => $this->itemA->id, 'to_product_id' => $farItem->id, 'quantity' => 1,
        ])->assertForbidden();
    }

    public function test_full_flow_sender_then_receiver_then_stock_moves(): void
    {
        $this->send(4, $this->makeUser(3, $this->a));
        $t = StockTransfer::first();

        // B staff cannot do step 1; A staff can
        $this->asStaffAt($this->b, 'approve', $t)->assertForbidden();
        $this->asStaffAt($this->a, 'approve', $t)->assertSessionHasNoErrors();
        $t->refresh();
        $this->assertSame(StockTransfer::PENDING_RECEIVER, $t->status);
        $this->assertNotNull($t->sender_confirmed_by);
        $this->assertEquals(10, $this->itemA->fresh()->stock_quantity, 'nothing moves after step 1');

        // A staff cannot do step 2; B staff can
        $this->asStaffAt($this->a, 'approve', $t)->assertForbidden();
        $this->asStaffAt($this->b, 'approve', $t)->assertSessionHasNoErrors();

        $t->refresh();
        $this->assertSame(StockTransfer::APPROVED, $t->status);
        $this->assertEquals(6, $this->itemA->fresh()->stock_quantity);
        $this->assertEquals(4, $this->itemB->fresh()->stock_quantity);

        // B received a completed stock entry at A's cost, so later sales have a cost
        $received = BatchItem::where('product_id', $this->itemB->id)->first();
        $this->assertEquals(3.00, $received->cost_per_unit);
        $this->assertSame('Completed', Batch::find($t->received_batch_id)->status);
        $this->assertSame(1, StockLog::where('type', 'transfer_out')->where('product_id', $this->itemA->id)->count());
        $this->assertSame(1, StockLog::where('type', 'transfer_in')->where('product_id', $this->itemB->id)->count());
    }

    public function test_branch_manager_cannot_confirm_but_superadmin_can_do_both_steps(): void
    {
        $this->send(2, $this->makeUser(3, $this->a));
        $t = StockTransfer::first();

        $this->actingAs($this->makeUser(3, $this->a))->post(route('stock_transfer.approve', $t))->assertForbidden();

        $boss = $this->makeUser(1);
        $this->actingAs($boss)->post(route('stock_transfer.approve', $t));
        $this->actingAs($boss)->post(route('stock_transfer.approve', $t));

        $this->assertSame(StockTransfer::APPROVED, $t->fresh()->status);
        $this->assertEquals(2, $this->itemB->fresh()->stock_quantity);
    }

    public function test_confirm_fails_cleanly_if_sender_ran_out(): void
    {
        $this->send(8, $this->makeUser(3, $this->a));
        $t = StockTransfer::first();
        $this->asStaffAt($this->a, 'approve', $t);
        $this->itemA->update(['stock_quantity' => 5]);  // sold meanwhile
        BatchItem::where('product_id', $this->itemA->id)->update(['balance' => 5]);

        $this->asStaffAt($this->b, 'approve', $t)->assertSessionHasErrors();

        $this->assertSame(StockTransfer::PENDING_RECEIVER, $t->fresh()->status);
        $this->assertEquals(5, $this->itemA->fresh()->stock_quantity);
        $this->assertEquals(0, $this->itemB->fresh()->stock_quantity);
    }

    public function test_either_side_can_reject_its_step_and_record_is_kept(): void
    {
        $this->send(3, $this->makeUser(3, $this->a));
        $t = StockTransfer::first();
        $this->asStaffAt($this->a, 'approve', $t);
        $this->asStaffAt($this->b, 'reject', $t, ['reject_reason' => 'wrong item']);

        $t->refresh();
        $this->assertSame(StockTransfer::REJECTED, $t->status);
        $this->assertSame('wrong item', $t->reject_reason);
        $this->assertEquals(10, $this->itemA->fresh()->stock_quantity);
    }

    public function test_pending_transfers_notify_the_right_staff(): void
    {
        $this->send(1, $this->makeUser(3, $this->a));
        $staff = $this->makeUser(5, $this->a, ['shift' => 'AM']);

        $this->actingAs($staff)->get('http://shopa.test/notifications')->assertJsonFragment(['key' => 'transfers']);
        $this->actingAs($staff)->get('http://shopb.test/notifications')->assertJsonMissing(['key' => 'transfers']);
    }

    public function test_only_superadmin_can_adjust_stock_and_it_is_logged(): void
    {
        $this->actingAs($this->makeUser(3, $this->a))->post(route('stock_adjustment.store'), [
            'product_id' => $this->itemA->id, 'type' => 'adjust_out', 'quantity' => 1, 'reason' => 'x',
        ])->assertForbidden();

        $this->actingAs($this->makeUser(1))->post(route('stock_adjustment.store'), [
            'product_id' => $this->itemA->id, 'type' => 'adjust_out', 'quantity' => 2, 'reason' => 'count fix',
        ])->assertSessionHasNoErrors();

        $this->assertEquals(8, $this->itemA->fresh()->stock_quantity);
        $this->assertSame(1, StockLog::where('type', 'adjust_out')->count());
    }
}
