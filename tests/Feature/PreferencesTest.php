<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsPosData;
use Tests\TestCase;

/** Language (English / Malay / Chinese) and light / dark mode. */
class PreferencesTest extends TestCase
{
    use RefreshDatabase, BuildsPosData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    public function test_user_switches_language_and_it_is_saved_on_the_user(): void
    {
        $boss = $this->makeUser(1);

        $this->actingAs($boss)->get(route('home'))->assertSee('Item List');

        $this->actingAs($boss)->post(route('preferences.update'), ['locale' => 'ms'])->assertRedirect();
        $this->assertSame('ms', $boss->fresh()->locale);
        $this->actingAs($boss->fresh())->get(route('home'))->assertSee('Senarai Barang')->assertSee('lang="ms"', false);

        $this->actingAs($boss->fresh())->post(route('preferences.update'), ['locale' => 'zh_CN']);
        $this->actingAs($boss->fresh())->get(route('home'))->assertSee('商品列表');
    }

    public function test_user_switches_dark_mode(): void
    {
        $boss = $this->makeUser(1);
        $this->actingAs($boss)->get(route('home'))->assertSee('theme-default.css', false)->assertSee('light-style', false);

        $this->actingAs($boss)->post(route('preferences.update'), ['theme' => 'dark']);
        $this->assertSame('dark', $boss->fresh()->theme);
        $this->actingAs($boss->fresh())->get(route('home'))
            ->assertSee('theme-default-dark.css', false)->assertSee('dark-style', false);
    }

    public function test_login_page_can_switch_before_login(): void
    {
        $this->post(route('preferences.update'), ['locale' => 'zh_CN', 'theme' => 'dark'])->assertRedirect();
        $this->get(route('login'))->assertSee('登录')->assertSee('core-dark.css', false);
    }

    public function test_unknown_values_are_refused(): void
    {
        $boss = $this->makeUser(1);
        $this->actingAs($boss)->post(route('preferences.update'), ['locale' => 'fr', 'theme' => 'pink'])->assertSessionHasErrors(['locale', 'theme']);
        $this->assertNull($boss->fresh()->locale);
    }

    public function test_counter_follows_the_language(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-08 12:00'));
        $company = $this->makeCompany($this->makeBranch(), 'A', 'shopa.test');
        $staff = $this->makeUser(5, $company, ['shift' => 'AM', 'locale' => 'ms']);

        $this->actingAs($staff)->get('http://shopa.test' . route('counter', [], false))
            ->assertOk()->assertSee('Pilih Barang')->assertSee('Troli');
    }
}
