<?php

namespace Database\Seeders;

use App\Services\Settings\SystemSettingsService;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array<string, mixed> $defaults */
        $defaults = array_merge(
            config('admin_ui.pagination', []),
            config('catalog_features.flags', []),
            config('user_features.flags', []),
            [
                'loyalty_points_per_currency' => (float) config('user_features.loyalty.points_per_currency', 1.0),
                'loyalty_min_order_total' => (float) config('user_features.loyalty.min_order_total', 0.0),
                'loyalty_reversal_mode' => (string) config('user_features.loyalty.reversal_mode', 'zero_out'),
            ]
        );

        app(SystemSettingsService::class)->putMany($defaults);
    }
}
