<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Front\NavigationMenuService;
use App\Services\Front\StoreSettingsService;
use Illuminate\Http\Response;

class StorefrontStylesController extends Controller
{
    public function __invoke(
        NavigationMenuService $navigation,
        StoreSettingsService $storeSettings
    ): Response {
        $appearance = $navigation->appearance();
        $topBar = $navigation->topBar();
        $announcement = $storeSettings->announcement();

        $css = sprintf(
            ':root{--storefront-container-width:%dpx;--header-content-width:%dpx;--navigation-item-height:%dpx;--navigation-font-size:%dpx;--header-logo-height:%dpx;--navigation-background-color:%s;--navigation-text-color:%s;--navigation-highlight-color:%s;--top-bar-height:%dpx;--top-bar-font-size:%dpx;--top-bar-background-color:%s;--top-bar-text-color:%s;--top-bar-border-color:%s;--store-announcement-background-color:%s;--store-announcement-text-color:%s;--store-announcement-duration:%ds;}',
            $appearance['container_width'],
            $appearance['header_content_width'],
            $appearance['item_height'],
            $appearance['font_size'],
            $appearance['logo_height'],
            $appearance['background_color'],
            $appearance['text_color'],
            $appearance['highlight_color'],
            $topBar['height'],
            $topBar['font_size'],
            $topBar['background_color'],
            $topBar['text_color'],
            $topBar['border_color'],
            $announcement['background_color'],
            $announcement['text_color'],
            $announcement['scroll_duration_seconds'],
        );

        return response($css, 200, [
            'Content-Type' => 'text/css; charset=UTF-8',
            'Cache-Control' => 'private, no-cache, must-revalidate',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
