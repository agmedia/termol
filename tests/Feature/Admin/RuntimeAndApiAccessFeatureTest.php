<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class RuntimeAndApiAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_runtime_and_api_pages(): void
    {
        $admin = $this->makeUserWithRole('admin');

        $this->actingAs($admin)
            ->get('/admin/settings/system/runtime')
            ->assertOk()
            ->assertSee('Runtime Controls');

        $this->actingAs($admin)
            ->get('/admin/settings/api')
            ->assertOk()
            ->assertSee('API Settings');
    }

    public function test_editor_cannot_open_runtime_and_api_pages(): void
    {
        $editor = $this->makeUserWithRole('editor');

        $this->actingAs($editor)
            ->get('/admin/settings/system/runtime')
            ->assertForbidden();

        $this->actingAs($editor)
            ->get('/admin/settings/api')
            ->assertForbidden();
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

