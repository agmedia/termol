<?php

namespace Tests\Feature\Front;

use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorefrontDeviceTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_desktop_user_agent_gets_desktop_storefront_template(): void
    {
        $response = $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            ])
            ->get('/');

        $response->assertOk();
        $response->assertSee('desktop-header-menu.js', false);
        $response->assertDontSee('front-theme/styles/bootstrap.css', false);
        $response->assertSee(
            asset('front-theme/styles/termol-overrides.css')
                .'?v='.filemtime(public_path('front-theme/styles/termol-overrides.css')),
            false
        );
        $response->assertSee('data-scroll-to-top', false);
        $response->assertSee('id="cookie-consent-floating-button"', false);
    }

    public function test_mobile_user_agent_uses_desktop_storefront_by_default(): void
    {
        $response = $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            ])
            ->get('/');

        $response->assertOk();
        $response->assertSee('desktop-header-menu.js', false);
        $response->assertDontSee('front-theme/styles/bootstrap.css', false);
        $response->assertDontSee('data-scroll-to-top', false);
        $response->assertDontSee('id="cookie-consent-floating-button"', false);
        $response->assertDontSee('front-theme/scripts/scroll-to-top.js', false);
    }

    public function test_responsive_desktop_header_keeps_search_visible_and_rotates_benefits(): void
    {
        $response = $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            ])
            ->get('/');

        $response->assertOk();
        $response->assertSee('class="responsive-header-brand', false);
        $response->assertSee('class="responsive-header-actions', false);
        $response->assertSee('data-header-search-persistent', false);
        $response->assertSee('data-header-search-suggestions-close', false);
        $response->assertSee('data-wishlist-always-visible', false);
        $response->assertSee('data-store-benefits-rotator', false);
        $response->assertSee('store-benefits-rotator.js', false);
    }

    public function test_mobile_user_agent_gets_mobile_storefront_template_when_mobile_view_is_enabled(): void
    {
        app(SystemSettingsService::class)->put('catalog_use_mobile_view', true);

        $mobileHeaders = [
            'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        ];
        $response = $this->withHeaders($mobileHeaders)->get('/');

        $response->assertOk();
        $response->assertSee('front-theme/styles/bootstrap.css', false);
        $response->assertDontSee('desktop-header-menu.js', false);
        $response->assertDontSee('id="cookie-consent-floating-button"', false);

        $shopResponse = $this->withHeaders($mobileHeaders)->get('/shop');

        $shopResponse->assertOk();
        $shopResponse->assertDontSee('data-scroll-to-top', false);
        $shopResponse->assertDontSee('id="cookie-consent-floating-button"', false);
    }

    public function test_desktop_home_header_uses_admin_store_logo_when_configured(): void
    {
        $logoPath = 'store-settings/front-logo.svg';

        app(SystemSettingsService::class)->putMany([
            'store_brand_name' => 'KZO',
            'store_brand_logo_path' => $logoPath,
        ]);

        $response = $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            ])
            ->get('/');

        $response->assertOk();
        $response->assertSee('src="'.Storage::disk('public')->url($logoPath).'"', false);
        $response->assertSee('alt="KZO"', false);
        $response->assertSee('data-store-brand-logo', false);
    }

    public function test_mobile_home_header_uses_admin_store_logo_when_configured(): void
    {
        $logoPath = 'store-settings/mobile-front-logo.svg';

        app(SystemSettingsService::class)->putMany([
            'catalog_use_mobile_view' => true,
            'store_brand_name' => 'KZO',
            'store_brand_logo_path' => $logoPath,
        ]);

        $response = $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            ])
            ->get('/');

        $response->assertOk();
        $response->assertSee('src="'.Storage::disk('public')->url($logoPath).'"', false);
        $response->assertSee('alt="KZO"', false);
        $response->assertSee('class="store-header-logo"', false);
    }

    public function test_storefront_responses_include_vary_user_agent_header(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertHeader('Vary');
        $this->assertStringContainsStringIgnoringCase('User-Agent', (string) $response->headers->get('Vary'));
    }

    public function test_brand_listing_uses_croatian_url_and_redirects_legacy_urls(): void
    {
        app(SystemSettingsService::class)->put('catalog_use_manufacturers', true);

        $this->get('/brendovi')->assertOk();
        $this->get('/brandovi')->assertStatus(301)->assertRedirect('/brendovi');
        $this->get('/manufacturers')->assertStatus(301)->assertRedirect('/brendovi');
    }

    public function test_mobile_user_agent_gets_desktop_template_when_mobile_view_feature_is_disabled(): void
    {
        app(SystemSettingsService::class)->put('catalog_use_mobile_view', false);

        $response = $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            ])
            ->get('/');

        $response->assertOk();
        $response->assertSee('desktop-header-menu.js', false);
        $response->assertDontSee('front-theme/styles/bootstrap.css', false);
    }
}
