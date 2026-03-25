<?php

namespace App\Jobs;

use App\Models\Integrations\KiposSyncRun;
use App\Services\Integrations\Kipos\KiposSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class RunKiposSyncActionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 1800;

    public bool $failOnTimeout = true;

    public function __construct(private readonly int $runId)
    {
        $this->onQueue((string) config('queue.kipos_queue', 'kipos'));
    }

    public function handle(KiposSyncService $syncService): void
    {
        $lockKey = 'kipos-sync-run.'.$this->runId.'.lock';

        if (! Cache::add($lockKey, 1, now()->addMinutes(45))) {
            return;
        }

        try {
            $run = KiposSyncRun::query()->find($this->runId);
            if (! $run) {
                return;
            }

            $syncService->executeQueuedRun($run);
        } finally {
            Cache::forget($lockKey);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $run = KiposSyncRun::query()->find($this->runId);
        if (! $run || $run->status === 'success') {
            return;
        }

        $run->fill([
            'status' => 'failed',
            'summary' => 'Execution failed.',
            'error_message' => $exception->getMessage(),
            'finished_at' => now(),
        ])->save();
    }
}
