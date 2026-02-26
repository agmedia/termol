<?php

namespace App\Jobs;

use App\Support\Media\MediaProfileRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GenerateWebpConversionsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(private readonly int $userId)
    {
    }

    public function handle(): void
    {
        $cacheKey = $this->stateCacheKey();
        $lockKey = $cacheKey.'.lock';

        if (! Cache::add($lockKey, 1, now()->addMinute())) {
            return;
        }

        try {
            $state = Cache::get($cacheKey, []);
            if (! is_array($state) || ! ((bool) ($state['running'] ?? false))) {
                return;
            }

            $pendingIds = array_values(array_map('intval', (array) ($state['pending_ids'] ?? [])));
            $cursor = max(0, (int) ($state['cursor'] ?? 0));
            $batchIds = array_slice($pendingIds, $cursor, 20);

            if ($batchIds === []) {
                $state['running'] = false;
                $state['finished'] = true;
                $state['finished_at'] = now()->toDateTimeString();
                $state['last_ping_at'] = now()->toDateTimeString();
                unset($state['pending_ids']);
                Cache::put($cacheKey, $state, now()->addHours(6));
                Cache::forget('settings.store.webp_coverage');

                return;
            }

            $batch = Media::query()
                ->whereIn('id', $batchIds)
                ->get()
                ->keyBy(fn (Media $media): int => (int) $media->id);

            $queueConversionsByDefault = (bool) config('media-library.queue_conversions_by_default', true);
            config()->set('media-library.queue_conversions_by_default', false);

            try {
                foreach ($batchIds as $mediaId) {
                    /** @var Media|null $media */
                    $media = $batch->get((int) $mediaId);
                    if (! $media) {
                        $state['failed'] = (int) ($state['failed'] ?? 0) + 1;
                        $state['processed'] = (int) ($state['processed'] ?? 0) + 1;
                        $state['cursor'] = (int) ($state['cursor'] ?? 0) + 1;
                        continue;
                    }

                    $conversionNames = $this->webpConversionNamesForModel((string) $media->model_type, (string) $media->collection_name);

                    try {
                        if ($conversionNames !== []) {
                            app(FileManipulator::class)->createDerivedFiles($media, $conversionNames, true, false, false);
                        }
                    } catch (\Throwable) {
                        $state['failed'] = (int) ($state['failed'] ?? 0) + 1;
                    }

                    $state['processed'] = (int) ($state['processed'] ?? 0) + 1;
                    $state['last_id'] = (int) $media->id;
                    $state['cursor'] = (int) ($state['cursor'] ?? 0) + 1;
                }
            } finally {
                config()->set('media-library.queue_conversions_by_default', $queueConversionsByDefault);
            }

            $state['last_ping_at'] = now()->toDateTimeString();

            if ((int) ($state['processed'] ?? 0) >= (int) ($state['total'] ?? 0)) {
                $state['running'] = false;
                $state['finished'] = true;
                $state['finished_at'] = now()->toDateTimeString();
                unset($state['pending_ids']);
                Cache::forget('settings.store.webp_coverage');
            }

            Cache::put($cacheKey, $state, now()->addHours(6));

            if ((bool) ($state['running'] ?? false)) {
                self::dispatch($this->userId);
            }
        } finally {
            Cache::forget($lockKey);
        }
    }

    private function stateCacheKey(): string
    {
        return 'settings.store.webp_generation.'.$this->userId;
    }

    /**
     * @return array<int, string>
     */
    private function webpConversionNamesForModel(string $modelType, ?string $collectionName = null): array
    {
        $map = MediaProfileRegistry::conversionMapForModel($modelType);

        if ($collectionName !== null && $collectionName !== '') {
            $map = array_filter($map, static function (array $config) use ($collectionName): bool {
                $collections = array_values(array_filter((array) ($config['collections'] ?? [])));

                return in_array($collectionName, $collections, true);
            });
        }

        $conversionNames = array_keys($map);
        if ($conversionNames === []) {
            return [];
        }

        return array_map(static fn (string $name): string => $name.'_webp', $conversionNames);
    }
}

