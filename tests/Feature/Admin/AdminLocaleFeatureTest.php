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

    public function test_admin_locale_persists_across_navigation_without_query_param(): void
    {
        $admin = $this->makeAdminUser();
        $this->seedLanguages();

        $this->actingAs($admin)
            ->get('/admin/dashboard?admin_locale=en')
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSessionHas('admin_locale', 'en');

        $this->get('/admin/orders')
            ->assertOk()
            ->assertSee('lang="en"', false)
            ->assertSessionHas('admin_locale', 'en');
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
