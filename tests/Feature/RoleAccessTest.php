<?php

namespace Tests\Feature;

use App\Http\Controllers\BranchManagerController;
use App\Http\Controllers\CompanyStaffController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Role access checks. These tests never touch the database: the default
 * connection points at a connection that does not exist, so any query fails
 * loudly instead of reading or writing real data.
 */
class RoleAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'no_database_in_these_tests']);
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    private function userWithRole(int $role, array $extra = []): User
    {
        $user = new User(array_merge([
            'name' => 'Test', 'username' => 'test', 'role_id' => $role, 'is_active' => 1,
        ], $extra));
        $user->id = 999;
        return $user;
    }

    public static function forbiddenRoutes(): array
    {
        return [
            // master settings: only roles 1, 2
            'manager opens branch list'            => [3, 'get',  '/branch/index'],
            'manager creates branch manager'       => [3, 'post', '/branch_manager/store'],
            'manager opens uom'                    => [3, 'get',  '/uom/index'],
            'staff edits receipt settings'         => [5, 'post', '/receipt_setting/store'],
            'manager opens payment methods'        => [3, 'get',  '/payment_method/index'],
            'staff opens bank list'                => [5, 'get',  '/bank/index'],
            // branch-level: only roles 1, 2, 3
            'staff opens companies'                => [5, 'get',  '/company/index'],
            // back office: not staff
            'staff opens orders'                   => [5, 'get',  '/order/index'],
            'staff creates staff account'          => [5, 'post', '/company_staff/store'],
            'staff opens products'                 => [5, 'get',  '/product/index'],
            'staff opens batches'                  => [5, 'get',  '/batch/index'],
            'staff opens report'                   => [5, 'get',  '/report/index'],
            'staff opens attendance'               => [5, 'get',  '/staff_attendance/index'],
            'staff opens suppliers'                => [5, 'get',  '/supplier/index'],
            'staff opens categories'               => [5, 'get',  '/category/index'],
        ];
    }

    /** @dataProvider forbiddenRoutes */
    public function test_role_is_blocked(int $role, string $method, string $uri): void
    {
        $this->actingAs($this->userWithRole($role))
            ->{$method}($uri)
            ->assertForbidden();
    }

    public function test_route_files_declare_expected_roles(): void
    {
        $expected = [
            'branch.index'           => 'role:1,2',
            'branch_manager.update'  => 'role:1,2',
            'bank.index'             => 'role:1,2',
            'uom.index'              => 'role:1,2',
            'payment_method.index'   => 'role:1,2',
            'receipt_setting.store'  => 'role:1,2',
            'company.index'          => 'role:1,2,3',
            'company_staff.update'   => 'role:1,2,3',
            'order.index'            => 'role:1,2,3',
            'product.index'          => 'role:1,2,3',
            'batch.index'            => 'role:1,2,3',
        ];

        foreach ($expected as $name => $middleware) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route, "Route [$name] missing");
            $this->assertContains($middleware, $route->gatherMiddleware(), "Route [$name] should use $middleware");
        }
    }

    public function test_staff_routes_stay_open_to_staff(): void
    {
        foreach (['staff_borrow.index', 'shift_closing.index'] as $name) {
            $middleware = Route::getRoutes()->getByName($name)->gatherMiddleware();
            $this->assertNotEmpty(array_filter($middleware, fn ($m) => str_starts_with($m, 'auth')), "$name needs auth");
            $this->assertEmpty(array_filter($middleware, fn ($m) => str_starts_with($m, 'role:')), "$name should not be role-limited");
        }
    }

    public function test_guest_cannot_use_cart(): void
    {
        $this->postJson('/cart/add', ['id' => 1])->assertUnauthorized();
        $this->postJson('/cart/placeorder')->assertUnauthorized();
        $this->get('/cart/load')->assertRedirect('/login');
    }

    public function test_staff_cannot_set_discount(): void
    {
        $this->actingAs($this->userWithRole(5));
        $this->expectHttp403(fn () => app(\App\Http\Controllers\OrderController::class)
            ->discount(request(), new \App\Models\Order()));
    }

    public function test_company_manager_routes_are_gone(): void
    {
        $this->assertNull(Route::getRoutes()->getByName('company_manager.index'));
    }

    public function test_branch_manager_cannot_edit_an_admin_through_staff_screen(): void
    {
        $this->actingAs($this->userWithRole(3, ['branch_id' => 1]));
        $admin = $this->userWithRole(1, ['company_id' => 1, 'branch_id' => 1]);

        $this->expectHttp403(fn () => app(CompanyStaffController::class)->edit($admin));
    }

    public function test_branch_manager_cannot_edit_staff_of_another_branch(): void
    {
        $this->actingAs($this->userWithRole(3, ['branch_id' => 1]));
        $other = $this->userWithRole(5, ['company_id' => 2, 'branch_id' => 2]);

        $this->expectHttp403(fn () => app(CompanyStaffController::class)->destroy($other));
    }

    public function test_branch_manager_screen_cannot_touch_admins(): void
    {
        $this->actingAs($this->userWithRole(1));
        $admin = $this->userWithRole(2);

        $this->expectHttp403(fn () => app(BranchManagerController::class)->destroy($admin));
    }

    private function expectHttp403(callable $fn): void
    {
        try {
            $fn();
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            return;
        }
        $this->fail('Expected a 403 response');
    }
}
