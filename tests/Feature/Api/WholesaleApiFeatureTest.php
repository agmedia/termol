<?php

namespace Tests\Feature\Api;

use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Category\CategoryTranslation;
use App\Models\Catalog\Manufacturer\Manufacturer;
use App\Models\Catalog\Manufacturer\ManufacturerTranslation;
use App\Models\Catalog\Option\Option;
use App\Models\Catalog\Option\OptionValue;
use App\Models\Catalog\Product\Product;
use App\Models\Catalog\Product\ProductOptionValue;
use App\Models\Catalog\Product\ProductTranslation;
use App\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WholesaleApiFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_endpoint_requires_sanctum_token(): void
    {
        $this->getJson('/api/v1/wholesale/products')->assertUnauthorized();
    }

    public function test_products_endpoint_returns_wholesale_payload(): void
    {
        $user = User::factory()->create(['api_access_enabled' => true]);
        Sanctum::actingAs($user, ['products.read']);

        [$product] = $this->seedCatalogGraph();

        $response = $this->getJson('/api/v1/wholesale/products?locale=en&per_page=10');
        $response->assertOk();
        $response->assertJsonPath('data.0.id', $product->id);
        $response->assertJsonPath('data.0.code', 'DSP-100');
        $response->assertJsonPath('data.0.sku', 'SKU-100');
        $response->assertJsonPath('data.0.name', 'Demo Product 100');
        $response->assertJsonPath('data.0.manufacturer.code', 'MAN-1');
        $response->assertJsonPath('data.0.categories.0.code', 'CAT-1');
    }

    public function test_product_prices_and_quantities_endpoints_return_sku_rows(): void
    {
        $user = User::factory()->create(['api_access_enabled' => true]);
        Sanctum::actingAs($user, ['products.read']);

        [$product, $optionValue] = $this->seedCatalogGraph();

        ProductOptionValue::query()->create([
            'product_id' => $product->id,
            'option_value_id' => $optionValue->id,
            'parent_option_value_id' => null,
            'mode' => 'single',
            'sku' => 'SKU-100-RED',
            'stock_qty' => 3,
            'price_override' => 123.45,
            'sort_order' => 1,
            'is_active' => true,
            'combination_hash' => hash('sha256', 'SKU-100-RED'),
            'payload' => null,
            'created_by' => null,
            'updated_by' => null,
        ]);

        $prices = $this->getJson('/api/v1/wholesale/product_prices?include_option_values=1&sort=sku_asc');
        $prices->assertOk();
        $prices->assertJsonFragment(['sku' => 'SKU-100']);
        $prices->assertJsonFragment(['sku' => 'SKU-100-RED']);

        $quantities = $this->getJson('/api/v1/wholesale/product_quantities?include_option_values=1&sort=sku_asc');
        $quantities->assertOk();
        $quantities->assertJsonFragment(['sku' => 'SKU-100']);
        $quantities->assertJsonFragment(['sku' => 'SKU-100-RED']);
    }

    public function test_ability_middleware_applies_per_resource_family(): void
    {
        $user = User::factory()->create(['api_access_enabled' => true]);
        Sanctum::actingAs($user, ['manufacturers.read']);

        $this->seedCatalogGraph();

        $this->getJson('/api/v1/wholesale/manufacturers')->assertOk();
        $this->getJson('/api/v1/wholesale/products')->assertForbidden();
        $this->getJson('/api/v1/wholesale/categories')->assertForbidden();
    }

    public function test_api_access_disabled_user_is_blocked_even_with_token_abilities(): void
    {
        $user = User::factory()->create(['api_access_enabled' => false]);
        Sanctum::actingAs($user, ['products.read', 'wholesale.read']);

        $this->seedCatalogGraph();

        $this->getJson('/api/v1/wholesale/products')
            ->assertForbidden()
            ->assertJsonPath('message', 'API access is disabled for this user.');
    }

    public function test_wholesale_api_endpoints_are_blocked_when_catalog_api_feature_is_disabled(): void
    {
        $user = User::factory()->create(['api_access_enabled' => true]);
        Sanctum::actingAs($user, ['products.read', 'wholesale.read']);

        $this->seedCatalogGraph();

        app(SystemSettingsService::class)->put('catalog_use_api', false);

        $this->getJson('/api/v1/wholesale/products')
            ->assertForbidden()
            ->assertJsonPath('message', 'This API module is disabled in Catalog Features.')
            ->assertJsonPath('flag', 'catalog_use_api');
    }

    /**
     * @return array{0: Product, 1: OptionValue}
     */
    private function seedCatalogGraph(): array
    {
        $manufacturer = Manufacturer::query()->create([
            'code' => 'MAN-1',
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 1,
            'payload' => null,
            'created_by' => null,
            'updated_by' => null,
        ]);

        ManufacturerTranslation::query()->create([
            'manufacturer_id' => $manufacturer->id,
            'locale' => 'en',
            'name' => 'Manufacturer One',
            'slug' => 'manufacturer-one',
            'description' => null,
            'meta_title' => null,
            'meta_description' => null,
            'payload' => null,
        ]);

        $category = Category::query()->create([
            'scope' => Category::SCOPE_CATALOG,
            'code' => 'CAT-1',
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
            'name' => 'Category One',
            'slug' => 'category-one',
            'description' => null,
            'meta_title' => null,
            'meta_description' => null,
            'payload' => null,
        ]);

        $product = Product::query()->create([
            'code' => 'DSP-100',
            'sku' => 'SKU-100',
            'is_active' => true,
            'manufacturer_id' => $manufacturer->id,
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

        $option = Option::query()->create([
            'code' => 'COLOR',
            'type' => 'select',
            'is_active' => true,
            'sort_order' => 1,
            'payload' => null,
            'created_by' => null,
            'updated_by' => null,
        ]);

        $optionValue = OptionValue::query()->create([
            'option_id' => $option->id,
            'code' => 'RED',
            'is_active' => true,
            'sort_order' => 1,
            'payload' => null,
            'created_by' => null,
            'updated_by' => null,
        ]);

        return [$product, $optionValue];
    }
}
