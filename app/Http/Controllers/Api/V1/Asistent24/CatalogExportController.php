<?php

namespace App\Http\Controllers\Api\V1\Asistent24;

use App\Http\Controllers\Api\V1\Wholesale\BaseWholesaleController;
use App\Services\Integrations\Asistent24\CatalogExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogExportController extends BaseWholesaleController
{
    public function __construct(private readonly CatalogExportService $exportService) {}

    public function ping(): JsonResponse
    {
        if (! (bool) config('asistent24.enabled', false)) {
            return response()->json([
                'ok' => false,
                'message' => 'Asistent24 connector is disabled.',
            ], 403);
        }

        return response()->json([
            'ok' => true,
            'platform' => 'agshop',
            'connector' => 'asistent24',
            'time' => now()->toISOString(),
        ]);
    }

    public function exportCatalog(Request $request): JsonResponse
    {
        $validated = $this->validateSignedExportRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $changedSince = $this->resolveUpdatedSince($validated['changed_since'] ?? null);
        $includeInactive = $this->toBoolean(
            $validated['include_inactive'] ?? null,
            (bool) config('asistent24.include_inactive', false)
        );
        $locale = isset($validated['locale']) ? (string) $validated['locale'] : null;

        return response()->json(
            $this->exportService->buildOpenCartCompatibleSnapshot($changedSince, $includeInactive, $locale)
        );
    }

    public function exportCustom(Request $request): JsonResponse
    {
        $validated = $this->validateSignedExportRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $changedSince = $this->resolveUpdatedSince($validated['changed_since'] ?? null);
        $includeInactive = $this->toBoolean(
            $validated['include_inactive'] ?? null,
            (bool) config('asistent24.include_inactive', false)
        );
        $locale = isset($validated['locale']) ? (string) $validated['locale'] : null;

        return response()->json(
            $this->exportService->buildCustomApiSnapshot($changedSince, $includeInactive, $locale)
        );
    }

    /**
     * @return array<string,mixed>|JsonResponse
     */
    private function validateSignedExportRequest(Request $request): array|JsonResponse
    {
        if (! (bool) config('asistent24.enabled', false)) {
            return response()->json([
                'message' => 'Asistent24 connector is disabled.',
            ], 403);
        }

        $validated = $request->validate([
            'store_key' => ['required', 'string', 'max:255'],
            'timestamp' => ['required', 'integer'],
            'signature' => ['required', 'string', 'size:64'],
            'changed_since' => ['nullable', 'string', 'max:40'],
            'include_inactive' => ['nullable'],
            'locale' => ['nullable', 'string', 'max:12'],
        ]);

        $configuredStoreKey = trim((string) config('asistent24.store_key', ''));
        $syncSecret = trim((string) config('asistent24.sync_secret', ''));

        if ($configuredStoreKey === '' || $syncSecret === '') {
            return response()->json([
                'message' => 'Asistent24 connector is not configured (store_key/sync_secret).',
            ], 500);
        }

        $storeKey = (string) $validated['store_key'];
        $timestamp = (int) $validated['timestamp'];
        $signature = strtolower(trim((string) $validated['signature']));
        $allowedSkew = max(30, (int) config('asistent24.allowed_skew_seconds', 300));

        if (! hash_equals($configuredStoreKey, $storeKey)) {
            return response()->json([
                'message' => 'Invalid store_key.',
            ], 401);
        }

        if (abs(time() - $timestamp) > $allowedSkew) {
            return response()->json([
                'message' => 'Timestamp outside allowed window.',
            ], 401);
        }

        $expectedSignature = hash_hmac('sha256', $storeKey.'|'.$timestamp, $syncSecret);
        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'message' => 'Invalid signature.',
            ], 401);
        }

        return $validated;
    }
}

