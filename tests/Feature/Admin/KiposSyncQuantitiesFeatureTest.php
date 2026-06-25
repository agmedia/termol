<?php

namespace Tests\Feature\Admin;

use App\Models\Catalog\Option\Option;
use App\Models\Catalog\Option\OptionValue;
use App\Models\Catalog\Product\Product;
use App\Models\Catalog\Product\ProductOptionValue;
use App\Models\Catalog\Product\ProductTranslation;
use App\Models\User;
use App\Services\Integrations\Kipos\KiposSyncService;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KiposSyncQuantitiesFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_kipos_quantity_update_syncs_parent_stock_and_matching_size_rows(): void
    {
        $admin = User::factory()->create();
        $product = $this->createProduct($admin, 'W7030');

        $size = $this->createOption($admin, 'size', 'Size', 'size');
        $small = $this->createOptionValue($admin, $size, 's', 'S', 's', 1);
        $medium = $this->createOptionValue($admin, $size, 'm', 'M', 'm', 2);
        $large = $this->createOptionValue($admin, $size, 'l', 'L', 'l', 3);

        $product->options()->sync([
            $size->id => [
                'is_required' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->createProductOptionRow($admin, $product, $small, 'W7030.S', 99, 0);
        $this->createProductOptionRow($admin, $product, $medium, 'W7030.M', 99, 1);
        $this->createProductOptionRow($admin, $product, $large, 'W7030.L', 99, 2);

        $this->enableKiposSync([
            'kipos_sync_stock_warehouse_ids' => '100',
        ]);
        Cache::put('front:catalog:last-modified-ts', 123, now()->addMinutes(2));
        Cache::put('front:product:last-modified:'.$product->id, 123, now()->addMinutes(2));

        Http::fake([
            '*getZalihaK*' => Http::response([
                [
                    'IDROBA' => 'W7030.S',
                    'IDODJEL' => 'W7030',
                    'ZALIHAK' => 2,
                    'IDSKL' => '100',
                ],
                [
                    'IDROBA' => 'W7030.M',
                    'IDODJEL' => 'W7030',
                    'ZALIHAK' => 3,
                    'IDSKL' => '100',
                ],
                [
                    'IDROBA' => 'W7030.L',
                    'IDODJEL' => 'W7030',
                    'ZALIHAK' => 0,
                    'IDSKL' => '100',
                ],
            ], 200),
        ]);

        $run = app(KiposSyncService::class)->run('update_quantities', $admin->id);

        $fresh = $product->fresh()->load('optionValues');
        $rows = $fresh->optionValues->keyBy('sku');

        $this->assertSame('success', $run->status);
        $this->assertSame(5, (int) $fresh->stock_qty);
        $this->assertSame(2, (int) $rows->get('W7030.S')?->stock_qty);
        $this->assertSame(3, (int) $rows->get('W7030.M')?->stock_qty);
        $this->assertSame(0, (int) $rows->get('W7030.L')?->stock_qty);
        $this->assertSame(1, (int) (($run->stats ?? [])['updated_products'] ?? 0));
        $this->assertSame(3, (int) (($run->stats ?? [])['updated_variants'] ?? 0));
        $this->assertFalse(Cache::has('front:catalog:last-modified-ts'));
        $this->assertFalse(Cache::has('front:product:last-modified:'.$product->id));
        Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'getZalihaK')
            && str_contains((string) $request->url(), 'idskl=100'));
    }

    public function test_kipos_quantity_update_uses_extended_stock_when_no_warehouse_filter_is_set(): void
    {
        $admin = User::factory()->create();
        $product = $this->createProduct($admin, 'W7037');

        $size = $this->createOption($admin, 'size', 'Size', 'size');
        $fourXl = $this->createOptionValue($admin, $size, '4xl', '4XL', '4xl', 1);

        $product->options()->sync([
            $size->id => [
                'is_required' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->createProductOptionRow($admin, $product, $fourXl, 'W7037.4XL', 0, 0);

        $this->enableKiposSync([
            'kipos_sync_stock_warehouse_ids' => '',
        ]);

        Http::fake([
            '*getitemsextended*' => Http::response([
                [
                    'IDROBA' => 'W7037.4XL',
                    'IDODJEL' => 'W7037',
                    'ZALIHAK' => 20,
                    'IDVELICINA' => '4XL',
                ],
            ], 200),
            '*getitems*' => Http::response([
                [
                    'IDROBA' => 'W7037.4XL',
                    'IDODJEL' => 'W7037',
                    'ZALIHAK' => 0,
                    'IDVELICINA' => '4XL',
                ],
            ], 200),
            '*getZalihaK*' => Http::response([
                [
                    'IDROBA' => 'W7037.4XL',
                    'ZALIHAK' => 0,
                    'IDSKL' => '100',
                ],
            ], 200),
        ]);

        $run = app(KiposSyncService::class)->run('update_quantities', $admin->id);

        $fresh = $product->fresh()->load('optionValues');

        $this->assertSame('success', $run->status);
        $this->assertSame(20, (int) $fresh->stock_qty);
        $this->assertSame(20, (int) $fresh->optionValues->firstWhere('sku', 'W7037.4XL')?->stock_qty);
    }

    private function createProduct(User $admin, string $code): Product
    {
        $product = Product::query()->create([
            'code' => $code,
            'sku' => $code,
            'is_active' => true,
            'base_price' => 10,
            'stock_qty' => 0,
            'payload' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        ProductTranslation::query()->create([
            'product_id' => $product->id,
            'locale' => 'hr',
            'name' => 'Test '.$code,
            'slug' => 'test-'.strtolower($code),
            'excerpt' => null,
            'description' => null,
            'meta_title' => null,
            'meta_description' => null,
            'payload' => null,
        ]);

        return $product;
    }

    private function createOption(User $admin, string $code, string $name, string $slug): Option
    {
        $option = Option::query()->create([
            'code' => $code,
            'type' => Option::TYPE_SELECT,
            'is_active' => true,
            'sort_order' => 1,
            'payload' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $option->translations()->create([
            'locale' => 'hr',
            'name' => $name,
            'slug' => $slug,
            'description' => null,
            'payload' => null,
        ]);

        return $option;
    }

    private function createOptionValue(User $admin, Option $option, string $code, string $name, string $slug, int $sortOrder): OptionValue
    {
        $value = OptionValue::query()->create([
            'option_id' => $option->id,
            'code' => $code,
            'is_active' => true,
            'sort_order' => $sortOrder,
            'payload' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $value->translations()->create([
            'locale' => 'hr',
            'name' => $name,
            'slug' => $slug,
            'payload' => null,
        ]);

        return $value;
    }

    private function createProductOptionRow(User $admin, Product $product, OptionValue $value, string $sku, int $stockQty, int $sortOrder): void
    {
        ProductOptionValue::query()->create([
            'product_id' => $product->id,
            'option_value_id' => $value->id,
            'parent_option_value_id' => null,
            'mode' => 'single',
            'sku' => $sku,
            'stock_qty' => $stockQty,
            'price_override' => 0,
            'sort_order' => $sortOrder,
            'is_active' => true,
            'combination_hash' => hash('sha256', 's:'.$value->id),
            'payload' => null,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function enableKiposSync(array $overrides = []): void
    {
        app(SystemSettingsService::class)->putMany(array_merge([
            'catalog_use_kipos_api' => true,
            'kipos_api_enabled' => true,
            'kipos_api_base_uri' => 'http://balidd.dyndns.org:8080/kipos.web.api/?route=',
            'kipos_api_query_suffix' => 'webshop=2',
            'kipos_api_timeout_seconds' => 30,
            'kipos_api_verify_tls' => true,
        ], $overrides));
    }
}
