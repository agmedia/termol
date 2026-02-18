<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'AG Shop').' Store')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $cartSummary = app(\App\Services\Front\CartService::class)->summary();
    $catalogFeatures = app(\App\Services\Catalog\CatalogFeatureService::class);

    $footerPages = \App\Models\Content\Page\InfoPage::query()
        ->where('is_active', true)
        ->where('show_in_footer', true)
        ->where(function ($q): void {
            $q->whereNull('published_at')->orWhere('published_at', '<=', now());
        })
        ->with(['translations' => fn ($q) => $q->whereIn('locale', [app()->getLocale(), config('app.locale')])])
        ->orderBy('sort_order')
        ->orderBy('id')
        ->limit(8)
        ->get();
@endphp
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-4">
        <a href="{{ route('home') }}" class="text-xl font-extrabold tracking-tight text-slate-900">{{ config('app.name', 'AG Shop') }}</a>

        <nav class="hidden items-center gap-5 text-sm font-medium text-slate-600 md:flex">
            <a href="{{ route('shop.index') }}" class="{{ request()->routeIs('shop.*') ? 'text-blue-700' : 'hover:text-slate-900' }}">Shop</a>
            <a href="{{ route('categories.index') }}" class="{{ request()->routeIs('categories.*') ? 'text-blue-700' : 'hover:text-slate-900' }}">Categories</a>
            @if ($catalogFeatures->useManufacturers())
                <a href="{{ route('manufacturers.index') }}" class="{{ request()->routeIs('manufacturers.*') ? 'text-blue-700' : 'hover:text-slate-900' }}">Manufacturers</a>
            @endif
            @if ($catalogFeatures->useBlog())
                <a href="{{ route('blog.index') }}" class="{{ request()->routeIs('blog.*') ? 'text-blue-700' : 'hover:text-slate-900' }}">Blog</a>
            @endif
            <a href="{{ route('contact.create') }}" class="{{ request()->routeIs('contact.*') ? 'text-blue-700' : 'hover:text-slate-900' }}">Contact</a>
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ route('cart.index') }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                Cart
                <span class="rounded-full bg-slate-900 px-2 py-0.5 text-xs font-bold text-white">{{ $cartSummary['item_qty'] }}</span>
            </a>

            @auth
                <a href="{{ route('account.dashboard') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Account
                </a>
                @if (auth()->user()->isA('superadmin') || auth()->user()->can('admin.access'))
                    <a href="{{ route('admin.dashboard') }}" class="rounded-lg bg-blue-700 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                        Admin
                    </a>
                @endif
            @else
                <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Login</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-700">Register</a>
            @endauth
        </div>
    </div>
</header>

<main class="mx-auto w-full max-w-7xl px-6 py-8">
    @include('front.desktop.partials.flash')
    @yield('content')
</main>

<footer class="mt-10 border-t border-slate-200 bg-white">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-6 py-8 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
        <p>© {{ now()->year }} {{ config('app.name', 'AG Shop') }}. All rights reserved.</p>

        <div class="flex flex-wrap items-center gap-4">
            @foreach ($footerPages as $footerPage)
                @php
                    $translation = $footerPage->translations->firstWhere('locale', app()->getLocale())
                        ?? $footerPage->translations->firstWhere('locale', config('app.locale'));
                @endphp

                @if ($translation)
                    <a href="{{ route('pages.show', ['slug' => $translation->slug]) }}" class="hover:text-slate-900">{{ $translation->title }}</a>
                @endif
            @endforeach
        </div>
    </div>
</footer>

@stack('scripts')
</body>
</html>
