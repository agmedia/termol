<?php

namespace Tests\Feature\Front;

use App\Http\Controllers\Front\CatalogController;
use App\Models\Catalog\Category\Category;
use App\Models\Catalog\Category\CategoryTranslation;
use App\Models\Catalog\Attribute\Attribute;
use App\Models\Catalog\Manufacturer\Manufacturer;
use App\Models\Catalog\Manufacturer\ManufacturerTranslation;
use App\Models\Catalog\Option\Option;
use App\Models\Catalog\Option\OptionValue;
use App\Models\Catalog\Product\Product;
use App\Models\Catalog\Product\ProductOptionValue;
use App\Models\Catalog\Product\ProductTranslation;
use App\Models\Content\Blog\BlogPost;
use App\Models\Content\Blog\BlogPostTranslation;
use App\Models\Content\ContentBlock;
use App\Models\Content\Page\InfoPage;
use App\Models\Content\Page\InfoPageTranslation;
use App\Models\Settings\Local\Currency;
use App\Models\Settings\Local\OrderStatus;
use App\Models\Settings\Local\PaymentMethod;
use App\Models\Settings\Local\ShippingMethod;
use App\Models\User;
use App\Services\Front\NavigationMenuService;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class StorefrontFrontFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_pretty_storefront_routes_are_available(): void
    {
        [$category, $categorySlug] = $this->seedCategory();
        [$product, $productSlug] = $this->seedProduct($category->id);
        [$manufacturer, $manufacturerSlug] = $this->seedManufacturer();
        [$post, $postSlug] = $this->seedBlogPost();
        [$page, $pageSlug] = $this->seedInfoPage();

        app(SystemSettingsService::class)->putMany([
            'catalog_use_manufacturers' => true,
            'catalog_use_blog' => true,
        ]);

        $this->get('/shop')->assertOk();
        $this->get('/categories')->assertOk();
        $this->get('/category/'.$categorySlug)->assertOk();
        $this->get('/product/'.$productSlug)->assertOk();
        $this->get('/manufacturers')->assertOk();
        $this->get('/manufacturer/'.$manufacturerSlug)->assertOk();
        $this->get('/blog')->assertOk();
        $this->get('/blog/'.$postSlug)->assertOk();
        $this->get('/page/'.$pageSlug)->assertOk();
        $this->get('/contact')->assertOk();
        $this->get('/cart')->assertOk();

        $this->assertNotNull($product);
        $this->assertNotNull($manufacturer);
        $this->assertNotNull($post);
        $this->assertNotNull($page);
    }

    public function test_contact_form_stores_message(): void
    {
        $this->post('/contact', [
            'name' => 'Front Tester',
            'email' => 'front@example.test',
            'phone' => '+38591000000',
            'subject' => 'Wholesale inquiry',
            'message' => 'Please contact me with available B2B pricing details.',
        ])->assertRedirect('/contact');

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'front@example.test',
            'subject' => 'Wholesale inquiry',
            'status' => 'new',
        ]);
    }

    public function test_shop_search_matches_product_sku(): void
    {
        [$category] = $this->seedCategory();
        [$product] = $this->seedProduct($category->id);

        $product->update(['sku' => 'FIND-ME-123']);

        $query = Product::query()->where('is_active', true);

        $controller = app(CatalogController::class);
        $method = new \ReflectionMethod($controller, 'applyProductSearch');
        $method->setAccessible(true);
        $method->invoke($controller, $query, 'en', 'en', 'FIND-ME-123');

        $this->assertSame([$product->id], $query->pluck('id')->all());
    }

    public function test_category_search_matches_product_sku(): void
    {
        [$category] = $this->seedCategory();
        [$product] = $this->seedProduct($category->id);

        $product->update(['sku' => 'CAT-FIND-456']);

        $query = Product::query()
            ->where('is_active', true)
            ->whereHas('categories', fn ($categoryQuery) => $categoryQuery->whereKey($category->id));

        $controller = app(CatalogController::class);
        $method = new \ReflectionMethod($controller, 'applyProductSearch');
        $method->setAccessible(true);
        $method->invoke($controller, $query, 'en', 'en', 'CAT-FIND-456');

        $this->assertSame([$product->id], $query->pluck('id')->all());
    }

    public function test_home_renders_configured_navigation_with_subcategories(): void
    {
        [$parent, $parentSlug] = $this->seedCategory();

        $child = Category::query()->create([
            'scope' => Category::SCOPE_CATALOG,
            'code' => 'child-'.strtolower((string) str()->random(5)),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 2,
            'parent_id' => $parent->id,
        ]);

        CategoryTranslation::query()->create([
            'category_id' => $child->id,
            'scope' => Category::SCOPE_CATALOG,
            'locale' => 'en',
            'name' => 'Child menu category',
            'slug' => 'child-menu-category',
            'description' => 'Child description',
        ]);

        app(SystemSettingsService::class)->put(NavigationMenuService::SETTINGS_KEY, [
            [
                'type' => 'category',
                'label' => 'Hrana i namirnice',
                'category_id' => $parent->id,
                'page_id' => 0,
                'url' => '',
                'open_in_new_tab' => false,
                'show_dropdown' => true,
                'is_active' => true,
                'sort_order' => 0,
            ],
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Hrana i namirnice')
            ->assertSee('Child menu category')
            ->assertSee('/category/'.$parentSlug, false);
    }

    public function test_home_renders_instagram_curated_grid_block(): void
    {
        $block = ContentBlock::query()->create([
            'code' => 'home-instagram-widget',
            'name' => 'Home Instagram Widget',
            'type' => 'instagram_curated_grid',
            'is_active' => true,
            'payload' => null,
        ]);

        $block->translations()->create([
            'locale' => 'hr',
            'title' => 'Prati nas na Instagramu',
            'subtitle' => '@kozo_bodywear',
            'body_html' => null,
            'cta_label' => 'Otvori profil',
            'cta_url' => 'https://www.instagram.com/kozo_bodywear/',
            'payload' => null,
        ]);

        $block->translations()->create([
            'locale' => 'en',
            'title' => 'Follow us on Instagram',
            'subtitle' => '@kozo_bodywear',
            'body_html' => null,
            'cta_label' => 'Open profile',
            'cta_url' => 'https://www.instagram.com/kozo_bodywear/',
            'payload' => null,
        ]);

        $block->slots()->create([
            'placement' => 'home.bottom',
            'frontend_variant' => 'all',
            'target_type' => null,
            'target_ref' => null,
            'sort_order' => 999,
            'is_active' => true,
        ]);

        $image = UploadedFile::fake()->image('instagram-widget.jpg', 1080, 1080);

        $block->addMedia($image->getPathname())
            ->usingName('Instagram widget image')
            ->usingFileName('instagram-widget.jpg')
            ->withCustomProperties([
                'link_url' => ['hr' => 'https://www.instagram.com/p/demo-post/', 'en' => 'https://www.instagram.com/p/demo-post/'],
                'link_url_value' => 'https://www.instagram.com/p/demo-post/',
                'caption' => ['hr' => 'Demo Instagram post caption', 'en' => 'Demo Instagram post caption'],
            ])
            ->toMediaCollection('block_slides');

        $this->get('/')
            ->assertOk()
            ->assertSee('Prati nas na Instagramu')
            ->assertSee('@kozo_bodywear')
            ->assertSee('https://www.instagram.com/kozo_bodywear/', false)
            ->assertSee('https://www.instagram.com/p/demo-post/', false);
    }

    public function test_category_page_includes_sastav_filter_and_filters_products(): void
    {
        $this->useEnglishStorefrontLocale();

        [$category, $categorySlug] = $this->seedCategory();
        [$bambooProduct, $bambooSlug] = $this->seedProduct($category->id);
        [$linenProduct, $linenSlug] = $this->seedProduct($category->id);

        $bambooAttribute = Attribute::query()->create([
            'code' => 'sastav-bamboo',
            'group_code' => 'sastav',
            'type' => Attribute::TYPE_SELECT,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $bambooAttribute->translations()->create([
            'locale' => 'en',
            'group_name' => 'Composition',
            'name' => 'Bamboo',
            'slug' => 'sastav-bamboo',
            'description' => null,
            'payload' => null,
        ]);

        $linenAttribute = Attribute::query()->create([
            'code' => 'sastav-linen',
            'group_code' => 'sastav',
            'type' => Attribute::TYPE_SELECT,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $linenAttribute->translations()->create([
            'locale' => 'en',
            'group_name' => 'Composition',
            'name' => 'Linen',
            'slug' => 'sastav-linen',
            'description' => null,
            'payload' => null,
        ]);

        $bambooProduct->attributes()->attach($bambooAttribute->id, [
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $linenProduct->attributes()->attach($linenAttribute->id, [
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->attachProductSizeOptions($bambooProduct, ['S', 'M']);
        $this->attachProductSizeOptions($linenProduct, ['M', 'L']);

        $bambooName = 'Product '.$bambooSlug;
        $linenName = 'Product '.$linenSlug;

        $this->get('/category/'.$categorySlug)
            ->assertOk()
            ->assertSee('Composition')
            ->assertSee('name="attr_sastav"', false)
            ->assertSee('Bamboo')
            ->assertSee('Linen')
            ->assertSee($bambooName)
            ->assertSee($linenName);

        $categoryResponse = $this->get('/category/'.$categorySlug);
        $categoryHtml = $categoryResponse->getContent();
        $this->assertIsString($categoryHtml);
        $this->assertNotFalse(strpos($categoryHtml, 'name="size"'));
        $this->assertNotFalse(strpos($categoryHtml, 'name="attr_sastav"'));
        $this->assertLessThan(
            strpos($categoryHtml, 'name="attr_sastav"'),
            strpos($categoryHtml, 'name="size"')
        );

        $this->get('/category/'.$categorySlug.'?attr_sastav='.$bambooAttribute->id)
            ->assertOk()
            ->assertSee('Composition')
            ->assertSee('Bamboo')
            ->assertSee($bambooName)
            ->assertDontSee($linenName);
    }

    public function test_mobile_home_renders_instagram_curated_grid_assets_and_slider_init(): void
    {
        $block = ContentBlock::query()->create([
            'code' => 'mobile-instagram-widget',
            'name' => 'Mobile Instagram Widget',
            'type' => 'instagram_curated_grid',
            'is_active' => true,
            'payload' => null,
        ]);

        $block->translations()->create([
            'locale' => 'hr',
            'title' => 'Prati nas na Instagramu',
            'subtitle' => '@kozo_bodywear',
            'body_html' => null,
            'cta_label' => 'Otvori profil',
            'cta_url' => 'https://www.instagram.com/kozo_bodywear/',
            'payload' => null,
        ]);

        $block->slots()->create([
            'placement' => 'home.hero',
            'frontend_variant' => 'mobile',
            'target_type' => null,
            'target_ref' => null,
            'sort_order' => 10,
            'is_active' => true,
        ]);

        $image = UploadedFile::fake()->image('instagram-mobile-widget.jpg', 1080, 1080);

        $block->addMedia($image->getPathname())
            ->usingName('Instagram mobile widget image')
            ->usingFileName('instagram-mobile-widget.jpg')
            ->withCustomProperties([
                'link_url' => ['hr' => 'https://www.instagram.com/p/mobile-demo-post/'],
                'link_url_value' => 'https://www.instagram.com/p/mobile-demo-post/',
                'caption' => ['hr' => 'Demo Instagram mobile post caption'],
            ])
            ->toMediaCollection('block_slides');

        $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            ])
            ->get('/')
            ->assertOk()
            ->assertSee('https://cdn.jsdelivr.net/npm/@splidejs/splide@4.1.4/dist/js/splide.min.js', false)
            ->assertSee('data-instagram-grid-splide', false)
            ->assertSee("rewind: count > 1", false)
            ->assertSee("640: { perPage: 1, gap: '0.8rem', arrows: false, pagination: count > 1 }", false);
    }

    public function test_shop_listing_falls_back_to_gallery_when_main_image_file_is_missing(): void
    {
        $this->useEnglishStorefrontLocale();

        [$category] = $this->seedCategory();
        [$product] = $this->seedProduct($category->id);

        $mainImage = UploadedFile::fake()->image('missing-main.jpg', 1200, 1600);
        $galleryImage = UploadedFile::fake()->image('gallery-fallback.jpg', 1200, 1600);

        $mainMedia = $product->addMedia($mainImage->getPathname())
            ->usingName('missing-main')
            ->usingFileName('missing-main.jpg')
            ->toMediaCollection('product_main');

        $galleryMedia = $product->addMedia($galleryImage->getPathname())
            ->usingName('gallery-fallback')
            ->usingFileName('gallery-fallback.jpg')
            ->toMediaCollection('product_gallery');

        @unlink($mainMedia->getPath());

        $this->get('/shop')
            ->assertOk()
            ->assertSee('gallery-fallback.jpg', false)
            ->assertSee('data-product-image="'.$galleryMedia->getUrl().'"', false);
    }

    public function test_account_routes_are_prefixed_and_require_authenticated_verified_user(): void
    {
        $this->get('/account')->assertRedirect('/login');
        $this->get('/account/orders')->assertRedirect('/login');
        $this->get('/account/profile')->assertRedirect('/login');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->get('/account')->assertOk();
        $this->actingAs($user)->get('/account/orders')->assertOk();
        $this->actingAs($user)->get('/account/profile')->assertOk();
    }

    public function test_checkout_creates_order_with_pretty_checkout_routes(): void
    {
        [$category] = $this->seedCategory();
        [$product] = $this->seedProduct($category->id);

        Currency::query()->create([
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => 'EUR',
            'symbol_position' => 'left',
            'decimal_places' => 2,
            'exchange_rate' => 1,
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        OrderStatus::query()->create([
            'code' => 'new',
            'name' => 'New',
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        ShippingMethod::query()->create([
            'code' => 'standard',
            'name' => 'Standard Shipping',
            'price' => 4.99,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        PaymentMethod::query()->create([
            'code' => 'bank',
            'name' => 'Bank Transfer',
            'provider' => 'bank',
            'fee_type' => 'fixed',
            'fee_value' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->post('/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $response = $this->post('/checkout', [
            'customer_first_name' => 'Jane',
            'customer_last_name' => 'Doe',
            'customer_email' => 'jane@example.test',
            'customer_phone' => '+38591000002',

            'billing_first_name' => 'Jane',
            'billing_last_name' => 'Doe',
            'billing_company' => 'AG Test',
            'billing_oib' => '12345678901',
            'billing_vat_id' => 'HR12345678901',
            'billing_address_line_1' => 'Main Street 1',
            'billing_address_line_2' => '',
            'billing_postal_code' => '10000',
            'billing_city' => 'Zagreb',
            'billing_state' => 'Grad Zagreb',
            'billing_country_code' => 'HR',

            'use_billing_for_shipping' => '1',

            'shipping_method_code' => 'standard',
            'payment_method_code' => 'bank',
            'customer_note' => 'Please ring bell on delivery.',
            'accept_terms' => '1',
        ]);

        $order = \App\Models\Sales\Order\Order::query()->first();

        $this->assertNotNull($order);

        $response->assertRedirect(route('checkout.success', ['orderNumber' => $order->order_number]));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_email' => 'jane@example.test',
            'item_qty' => 2,
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->assertDatabaseMissing('orders', [
            'customer_email' => 'missing@example.test',
        ]);
    }

    public function test_category_shows_only_option_filters_available_in_that_category_scope(): void
    {
        [$categoryA, $slugA] = $this->seedCategory();
        [$categoryB, $slugB] = $this->seedCategory();
        [$productA] = $this->seedProduct($categoryA->id);
        [$productB] = $this->seedProduct($categoryB->id);

        $user = User::factory()->create();

        $colorOption = Option::query()->create([
            'code' => 'color-scope',
            'type' => Option::TYPE_SELECT,
            'is_active' => true,
            'sort_order' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $colorOption->translations()->create([
            'locale' => 'en',
            'name' => 'Color Scope',
            'slug' => 'color-scope',
        ]);

        $sizeOption = Option::query()->create([
            'code' => 'size-scope',
            'type' => Option::TYPE_SELECT,
            'is_active' => true,
            'sort_order' => 2,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $sizeOption->translations()->create([
            'locale' => 'en',
            'name' => 'Size Scope',
            'slug' => 'size-scope',
        ]);

        $black = OptionValue::query()->create([
            'option_id' => $colorOption->id,
            'code' => 'black-scope',
            'is_active' => true,
            'sort_order' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $black->translations()->create([
            'locale' => 'en',
            'name' => 'Black Scope',
            'slug' => 'black-scope',
        ]);

        $medium = OptionValue::query()->create([
            'option_id' => $sizeOption->id,
            'code' => 'm-scope',
            'is_active' => true,
            'sort_order' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
        $medium->translations()->create([
            'locale' => 'en',
            'name' => 'M Scope',
            'slug' => 'm-scope',
        ]);

        ProductOptionValue::query()->create([
            'product_id' => $productA->id,
            'option_value_id' => $black->id,
            'parent_option_value_id' => null,
            'mode' => 'single',
            'combination_hash' => hash('sha256', 's:'.$black->id),
            'sku' => 'A-BLACK',
            'stock_qty' => 10,
            'is_active' => true,
            'sort_order' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        ProductOptionValue::query()->create([
            'product_id' => $productB->id,
            'option_value_id' => $medium->id,
            'parent_option_value_id' => null,
            'mode' => 'single',
            'combination_hash' => hash('sha256', 's:'.$medium->id),
            'sku' => 'B-M',
            'stock_qty' => 10,
            'is_active' => true,
            'sort_order' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        app(SystemSettingsService::class)->put('store_product_filter_option_ids', [$colorOption->id, $sizeOption->id]);

        $this->get('/category/'.$slugA)
            ->assertOk()
            ->assertSee('Color Scope')
            ->assertDontSee('Size Scope');

        $this->get('/category/'.$slugB)
            ->assertOk()
            ->assertSee('Size Scope')
            ->assertDontSee('Color Scope');
    }

    public function test_product_detail_renders_attribute_panels_in_requested_order_without_store_check_button(): void
    {
        $this->useEnglishStorefrontLocale();
        [$category] = $this->seedCategory();
        [$product, $slug] = $this->seedProduct($category->id);

        $this->attachProductAttribute($product, 'garancija', 'Guarantee Label', '2 year guarantee', 30);
        $this->attachProductAttribute($product, 'sastav', 'Composition Label', '100% cotton', 10);
        $this->attachProductAttribute($product, 'kvaliteta', 'Quality Label', 'Premium stitching', 20);
        $this->attachProductAttribute($product, 'origin', 'Origin Label', 'Croatia', 40);

        $this->get('/product/'.$slug)
            ->assertOk()
            ->assertDontSee('Check store availability')
            ->assertSeeInOrder(['Composition Label', 'Quality Label', 'Guarantee Label'])
            ->assertSee('100% cotton')
            ->assertSee('Premium stitching')
            ->assertSee('2 year guarantee')
            ->assertDontSee('Origin Label')
            ->assertDontSee('Croatia');
    }

    public function test_mobile_product_detail_renders_attribute_panels_in_requested_order(): void
    {
        $this->useEnglishStorefrontLocale();
        [$category] = $this->seedCategory();
        [$product, $slug] = $this->seedProduct($category->id);

        $this->attachProductAttribute($product, 'kvaliteta', 'Quality Label', 'Premium finish', 20);
        $this->attachProductAttribute($product, 'sastav', 'Composition Label', '95% cotton / 5% elastane', 10);
        $this->attachProductAttribute($product, 'garancija', 'Guarantee Label', 'Quality guarantee included', 30);

        $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            ])
            ->get('/product/'.$slug)
            ->assertOk()
            ->assertSeeInOrder(['Composition Label', 'Quality Label', 'Guarantee Label'])
            ->assertSee('95% cotton / 5% elastane')
            ->assertSee('Premium finish')
            ->assertSee('Quality guarantee included');
    }

    public function test_desktop_product_detail_uses_size_guide_man_for_male_products_with_options(): void
    {
        $this->useEnglishStorefrontLocale();
        [$category] = $this->seedMaleCategory();
        [$product, $slug] = $this->seedProduct($category->id);

        $this->attachProductSizeOptions($product, ['M', 'L']);
        $this->seedSizeGuidePage('size-guide-man', 'Men size guide content');

        $this->get('/product/'.$slug)
            ->assertOk()
            ->assertSee('data-size-guide-open', false)
            ->assertSee('Men size guide content');
    }

    public function test_mobile_product_detail_renders_size_guide_when_options_exist(): void
    {
        $this->useEnglishStorefrontLocale();
        [$category] = $this->seedCategory();
        [$product, $slug] = $this->seedProduct($category->id);

        $this->attachProductSizeOptions($product, ['S', 'M']);
        $this->seedSizeGuidePage('size-guide-women', 'Women size guide content');

        $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            ])
            ->get('/product/'.$slug)
            ->assertOk()
            ->assertSee('data-size-guide-open', false)
            ->assertSee('Women size guide content');
    }

    public function test_product_detail_hides_size_guide_when_product_has_no_options(): void
    {
        $this->useEnglishStorefrontLocale();
        [$category] = $this->seedCategory();
        [$product, $slug] = $this->seedProduct($category->id);

        $this->seedSizeGuidePage('size-guide-women', 'Women size guide content');

        $this->get('/product/'.$slug)
            ->assertOk()
            ->assertDontSee('data-size-guide-open', false)
            ->assertDontSee('Women size guide content');
    }

    public function test_category_can_hide_filters_and_products_via_category_settings(): void
    {
        $this->useEnglishStorefrontLocale();
        [$category, $categorySlug] = $this->seedCategory();
        [$product, $productSlug] = $this->seedProduct($category->id);

        $category->update([
            'payload' => [
                Category::PAYLOAD_SHOW_FILTERS => false,
                Category::PAYLOAD_SHOW_PRODUCTS => false,
            ],
        ]);

        $response = $this->get('/category/'.$categorySlug);

        $response
            ->assertOk()
            ->assertDontSee('aria-controls="category-mobile-filter-panel"', false)
            ->assertDontSee('data-desktop-filter-form', false)
            ->assertDontSee('data-catalog-grid', false)
            ->assertDontSee('Product '.$productSlug)
            ->assertDontSee((string) $product->sku);
    }

    public function test_category_editorial_tiles_block_renders_on_targeted_category(): void
    {
        $this->useEnglishStorefrontLocale();
        [$category, $categorySlug] = $this->seedCategory();

        $block = ContentBlock::query()->create([
            'code' => 'category-editorial-test',
            'name' => 'Category Editorial Test',
            'type' => 'category_editorial_tiles',
            'is_active' => true,
            'payload' => null,
        ]);

        $block->translations()->create([
            'locale' => 'en',
            'title' => null,
            'subtitle' => null,
            'cta_label' => null,
            'cta_url' => null,
            'payload' => null,
        ]);

        $block->slots()->create([
            'placement' => 'category.top',
            'frontend_variant' => 'all',
            'target_type' => 'category',
            'target_ref' => $categorySlug,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $image = UploadedFile::fake()->image('tile-1.jpg', 900, 1200);

        $media = $block
            ->addMedia($image->getPathname())
            ->usingName('tile-1')
            ->usingFileName('tile-1.jpg')
            ->withCustomProperties([
                'block_title' => ['en' => 'Nightwear'],
                'link_url' => ['en' => '/category/nightwear'],
                'alt' => ['en' => 'Nightwear'],
            ])
            ->toMediaCollection('block_slides');

        $this->assertNotNull($media);

        $this->get('/category/'.$categorySlug)
            ->assertOk()
            ->assertSee('Nightwear')
            ->assertSee('/category/nightwear', false);
    }

    /**
     * @return array{Category,string}
     */
    private function seedCategory(): array
    {
        $category = Category::query()->create([
            'scope' => Category::SCOPE_CATALOG,
            'code' => 'cat-'.strtolower((string) str()->random(6)),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 1,
        ]);

        $slug = 'category-'.strtolower((string) str()->random(6));

        CategoryTranslation::query()->create([
            'category_id' => $category->id,
            'scope' => Category::SCOPE_CATALOG,
            'locale' => 'en',
            'name' => 'Category '.$slug,
            'slug' => $slug,
            'description' => 'Category description',
        ]);

        return [$category, $slug];
    }

    /**
     * @return array{Category,string}
     */
    private function seedMaleCategory(): array
    {
        $category = Category::query()->create([
            'scope' => Category::SCOPE_CATALOG,
            'code' => 'men-'.strtolower((string) str()->random(6)),
            'is_active' => true,
            'show_in_menu' => true,
            'sort_order' => 1,
        ]);

        $slug = 'men-category-'.strtolower((string) str()->random(6));

        CategoryTranslation::query()->create([
            'category_id' => $category->id,
            'scope' => Category::SCOPE_CATALOG,
            'locale' => 'en',
            'name' => 'Men category',
            'slug' => $slug,
            'description' => 'Men category description',
        ]);

        return [$category, $slug];
    }

    /**
     * @return array{Product,string}
     */
    private function seedProduct(int $categoryId): array
    {
        $product = Product::query()->create([
            'code' => 'prod-'.strtolower((string) str()->random(6)),
            'sku' => 'SKU-'.strtoupper((string) str()->random(5)),
            'is_active' => true,
            'base_price' => 49.99,
            'stock_qty' => 15,
        ]);

        $slug = 'product-'.strtolower((string) str()->random(6));

        ProductTranslation::query()->create([
            'product_id' => $product->id,
            'locale' => 'en',
            'name' => 'Product '.$slug,
            'slug' => $slug,
            'excerpt' => 'Product excerpt',
            'description' => 'Product body',
        ]);

        $product->categories()->sync([$categoryId => ['sort_order' => 1, 'is_primary' => true]]);

        return [$product, $slug];
    }

    private function attachProductAttribute(Product $product, string $groupCode, string $groupName, string $name, int $sortOrder): void
    {
        $attribute = Attribute::query()->create([
            'code' => $groupCode.'-'.strtolower((string) str()->random(6)),
            'group_code' => $groupCode,
            'type' => Attribute::TYPE_SELECT,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);

        $attribute->translations()->create([
            'locale' => 'en',
            'group_name' => $groupName,
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'description' => null,
            'payload' => null,
        ]);

        $product->attributes()->attach($attribute->id, [
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<int, string>  $labels
     */
    private function attachProductSizeOptions(Product $product, array $labels): void
    {
        $option = Option::query()->create([
            'code' => 'size-'.strtolower((string) str()->random(6)),
            'type' => Option::TYPE_SELECT,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $option->translations()->create([
            'locale' => 'en',
            'name' => 'Size',
            'slug' => 'size-'.strtolower((string) str()->random(6)),
            'description' => null,
            'payload' => null,
        ]);

        $option->products()->attach($product->id, [
            'is_required' => true,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (array_values($labels) as $index => $label) {
            $value = OptionValue::query()->create([
                'option_id' => $option->id,
                'code' => 'size-'.strtolower((string) str($label)->slug()->value()).'-'.strtolower((string) str()->random(4)),
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);

            $value->translations()->create([
                'locale' => 'en',
                'name' => $label,
                'slug' => str($label)->slug()->value().'-'.strtolower((string) str()->random(4)),
                'payload' => null,
            ]);

            ProductOptionValue::query()->create([
                'product_id' => $product->id,
                'option_value_id' => $value->id,
                'parent_option_value_id' => null,
                'mode' => 'single',
                'sku' => 'OPT-'.strtoupper((string) str()->random(4)),
                'stock_qty' => 5,
                'price_override' => null,
                'sort_order' => $index + 1,
                'is_active' => true,
                'combination_hash' => hash('sha256', $product->id.'-'.$value->id.'-single'),
                'payload' => null,
            ]);
        }
    }

    private function seedSizeGuidePage(string $code, string $bodyHtml): void
    {
        $page = InfoPage::query()->create([
            'code' => $code,
            'layout' => 'default',
            'is_active' => true,
            'published_at' => now()->subDay(),
            'sort_order' => 1,
        ]);

        InfoPageTranslation::query()->create([
            'page_id' => $page->id,
            'locale' => 'en',
            'title' => 'Size guide',
            'slug' => $code,
            'excerpt' => null,
            'body_html' => '<p>'.$bodyHtml.'</p>',
        ]);
    }

    private function useEnglishStorefrontLocale(): void
    {
        config([
            'app.locale' => 'en',
            'app.fallback_locale' => 'en',
        ]);

        app()->setLocale('en');
    }

    /**
     * @return array{Manufacturer,string}
     */
    private function seedManufacturer(): array
    {
        $manufacturer = Manufacturer::query()->create([
            'code' => 'man-'.strtolower((string) str()->random(6)),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $slug = 'manufacturer-'.strtolower((string) str()->random(6));

        ManufacturerTranslation::query()->create([
            'manufacturer_id' => $manufacturer->id,
            'locale' => 'en',
            'name' => 'Manufacturer '.$slug,
            'slug' => $slug,
            'description' => 'Manufacturer description',
        ]);

        return [$manufacturer, $slug];
    }

    /**
     * @return array{BlogPost,string}
     */
    private function seedBlogPost(): array
    {
        $post = BlogPost::query()->create([
            'code' => 'blog-'.strtolower((string) str()->random(6)),
            'is_active' => true,
            'published_at' => now()->subDay(),
            'sort_order' => 1,
        ]);

        $slug = 'blog-'.strtolower((string) str()->random(6));

        BlogPostTranslation::query()->create([
            'post_id' => $post->id,
            'locale' => 'en',
            'title' => 'Blog '.$slug,
            'slug' => $slug,
            'excerpt' => 'Blog excerpt',
            'body_html' => '<p>Blog body</p>',
        ]);

        return [$post, $slug];
    }

    /**
     * @return array{InfoPage,string}
     */
    private function seedInfoPage(): array
    {
        $page = InfoPage::query()->create([
            'code' => 'page-'.strtolower((string) str()->random(6)),
            'layout' => 'default',
            'is_active' => true,
            'published_at' => now()->subDay(),
            'sort_order' => 1,
        ]);

        $slug = 'page-'.strtolower((string) str()->random(6));

        InfoPageTranslation::query()->create([
            'page_id' => $page->id,
            'locale' => 'en',
            'title' => 'Page '.$slug,
            'slug' => $slug,
            'excerpt' => 'Page excerpt',
            'body_html' => '<p>Page body</p>',
        ]);

        return [$page, $slug];
    }
}
