<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class DashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_dashboard_with_panels(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee(__('Performance Overview'))
            ->assertSee(__('Order Pipeline'))
            ->assertSee(__('Sales Trend (:days Days)', ['days' => 7]))
            ->assertSee(__('Recent Orders'))
            ->assertSee(__('Catalog & Content Snapshot'));
    }

    public function test_dashboard_hides_loyalty_and_tracking_sections_when_disabled(): void
    {
        app(SystemSettingsService::class)->putMany([
            'user_loyalty_enabled' => false,
            'user_tracking_enabled' => false,
        ]);

        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee(__('Loyalty Net Points'))
            ->assertDontSee(__('Recent Loyalty Activity'))
            ->assertDontSee(__('Recent Tracking Events'));
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
