<?php

namespace App\Services\Integrations\Kipos\Concerns;

use App\Models\Sales\Order\Order;
use App\Models\Sales\Order\OrderHistory;
use App\Models\Settings\Local\OrderStatus;
use App\Services\Loyalty\LoyaltyService;
use Illuminate\Support\Str;

trait SyncsKiposOrderStatuses
{
    /**
     * @return array<string, mixed>
     */
    private function handleUpdateOrderStatuses(): array
    {
        $settings = $this->syncSettings();
        $rows = $this->kiposOrderStatusRows($settings);
        $statusMap = $this->kiposOrderStatusMap($settings);
        $actorUserId = $this->currentUserId();

        $updated = 0;
        $unchanged = 0;
        $unmatched = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            $remoteCode = $this->kiposRemoteOrderStatusCode($row);
            $remoteName = $this->kiposRemoteOrderStatusName($row, $remoteCode);

            if ($remoteCode === '' && $remoteName === '') {
                $skipped++;

                continue;
            }

            $order = $this->matchKiposOrderStatusRowToOrder($row, $settings);
            if (! $order) {
                $unmatched++;

                continue;
            }

            $targetStatus = $this->resolveKiposOrderStatus($remoteCode, $remoteName, $statusMap);
            $oldStatusId = (int) ($order->status_id ?? 0);
            $targetStatusId = (int) $targetStatus->id;
            $now = now();

            $payload = (array) ($order->payload ?? []);
            $payload['kipos_order_status'] = [
                'remote_code' => $remoteCode,
                'remote_name' => $remoteName,
                'row' => $row,
                'synced_at' => $now->toIso8601String(),
            ];
            $order->payload = $payload;

            if ($oldStatusId === $targetStatusId) {
                $order->updated_by = $actorUserId;
                $order->save();
                app(LoyaltyService::class)->syncOrderSettlement($order, $targetStatus, $actorUserId);
                $unchanged++;

                continue;
            }

            $order->status_id = $targetStatusId;
            $order->updated_by = $actorUserId;

            if ($targetStatus->is_paid && ! $order->paid_at) {
                $order->paid_at = $now;
            }

            $order->save();

            OrderHistory::query()->create([
                'order_id' => $order->id,
                'from_status_id' => $oldStatusId ?: null,
                'to_status_id' => $targetStatusId,
                'changed_by' => $actorUserId,
                'comment' => 'Kipos sync status update.',
                'payload' => [
                    'kipos' => [
                        'remote_code' => $remoteCode,
                        'remote_name' => $remoteName,
                    ],
                ],
            ]);

            app(LoyaltyService::class)->syncOrderSettlement($order, $targetStatus, $actorUserId);
            $updated++;
        }

