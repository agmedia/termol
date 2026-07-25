<?php

namespace Tests\Feature\Front;

use App\Models\Content\ContentBlock;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class HomeFullWidthImageSliderTest extends TestCase
{
    use RefreshDatabase;

    public function test_desktop_home_slider_uses_the_wide_storefront_frame(): void
    {
        $block = ContentBlock::query()->create([
            'code' => 'home-main-slider',
            'name' => 'Home Wide Slider Test',
            'type' => 'full_width_image_slider',
            'is_active' => true,
            'payload' => null,
        ]);

        $block->translations()->create([
            'locale' => 'hr',
            'title' => null,
            'subtitle' => null,
            'body_html' => null,
            'cta_label' => null,
            'cta_url' => null,
            'payload' => null,
        ]);

        $block->slots()->create([
            'placement' => 'home.hero',
            'frontend_variant' => 'desktop',
            'target_type' => null,
            'target_ref' => null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $block->slots()->create([
            'placement' => 'home.hero',
            'frontend_variant' => 'mobile',
            'target_type' => null,
            'target_ref' => null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $image = UploadedFile::fake()->image('home-slide.jpg', 1920, 820);
        $block
            ->addMedia($image->getPathname())
            ->usingName('Home slide')
            ->usingFileName('home-slide.jpg')
            ->withCustomProperties([
                'button_label' => ['hr' => 'Pogledajte ponudu'],
                'link_url' => ['hr' => '/shop'],
                'link_url_value' => '/shop',
            ])
            ->toMediaCollection('block_slides');

        $mobileImage = UploadedFile::fake()->image('home-slide-mobile.jpg', 1080, 1080);
        $block
            ->addMedia($mobileImage->getPathname())
            ->usingName('Home slide mobile')
            ->usingFileName('home-slide-mobile.jpg')
            ->toMediaCollection('block_slides_mobile');

        $sliderId = 'full-width-slider-'.$block->id;

        $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            ])
            ->get('/')
            ->assertOk()
            ->assertSee('id="'.$sliderId.'-shell"', false)
            ->assertSee('class="full-width-image-slider-shell', false)
            ->assertSee('sizes="(max-width: 1860px) 100vw, 1860px"', false)
            ->assertSee('media="(max-width: 768px)"', false)
            ->assertSee('sizes="100vw"', false)
            ->assertSee('home-slide-mobile', false)
            ->assertSee('class="hero-slide-cta"', false)
            ->assertSee('Pogledajte ponudu')
            ->assertSee('bottom: 6.75rem;', false)
            ->assertSee('@media (min-width: 1440px) and (max-width: 1599px)', false)
            ->assertSee('height: calc(100dvh - 223px);', false)
            ->assertSee('data-fullwidth-splide', false);

        app(SystemSettingsService::class)->put('catalog_use_mobile_view', true);

        $this
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            ])
            ->get('/')
            ->assertOk()
            ->assertSee('id="'.$sliderId.'-shell"', false)
            ->assertSee('aspect-ratio: 1 / 1;', false)
            ->assertSee('home-slide-mobile', false);
    }
}
