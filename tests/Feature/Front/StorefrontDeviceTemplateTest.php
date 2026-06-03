<?php

namespace Tests\Feature\Front;

use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    }

    public function test_mobile_user_agent_gets_mobile_storefront_template_when_mobile_view_is_enabled(): void
    {
        app(SystemSettingsService::class)->put('catalog_use_mobile_view', true);

        $response = $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            ])
            ->get('/');

        $response->assertOk();
        $response->assertSee('front-theme/styles/bootstrap.css', false);
        $response->assertDontSee('desktop-header-menu.js', false);
    }

    public function test_desktop_home_header_uses_admin_store_logo_when_configured(): void
    {
        app(SystemSettingsService::class)->putMany([
            'store_brand_name' => 'KZO',
            'store_brand_logo_path' => 'store-settings/front-logo.svg',
        ]);

        $response = $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            ])
            ->get('/');

        $response->assertOk();
        $response->assertSee('src="http://localhost/storage/store-settings/front-logo.svg"', false);
        $response->assertSee('alt="KZO"', false);
        $response->assertSee('data-store-brand-logo', false);
    }

    public function test_mobile_home_header_uses_admin_store_logo_when_configured(): void
    {
        app(SystemSettingsService::class)->putMany([
            'catalog_use_mobile_view' => true,
            'store_brand_name' => 'KZO',
            'store_brand_logo_path' => 'store-settings/mobile-front-logo.svg',
        ]);

        $response = $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            ])
            ->get('/');

        $response->assertOk();
        $response->assertSee('src="http://localhost/storage/store-settings/mobile-front-logo.svg"', false);
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
