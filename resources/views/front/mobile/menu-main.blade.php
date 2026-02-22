@php
    try {
        $catalogFeatures = app(\App\Services\Catalog\CatalogFeatureService::class);
        $showManufacturers = $catalogFeatures->useManufacturers();
        $showBlog = $catalogFeatures->useBlog();
        $loyaltyEnabled = (bool) app(\App\Services\Settings\SystemSettingsService::class)->get(
            'user_loyalty_enabled',
            (bool) config('user_features.flags.user_loyalty_enabled', true)
        );
    } catch (\Throwable $e) {
        $showManufacturers = (bool) config('catalog_features.flags.catalog_use_manufacturers', true);
        $showBlog = (bool) config('catalog_features.flags.catalog_use_blog', true);
        $loyaltyEnabled = (bool) config('user_features.flags.user_loyalty_enabled', true);
    }
@endphp

<div class="menu-header">
    <a href="/" class="menu-logo text-center">
        <span class="font-800 font-16">{{ config('app.name', 'AG Shop') }}</span>
    </a>
    <p class="text-center mt-2 mb-0 opacity-70 font-13">
        {{ __('ui.mobile.menu.subtitle') }}
    </p>
</div>

<div class="divider divider-margins mt-3 mb-3"></div>

<div class="list-group list-custom-small list-menu">
    <a href="{{ route('home') }}" class="close-menu">
        <i class="fa fa-home color-highlight"></i>
        <span>{{ __('ui.mobile.menu.home') }}</span>
        <i class="fa fa-angle-right"></i>
    </a>

    <a href="{{ route('home', ['frontend_variant' => 'desktop']) }}" class="close-menu">
        <i class="fa fa-globe color-blue-dark"></i>
        <span>{{ __('ui.mobile.menu.desktop_storefront') }}</span>
        <i class="fa fa-angle-right"></i>
    </a>

    <a href="{{ route('shop.index') }}" class="close-menu">
        <i class="fa fa-bag-shopping color-green-dark"></i>
        <span>{{ __('ui.mobile.menu.shop') }}</span>
        <i class="fa fa-angle-right"></i>
    </a>

    <a href="{{ route('categories.index') }}" class="close-menu">
        <i class="fa fa-th-large color-highlight"></i>
        <span>{{ __('ui.mobile.menu.categories') }}</span>
        <i class="fa fa-angle-right"></i>
    </a>

    @if ($showManufacturers)
        <a href="{{ route('manufacturers.index') }}" class="close-menu">
            <i class="fa fa-industry color-brown-dark"></i>
            <span>{{ __('ui.mobile.menu.manufacturers') }}</span>
            <i class="fa fa-angle-right"></i>
        </a>
    @endif

    @if ($showBlog)
        <a href="{{ route('blog.index') }}" class="close-menu">
            <i class="fa fa-newspaper color-blue-dark"></i>
            <span>{{ __('ui.mobile.menu.blog') }}</span>
            <i class="fa fa-angle-right"></i>
        </a>
    @endif

    <a href="{{ route('cart.index') }}" class="close-menu">
        <i class="fa fa-shopping-cart color-green-dark"></i>
        <span>{{ __('ui.mobile.menu.cart') }}</span>
        <i class="fa fa-angle-right"></i>
    </a>

    <a href="{{ route('wishlist.index') }}" class="close-menu">
        <i class="fa fa-heart color-red-dark"></i>
        <span>{{ __('ui.front.desktop.favorites') }}</span>
        <i class="fa fa-angle-right"></i>
    </a>

    <a href="{{ route('contact.create') }}" class="close-menu">
        <i class="fa fa-envelope color-orange-dark"></i>
        <span>{{ __('ui.mobile.menu.contact') }}</span>
        <i class="fa fa-angle-right"></i>
    </a>

    @auth
        <a href="{{ route('account.dashboard') }}" class="close-menu">
            <i class="fa fa-user color-highlight"></i>
            <span>{{ __('ui.mobile.menu.my_account') }}</span>
            <i class="fa fa-angle-right"></i>
        </a>

        <a href="{{ route('account.orders') }}" class="close-menu">
            <i class="fa fa-receipt color-blue-dark"></i>
            <span>{{ __('ui.mobile.menu.my_orders') }}</span>
            <i class="fa fa-angle-right"></i>
        </a>

        <a href="{{ route('account.profile') }}" class="close-menu">
            <i class="fa fa-gear color-gray-dark"></i>
            <span>{{ __('ui.mobile.menu.profile_settings') }}</span>
            <i class="fa fa-angle-right"></i>
        </a>

        @if ($loyaltyEnabled)
            <a href="{{ route('account.loyalty') }}" class="close-menu">
                <i class="fa fa-star color-yellow-dark"></i>
                <span>{{ __('ui.account.nav.loyalty') }}</span>
                <i class="fa fa-angle-right"></i>
            </a>
        @endif

        @if (auth()->user()->isA('superadmin') || auth()->user()->can('admin.access'))
            <a href="{{ route('admin.dashboard') }}" class="close-menu">
                <i class="fa fa-shield-halved color-gray-dark"></i>
                <span>{{ __('ui.mobile.menu.admin_dashboard') }}</span>
                <i class="fa fa-angle-right"></i>
            </a>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
            @csrf
            <button type="submit" class="list-group-item close-menu border-0 bg-transparent w-100 text-start">
                <i class="fa fa-arrow-right-from-bracket color-red-light"></i>
                <span>{{ __('ui.account.nav.logout') }}</span>
                <i class="fa fa-angle-right"></i>
            </button>
        </form>
    @else
        <a href="{{ route('front.auth.login') }}" class="close-menu">
            <i class="fa fa-right-to-bracket color-highlight"></i>
            <span>{{ __('ui.mobile.menu.login') }}</span>
            <i class="fa fa-angle-right"></i>
        </a>

        <a href="{{ route('front.auth.register') }}" class="close-menu">
            <i class="fa fa-user-plus color-highlight"></i>
            <span>{{ __('ui.mobile.menu.register') }}</span>
            <i class="fa fa-angle-right"></i>
        </a>
    @endauth
</div>

<div class="divider divider-margins mt-3 mb-3"></div>

<div class="content px-3">
    <a href="#" data-menu="menu-colors" class="btn btn-full rounded-s font-13 font-700 bg-highlight">
        {{ __('ui.mobile.menu.theme_colors') }}
    </a>
</div>
