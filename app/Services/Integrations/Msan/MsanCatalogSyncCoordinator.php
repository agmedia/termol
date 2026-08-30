<?php

namespace App\Services\Integrations\Msan;

use App\Jobs\Integrations\Msan\SyncMsanCatalogJob;
use App\Jobs\Integrations\Msan\TestMsanConnectionJob;
use App\Jobs\Integrations\Msan\TestMsanFtpConnectionJob;
use App\Models\Integrations\Msan\MsanSyncRun;
use DomainException;
use Illuminate\Support\Facades\Cache;
use Throwable;

class MsanCatalogSyncCoordinator
{
    public function queueFullSync(?int $userId = null): MsanSyncRun
    {
        $lock = Cache::lock('integrations:msan:queue-run', 30);
        if (! $lock->get()) {
            throw new DomainException('Druga M SAN obrada upravo se pokreće.');
        }

        try {
            $this->assertNoActiveRun();
            $run = MsanSyncRun::query()->create([
                'kind' => MsanSyncRun::KIND_FULL,
                'status' => MsanSyncRun::STATUS_PENDING,
                'requested_by' => $userId,
                'progress' => 0,
            ]);

            try {
                SyncMsanCatalogJob::dispatch((int) $run->id)->onQueue('integrations');
            } catch (Throwable $exception) {
                $run->forceFill([
                    'status' => MsanSyncRun::STATUS_FAILED,
                    'error_message' => 'M SAN sinkronizaciju nije moguće staviti u red.',
                    'completed_at' => now(),
                ])->save();

                throw $exception;
            }

            return $run->refresh();
        } finally {
            $lock->release();
        }
    }

    public function queueConnectionTest(?int $userId = null): MsanSyncRun
    {
        $run = MsanSyncRun::query()->create([
            'kind' => 'connection_test',
            'status' => MsanSyncRun::STATUS_PENDING,
            'requested_by' => $userId,
            'progress' => 0,
        ]);

        try {
            TestMsanConnectionJob::dispatch((int) $run->id)->onQueue('integrations');
        } catch (Throwable $exception) {
            $this->markDispatchFailed($run, 'Provjeru M SAN veze nije moguće staviti u red.');
            throw $exception;
        }

        return $run->refresh();
    }

    public function queueFtpConnectionTest(?int $userId = null): MsanSyncRun
    {
        $run = MsanSyncRun::query()->create([
            'kind' => 'ftp_connection_test',
            'status' => MsanSyncRun::STATUS_PENDING,
            'requested_by' => $userId,
            'progress' => 0,
        ]);

        try {
            TestMsanFtpConnectionJob::dispatch((int) $run->id)->onQueue('integrations');
        } catch (Throwable $exception) {
            $this->markDispatchFailed($run, 'Provjeru M SAN FTPS veze nije moguće staviti u red.');
            throw $exception;
        }

        return $run->refresh();
    }

    private function assertNoActiveRun(): void
    {
        $active = MsanSyncRun::query()
            ->whereIn('kind', [MsanSyncRun::KIND_FULL, MsanSyncRun::KIND_IMPORT])
            ->whereIn('status', [MsanSyncRun::STATUS_PENDING, MsanSyncRun::STATUS_RUNNING])
            ->exists();

        if ($active) {
            throw new DomainException('Pričekajte da trenutačna M SAN sinkronizacija ili uvoz završi.');
        }
    }

    private function markDispatchFailed(MsanSyncRun $run, string $message): void
    {
        $run->forceFill([
            'status' => MsanSyncRun::STATUS_FAILED,
            'error_message' => $message,
            'completed_at' => now(),
        ])->save();
    }
}
