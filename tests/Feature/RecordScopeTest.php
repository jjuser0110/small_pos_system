<?php

namespace Tests\Feature;

use App\Http\Controllers\BatchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShiftClosingController;
use App\Models\Batch;
use App\Models\BatchItem;
use App\Models\Category;
use App\Models\Company;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShiftClosing;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * Record-level (ownership) checks. Like RoleAccessTest, no database is used:
 * the default connection does not exist, and every check under test runs
 * before any query.
 */
class RecordScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'no_database_in_these_tests']);
    }

    private function login(int $role, array $extra = []): User
    {
        $user = new User(array_merge(['name' => 'T', 'username' => 't', 'role_id' => $role], $extra));
        $user->id = 100;
        $this->actingAs($user);
        return $user;
    }

    /** Exposes the shared helper so the rules can be checked directly. */
    private function scope(): Controller
    {
        return new class extends Controller {
            public function can($b, $c, $o = null) { return $this->userCanAccess($b, $c, $o); }
        };
    }

    public function test_scope_rules_per_role(): void
    {
        // [role, user branch, user company, record branch, record company, record owner, expected]
        $cases = [
            [1, null, null, 9, 9, null, true],   // super admin: everything
            [2, null, null, 9, 9, null, true],   // admin: everything
            [3, 1, null, 1, 5, null, true],      // branch manager: own branch
            [3, 1, null, 2, 5, null, false],     //   other branch
            [3, null, null, null, 5, null, false], //   manager with no branch sees nothing
            [4, 1, 5, 1, 5, null, true],         // company manager: own company
            [4, 1, 5, 1, 6, null, false],        //   other company, same branch
            [5, 1, 5, 1, 5, 100, true],          // staff: own record
            [5, 1, 5, 1, 5, 101, false],         //   someone else's, same company
            [5, 1, 5, 1, 5, null, false],        //   records with no owner
        ];

        foreach ($cases as $i => [$role, $ub, $uc, $rb, $rc, $owner, $expected]) {
            $this->login($role, ['branch_id' => $ub, 'company_id' => $uc]);
            $this->assertSame($expected, $this->scope()->can($rb, $rc, $owner), "case #$i");
        }
    }

    public function test_branch_manager_cannot_open_order_of_other_branch(): void
    {
        $this->login(3, ['branch_id' => 1]);
        $order = new Order(['branch_id' => 2, 'company_id' => 7]);

        $this->expect403(fn () => app(OrderController::class)->view($order));
        $this->expect403(fn () => app(OrderController::class)->void(Request::create('/', 'POST', ['voided_reason' => 'x']), $order));
        $this->expect403(fn () => app(OrderController::class)->discount(Request::create('/', 'POST', ['discount' => 1]), $order));
    }

    public function test_company_manager_cannot_open_order_of_other_company(): void
    {
        $this->login(4, ['branch_id' => 1, 'company_id' => 5]);

        $this->expect403(fn () => app(OrderController::class)->view(new Order(['branch_id' => 1, 'company_id' => 6])));
    }

    public function test_staff_cannot_open_someone_elses_shift(): void
    {
        $this->login(5, ['branch_id' => 1, 'company_id' => 5]);
        $shift = new ShiftClosing(['branch_id' => 1, 'company_id' => 5, 'user_id' => 101]);

        $this->expect403(fn () => app(ShiftClosingController::class)->view($shift));
    }

    public function test_company_manager_cannot_touch_other_company_batches(): void
    {
        $this->login(4, ['branch_id' => 1, 'company_id' => 5]);
        $batch = new Batch(['branch_id' => 1, 'company_id' => 6]);
        $item  = new BatchItem(['branch_id' => 1, 'company_id' => 6]);
        $c = app(BatchController::class);

        $this->expect403(fn () => $c->edit($batch));
        $this->expect403(fn () => $c->update(Request::create('/', 'POST'), $batch));
        $this->expect403(fn () => $c->complete($batch));
        $this->expect403(fn () => $c->destroy($batch));
        $this->expect403(fn () => $c->addBatchItem(Request::create('/', 'POST'), $batch));
        $this->expect403(fn () => $c->downloadTemplate($batch));
        $this->expect403(fn () => $c->import(Request::create('/', 'POST'), $batch));
        $this->expect403(fn () => $c->destroyItem($item));
        $this->expect403(fn () => $c->updateBatchItem(Request::create('/', 'POST'), $item));
    }

    public function test_branch_manager_cannot_touch_other_branch_products(): void
    {
        $this->login(3, ['branch_id' => 1]);
        $product = new Product(['branch_id' => 2, 'company_id' => 7]);
        $c = app(ProductController::class);

        $this->expect403(fn () => $c->edit($product));
        $this->expect403(fn () => $c->update(Request::create('/', 'POST'), $product));
        $this->expect403(fn () => $c->destroy($product));
        $this->expect403(fn () => $c->viewlog($product));
        $this->expect403(fn () => $c->convert($product));
    }

    public function test_company_manager_cannot_touch_other_company_categories(): void
    {
        $this->login(4, ['branch_id' => 1, 'company_id' => 5]);
        $category = new Category(['branch_id' => 1, 'company_id' => 6]);
        $c = app(CategoryController::class);

        $this->expect403(fn () => $c->edit($category));
        $this->expect403(fn () => $c->update(Request::create('/', 'POST'), $category));
        $this->expect403(fn () => $c->destroy($category));
    }

    public function test_branch_manager_cannot_touch_company_in_other_branch(): void
    {
        $this->login(3, ['branch_id' => 1]);
        $company = new Company(['branch_id' => 2]);
        $company->id = 7;
        $c = app(CompanyController::class);

        $this->expect403(fn () => $c->edit($company));
        $this->expect403(fn () => $c->update(Request::create('/', 'POST'), $company));
        $this->expect403(fn () => $c->destroy($company));
    }

    private function expect403(callable $fn): void
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
