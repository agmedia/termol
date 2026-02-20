@php
    try {
        $catalogFeatures = app(\App\Services\Catalog\CatalogFeatureService::class);
        $showManufacturers = $catalogFeatures->useManufacturers();
        $showBlog = $catalogFeatures->useBlog();
    } catch (\Throwable $e) {
        $showManufacturers = (bool) config('catalog_features.flags.catalog_use_manufacturers', true);
        $showBlog = (bool) config('catalog_features.flags.catalog_use_blog', true);
    }
@endphp

<div class="menu-header">
    <a href="/" class="menu-logo text-center">
        <span class="font-800 font-16">{{ config('app.name', 'AG Shop') }}</span>
    </a>
    <p class="text-center mt-2 mb-0 opacity-70 font-13">
        Mobile starter navigation
    </p>
</div>

<div class="divider divider-margins mt-3 mb-3"></div>

<div class="list-group list-custom-small list-menu">
    <a href="{{ route('home') }}" class="close-menu">
        <i class="fa fa-home color-highlight"></i>
        <span>Home</span>
        <i class="fa fa-angle-right"></i>
    </a>

    <a href="{{ route('home', ['frontend_variant' => 'desktop']) }}" class="close-menu">
        <i class="fa fa-globe color-blue-dark"></i>
        <span>Desktop storefront</span>
        <i class="fa fa-angle-right"></i>
    </a>

    <a href="{{ route('shop.index') }}" class="close-menu">
        <i class="fa fa-bag-shopping color-green-dark"></i>
        <span>Shop</span>
        <i class="fa fa-angle-right"></i>
    </a>

    <a href="{{ route('categories.index') }}" class="close-menu">
        <i class="fa fa-th-large color-highlight"></i>
        <span>Categories</span>
        <i class="fa fa-angle-right"></i>
    </a>

    @if ($showManufacturers)
        <a href="{{ route('manufacturers.index') }}" class="close-menu">
            <i class="fa fa-industry color-brown-dark"></i>
            <span>Manufacturers</span>
            <i class="fa fa-angle-right"></i>
        </a>
    @endif

    @if ($showBlog)
        <a href="{{ route('blog.index') }}" class="close-menu">
            <i class="fa fa-newspaper color-blue-dark"></i>
            <span>Blog</span>
            <i class="fa fa-angle-right"></i>
        </a>
    @endif

    <a href="{{ route('cart.index') }}" class="close-menu">
        <i class="fa fa-shopping-cart color-green-dark"></i>
        <span>Cart</span>
        <i class="fa fa-angle-right"></i>
    </a>

    <a href="{{ route('wishlist.index') }}" class="close-menu">
        <i class="fa fa-heart color-red-dark"></i>
        <span>{{ __('ui.front.desktop.favorites') }}</span>
        <i class="fa fa-angle-right"></i>
    </a>

    <a href="{{ route('contact.create') }}" class="close-menu">
        <i class="fa fa-envelope color-orange-dark"></i>
        <span>Contact</span>
        <i class="fa fa-angle-right"></i>
    </a>

    @auth
        <a href="{{ route('account.dashboard') }}" class="close-menu">
            <i class="fa fa-user color-highlight"></i>
            <span>My account</span>
            <i class="fa fa-angle-right"></i>
        </a>

        <a href="{{ route('account.orders') }}" class="close-menu">
            <i class="fa fa-receipt color-blue-dark"></i>
            <span>My orders</span>
            <i class="fa fa-angle-right"></i>
        </a>

        <a href="{{ route('account.profile') }}" class="close-menu">
            <i class="fa fa-gear color-gray-dark"></i>
            <span>Profile settings</span>
            <i class="fa fa-angle-right"></i>
        </a>

        @if (auth()->user()->isA('superadmin') || auth()->user()->can('admin.access'))
            <a href="{{ route('admin.dashboard') }}" class="close-menu">
                <i class="fa fa-shield-halved color-gray-dark"></i>
                <span>Admin dashboard</span>
                <i class="fa fa-angle-right"></i>
            </a>
        @endif

        <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
            @csrf
            <button type="submit" class="list-group-item close-menu border-0 bg-transparent w-100 text-start">
                <i class="fa fa-arrow-right-from-bracket color-red-light"></i>
                <span>Logout</span>
                <i class="fa fa-angle-right"></i>
            </button>
        </form>
    @else
        <a href="{{ route('login') }}" class="close-menu">
            <i class="fa fa-right-to-bracket color-highlight"></i>
            <span>Login</span>
            <i class="fa fa-angle-right"></i>
        </a>

        <a href="{{ route('register') }}" class="close-menu">
            <i class="fa fa-user-plus color-highlight"></i>
            <span>Register</span>
            <i class="fa fa-angle-right"></i>
        </a>
    @endauth
</div>

<div class="divider divider-margins mt-3 mb-3"></div>

<div class="content px-3">
    <a href="#" data-menu="menu-colors" class="btn btn-full rounded-s font-13 font-700 bg-highlight">
        Theme colors
    </a>
</div>
