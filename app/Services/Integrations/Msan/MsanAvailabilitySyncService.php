<?php

namespace App\Services\Integrations\Msan;

use App\Models\Catalog\Product\Product;
use App\Models\Import\CatalogSourceMapping;
use App\Models\Integrations\Msan\MsanProduct;
use App\Models\Integrations\Msan\MsanSyncRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class MsanAvailabilitySyncService
{
    private const UPSERT_SIZE = 400;

    private const MINIMUM_COVERAGE_RATIO = 0.5;

    public function __construct(
        private readonly MsanClient $client,
        private readonly MsanXmlStreamReader $xml,
        private readonly MsanSettingsService $settings,
    ) {}

    public function sync(MsanSyncRun $run): MsanSyncRun
    {
        $run->forceFill([
            'status' => MsanSyncRun::STATUS_RUNNING,
            'started_at' => $run->started_at ?: now(),
            'completed_at' => null,
            'error_message' => null,
            'progress' => 5,
        ])->save();

        $directory = 'integrations/msan/availability/'.$run->id.'-'.bin2hex(random_bytes(5));
        $path = Storage::disk('local')->path($directory.'/availability.xml');
        Storage::disk('local')->makeDirectory($directory);

        try {
            // This endpoint is the small availability feed. A scheduled refresh
            // must never fall back to the full catalog download.
            $this->client->downloadDataset('availability', $path);
            $run->forceFill(['progress' => 35])->save();

            $activeProductCount = MsanProduct::query()->where('is_stale', false)->count();
            if ($activeProductCount === 0) {
                throw new RuntimeException('M SAN katalog još nije dohvaćen.');
            }

            $result = DB::transaction(function () use ($path, $activeProductCount): array {
                MsanProduct::query()
                    ->where('is_stale', false)
                    ->update([
                        'availability_level' => null,
                        'availability_checksum' => null,
                        'availability_synced_at' => null,
                    ]);

                $matched = $this->syncStagingAvailability($path);
                $minimumCoverage = max(1, (int) ceil($activeProductCount * self::MINIMUM_COVERAGE_RATIO));
                if ($matched < $minimumCoverage) {
                    throw new RuntimeException(sprintf(
                        'M SAN dostupnost pokriva premalo artikala (%d/%d); prethodne zalihe su sačuvane.',
                        $matched,
                        $activeProductCount,
                    ));
                }

                return ['matched' => $matched] + $this->refreshOwnedLocalStock();
            }, 3);

            $run->forceFill([
                'status' => MsanSyncRun::STATUS_COMPLETED,
                'progress' => 100,
                'total_count' => $activeProductCount,
                'processed_count' => $result['matched'],
                'succeeded_count' => $result['matched'],
                'skipped_count' => max(0, $activeProductCount - $result['matched']),
                'summary' => [
                    'dataset' => 'availability',
                    'staging_products' => $activeProductCount,
                    'availability_rows_matched' => $result['matched'],
                    'local_products_eligible' => $result['eligible'],
                    'local_stock_updated' => $result['updated'],
                    'local_stock_unchanged' => $result['unchanged'],
                    'local_products_not_msan_owned' => $result['not_owned'],
                ],
                'completed_at' => now(),
            ])->save();
        } catch (Throwable $exception) {
            $summary = is_array($run->summary) ? $run->summary : [];
            $summary['last_attempt_failed_at'] = now()->toIso8601String();
            $run->forceFill([
                // The queue job owns retry and terminal failure state.
                'status' => MsanSyncRun::STATUS_RUNNING,
                'error_message' => $this->sanitizeError($exception->getMessage()),
                'summary' => $summary,
                'completed_at' => null,
            ])->save();

            throw $exception;
        } finally {
            Storage::disk('local')->deleteDirectory($directory);
        }

        return $run->refresh();
    }

    private function syncStagingAvailability(string $path): int
    {
        $buffer = [];
        $seenCodes = [];
        $matched = 0;

        foreach ($this->xml->rows($path) as $row) {
            $code = trim((string) ($row['ProductCode'] ?? ''));
            if ($code === '' || mb_strlen($code) > 191 || isset($seenCodes[$code])) {
                continue;
            }

            $seenCodes[$code] = true;
            $level = $this->availabilityLevel($row['ProductAvailability'] ?? null);
            $availabilityData = ['availability_level' => $level];
            $buffer[$code] = [
                'availability_level' => $level,
                'availability_checksum' => hash(
                    'sha256',
                    json_encode($availabilityData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ),
                'availability_synced_at' => now(),
            ];

            if (count($buffer) >= self::UPSERT_SIZE) {
                $matched += $this->updateProductsByCode($buffer);
                $buffer = [];
            }
        }

        return $matched + $this->updateProductsByCode($buffer);
    }

    /**
     * @param  array<string, array<string, mixed>>  $updates
     */
    private function updateProductsByCode(array $updates): int
    {
        if ($updates === []) {
            return 0;
        }

        $products = MsanProduct::query()
            ->where('is_stale', false)
            ->whereIn('external_code', array_keys($updates))
            ->get(['id', 'external_code'])
            ->keyBy('external_code');
        $now = now();
        $rows = [];

        foreach ($updates as $code => $data) {
            $product = $products->get($code);
            if (! $product) {
                continue;
            }

            $rows[] = [
                'id' => (int) $product->id,
                'external_code' => (string) $product->external_code,
                'updated_at' => $now,
            ] + $data;
        }

        if ($rows !== []) {
            DB::table('msan_products')->upsert(
                $rows,
                ['id'],
                ['availability_level', 'availability_checksum', 'availability_synced_at', 'updated_at'],
            );
        }

        return count($rows);
    }

    /**
     * @return array{eligible:int,updated:int,unchanged:int,not_owned:int}
     */
    private function refreshOwnedLocalStock(): array
    {
        $counts = ['eligible' => 0, 'updated' => 0, 'unchanged' => 0, 'not_owned' => 0];

        MsanProduct::query()
            ->where('is_stale', false)
            ->whereNotNull('local_product_id')
            ->select(['id', 'external_code', 'local_product_id', 'availability_level'])
            ->chunkById(self::UPSERT_SIZE, function ($sources) use (&$counts): void {
                $localIds = $sources
                    ->pluck('local_product_id')
                    ->filter()
                    ->map(static fn ($id): int => (int) $id)
                    ->values()
                    ->all();
                if ($localIds === []) {
                    return;
                }

                // Lock local products while applying the supplier limit so a
                // concurrent administrator write cannot be silently overwritten.
                $products = Product::query()
                    ->whereIn('id', $localIds)
                    ->lockForUpdate()
                    ->get(['id', 'stock_qty', 'payload'])
                    ->keyBy('id');
                $catalogOwnerIds = CatalogSourceMapping::query()
                    ->where('entity_type', CatalogSourceMapping::ENTITY_PRODUCT)
                    ->whereIn('local_id', $localIds)
                    ->pluck('local_id')
                    ->map(static fn ($id): int => (int) $id)
                    ->flip();
                $updatesByQuantity = [];

                foreach ($sources as $source) {
                    $localId = (int) $source->local_product_id;
                    /** @var Product|null $product */
                    $product = $products->get($localId);
                    if (! $product || ! $this->isMsanOwned($product, (string) $source->external_code, $catalogOwnerIds->has($localId))) {
                        $counts['not_owned']++;

                        continue;
                    }

                    $counts['eligible']++;
                    $quantity = $this->settings->stockLevelQuantity(
                        max(0, min(4, (int) ($source->availability_level ?? 0))),
                    );
                    if ((int) $product->stock_qty === $quantity) {
                        $counts['unchanged']++;

                        continue;
                    }

                    $updatesByQuantity[$quantity][] = $localId;
                }

                foreach ($updatesByQuantity as $quantity => $ids) {
                    $counts['updated'] += Product::query()
                        ->whereIn('id', $ids)
                        ->update([
                            'stock_qty' => (int) $quantity,
                            'updated_at' => now(),
                        ]);
                }
            });

        return $counts;
    }

    private function isMsanOwned(Product $product, string $externalCode, bool $hasCatalogOwner): bool
    {
        $payload = is_array($product->payload) ? $product->payload : [];

        return data_get($payload, 'catalog_origin') === 'msan'
            && ! $hasCatalogOwner
            && empty($payload['import_sources'] ?? [])
            && (string) data_get($payload, 'supplier_sources.msan.external_code') === $externalCode;
    }

    private function availabilityLevel(mixed $value): ?int
    {
        return is_numeric($value) ? max(0, min(4, (int) $value)) : null;
    }

    private function sanitizeError(string $message): string
    {
        $message = preg_replace('/(password|passphrase|pin)\s*[=:]\s*\S+/iu', '$1=[skriveno]', $message) ?? $message;

        return mb_substr(trim($message), 0, 1500);
    }
}
