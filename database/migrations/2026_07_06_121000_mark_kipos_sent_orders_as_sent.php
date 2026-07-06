<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sentStatus = DB::table('order_statuses')
            ->where('code', 'sent')
            ->where('is_active', true)
            ->first(['id', 'is_paid']);

        if (! $sentStatus) {
            return;
        }

        $now = now();
        $sentStatusId = (int) $sentStatus->id;
        $sentStatusIsPaid = (bool) $sentStatus->is_paid;

        DB::table('orders')
            ->select(['id', 'status_id', 'payload', 'paid_at'])
            ->whereNotNull('payload')
            ->orderBy('id')
            ->chunkById(200, function ($orders) use ($now, $sentStatusId, $sentStatusIsPaid): void {
                foreach ($orders as $order) {
                    if ((int) $order->status_id === $sentStatusId) {
                        continue;
                    }

                    $payload = json_decode((string) $order->payload, true);
                    if (! is_array(data_get($payload, 'kipos_order.last_send'))) {
                        continue;
                    }

                    $updates = [
                        'status_id' => $sentStatusId,
                        'updated_at' => $now,
                    ];

                    if ($sentStatusIsPaid && empty($order->paid_at)) {
                        $updates['paid_at'] = $now;
                    }

                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update($updates);

                    DB::table('order_history')->insert([
                        'order_id' => (int) $order->id,
                        'from_status_id' => $order->status_id ? (int) $order->status_id : null,
                        'to_status_id' => $sentStatusId,
                        'changed_by' => null,
                        'comment' => 'Kipos ERP order was already sent; order status backfilled to sent.',
                        'payload' => json_encode([
                            'origin' => 'kipos_sent_backfill',
                            'status_changed' => true,
                        ], JSON_UNESCAPED_SLASHES),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Intentionally not reversible because original order statuses are not stored.
    }
};
