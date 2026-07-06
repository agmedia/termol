<?php

namespace App\Http\Controllers\Cron;

use App\Http\Controllers\Controller;
use App\Models\Integrations\KiposSyncRun;
use App\Services\Integrations\Kipos\KiposSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KiposCronController extends Controller
{
    public function updateQuantities(Request $request, KiposSyncService $syncService): JsonResponse
    {
        $this->authorizeCronRequest($request);

        @set_time_limit(0);

        try {
            $activeRun = $syncService->activeRun('update_quantities');

            if ($activeRun && $activeRun->status === 'started') {
                return response()->json([
                    'ok' => true,
                    'status' => 'already_running',
                    'run_id' => $activeRun->id,
                    'summary' => $activeRun->summary,
                ], 202);
            }

            $run = $activeRun && $activeRun->status === 'queued'
                ? $syncService->executeQueuedRun($activeRun)
                : $syncService->run('update_quantities');

            return response()->json([
                'ok' => $run->status === 'success',
                'status' => $run->status,
                'run_id' => $run->id,
                'summary' => $run->summary,
                'stats' => $run->stats,
                'finished_at' => optional($run->finished_at)->toIso8601String(),
            ], $run->status === 'success' ? 200 : 500);
        } catch (\Throwable $exception) {
            report($exception);

            $run = KiposSyncRun::query()
                ->where('action_key', 'update_quantities')
                ->latest('id')
                ->first();

            return response()->json([
                'ok' => false,
                'status' => $run?->status ?? 'failed',
                'run_id' => $run?->id,
                'summary' => $run?->summary ?? 'Kipos quantity update failed.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    public function updateOrderStatuses(Request $request, KiposSyncService $syncService): JsonResponse
    {
        $this->authorizeCronRequest($request);

        @set_time_limit(0);

        try {
            $activeRun = $syncService->activeRun('update_order_statuses');

            if ($activeRun && $activeRun->status === 'started') {
                return response()->json([
                    'ok' => true,
                    'status' => 'already_running',
                    'run_id' => $activeRun->id,
                    'summary' => $activeRun->summary,
                ], 202);
            }

            $run = $activeRun && $activeRun->status === 'queued'
                ? $syncService->executeQueuedRun($activeRun)
                : $syncService->run('update_order_statuses');

            return response()->json([
                'ok' => $run->status === 'success',
                'status' => $run->status,
                'run_id' => $run->id,
                'summary' => $run->summary,
                'stats' => $run->stats,
                'finished_at' => optional($run->finished_at)->toIso8601String(),
            ], $run->status === 'success' ? 200 : 500);
        } catch (\Throwable $exception) {
            report($exception);

            $run = KiposSyncRun::query()
                ->where('action_key', 'update_order_statuses')
                ->latest('id')
                ->first();

            return response()->json([
                'ok' => false,
                'status' => $run?->status ?? 'failed',
                'run_id' => $run?->id,
                'summary' => $run?->summary ?? 'Kipos order status update failed.',
                'error' => $exception->getMessage(),
            ], 500);
        }
    }

    private function authorizeCronRequest(Request $request): void
    {
        $expected = trim((string) config('services.kipos.cron_token', ''));

        abort_if($expected === '', 404);

        $provided = trim((string) ($request->query('token') ?: $request->header('X-Kipos-Cron-Token', '')));

        abort_unless($provided !== '' && hash_equals($expected, $provided), 403);
    }
}
