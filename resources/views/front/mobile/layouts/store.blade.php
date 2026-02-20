<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>@yield('title', config('app.name', 'AG Shop').' Mobile')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <link rel="stylesheet" href="{{ asset('front-theme/styles/bootstrap.css') }}">
    <link rel="stylesheet" href="{{ asset('front-theme/styles/style.css') }}">
    <link rel="stylesheet" href="{{ asset('front-theme/styles/rising-sun-font.css') }}">
    <link rel="stylesheet" href="{{ asset('front-theme/fonts/css/fontawesome-all.min.css') }}">
    <link rel="manifest" href="{{ asset('front-theme/_manifest.json') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('front-theme/app/icons/icon-192x192.png') }}">
</head>
<body class="theme-light font-risingsun" data-highlight="highlight-red">
<div id="preloader"><div class="spinner-border color-highlight" role="status"></div></div>

<div id="page">
    <div class="header header-fixed header-logo-center header-auto-show">
        <a href="{{ route('home') }}" class="header-title">@yield('header_title', 'Store')</a>
        <a href="#" data-back-button class="header-icon header-icon-1" aria-label="Back"><i class="fas fa-chevron-left"></i></a>
        <a href="#" data-menu="menu-main" class="header-icon header-icon-4" aria-label="Menu"><i class="fas fa-bars"></i></a>
        <a href="#" data-toggle-theme class="header-icon header-icon-3 show-on-theme-dark" aria-label="Light mode"><i class="fas fa-sun"></i></a>
        <a href="#" data-toggle-theme class="header-icon header-icon-3 show-on-theme-light" aria-label="Dark mode"><i class="fas fa-moon"></i></a>
    </div>

    <div id="footer-bar" class="footer-bar-6">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active-nav' : '' }}"><i class="fa fa-home"></i><span>Home</span></a>
        <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.*') ? 'active-nav' : '' }}"><i class="fa fa-th-large"></i><span>Categories</span></a>
        <a href="{{ route('shop.index') }}" class="{{ request()->routeIs('shop.*') ? 'active-nav circle-nav' : 'circle-nav' }}"><i class="fa fa-star"></i><span>Shop</span></a>
        <a href="{{ route('cart.index') }}" class="{{ request()->routeIs('cart.*') ? 'active-nav' : '' }}"><i class="fa fa-bag-shopping"></i><span>Cart</span></a>
        <a href="{{ route('wishlist.index') }}" class="{{ request()->routeIs('wishlist.*') ? 'active-nav' : '' }}">
            <i class="fa fa-heart"></i>
            <span>{{ __('ui.front.desktop.favorites') }}</span>
            <u data-wishlist-count>{{ (int) ($wishlistSummary['item_count'] ?? 0) }}</u>
        </a>
        <a href="#" data-menu="menu-main"><i class="fa fa-bars"></i><span>Menu</span></a>
    </div>

    <div class="page-title page-title-fixed">
        <h1>@yield('page_title', 'Store')</h1>
        <a href="#" class="page-title-icon shadow-xl bg-theme color-theme" data-menu="menu-share" aria-label="Share"><i class="fa fa-share-alt"></i></a>
        <a href="#" class="page-title-icon shadow-xl bg-theme color-theme show-on-theme-light" data-toggle-theme aria-label="Dark mode"><i class="fa fa-moon"></i></a>
        <a href="#" class="page-title-icon shadow-xl bg-theme color-theme show-on-theme-dark" data-toggle-theme aria-label="Light mode"><i class="fa fa-lightbulb color-yellow-dark"></i></a>
        <a href="#" class="page-title-icon shadow-xl bg-theme color-theme" data-menu="menu-main" aria-label="Menu"><i class="fa fa-bars"></i></a>
    </div>
    <div class="page-title-clear"></div>

    <div class="page-content footer-bar-clear">
        @include('front.mobile.partials.flash')
        @yield('content')
        <div class="mb-5"></div>
    </div>

    <div id="menu-main" class="menu menu-box-left rounded-0" data-menu-width="280">
        @include('front.mobile.menu-main')
    </div>
    <div id="menu-colors" class="menu menu-box-bottom rounded-m" data-menu-load="/front-theme/menu-colors.html" data-menu-height="480"></div>
    <div id="menu-share" class="menu menu-box-bottom rounded-m" data-menu-load="/front-theme/menu-share.html" data-menu-height="370"></div>
    @stack('mobile-menus')
</div>

<script defer src="{{ asset('front-theme/scripts/bootstrap.min.js') }}"></script>
<script defer src="{{ asset('front-theme/scripts/custom.js') }}"></script>
</body>
</html>
