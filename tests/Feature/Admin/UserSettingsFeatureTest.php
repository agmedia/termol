<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Settings\User\UserFeatures;
use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class UserSettingsFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_user_settings_page(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->get('/admin/settings/user')
            ->assertOk()
            ->assertSee(__('User Settings'));
    }

    public function test_editor_cannot_open_user_settings_page(): void
    {
        $editor = $this->makeUserWithRole('editor');

        $this->actingAs($editor)
            ->get('/admin/settings/user')
            ->assertForbidden();
    }

    public function test_admin_can_save_user_tracking_and_loyalty_switches(): void
    {
        $admin = $this->makeUserWithRole('admin');

        Livewire::actingAs($admin)
            ->test(UserFeatures::class)
            ->set('form.user_tracking_enabled', false)
            ->set('form.user_loyalty_enabled', true)
            ->set('form.loyalty_points_per_currency', 2.5)
            ->set('form.loyalty_min_order_total', 50)
            ->set('form.loyalty_reversal_mode', 'separate_entry')
            ->call('save')
            ->assertHasNoErrors();

        $settings = app(SystemSettingsService::class);

        $this->assertFalse((bool) $settings->get('user_tracking_enabled', true));
        $this->assertTrue((bool) $settings->get('user_loyalty_enabled', false));
        $this->assertSame(2.5, (float) $settings->get('loyalty_points_per_currency', 0));
        $this->assertSame(50.0, (float) $settings->get('loyalty_min_order_total', 0));
        $this->assertSame('separate_entry', (string) $settings->get('loyalty_reversal_mode', ''));
    }

    private function makeUserWithRole(string $role): User
    {
        Bouncer::role()->firstOrCreate(['name' => 'superadmin']);
        Bouncer::role()->firstOrCreate(['name' => 'admin']);
        Bouncer::role()->firstOrCreate(['name' => 'editor']);
        Bouncer::role()->firstOrCreate(['name' => 'customer']);

        $user = User::factory()->create();
        Bouncer::assign($role)->to($user);

        return $user;
    }
}
