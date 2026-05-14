<?php

namespace Tests\Feature\Admin;

use App\Jobs\RunKiposSyncActionJob;
use App\Livewire\Admin\Settings\Api\KiposSyncManager;
use App\Models\Catalog\Product\Product;
use App\Models\Integrations\KiposSyncRun;
use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_admin_runs_kipos_price_update_immediately(): void
    {
        Queue::fake();

        $admin = $this->makeUserWithRole('superadmin');
        $product = $this->createKiposProduct($admin, 'W7030');

        $this->enableKiposPriceSync();
        $this->fakeKiposPrice('W7030', '42,50');

        Livewire::actingAs($admin)
            ->test(KiposSyncManager::class)
            ->call('runAction', 'update_prices')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $run = KiposSyncRun::query()->where('action_key', 'update_prices')->latest('id')->first();

        $this->assertNotNull($run);
        $this->assertSame('success', $run?->status);
        $this->assertEqualsWithDelta(42.50, (float) $product->fresh()?->base_price, 0.001);
        Queue::assertNothingPushed();
    }

    public function test_admin_executes_existing_queued_price_update_immediately(): void
    {
        Queue::fake();

        $admin = $this->makeUserWithRole('superadmin');
        $product = $this->createKiposProduct($admin, 'W7030');

        $this->enableKiposPriceSync();
        $this->fakeKiposPrice('W7030', '43,50');

        $run = KiposSyncRun::query()->create([
            'action_key' => 'update_prices',
            'action_label' => 'Update Prices',
            'status' => 'queued',
            'summary' => 'Queued from admin. Waiting for background worker.',
            'initiated_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(KiposSyncManager::class)
            ->call('runAction', 'update_prices')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $run->refresh();

        $this->assertSame('success', $run->status);
        $this->assertSame(1, KiposSyncRun::query()->where('action_key', 'update_prices')->count());
        $this->assertEqualsWithDelta(43.50, (float) $product->fresh()?->base_price, 0.001);
        Queue::assertNothingPushed();
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

    public function test_stale_started_run_is_failed_before_retry_is_queued(): void
    {
        Queue::fake();

        $admin = $this->makeUserWithRole('superadmin');

        $staleRun = KiposSyncRun::query()->create([
            'action_key' => 'update_images',
            'action_label' => 'Update Images',
            'status' => 'started',
            'summary' => 'Execution started.',
            'started_at' => now()->subMinutes(46),
            'initiated_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(KiposSyncManager::class)
            ->call('runAction', 'update_images')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $staleRun->refresh();
        $replacementRun = KiposSyncRun::query()->where('action_key', 'update_images')->latest('id')->first();

        $this->assertSame('failed', $staleRun->status);
        $this->assertSame(
            'Execution marked as failed because the previous run became stale.',
            $staleRun->summary
        );
        $this->assertNotNull($replacementRun);
        $this->assertNotSame($staleRun->id, $replacementRun?->id);
        $this->assertSame('queued', $replacementRun?->status);
        Queue::assertPushedOn(config('queue.kipos_queue', 'kipos'), RunKiposSyncActionJob::class);
        Queue::assertPushed(RunKiposSyncActionJob::class, 1);
    }

    public function test_component_polls_only_while_kipos_run_is_active(): void
    {
        $admin = $this->makeUserWithRole('superadmin');

        Livewire::actingAs($admin)
            ->test(KiposSyncManager::class)
            ->assertDontSeeHtml('wire:poll.5s');

        KiposSyncRun::query()->create([
            'action_key' => 'update_images',
            'action_label' => 'Update Images',
            'status' => 'queued',
            'summary' => 'Queued from admin. Waiting for background worker.',
            'initiated_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(KiposSyncManager::class)
            ->assertSeeHtml('wire:poll.5s');
    }

    public function test_component_marks_stale_started_runs_failed_and_stops_polling(): void
    {
        $admin = $this->makeUserWithRole('superadmin');

        $staleRun = KiposSyncRun::query()->create([
            'action_key' => 'update_images',
            'action_label' => 'Update Images',
            'status' => 'started',
            'summary' => 'Execution started.',
            'started_at' => now()->subMinutes(46),
            'initiated_by' => $admin->id,
        ]);

        Livewire::actingAs($admin)
            ->test(KiposSyncManager::class)
            ->assertDontSeeHtml('wire:poll.5s');

        $staleRun->refresh();

        $this->assertSame('failed', $staleRun->status);
        $this->assertSame(
            'Execution marked as failed because the previous run became stale.',
            $staleRun->summary
        );
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

    private function createKiposProduct(User $admin, string $code): Product
    {
        return Product::query()->create([
            'code' => $code,
            'sku' => $code,
            'is_active' => true,
            'base_price' => 10,
            'stock_qty' => 0,
            'payload' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    private function enableKiposPriceSync(): void
    {
        app(SystemSettingsService::class)->putMany([
            'catalog_use_kipos_api' => true,
            'kipos_api_enabled' => true,
            'kipos_api_base_uri' => 'http://balidd.dyndns.org:8080/kipos.web.api/?route=',
            'kipos_api_query_suffix' => 'webshop=2',
            'kipos_api_timeout_seconds' => 30,
            'kipos_api_verify_tls' => true,
            'kipos_sync_price_field' => 'CIJENA_MPC',
        ]);
    }

    private function fakeKiposPrice(string $code, string $price): void
    {
        Http::fake([
            '*getitemsextended*' => Http::response([], 200),
            '*getitems*' => Http::response([
                ['IDROBA' => $code, 'IDODJEL' => $code, 'CIJENA_MPC' => $price],
            ], 200),
        ]);
    }
}
