<?php

namespace Tests\Feature\Admin;

use App\Models\Settings\Local\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class AdminLocaleFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_uses_the_default_locale_and_clears_a_legacy_selection(): void
    {
        $admin = $this->makeAdminUser();
        $this->seedLanguages();

        $this->actingAs($admin)
            ->withSession(['admin_locale' => 'en'])
            ->get('/admin/dashboard?admin_locale=en')
            ->assertOk()
            ->assertSee('lang="hr"', false)
            ->assertSessionMissing('admin_locale');

        $this->get('/admin/orders')
            ->assertOk()
            ->assertSee('lang="hr"', false)
            ->assertSessionMissing('admin_locale');
    }

    public function test_admin_header_does_not_render_locale_or_help_controls(): void
    {
        $admin = $this->makeAdminUser();

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('admin_locale=', false)
            ->assertDontSee('id="admin-help-open"', false)
            ->assertDontSee('class="admin-help-button"', false);
    }

    private function makeAdminUser(): User
    {
        $user = User::factory()->create();

        Bouncer::role()->firstOrCreate(['name' => 'admin']);
        Bouncer::assign('admin')->to($user);

        return $user;
    }

    private function seedLanguages(): void
    {
        Language::query()->create([
            'code' => 'hr',
            'locale' => 'hr_HR',
            'name' => 'Croatian',
            'native_name' => 'Hrvatski',
            'direction' => 'ltr',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Language::query()->create([
            'code' => 'en',
            'locale' => 'en_US',
            'name' => 'English',
            'native_name' => 'English',
            'direction' => 'ltr',
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);
    }
}
