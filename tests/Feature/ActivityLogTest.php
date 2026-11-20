<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    public function test_changes_are_logged_with_who_and_what(): void
    {
        $company = $this->makeCompany($this->makeBranch(), 'A');
        $boss = $this->makeUser(1);
        $this->actingAs($boss);

        $item = $this->makeProduct($company, 'Tiger Beer', 10);
        $created = ActivityLog::where('subject_type', 'Product')->where('action', 'created')->first();
        $this->assertSame('Tiger Beer', $created->subject_label);
        $this->assertSame($boss->id, $created->user_id);
        $this->assertSame($company->id, $created->company_id);

        $item->update(['selling_price' => 12]);
        $updated = ActivityLog::where('subject_type', 'Product')->where('action', 'updated')->first();
        $this->assertEquals([10, 12], array_map('floatval', $updated->changes['selling_price']));

        // a sale moving stock is routine: no line
        $item->update(['stock_quantity' => 5]);
        $this->assertSame(1, ActivityLog::where('subject_type', 'Product')->where('action', 'updated')->count());

        $item->delete();
        $this->assertSame(1, ActivityLog::where('subject_type', 'Product')->where('action', 'deleted')->count());
    }

    public function test_passwords_are_never_logged(): void
    {
        $this->actingAs($this->makeUser(1));
        $user = $this->makeUser(3, $this->makeCompany($this->makeBranch(), 'A'));
        $user->update(['password' => bcrypt('new-secret')]);

        foreach (ActivityLog::where('subject_type', 'User')->get() as $log) {
            $this->assertArrayNotHasKey('password', $log->changes ?? []);
        }
    }

    public function test_logins_are_logged(): void
    {
        $user = $this->makeUser(1);
        event(new \Illuminate\Auth\Events\Login('web', $user, false));
        $this->assertSame(1, ActivityLog::where('action', 'login')->where('user_id', $user->id)->count());
    }

    public function test_only_superadmin_sees_the_log(): void
    {
        $company = $this->makeCompany($this->makeBranch(), 'A');
        $boss = $this->makeUser(1);
        $this->actingAs($boss);
        $this->makeProduct($company, 'Logged Item');

        $this->get(route('activity_log.index'))->assertOk()->assertSee('Logged Item');
        foreach ([2, 3] as $role) {
            $this->actingAs($this->makeUser($role, $company))->get(route('activity_log.index'))->assertStatus(403);
        }
    }
}