        return [
            'summary' => sprintf('Order statuses: %d updated, %d unchanged, %d unmatched, %d skipped.', $updated, $unchanged, $unmatched, $skipped),
            'updated' => $updated,
            'unchanged' => $unchanged,
            'unmatched' => $unmatched,
            'skipped' => $skipped,
            'source_rows' => count($rows),
            'status_map' => $statusMap,
            'lookback_days' => max(1, (int) ($settings['kipos_order_status_lookback_days'] ?? 30)),
        ];
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return list<array<string, mixed>>
     */
    private function kiposOrderStatusRows(array $settings): array
    {
        $lookback = max(1, (int) ($settings['kipos_order_status_lookback_days'] ?? 30));
        $from = now()->subDays($lookback);
        $statusCodes = $this->csvValues((string) ($settings['kipos_order_status_codes'] ?? ''));

        $endpoint = trim((string) ($settings['kipos_order_status_endpoint'] ?? 'narudzba/statusi'));
        if ($endpoint === '') {
            $endpoint = 'narudzba/statusi';
        }

        $route = strtr($endpoint, [
            '{from}' => $from->format('Y-m-d'),
            '{from_dmy}' => $from->format('d.m.Y'),
            '{date_from}' => $from->format('Y-m-d'),
            '{date_from_dmy}' => $from->format('d.m.Y'),
            '{status_codes}' => implode(',', $statusCodes),
            '{status_codes_bracketed}' => '['.implode(',', $statusCodes).']',
        ]);

        $query = [];
        if (! str_contains($endpoint, '{from') && ! str_contains($endpoint, '{date_from')) {
            $query['from'] = $from->format('Y-m-d');
        }

        if ($statusCodes !== [] && ! str_contains($endpoint, '{status_codes')) {
            $query['statusi'] = implode(',', $statusCodes);
        }

        return $this->kipos->getRows($route, $query);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, string>
     */
    private function kiposOrderStatusMap(array $settings): array
    {
        $raw = trim((string) ($settings['kipos_order_status_map'] ?? ''));
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        $source = is_array($decoded) ? $decoded : [];

        if ($source === []) {
            foreach (preg_split('/\r\n|\r|\n/', $raw) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || (! str_contains($line, ':') && ! str_contains($line, '='))) {
                    continue;
                }

                [$remote, $local] = preg_split('/[:=]/', $line, 2) ?: [null, null];
                $source[(string) $remote] = (string) $local;
            }
        }

        $map = [];
        foreach ($source as $remote => $local) {
            $key = $this->normalizeKiposStatusKey((string) $remote);
            $value = trim((string) $local);

            if ($key !== '' && $value !== '') {
                $map[$key] = $value;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $settings
     */
    private function matchKiposOrderStatusRowToOrder(array $row, array $settings): ?Order
    {
        $candidates = $this->kiposOrderNumberCandidates($row, $settings);
        if ($candidates === []) {
            return null;
        }

        return Order::query()
            ->whereIn('order_number', $candidates)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $settings
     * @return array<int, string>
     */
    private function kiposOrderNumberCandidates(array $row, array $settings): array
    {
        $prefix = trim((string) ($settings['kipos_order_prefix'] ?? 'KHR'));
        $rawValues = [
            $this->rowStringValue($row, ['CMS_ID', 'cms_id', 'ORDER_CMS_ID', 'NARUDZBA_CMS_ID']),
            $this->rowStringValue($row, ['ORDER_NUMBER', 'order_number', 'BROJ_NARUDZBE', 'NARUDZBA', 'BROJ', 'WEB_ORDER_NUMBER']),
            $this->rowStringValue($row, ['IDNARUDZBA', 'ID_NARUDZBA', 'NALOG', 'NALOG_UID']),
        ];

        $candidates = [];
        foreach ($rawValues as $rawValue) {
            $value = trim($rawValue);
            if ($value === '') {
                continue;
            }

            $candidates[] = $value;

            if ($prefix !== '' && Str::startsWith(Str::upper($value), Str::upper($prefix))) {
                $withoutPrefix = trim(substr($value, strlen($prefix)));
                if ($withoutPrefix !== '') {
                    $candidates[] = $withoutPrefix;
                }
            }

            if (preg_match('/(\d+)$/', $value, $matches) === 1) {
                $number = ltrim($matches[1], '0');
                $candidates[] = $matches[1];

                if ($number !== '') {
                    $candidates[] = $number;
                }
            }
        }

        return collect($candidates)
            ->map(fn (string $candidate): string => trim($candidate))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, string>  $statusMap
     */
    private function resolveKiposOrderStatus(string $remoteCode, string $remoteName, array $statusMap): OrderStatus
    {
        foreach ([$remoteCode, $remoteName] as $candidate) {
            $key = $this->normalizeKiposStatusKey($candidate);
            if ($key === '' || ! isset($statusMap[$key])) {
                continue;
            }

            $mappedStatus = OrderStatus::query()
                ->where('code', $statusMap[$key])
                ->first();

            if ($mappedStatus) {
                return $mappedStatus;
            }
        }

        $source = $remoteCode !== '' ? $remoteCode : $remoteName;
        $code = $this->statusCodeSlug($source);
        $name = $remoteName !== '' ? $remoteName : ('Kipos status '.$remoteCode);

        $status = OrderStatus::query()->firstOrNew(['code' => $code]);
        $settings = (array) ($status->settings ?? []);
        $settings['kipos'] = [
            'source_code' => $remoteCode,
            'source_name' => $remoteName,
        ];

        $status->fill([
            'name' => $status->exists ? $status->name : $name,
            'description' => $status->exists ? $status->description : 'Kipos status '.$source,
            'color' => $status->exists ? $status->color : 'slate',
            'is_default' => (bool) ($status->exists ? $status->is_default : false),
            'is_paid' => (bool) ($status->exists ? $status->is_paid : false),
            'is_cancelled' => (bool) ($status->exists ? $status->is_cancelled : false),
            'is_active' => true,
            'sort_order' => (int) ($status->exists ? $status->sort_order : 0),
            'settings' => $settings,
        ]);
        $status->save();

        return $status;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function kiposRemoteOrderStatusCode(array $row): string
    {
        return $this->rowStringValue($row, [
            'STATUS',
            'STATUS_CODE',
            'STATUS_SIFRA',
            'SIFRA_STATUSA',
            'IDSTATUS',
            'STATUS_ID',
            'ORDER_STATUS',
            'STATUS_NARUDZBE',
            'STANJE',
        ]);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function kiposRemoteOrderStatusName(array $row, string $fallback): string
    {
        $name = $this->rowStringValue($row, [
            'STATUS_NAZIV',
            'NAZIV_STATUSA',
            'NAZIV',
            'OPIS_STATUSA',
            'STATUS_NAME',
            'STANJE_NAZIV',
        ]);

        return $name !== '' ? $name : $fallback;
    }

    private function csvValues(string $raw): array
    {
        return collect(preg_split('/[,;\r\n]+/', $raw) ?: [])
            ->map(fn ($item): string => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<int, string>  $keys
     */
    private function rowStringValue(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            foreach ($row as $rowKey => $value) {
                if (strcasecmp((string) $rowKey, $key) === 0) {
                    return trim((string) $value);
                }
            }
        }

        return '';
    }

    private function normalizeKiposStatusKey(string $value): string
    {
        return trim(Str::slug($value, '-'), '-');
    }

    private function statusCodeSlug(string $value): string
    {
        $base = $this->normalizeKiposStatusKey($value);
        if ($base === '') {
            $base = substr(sha1($value), 0, 12);
        }

        $prefix = 'kipos-status-';
        $code = $prefix.$base;

        if (strlen($code) <= 60) {
            return $code;
        }

        $hash = substr(sha1($code), 0, 8);
        $maxBaseLength = max(1, 60 - strlen($prefix) - 9);

        return $prefix.substr($base, 0, $maxBaseLength).'-'.$hash;
    }
}
