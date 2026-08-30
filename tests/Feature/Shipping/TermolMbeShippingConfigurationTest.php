<?php

namespace Tests\Feature\Shipping;

use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Product\Product;
use App\Models\Settings\Local\ShippingMethod;
use App\Services\Shipping\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermolMbeShippingConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mbe_tariffs_are_configured_inactive_with_gapless_thousandth_kg_boundaries(): void
    {
        $mainland = ShippingMethod::query()->with('rates')->where('code', 'mbe_mainland_hr')->firstOrFail();
        $islands = ShippingMethod::query()->with('rates')->where('code', 'mbe_islands_hr')->firstOrFail();
        $pickup = ShippingMethod::query()->where('code', 'pickup')->firstOrFail();

        $this->assertFalse($mainland->is_active);
        $this->assertFalse($islands->is_active);
        $this->assertSame('mbe', $mainland->carrier);
        $this->assertSame('weight_tiers', $mainland->pricing_type);
        $this->assertSame('block', $mainland->missing_measurements_policy);
        $this->assertSame('hr_mainland', data_get($mainland->settings, 'destination_scope'));
        $this->assertSame('hr_islands', data_get($islands->settings, 'destination_scope'));
        $this->assertSame(12, $mainland->rates->count());
        $this->assertSame(12, $islands->rates->count());
        $this->assertFalse($pickup->is_active);
        $this->assertSame('pickup', $pickup->service_type);
        $this->assertSame('free', $pickup->pricing_type);
        $this->assertSame('Lapovačka 11A, 32100 Vinkovci', data_get($pickup->settings, 'pickup_address'));
        $this->assertSame('Radnim danom 08:00–16:00', data_get($pickup->settings, 'pickup_opening_hours'));

        $this->assertGaplessRates($mainland);
        $this->assertGaplessRates($islands);
        $this->assertSame(
            config('termol_shipping.mbe.methods.mbe_mainland_hr.rates'),
            $this->serializedRates($mainland),
        );
        $this->assertSame(
            config('termol_shipping.mbe.methods.mbe_islands_hr.rates'),
            $this->serializedRates($islands),
        );

        $this->assertSame(6.0, $this->priceForWeight($mainland, 4.999));
        $this->assertSame(7.0, $this->priceForWeight($mainland, 5.000));
        $this->assertSame(7.0, $this->priceForWeight($mainland, 9.999));
        $this->assertSame(9.5, $this->priceForWeight($mainland, 10.000));
        $this->assertSame(100.0, $this->priceForWeight($mainland, 799.999));
        $this->assertSame(200.0, $this->priceForWeight($mainland, 800.000));

        $this->assertSame(10.0, $this->priceForWeight($islands, 4.999));
        $this->assertSame(12.0, $this->priceForWeight($islands, 5.000));
        $this->assertSame(12.0, $this->priceForWeight($islands, 19.999));
        $this->assertSame(15.0, $this->priceForWeight($islands, 20.000));
        $this->assertSame(120.0, $this->priceForWeight($islands, 499.999));
        $this->assertSame(150.0, $this->priceForWeight($islands, 500.000));
    }

    public function test_reapplying_configuration_labels_only_the_listed_catalog_categories(): void
    {
        $quoteCategory = Category::query()->create([
            'scope' => Category::SCOPE_CATALOG,
            'code' => '020203',
            'payload' => [
                'show_filters' => false,
                'shipping_labels' => ['fragile'],
            ],
        ]);
        $regularCategory = Category::query()->create([
            'scope' => Category::SCOPE_CATALOG,
            'code' => 'not-in-termol-list',
            'payload' => ['shipping_labels' => ['fragile']],
        ]);
        $sameCodeInAnotherScope = Category::query()->create([
            'scope' => Category::SCOPE_BLOG,
            'code' => '020203',
            'payload' => [],
        ]);

        $migration = require database_path('migrations/2026_08_30_082000_configure_termol_mbe_shipping.php');
        $rateIds = ShippingMethod::query()
            ->with('rates')
            ->whereIn('code', ['mbe_mainland_hr', 'mbe_islands_hr'])
            ->get()
            ->flatMap->rates
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $migration->up();
        $migration->up();

        $quoteCategory->refresh();
        $regularCategory->refresh();
        $sameCodeInAnotherScope->refresh();

        $this->assertFalse($quoteCategory->payload['show_filters']);
        $this->assertSame(['fragile', 'quote_shipping'], $quoteCategory->shippingLabels());
        $this->assertSame(['fragile'], $regularCategory->shippingLabels());
        $this->assertSame([], $sameCodeInAnotherScope->shippingLabels());
        $this->assertCount(42, config('termol_shipping.quote_shipping_category_codes'));
        $this->assertSame(
            24,
            ShippingMethod::query()
                ->whereIn('code', ['mbe_mainland_hr', 'mbe_islands_hr'])
                ->withCount('rates')
                ->get()
                ->sum('rates_count'),
        );
        $this->assertSame(
            $rateIds,
            ShippingMethod::query()
                ->with('rates')
                ->whereIn('code', ['mbe_mainland_hr', 'mbe_islands_hr'])
                ->get()
                ->flatMap->rates
                ->pluck('id')
                ->sort()
                ->values()
                ->all(),
        );

        $migration->down();
        $quoteCategory->refresh();

        $this->assertSame(['fragile', 'quote_shipping'], $quoteCategory->shippingLabels());
        $this->assertSame(
            0,
            ShippingMethod::query()
                ->whereIn('code', ['mbe_mainland_hr', 'mbe_islands_hr'])
                ->count(),
        );
    }

    public function test_quote_categories_keep_pickup_available_and_require_quote_for_delivery(): void
    {
        $category = new Category([
            'payload' => ['shipping_labels' => ['quote_shipping']],
        ]);
        $product = new Product([
            'code' => 'quote-shipping-product',
            'sku' => 'QUOTE-SHIPPING-PRODUCT',
            'is_active' => true,
            'base_price' => 100,
            'stock_qty' => 1,
            'weight_kg' => 20,
            'shipping_labels' => [],
        ]);
        $product->forceFill(['id' => 200]);
        $product->setRelation('packages', collect());
        $product->setRelation('categories', collect([$category]));
        $lines = collect([['product' => $product, 'quantity' => 1]]);
        $calculator = app(ShippingCalculator::class);

        $mainland = ShippingMethod::query()->with('rates')->where('code', 'mbe_mainland_hr')->firstOrFail();
        $quoteMethod = ShippingMethod::query()->with('rates')->where('code', 'shipping_quote')->firstOrFail();
        $pickup = ShippingMethod::query()->with('rates')->where('code', 'pickup')->firstOrFail();

        $this->assertNull($calculator->quote($mainland, $lines, 100));

        $quote = $calculator->quote($quoteMethod, $lines, 100);
        $this->assertNotNull($quote);
        $this->assertTrue($quote['requires_quote']);

        $pickupQuote = $calculator->quote($pickup, $lines, 100);
        $this->assertNotNull($pickupQuote);
        $this->assertSame('free', $pickupQuote['pricing_type']);
        $this->assertSame(0.0, $pickupQuote['price']);
    }

    public function test_rollback_removes_only_methods_created_by_the_configuration(): void
    {
        $mainland = ShippingMethod::query()->where('code', 'mbe_mainland_hr')->firstOrFail();
        $settings = $mainland->settings;
        unset($settings['created_by_termol_requirements_migration']);
        $mainland->update(['settings' => $settings]);

        $category = Category::query()->create([
            'scope' => Category::SCOPE_CATALOG,
            'code' => '020203',
            'payload' => ['shipping_labels' => ['quote_shipping']],
        ]);
        $migration = require database_path('migrations/2026_08_30_082000_configure_termol_mbe_shipping.php');

        $migration->down();

        $this->assertDatabaseHas('shipping_methods', ['code' => 'mbe_mainland_hr']);
        $this->assertDatabaseMissing('shipping_methods', ['code' => 'mbe_islands_hr']);
        $this->assertDatabaseHas('shipping_methods', ['code' => 'pickup']);
        $this->assertSame(['quote_shipping'], $category->refresh()->shippingLabels());
    }

    private function assertGaplessRates(ShippingMethod $method): void
    {
        $rates = $method->rates->values();

        $this->assertSame(0.0, (float) $rates->first()->min_weight_kg);
        $this->assertNull($rates->last()->max_weight_kg);

        foreach ($rates->slice(0, -1) as $index => $rate) {
            $next = $rates->get($index + 1);

            $this->assertNotNull($rate->max_weight_kg);
            $this->assertSame(
                round((float) $rate->max_weight_kg + 0.001, 3),
                round((float) $next->min_weight_kg, 3),
            );
        }
    }

    private function priceForWeight(ShippingMethod $method, float $weight): float
    {
        $product = new Product([
            'code' => 'mbe-boundary-'.$weight,
            'sku' => 'MBE-'.$weight,
            'is_active' => true,
            'base_price' => 100,
            'stock_qty' => 1,
            'weight_kg' => $weight,
            'shipping_labels' => [],
        ]);
        $product->forceFill(['id' => 100]);
        $product->setRelation('packages', collect());
        $product->setRelation('categories', collect());

        $quote = app(ShippingCalculator::class)->quote(
            $method,
            collect([['product' => $product, 'quantity' => 1]]),
            100,
        );

        $this->assertNotNull($quote);

        return $quote['price'];
    }

    /** @return list<array{min_weight_kg:string,max_weight_kg:string|null,price:string}> */
    private function serializedRates(ShippingMethod $method): array
    {
        return $method->rates
            ->map(static fn ($rate): array => [
                'min_weight_kg' => $rate->min_weight_kg,
                'max_weight_kg' => $rate->max_weight_kg,
                'price' => $rate->price,
            ])
            ->all();
    }
}
