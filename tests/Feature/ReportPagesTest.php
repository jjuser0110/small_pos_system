<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\PaymentMethod;
use App\Models\ShiftClosing;
use App\Models\ShiftMethodClosing;
use App\Models\StaffAttendance;
use App\Models\StaffBorrow;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

/** Shift report, staff detail page, and a smoke test that every main page renders. */
class ReportPagesTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    private Company $company;
    private User $staff;
    private ShiftClosing $shift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-11-08 12:00'));
        PaymentMethod::create(['payment_method_name' => 'Cash']);
        PaymentMethod::create(['payment_method_name' => 'QR']);

        $this->company = $this->makeCompany($this->makeBranch(), 'A', 'shopa.test');
        $this->makeProduct($this->company, 'Beer', 12);
        $this->staff = $this->makeUser(5, $this->company, ['shift' => 'AM', 'name' => 'Ali Staff']);

        $this->shift = ShiftClosing::create([
            'user_id' => $this->staff->id, 'branch_id' => $this->company->branch_id, 'company_id' => $this->company->id,
            'shift' => 'AM', 'work_date' => '2026-11-08',
            'total_order_count' => 4, 'total_item_count' => 9, 'total_order_amount' => 150.00,
            'refund_amount' => 12.00, 'advance_amount' => 20.00,
        ]);
        ShiftMethodClosing::create(['shift_closing_id' => $this->shift->id, 'payment_method' => 'Cash', 'amount' => 100.00]);
        ShiftMethodClosing::create(['shift_closing_id' => $this->shift->id, 'payment_method' => 'QR', 'amount' => 38.00]);

        StaffAttendance::create([
            'user_id' => $this->staff->id, 'branch_id' => $this->company->branch_id, 'company_id' => $this->company->id,
            'work_date' => '2026-11-07', 'shift' => 'AM', 'clock_in' => '2026-11-07 09:50:00',
            'clock_out' => '2026-11-07 22:05:00', 'clock_out_reason' => 'closing',
        ]);
        StaffBorrow::create([
            'user_id' => $this->staff->id, 'branch_id' => $this->company->branch_id, 'company_id' => $this->company->id,
            'shift_closing_id' => $this->shift->id, 'amount' => 20, 'reason' => 'bus fare', 'status' => 'approved',
        ]);
    }

    public function test_shift_report_shows_sales_refunds_advances_net_and_methods(): void
    {
        $this->actingAs($this->makeUser(1))
            ->get(route('shift_closing.index', ['date_from' => '2026-11-08', 'date_to' => '2026-11-08']))
            ->assertOk()
            ->assertSee('Ali Staff')
            ->assertSee('150.00')
            ->assertSee('12.00')
            ->assertSee('118.00')       // 150 - 12 - 20
            ->assertSee('100.00')       // Cash column
            ->assertSee('38.00');       // QR column
    }

    public function test_shift_report_filters_by_shift(): void
    {
        $this->actingAs($this->makeUser(1))
            ->get(route('shift_closing.index', ['date_from' => '2026-11-08', 'date_to' => '2026-11-08', 'shift' => 'PM']))
            ->assertOk()
            ->assertDontSee('Ali Staff');
    }

    public function test_staff_only_see_their_own_shifts(): void
    {
        $other = $this->makeUser(5, $this->company, ['shift' => 'AM', 'name' => 'Other Person']);
        $this->actingAs($other)
            ->get('http://shopa.test' . route('shift_closing.index', ['date_from' => '2026-11-08', 'date_to' => '2026-11-08'], false))
            ->assertOk()
            ->assertDontSee('Ali Staff');

        $this->get('http://shopa.test' . route('shift_closing.view', $this->shift, false))->assertForbidden();
    }

    public function test_staff_detail_page_shows_month_attendance_and_advances(): void
    {
        $this->actingAs($this->makeUser(1))
            ->get(route('company_staff.show', [$this->staff, 'month' => '2026-11']))
            ->assertOk()
            ->assertSee('Ali Staff')
            ->assertSee('Present days')
            ->assertSee('12h 15m')       // 09:50 to 22:05
            ->assertSee('Closing button')
            ->assertSee('bus fare')
            ->assertSee('RM 20.00');
    }

    public function test_branch_manager_cannot_open_staff_of_another_branch(): void
    {
        $otherBranchManager = $this->makeUser(3, $this->makeCompany($this->makeBranch('North'), 'N'));
        $this->actingAs($otherBranchManager)->get(route('company_staff.show', $this->staff))->assertForbidden();
    }

    /** Every main page renders without errors for the roles that use it. */
    public function test_main_pages_render(): void
    {
        $admin = $this->makeUser(1);
        foreach ([
            'home', 'counter', 'checkout', 'product.index', 'product.create', 'batch.index', 'batch.create',
            'category.index', 'company.index', 'company.create', 'company_staff.index', 'company_staff.create',
            'stock_transfer.index', 'stock_adjustment.index', 'refund.index', 'damage.index',
            'staff_borrow.index', 'shift_closing.index', 'shift_closing.print', 'report.index', 'order.index',
            'staff_attendance.index',
        ] as $name) {
            $this->actingAs($admin)->get(route($name))->assertOk();
        }
        $this->actingAs($admin)->get(route('shift_closing.view', $this->shift))->assertOk();

        $manager = $this->makeUser(3, $this->company);
        foreach (['home', 'stock_transfer.index', 'refund.index', 'damage.index', 'staff_borrow.index', 'shift_closing.index'] as $name) {
            $this->actingAs($manager)->get(route($name))->assertOk();
        }

        foreach (['home', 'counter', 'checkout', 'refund.create', 'refund.index', 'damage.index', 'staff_borrow.index',
                  'stock_transfer.index', 'shift_closing.index'] as $name) {
            $this->actingAs($this->staff)->get('http://shopa.test' . route($name, [], false))->assertOk();
        }
    }
}
