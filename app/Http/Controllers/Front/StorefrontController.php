<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StorefrontController extends Controller
{
    public function home(Request $request): View
    {
        $variant = (string) $request->attributes->get('frontend_variant', 'desktop');

        return view($variant === 'mobile' ? 'welcome-mobile' : 'front.desktop.home.index');
    }
}
