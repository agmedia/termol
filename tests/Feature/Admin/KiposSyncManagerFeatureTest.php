<?php

namespace Tests\Feature\Admin;

use App\Jobs\RunKiposSyncActionJob;
use App\Livewire\Admin\Settings\Api\KiposSyncManager;
use App\Models\Integrations\KiposSyncRun;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Silber\Bouncer\BouncerFacade as Bouncer;
use Tests\TestCase;

class KiposSyncManagerFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_queues_kipos_sync_action_in_background(): void
    {
        Queue::fake();

        $admin = $this->makeUserWithRole('superadmin');

        Livewire::actingAs($admin)
            ->test(KiposSyncManager::class)
            ->call('runAction', 'update_images')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $run = KiposSyncRun::query()->where('action_key', 'update_images')->latest('id')->first();

        $this->assertNotNull($run);
        $this->assertSame('queued', $run?->status);
        Queue::assertPushedOn(config('queue.kipos_queue', 'kipos'), RunKiposSyncActionJob::class);
        Queue::assertPushed(RunKiposSyncActionJob::class, 1);
    }

    public function test_admin_cannot_queue_same_kipos_action_twice_while_it_is_active(): void
    {
        Queue::fake();

        $admin = $this->makeUserWithRole('superadmin');

        KiposSyncRun::query()->create([
            'action_key' => 'update_images',
            'action_label' => 'Update Images',
            'status' => 'queued',
            'summary' => 'Queued from admin. Waiting for background worker.',
            'initiated_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(KiposSyncManager::class)
            ->call('runAction', 'update_images')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->assertSame(1, KiposSyncRun::query()->where('action_key', 'update_images')->count());
        Queue::assertNothingPushed();
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
