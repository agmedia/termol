<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $row = DB::table('system_settings')
            ->where('key', 'kipos_sync_stock_warehouse_ids')
            ->first();

        $current = $row ? json_decode((string) $row->value, true) : null;

        if ($row && trim((string) $current) !== '') {
            return;
        }

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'kipos_sync_stock_warehouse_ids'],
            [
                'value' => json_encode('200', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => $row->created_at ?? now(),
                'updated_at' => now(),
            ]
        );

        Cache::forget('settings.system.map');
    }

    public function down(): void
    {
        DB::table('system_settings')
            ->where('key', 'kipos_sync_stock_warehouse_ids')
            ->where('value', json_encode('200', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->update([
                'value' => json_encode('', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);

        Cache::forget('settings.system.map');
    }
};
