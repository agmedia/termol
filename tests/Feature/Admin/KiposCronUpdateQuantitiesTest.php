<?php

namespace Tests\Feature\Admin;

use App\Models\Catalog\Product\Product;
use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KiposCronUpdateQuantitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_kipos_quantity_cron_endpoint_requires_configured_token(): void
    {
        config(['services.kipos.cron_token' => null]);

        $this->getJson('/cron/kipos/update-quantities?token=anything')
            ->assertNotFound();
    }

    public function test_kipos_quantity_cron_endpoint_rejects_invalid_token(): void
    {
        config(['services.kipos.cron_token' => 'valid-token']);

        $this->getJson('/cron/kipos/update-quantities?token=wrong-token')
            ->assertForbidden();
    }

    public function test_kipos_quantity_cron_endpoint_runs_quantity_update(): void
    {
        config(['services.kipos.cron_token' => 'valid-token']);

        $admin = User::factory()->create();
        $product = Product::query()->create([
            'code' => 'W7030',
            'sku' => 'W7030',
            'is_active' => true,
            'base_price' => 10,
            'stock_qty' => 0,
            'payload' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        app(SystemSettingsService::class)->putMany([
            'catalog_use_kipos_api' => true,
            'kipos_api_enabled' => true,
            'kipos_api_base_uri' => 'http://balidd.dyndns.org:8080/kipos.web.api/?route=',
            'kipos_api_query_suffix' => 'webshop=2',
            'kipos_api_timeout_seconds' => 30,
            'kipos_api_verify_tls' => true,
        ]);

        Http::fake([
            '*getitemsextended*' => Http::response([
                [
                    'IDROBA' => 'W7030',
                    'IDODJEL' => 'W7030',
                    'ZALIHAK' => 8,
                ],
            ], 200),
            '*getitems*' => Http::response([
                [
                    'IDROBA' => 'W7030',
                    'IDODJEL' => 'W7030',
                    'ZALIHAK' => 0,
                ],
            ], 200),
        ]);

        $this->getJson('/cron/kipos/update-quantities?token=valid-token')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('stats.updated_products', 1);

        $this->assertSame(8, (int) $product->fresh()?->stock_qty);
    }
}
