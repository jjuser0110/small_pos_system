<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Support\CompanyPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

/** Top bar picker: All companies, or one company for every back-office page. */
class CompanyPickTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    private Company $a;
    private Company $b;
    private Company $far;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        CompanyPick::flush();
        $branch = $this->makeBranch('Main');
        $this->a = $this->makeCompany($branch, 'AAA');
        $this->b = $this->makeCompany($branch, 'BBB');
        $this->far = $this->makeCompany($this->makeBranch('Far'), 'FAR');
        $this->makeProduct($this->a, 'Apple Juice');
        $this->makeProduct($this->b, 'Banana Milk');
        $this->makeProduct($this->far, 'Far Tea');
    }

    public function test_admin_sees_all_then_only_the_picked_company(): void
    {
        $boss = $this->makeUser(1);

        $this->actingAs($boss)->get(route('product.index'))
            ->assertSee('Apple Juice')->assertSee('Banana Milk')->assertSee('Far Tea')
            ->assertSee('All companies');

        $this->actingAs($boss)->from(route('product.index'))
            ->post(route('company_pick'), ['company_id' => $this->a->id])->assertRedirect(route('product.index'));

        $this->actingAs($boss)->get(route('product.index'))
            ->assertSee('Apple Juice')->assertDontSee('Banana Milk')->assertDontSee('Far Tea');
        $this->actingAs($boss)->get(route('counter'))
            ->assertSee('Apple Juice')->assertDontSee('Banana Milk');
        $this->actingAs($boss)->get(route('company.index'))
            ->assertViewHas('company', fn ($list) => $list->pluck('id')->all() === [$this->a->id]);

        // back to All
        $this->actingAs($boss)->post(route('company_pick'), ['company_id' => '']);
        $this->actingAs($boss)->get(route('product.index'))->assertSee('Banana Milk')->assertSee('Far Tea');
    }

    public function test_branch_manager_picks_only_own_branch_companies(): void
    {
        $manager = $this->makeUser(3, $this->a);

        $this->actingAs($manager)->post(route('company_pick'), ['company_id' => $this->far->id])->assertForbidden();

        $this->actingAs($manager)->post(route('company_pick'), ['company_id' => $this->b->id]);
        $this->actingAs($manager)->get(route('product.index'))
            ->assertSee('Banana Milk')->assertDontSee('Apple Juice')->assertDontSee('Far Tea');
    }

    public function test_staff_has_no_picker(): void
    {
        $staff = $this->makeUser(5, $this->a, ['shift' => 'AM']);
        $this->withoutMiddleware(\App\Http\Middleware\CheckStaffAccess::class);
        $this->actingAs($staff)->post(route('company_pick'), ['company_id' => $this->a->id])->assertForbidden();
    }
}
