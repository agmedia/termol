<?php

namespace Tests\Feature\Api;

use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Category\CategoryTranslation;
use App\Models\Catalog\Product\Product;
use App\Models\Catalog\Product\ProductTranslation;
use App\Models\Content\Page\InfoPage;
use App\Models\Content\Page\InfoPageTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Asistent24CatalogExportFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_endpoint_returns_ok_when_connector_is_enabled(): void
    {
        config()->set('asistent24.enabled', true);

        $this->getJson('/api/v1/asistent24/ping')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('platform', 'agshop');
    }

    public function test_export_catalog_returns_signed_snapshot(): void
    {
        $this->configureConnector();
        $this->seedCatalogGraph();

        $response = $this->getJson('/api/v1/asistent24/export-catalog?'.$this->signedQueryString());

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('platform', 'agshop');
        $response->assertJsonPath('categories.0.code', 'CAT-100');
        $response->assertJsonPath('products.0.model', 'DSP-100');
        $response->assertJsonPath('products.0.sku', 'SKU-100');
        $response->assertJsonPath('blogs.0.code', 'PAGE-100');
    }

    public function test_export_custom_returns_generic_schema(): void
    {
        $this->configureConnector();
        $this->seedCatalogGraph();

        $response = $this->getJson('/api/v1/asistent24/export-custom?'.$this->signedQueryString());

        $response->assertOk();
        $response->assertJsonPath('schema', 'asistent24-custom-api/v1');
        $response->assertJsonPath('entities.categories.0.code', 'CAT-100');
        $response->assertJsonPath('entities.products.0.code', 'DSP-100');
        $response->assertJsonPath('entities.pages.0.code', 'PAGE-100');
    }

    public function test_export_rejects_invalid_signature(): void
    {
        $this->configureConnector();
        $this->seedCatalogGraph();

        $response = $this->getJson('/api/v1/asistent24/export-catalog?'.$this->signedQueryString([
            'signature' => str_repeat('a', 64),
        ]));

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Invalid signature.');
    }

    public function test_export_rejects_stale_timestamp(): void
    {
        $this->configureConnector();
        $this->seedCatalogGraph();

        $timestamp = time() - 3600;
        $storeKey = (string) config('asistent24.store_key');
        $secret = (string) config('asistent24.sync_secret');
        $signature = hash_hmac('sha256', $storeKey.'|'.$timestamp, $secret);

        $response = $this->getJson('/api/v1/asistent24/export-catalog?'.http_build_query([
            'store_key' => $storeKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ]));

        $response->assertStatus(401);
        $response->assertJsonPath('message', 'Timestamp outside allowed window.');
    }

    private function configureConnector(): void
    {
        config()->set('asistent24.enabled', true);
        config()->set('asistent24.store_key', 'pk_agshop_store_123');
        config()->set('asistent24.sync_secret', 'sk_agshop_sync_secret_123');
        config()->set('asistent24.allowed_skew_seconds', 300);
        config()->set('asistent24.include_inactive', false);
    }

    /**
     * @param  array<string,mixed>  $overrides
     */
    private function signedQueryString(array $overrides = []): string
    {
        $storeKey = (string) config('asistent24.store_key');
        $timestamp = isset($overrides['timestamp']) ? (int) $overrides['timestamp'] : time();
        $secret = (string) config('asistent24.sync_secret');
        $signature = isset($overrides['signature'])
            ? (string) $overrides['signature']
            : hash_hmac('sha256', $storeKey.'|'.$timestamp, $secret);

        $query = array_merge([
            'store_key' => $storeKey,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'locale' => 'en',
        ], $overrides);

        return http_build_query($query);
    }

    private function seedCatalogGraph(): void
    {
        $category = Category::query()->create([
            'scope' => Category::SCOPE_CATALOG,
            'code' => 'CAT-100',
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 1,
            'payload' => null,
            'created_by' => null,
            'updated_by' => null,
            'parent_id' => null,
        ]);

        CategoryTranslation::query()->create([
            'category_id' => $category->id,
            'scope' => Category::SCOPE_CATALOG,
            'locale' => 'en',
            'name' => 'Category 100',
            'slug' => 'category-100',
            'description' => 'Category 100 description',
            'meta_title' => null,
            'meta_description' => null,
            'payload' => null,
        ]);

        $product = Product::query()->create([
            'code' => 'DSP-100',
            'sku' => 'SKU-100',
            'is_active' => true,
            'manufacturer_id' => null,
            'tax_rate_id' => null,
            'base_price' => 99.99,
            'stock_qty' => 25,
            'payload' => null,
            'created_by' => null,
            'updated_by' => null,
        ]);

        ProductTranslation::query()->create([
            'product_id' => $product->id,
            'locale' => 'en',
            'name' => 'Demo Product 100',
            'slug' => 'demo-product-100',
            'excerpt' => 'Demo excerpt',
            'description' => 'Demo description',
            'meta_title' => null,
            'meta_description' => null,
            'payload' => null,
        ]);

        $product->categories()->attach($category->id, [
            'sort_order' => 1,
            'is_primary' => true,
        ]);

        $page = InfoPage::query()->create([
            'code' => 'PAGE-100',
            'layout' => 'default',
            'is_active' => true,
            'show_in_footer' => true,
            'published_at' => now(),
            'sort_order' => 1,
            'payload' => null,
            'created_by' => null,
            'updated_by' => null,
        ]);

        InfoPageTranslation::query()->create([
            'page_id' => $page->id,
            'locale' => 'en',
            'title' => 'Returns Policy',
            'slug' => 'returns-policy',
            'excerpt' => null,
            'body_html' => '<p>Returns allowed in 14 days.</p>',
            'meta_title' => null,
            'meta_description' => null,
            'payload' => null,
        ]);
    }
}

