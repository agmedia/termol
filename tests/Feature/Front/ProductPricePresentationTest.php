<?php

namespace Tests\Feature\Front;

use App\Models\Catalog\Product\Product;
use App\Services\Pricing\ProductPricePresentationService;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPricePresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_that_already_includes_tax_is_not_changed_by_a_tax_round_trip(): void
    {
        app(SystemSettingsService::class)->put('store_pricing_prices_include_tax', true);

        $product = Product::query()->create([
            'code' => 'gross-price-rounding-test',
            'sku' => 'GROSS-PRICE-ROUNDING-TEST',
            'is_active' => true,
            'base_price' => 229.57,
            'stock_qty' => 1,
        ]);

        $price = app(ProductPricePresentationService::class)->forProduct($product);

        $this->assertSame(229.57, $price['current_gross']);
        $this->assertSame(229.57, $price['base_gross']);
        $this->assertFalse($price['has_discount']);
    }
}
