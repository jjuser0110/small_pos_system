<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\ShiftClosing;
use App\Models\StaffAttendance;
use App\Support\ShiftWindow;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

class StaffShiftAccessTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    private Company $companyA;
    private Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();
        $branch = $this->makeBranch();
        $this->companyA = $this->makeCompany($branch, 'A', 'shopa.test');
        $this->companyB = $this->makeCompany($branch, 'B', 'shopb.test');
    }

    private function loginAt(string $host, $user)
    {
        return $this->post("http://$host/login", ['username' => $user->username, 'password' => 'secret-test']);
    }

    // ---- shift windows -------------------------------------------------

    public function test_am_window_edges(): void
    {
        $this->assertNull(ShiftWindow::current('AM', Carbon::parse('2026-11-08 09:44:59')));
        $this->assertSame('2026-11-08', ShiftWindow::current('AM', Carbon::parse('2026-11-08 09:45:00'))->workDate);
        $this->assertNotNull(ShiftWindow::current('AM', Carbon::parse('2026-11-08 22:14:59')));
        $this->assertNull(ShiftWindow::current('AM', Carbon::parse('2026-11-08 22:15:00')));
    }

    public function test_pm_window_runs_past_midnight_and_belongs_to_start_day(): void
    {
        $this->assertNull(ShiftWindow::current('PM', Carbon::parse('2026-11-08 21:44:00')));
        $this->assertSame('2026-11-08', ShiftWindow::current('PM', Carbon::parse('2026-11-08 21:45:00'))->workDate);
        $this->assertSame('2026-11-08', ShiftWindow::current('PM', Carbon::parse('2026-11-09 03:00:00'))->workDate);
        $this->assertSame('2026-11-08', ShiftWindow::current('PM', Carbon::parse('2026-11-09 10:14:00'))->workDate);
        $this->assertNull(ShiftWindow::current('PM', Carbon::parse('2026-11-09 10:15:00')));
    }

    // ---- login ---------------------------------------------------------

    public function test_staff_logs_in_on_own_domain_during_shift(): void
    {
        $this->travelTo(Carbon::parse('2026-11-08 09:50'));
        $staff = $this->makeUser(5, $this->companyA, ['shift' => 'AM']);

        $this->loginAt('shopa.test', $staff)->assertRedirect(route('counter'));
        $this->assertAuthenticatedAs($staff);

        $attendance = StaffAttendance::where('user_id', $staff->id)->first();
        $this->assertSame('AM', $attendance->shift);
        $this->assertSame('2026-11-08', Carbon::parse($attendance->work_date)->toDateString());
        $this->assertNotNull(ShiftClosing::openFor($staff), 'shift record opens at login');
    }

    public function test_staff_cannot_log_in_at_a_company_of_another_branch(): void
    {
        $this->travelTo(Carbon::parse('2026-11-08 12:00'));
        $this->makeCompany($this->makeBranch('North'), 'N', 'north.test');
        $staff = $this->makeUser(5, $this->companyA, ['shift' => 'AM']);

        $this->loginAt('north.test', $staff)->assertRedirect(route('login'))->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_staff_can_work_at_another_company_in_their_branch(): void
    {
        $this->travelTo(Carbon::parse('2026-11-08 12:00'));
        $staff = $this->makeUser(5, $this->companyA, ['shift' => 'AM']);   // home company A
        $itemB = $this->makeProduct($this->companyB, 'B Only Item');
        $this->makeProduct($this->companyA, 'A Only Item');

        $this->loginAt('shopb.test', $staff)->assertRedirect(route('counter'));
        $this->assertAuthenticatedAs($staff);

        // attendance and shift are recorded under the company logged in at (B)
        $this->assertSame($this->companyB->id, (int) StaffAttendance::where('user_id', $staff->id)->value('company_id'));
        $this->assertSame($this->companyB->id, (int) ShiftClosing::openFor($staff)->company_id);

        // the counter shows company B's items
        $tiles = collect($this->get('http://shopb.test/counter')->assertOk()->viewData('sellables'));
        $this->assertSame(['B Only Item'], $tiles->pluck('name')->unique()->values()->all());

        // and the sale belongs to company B
        \App\Models\PaymentMethod::create(['payment_method_name' => 'Cash']);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->postJson('http://shopb.test/cart/add', ['id' => $itemB->id])->assertOk();
        $order = $this->postJson('http://shopb.test/cart/placeorder', ['payment_method' => 'Cash', 'amount_received' => 10])
            ->assertOk()->json('order_id');
        $this->assertSame($this->companyB->id, (int) \App\Models\Order::find($order)->company_id);

        // home shows where they checked in
        $this->get('http://shopb.test/home')->assertOk()->assertSee("Today's Check-in", false)->assertSee('Company B');
    }

    public function test_www_and_port_are_ignored_for_domain(): void
    {
        $this->travelTo(Carbon::parse('2026-11-08 12:00'));
        $staff = $this->makeUser(5, $this->companyA, ['shift' => 'AM']);

        $this->loginAt('www.shopa.test:8080', $staff)->assertRedirect(route('counter'));
    }

    public function test_staff_cannot_log_in_before_grace_window(): void
    {
        $this->travelTo(Carbon::parse('2026-11-08 09:40'));
        $staff = $this->makeUser(5, $this->companyA, ['shift' => 'AM']);

        $this->loginAt('shopa.test', $staff)->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_managers_may_log_in_on_any_domain_any_time(): void
    {
        $this->travelTo(Carbon::parse('2026-11-08 04:00'));
        $manager = $this->makeUser(3, $this->companyB);

        $this->loginAt('shopa.test', $manager)->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($manager);
    }

    // ---- while logged in -----------------------------------------------

    public function test_staff_is_logged_out_after_shift_end_plus_grace(): void
    {
        $this->travelTo(Carbon::parse('2026-11-08 21:00'));
        $staff = $this->makeUser(5, $this->companyA, ['shift' => 'AM']);
        $this->loginAt('shopa.test', $staff);
        $this->assertAuthenticatedAs($staff);

        $this->travelTo(Carbon::parse('2026-11-08 22:10'));
        $this->get('http://shopa.test/home')->assertOk();   // still in grace

        $this->travelTo(Carbon::parse('2026-11-08 22:16'));
        $this->get('http://shopa.test/home')->assertRedirect(route('login'));
        $this->assertGuest();

        $attendance = StaffAttendance::where('user_id', $staff->id)->first();
        $this->assertSame('auto', $attendance->clock_out_reason);
    }

    public function test_attendance_left_open_is_closed_at_shift_logout_time(): void
    {
        $staff = $this->makeUser(5, $this->companyA, ['shift' => 'PM']);
        StaffAttendance::create([
            'user_id' => $staff->id, 'branch_id' => $staff->branch_id, 'company_id' => $staff->company_id,
            'work_date' => '2026-11-07', 'shift' => 'PM', 'clock_in' => '2026-11-07 21:50:00',
        ]);

        $this->travelTo(Carbon::parse('2026-11-08 21:50'));
        StaffAttendance::closeStale($staff);

        $row = StaffAttendance::where('work_date', '2026-11-07')->first();
        $this->assertSame('2026-11-08 10:15:00', Carbon::parse($row->clock_out)->format('Y-m-d H:i:s'));
        $this->assertSame('auto', $row->clock_out_reason);
    }

    public function test_normal_logout_records_reason(): void
    {
        $this->travelTo(Carbon::parse('2026-11-08 12:00'));
        $staff = $this->makeUser(5, $this->companyA, ['shift' => 'AM']);
        $this->loginAt('shopa.test', $staff);

        $this->post('http://shopa.test/logout');

        $this->assertSame('logout', StaffAttendance::where('user_id', $staff->id)->first()->clock_out_reason);
    }

    public function test_company_domain_is_normalised(): void
    {
        $this->assertSame('shop.example.com', Company::normalizeDomain('https://WWW.Shop.Example.com:443/login'));
    }
}
