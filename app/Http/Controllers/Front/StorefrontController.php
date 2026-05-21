<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Front\StoreSettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function __construct(
        private readonly StoreSettingsService $storeSettingsService
    ) {
    }

    public function home(Request $request): View
    {
        $variant = (string) $request->attributes->get('frontend_variant', 'desktop');

        return view(
            $variant === 'mobile' ? 'welcome-mobile' : 'front.desktop.home.index',
            ['storeSettings' => $this->storeSettingsService->all()]
        );
    }
}
