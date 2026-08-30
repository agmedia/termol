<?php

namespace Tests\Feature\Integrations;

use App\Jobs\Integrations\Msan\SyncMsanAvailabilityJob;
use App\Models\Catalog\Product\Product;
use App\Models\Integrations\Msan\MsanProduct;
use App\Models\Integrations\Msan\MsanSyncRun;
use App\Services\Integrations\Msan\MsanAvailabilitySyncService;
use App\Services\Integrations\Msan\MsanCatalogSyncCoordinator;
use App\Services\Integrations\Msan\MsanClient;
use App\Services\Integrations\Msan\MsanImportCoordinator;
use App\Services\Integrations\Msan\MsanSettingsService;
use App\Services\Integrations\Msan\MsanXmlStreamReader;
use DomainException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class MsanAvailabilitySyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_lightweight_sync_downloads_only_availability_and_updates_only_msan_owned_stock(): void
    {
        $this->configureAvailability();

        $ownedHigh = $this->sourceWithLocalProduct('MSAN-HIGH', 99, true);
        $erpOwned = $this->sourceWithLocalProduct('ERP-OWNED', 77, false);
        $ownedMissing = $this->sourceWithLocalProduct('MSAN-MISSING', 99, true);
        $unimported = MsanProduct::query()->create([
            'external_code' => 'NOT-IMPORTED',
            'availability_level' => 4,
            'last_seen_at' => now(),
            'is_stale' => false,
        ]);

        $run = MsanSyncRun::query()->create([
            'kind' => MsanSyncRun::KIND_AVAILABILITY,
            'status' => MsanSyncRun::STATUS_PENDING,
        ]);
        $client = $this->fixtureClient($this->xml([
            ['ProductCode' => 'MSAN-HIGH', 'ProductAvailability' => '4'],
            ['ProductCode' => 'ERP-OWNED', 'ProductAvailability' => '1'],
            ['ProductCode' => 'NOT-IMPORTED', 'ProductAvailability' => '2'],
        ]));

        $service = new MsanAvailabilitySyncService(
            $client,
            app(MsanXmlStreamReader::class),
            app(MsanSettingsService::class),
        );
        $service->sync($run);

        $this->assertSame(['availability'], $client->downloads);
        $this->assertSame(4, $ownedHigh->fresh()->availability_level);
        $this->assertSame(1, $erpOwned->fresh()->availability_level);
        $this->assertNull($ownedMissing->fresh()->availability_level);
        $this->assertSame(2, $unimported->fresh()->availability_level);
        $this->assertSame(10, $ownedHigh->localProduct()->firstOrFail()->stock_qty);
        $this->assertSame(77, $erpOwned->localProduct()->firstOrFail()->stock_qty);
        $this->assertSame(0, $ownedMissing->localProduct()->firstOrFail()->stock_qty);

        $run->refresh();
        $this->assertSame(MsanSyncRun::STATUS_COMPLETED, $run->status);
        $this->assertSame(4, $run->total_count);
        $this->assertSame(3, $run->processed_count);
        $this->assertSame(2, data_get($run->summary, 'local_products_eligible'));
        $this->assertSame(2, data_get($run->summary, 'local_stock_updated'));
        $this->assertSame(1, data_get($run->summary, 'local_products_not_msan_owned'));
    }

    public function test_incomplete_availability_feed_rolls_back_staging_and_local_stock(): void
    {
        $this->configureAvailability();
        $first = $this->sourceWithLocalProduct('ROLLBACK-1', 44, true, 3);
        $this->sourceWithLocalProduct('ROLLBACK-2', 55, true, 2);
        $this->sourceWithLocalProduct('ROLLBACK-3', 66, true, 1);

        $run = MsanSyncRun::query()->create([
            'kind' => MsanSyncRun::KIND_AVAILABILITY,
            'status' => MsanSyncRun::STATUS_PENDING,
        ]);
        $service = new MsanAvailabilitySyncService(
            $this->fixtureClient($this->xml([
                ['ProductCode' => 'ROLLBACK-1', 'ProductAvailability' => '0'],
            ])),
            app(MsanXmlStreamReader::class),
            app(MsanSettingsService::class),
        );

        try {
            $service->sync($run);
            $this->fail('Premali M SAN availability snapshot mora biti odbijen.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('pokriva premalo artikala', $exception->getMessage());
        }

        $this->assertSame(3, $first->fresh()->availability_level);
        $this->assertSame(44, $first->localProduct()->firstOrFail()->stock_qty);
        $this->assertSame(MsanSyncRun::STATUS_RUNNING, $run->fresh()->status);
        $this->assertNotNull($run->fresh()->error_message);
    }

    public function test_coordinator_queues_one_availability_job_and_excludes_full_sync_and_import(): void
    {
        Queue::fake();
        $this->configureAvailability();
        MsanProduct::query()->create([
            'external_code' => 'QUEUE-1',
            'last_seen_at' => now(),
            'is_stale' => false,
        ]);

        $coordinator = app(MsanCatalogSyncCoordinator::class);
        $run = $coordinator->queueAvailability();

        $this->assertNotNull($run);
        $this->assertSame(MsanSyncRun::KIND_AVAILABILITY, $run->kind);
        $this->assertNull($run->requested_by);
        Queue::assertPushed(SyncMsanAvailabilityJob::class, fn (SyncMsanAvailabilityJob $job): bool => $job->queue === 'integrations');

        $this->assertNull($coordinator->queueAvailability(scheduled: true));
        Queue::assertPushed(SyncMsanAvailabilityJob::class, 1);

        try {
            $coordinator->queueFullSync();
            $this->fail('Puni sync ne smije krenuti tijekom availability synca.');
        } catch (DomainException) {
            $this->assertTrue(true);
        }

        $this->expectException(DomainException::class);
        app(MsanImportCoordinator::class)->queueSelected();
    }

    public function test_scheduled_queue_is_a_quiet_noop_when_integration_is_disabled(): void
    {
        Queue::fake();
        MsanProduct::query()->create([
            'external_code' => 'DISABLED-1',
            'last_seen_at' => now(),
            'is_stale' => false,
        ]);

        $this->assertNull(app(MsanCatalogSyncCoordinator::class)->queueAvailability(scheduled: true));
        $this->assertSame(0, MsanSyncRun::query()->count());
        Queue::assertNothingPushed();
    }

    public function test_availability_refresh_is_scheduled_every_fifteen_minutes(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($scheduledEvent): bool => $scheduledEvent->description === 'msan-availability-sync');

        $this->assertNotNull($event);
        $this->assertSame('*/15 * * * *', $event->expression);
    }

    private function configureAvailability(): void
    {
        app(MsanSettingsService::class)->saveAdminValues([
            'msan_enabled' => true,
            'msan_stock_level_0' => 0,
            'msan_stock_level_1' => 1,
            'msan_stock_level_2' => 3,
            'msan_stock_level_3' => 5,
            'msan_stock_level_4' => 10,
        ]);
    }

    private function sourceWithLocalProduct(
        string $code,
        int $stock,
        bool $msanOwned,
        int $availability = 4,
    ): MsanProduct {
        $payload = [
            'catalog_origin' => $msanOwned ? 'msan' : 'erp',
            'supplier_sources' => [
                'msan' => ['external_code' => $code],
            ],
        ];
        $local = Product::query()->create([
            'code' => 'LOCAL-'.$code,
            'sku' => 'LOCAL-'.$code,
            'stock_qty' => $stock,
            'is_active' => true,
            'payload' => $payload,
        ]);

        return MsanProduct::query()->create([
            'external_code' => $code,
            'availability_level' => $availability,
            'local_product_id' => $local->id,
            'import_status' => MsanProduct::IMPORT_IMPORTED,
            'last_seen_at' => now(),
            'is_stale' => false,
        ]);
    }

    private function fixtureClient(string $availabilityXml): MsanClient
    {
        return new class($availabilityXml) extends MsanClient
        {
            /** @var list<string> */
            public array $downloads = [];

            public function __construct(private readonly string $availabilityXml) {}

            public function downloadDataset(string $dataset, string $destinationPath): void
            {
                $this->downloads[] = $dataset;
                if ($dataset !== 'availability') {
                    throw new RuntimeException('Availability sync ne smije dohvaćati '.$dataset.'.');
                }

                $directory = dirname($destinationPath);
                if (! is_dir($directory)) {
                    mkdir($directory, 0750, true);
                }
                file_put_contents($destinationPath, $this->availabilityXml);
            }
        };
    }

    /** @param list<array<string, scalar|null>> $rows */
    private function xml(array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><NewDataSet>';
        foreach ($rows as $row) {
            $xml .= '<Table>';
            foreach ($row as $field => $value) {
                $xml .= '<'.$field.'>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</'.$field.'>';
            }
            $xml .= '</Table>';
        }

        return $xml.'</NewDataSet>';
    }
}
