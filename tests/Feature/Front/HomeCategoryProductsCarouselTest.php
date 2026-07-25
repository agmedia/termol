<?php

namespace Tests\Feature\Front;

use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Product\Product;
use App\Models\Content\ContentBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HomeCategoryProductsCarouselTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_category_carousel_loads_products_and_uses_category_card_style(): void
    {
        $category = Category::query()->create([
            'scope' => Category::SCOPE_CATALOG,
            'code' => 'small-appliances',
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 1,
        ]);
        $category->translations()->create([
            'scope' => Category::SCOPE_CATALOG,
            'locale' => 'hr',
            'name' => 'Mali kućanski aparati',
            'slug' => 'mali-kucanski-aparati',
            'description' => null,
        ]);

        $product = Product::query()->create([
            'code' => 'test-appliance',
            'sku' => 'TEST-APPLIANCE',
            'is_active' => true,
            'base_price' => 129.99,
            'stock_qty' => 10,
        ]);
        $product->translations()->create([
            'locale' => 'hr',
            'name' => 'Testni kućanski aparat',
            'slug' => 'testni-kucanski-aparat',
            'excerpt' => null,
            'description' => null,
        ]);
        $product->categories()->attach($category->id, [
            'sort_order' => 1,
            'is_primary' => true,
        ]);
        $image = UploadedFile::fake()->image('appliance.jpg', 900, 1200);
        $product
            ->addMedia($image->getPathname())
            ->usingName('Testni kućanski aparat')
            ->usingFileName('appliance.jpg')
            ->toMediaCollection('product_main');

        foreach ([2, 3] as $index) {
            $additionalProduct = Product::query()->create([
                'code' => "test-appliance-{$index}",
                'sku' => "TEST-APPLIANCE-{$index}",
                'is_active' => true,
                'base_price' => 129.99 + $index,
                'stock_qty' => 10,
            ]);
            $additionalProduct->translations()->create([
                'locale' => 'hr',
                'name' => "Testni kućanski aparat {$index}",
                'slug' => "testni-kucanski-aparat-{$index}",
                'excerpt' => null,
                'description' => null,
            ]);
            $additionalProduct->categories()->attach($category->id, [
                'sort_order' => $index,
                'is_primary' => true,
            ]);
            $additionalImage = UploadedFile::fake()->image("appliance-{$index}.jpg", 900, 1200);
            $additionalProduct
                ->addMedia($additionalImage->getPathname())
                ->usingName("Testni kućanski aparat {$index}")
                ->usingFileName("appliance-{$index}.jpg")
                ->toMediaCollection('product_main');
        }

        $block = ContentBlock::query()->create([
            'code' => 'home-category-products-test',
            'name' => 'Home Category Products Test',
            'type' => 'category_products_carousel',
            'is_active' => true,
            'payload' => null,
        ]);
        $block->translations()->create([
            'locale' => 'hr',
            'title' => 'Preporučujemo',
            'subtitle' => null,
            'body_html' => null,
            'cta_label' => null,
            'cta_url' => null,
            'payload' => ['items_limit' => 12],
        ]);
        $block->items()->create([
            'item_type' => 'category',
            'item_id' => $category->id,
            'sort_order' => 0,
        ]);
        $block->slots()->create([
            'placement' => 'home.before_products',
            'frontend_variant' => 'desktop',
            'target_type' => null,
            'target_ref' => null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/122.0 Safari/537.36',
            ])
            ->get('/')
            ->assertOk()
            ->assertSee('Preporučujemo')
            ->assertSee('iz kategorije')
            ->assertSee('Mali kućanski aparati')
            ->assertSee('/category/mali-kucanski-aparati', false)
            ->assertSee('Testni kućanski aparat')
            ->assertSee('data-product-card-lined', false)
            ->assertSee('border-top: 1px solid #e2e8f0;', false)
            ->assertSee('border-bottom: 1px solid #e2e8f0;', false)
            ->assertSee('<section class="w-full bg-white', false)
            ->assertSee('class="mt-4 relative left-1/2 -translate-x-1/2"', false)
            ->assertSee('width: min(calc(100vw - 2rem), var(--storefront-container-width, 1860px));', false)
            ->assertSee('data-products-carousel-splide', false)
            ->assertSee('data-products-carousel-swipe-hint', false)
            ->assertSee('arrows: false', false)
            ->assertSee('pagination: count > mobilePerPage', false);
    }
}
