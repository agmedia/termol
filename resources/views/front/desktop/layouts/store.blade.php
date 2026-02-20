<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'AG Shop').' '.__('ui.front.desktop.store'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $cartSummary = app(\App\Services\Front\CartService::class)->summary();
    $catalogFeatures = app(\App\Services\Catalog\CatalogFeatureService::class);
@endphp
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<header class="sticky top-0 z-40 bg-white">
    <div class="bg-black py-2 text-center text-xs font-semibold uppercase tracking-wide text-white">
        {{ __('ui.front.desktop.promo_bar') }}
    </div>

    <div class="border-b border-slate-200">
        <div class="flex w-full items-stretch justify-between pl-3 sm:pl-8">
            <a href="{{ route('home') }}" class="inline-flex items-center py-4 text-2xl font-black tracking-tight text-slate-900 sm:py-5 sm:text-4xl">
                AMDS
            </a>

            <nav class="hidden flex-1 items-center justify-center gap-8 px-6 text-sm font-semibold uppercase tracking-wide text-slate-900 xl:flex">
                <a href="{{ route('shop.index') }}" class="hover:text-slate-600">{{ __('ui.front.desktop.nav.new') }}</a>
                <a href="{{ route('shop.index') }}" class="hover:text-slate-600">{{ __('ui.front.desktop.nav.men') }}</a>
                <a href="{{ route('shop.index') }}" class="hover:text-slate-600">{{ __('ui.front.desktop.nav.women') }}</a>
                <a href="{{ route('shop.index') }}" class="hover:text-slate-600">{{ __('ui.front.desktop.nav.special') }}</a>
                @if ($catalogFeatures->useBlog())
                    <a href="{{ route('blog.index') }}" class="hover:text-slate-600">{{ __('ui.front.desktop.nav.blog') }}</a>
                @else
                    <a href="#" class="hover:text-slate-600">{{ __('ui.front.desktop.nav.blog') }}</a>
                @endif
                <a href="#" class="hover:text-slate-600">{{ __('ui.front.desktop.nav.stores') }}</a>
                <a href="#" class="hover:text-slate-600">{{ __('ui.front.desktop.nav.faq') }}</a>
                <a href="{{ route('contact.create') }}" class="hover:text-slate-600">{{ __('ui.front.desktop.nav.contact') }}</a>
            </nav>

            <div class="hidden min-h-[76px] items-stretch border-l border-slate-200 xl:flex">
                <div class="inline-flex w-[76px] items-center justify-center border-r border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-700">
                    @php
                        $activeLocale = (string) ($frontLocale ?? app()->getLocale());
                        $switchLanguage = collect($frontLanguages ?? [])->first(
                            static fn (array $language): bool => (string) ($language['code'] ?? '') !== $activeLocale
                        );
                    @endphp
                    @if ($switchLanguage)
                        <a
                            href="{{ route('front.locale.switch', ['code' => $switchLanguage['code']]) }}"
                            class="text-slate-500 hover:text-black"
                            hreflang="{{ $switchLanguage['code'] }}"
                        >
                            {{ strtoupper((string) $switchLanguage['code']) }}
                        </a>
                    @endif
                </div>

                <button type="button" class="inline-flex w-[76px] items-center justify-center border-r border-slate-200 text-slate-700 transition hover:bg-slate-50 hover:text-black" aria-label="{{ __('ui.front.desktop.search') }}" data-header-search-toggle>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.5-3.5"></path>
                    </svg>
                </button>

                @auth
                    <a href="{{ route('account.dashboard') }}" class="inline-flex w-[172px] items-center justify-center gap-2.5 border-r border-slate-200 px-0 text-sm text-slate-700 transition hover:bg-slate-50 hover:text-black">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 20c1.6-3.2 4.3-5 8-5s6.4 1.8 8 5"></path>
                        </svg>
                        {{ __('ui.front.desktop.account') }}
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex w-[172px] items-center justify-center gap-2.5 border-r border-slate-200 px-0 text-sm text-slate-700 transition hover:bg-slate-50 hover:text-black">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 20c1.6-3.2 4.3-5 8-5s6.4 1.8 8 5"></path>
                        </svg>
                        {{ __('ui.front.desktop.account') }}
                    </a>
                @endauth

                <a href="{{ route('wishlist.index') }}" class="relative inline-flex w-[76px] items-center justify-center border-r border-slate-200 text-slate-700 transition hover:bg-slate-50 hover:text-black" aria-label="{{ __('ui.front.desktop.favorites') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20.8 8.6c0 5.9-8.8 10.9-8.8 10.9S3.2 14.5 3.2 8.6a4.8 4.8 0 0 1 8.8-2.7 4.8 4.8 0 0 1 8.8 2.7Z"></path>
                    </svg>
                    <span class="absolute right-3 top-4 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-black px-1 text-xs font-bold text-white" data-wishlist-count>
                        {{ (int) ($wishlistSummary['item_count'] ?? 0) }}
                    </span>
                </a>

                <a href="{{ route('cart.index') }}" class="relative inline-flex w-[76px] items-center justify-center border-r border-slate-200 text-slate-900 transition hover:bg-slate-50 hover:text-black" aria-label="{{ __('ui.front.desktop.cart') }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 9h10l-1 10H8L7 9Z"></path>
                        <path d="M9 9V7a3 3 0 0 1 6 0v2"></path>
                    </svg>
                    <span class="absolute right-3 top-4 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-black px-1 text-xs font-bold text-white" data-cart-count>
                        {{ (int) $cartSummary['item_qty'] }}
                    </span>
                </a>
            </div>

            <div class="flex min-h-[68px] items-stretch border-l border-slate-200 xl:hidden">
                <button type="button" class="inline-flex w-12 items-center justify-center border-r border-slate-200 text-slate-700 transition hover:bg-slate-50 hover:text-black sm:w-14 lg:w-16" aria-label="{{ __('ui.front.desktop.search') }}" data-header-search-toggle>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.5-3.5"></path>
                    </svg>
                </button>

                @auth
                    <a href="{{ route('account.dashboard') }}" class="inline-flex w-12 items-center justify-center border-r border-slate-200 text-slate-700 transition hover:bg-slate-50 hover:text-black sm:w-14 lg:w-16" aria-label="{{ __('ui.front.desktop.account') }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 20c1.6-3.2 4.3-5 8-5s6.4 1.8 8 5"></path>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex w-12 items-center justify-center border-r border-slate-200 text-slate-700 transition hover:bg-slate-50 hover:text-black sm:w-14 lg:w-16" aria-label="{{ __('ui.front.desktop.sign_in') }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 20c1.6-3.2 4.3-5 8-5s6.4 1.8 8 5"></path>
                        </svg>
                    </a>
                @endauth

                <a href="{{ route('wishlist.index') }}" class="relative inline-flex w-12 items-center justify-center border-r border-slate-200 text-slate-700 transition hover:bg-slate-50 hover:text-black sm:w-14 lg:w-16" aria-label="{{ __('ui.front.desktop.favorites') }}">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20.8 8.6c0 5.9-8.8 10.9-8.8 10.9S3.2 14.5 3.2 8.6a4.8 4.8 0 0 1 8.8-2.7 4.8 4.8 0 0 1 8.8 2.7Z"></path>
                    </svg>
                    <span class="absolute right-0.5 top-2.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-black px-1 text-[10px] font-bold text-white" data-wishlist-count>
                        {{ (int) ($wishlistSummary['item_count'] ?? 0) }}
                    </span>
                </a>

                <a href="{{ route('cart.index') }}" class="relative inline-flex w-12 items-center justify-center border-r border-slate-200 text-slate-900 transition hover:bg-slate-50 hover:text-black sm:w-14 lg:w-16" aria-label="{{ __('ui.front.desktop.cart') }}">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 9h10l-1 10H8L7 9Z"></path>
                        <path d="M9 9V7a3 3 0 0 1 6 0v2"></path>
                    </svg>
                    <span class="absolute right-0.5 top-2.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-black px-1 text-[10px] font-bold text-white" data-cart-count>
                        {{ (int) $cartSummary['item_qty'] }}
                    </span>
                </a>

                <button type="button" class="flex h-full w-12 items-center justify-center border-r border-slate-200 bg-white text-slate-900 transition hover:bg-slate-50 sm:w-14 lg:w-16" aria-label="{{ __('ui.front.desktop.open_navigation') }}" data-mobile-menu-open>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M4 7h16M4 12h16M4 17h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</header>

<div class="max-h-0 overflow-hidden border-b border-slate-200 bg-white opacity-0 transition-all duration-300 pointer-events-none" data-header-search-panel>
    <div class="px-4 py-3 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('shop.index') }}" class="flex items-center gap-2">
            @foreach (['category', 'manufacturer', 'size', 'sort', 'cols'] as $queryKey)
                @if (request()->routeIs('shop.index') && request()->filled($queryKey))
                    <input type="hidden" name="{{ $queryKey }}" value="{{ (string) request()->query($queryKey) }}">
                @endif
            @endforeach
            <input
                type="search"
                name="q"
                value="{{ (string) request()->query('q', '') }}"
                placeholder="{{ __('ui.shop.filters.search_placeholder') }}"
                class="h-[42px] w-full border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none ring-0 focus:border-slate-500 focus:ring-1 focus:ring-slate-300/60"
                data-header-search-input
            >
            <button type="submit" class="inline-flex h-[42px] items-center justify-center border border-slate-900 bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-700">
                {{ __('ui.shop.filters.search') }}
            </button>
        </form>
    </div>
</div>

<div class="pointer-events-none fixed inset-0 z-[60] xl:hidden" data-mobile-menu-root>
    <button type="button" class="absolute inset-0 bg-black/45 opacity-0 transition-opacity duration-300" aria-label="{{ __('ui.front.desktop.close_navigation') }}" data-mobile-menu-close></button>
    <aside class="absolute inset-y-0 left-0 flex w-full -translate-x-full flex-col bg-white shadow-2xl transition-transform duration-300 ease-out" data-mobile-menu-panel>
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4">
            <span class="text-xl font-black tracking-tight text-slate-900">AMDS</span>
            <button type="button" class="inline-flex h-10 w-10 items-center justify-center border border-slate-200 text-slate-700 transition hover:bg-slate-50 hover:text-black" aria-label="{{ __('ui.front.desktop.close_navigation') }}" data-mobile-menu-close>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18"></path>
                </svg>
            </button>
        </div>
        <nav class="grid gap-1 overflow-y-auto p-4 text-sm font-semibold uppercase tracking-wide text-slate-900">
            <a href="{{ route('shop.index') }}" class="rounded-md px-3 py-3 hover:bg-slate-100">{{ __('ui.front.desktop.nav.new') }}</a>
            <a href="{{ route('shop.index') }}" class="rounded-md px-3 py-3 hover:bg-slate-100">{{ __('ui.front.desktop.nav.men') }}</a>
            <a href="{{ route('shop.index') }}" class="rounded-md px-3 py-3 hover:bg-slate-100">{{ __('ui.front.desktop.nav.women') }}</a>
            <a href="{{ route('shop.index') }}" class="rounded-md px-3 py-3 hover:bg-slate-100">{{ __('ui.front.desktop.nav.special') }}</a>
            @if ($catalogFeatures->useBlog())
                <a href="{{ route('blog.index') }}" class="rounded-md px-3 py-3 hover:bg-slate-100">{{ __('ui.front.desktop.nav.blog') }}</a>
            @else
                <a href="#" class="rounded-md px-3 py-3 hover:bg-slate-100">{{ __('ui.front.desktop.nav.blog') }}</a>
            @endif
            <a href="#" class="rounded-md px-3 py-3 hover:bg-slate-100">{{ __('ui.front.desktop.nav.stores') }}</a>
            <a href="#" class="rounded-md px-3 py-3 hover:bg-slate-100">{{ __('ui.front.desktop.nav.faq') }}</a>
            <a href="{{ route('contact.create') }}" class="rounded-md px-3 py-3 hover:bg-slate-100">{{ __('ui.front.desktop.nav.contact') }}</a>
        </nav>
    </aside>
</div>

<main class="@yield('main_class', 'mx-auto w-full max-w-7xl px-6 py-8')">
    @include('front.desktop.partials.flash')
    @yield('content')
</main>

<footer class="mt-20 border-t border-slate-200 bg-white">
    <div class="w-full px-4 py-10 sm:px-6 lg:px-8">
        <section class="border border-slate-200 px-7 py-5">
            <div class="grid gap-4 lg:grid-cols-[1.2fr_1fr] lg:items-center">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.2em] text-slate-500">{{ __('ui.front.desktop.newsletter.club') }}</p>
                    <h3 class="mt-1 text-xl font-bold leading-tight text-slate-900">{{ __('ui.front.desktop.newsletter.title') }}</h3>
                    <p class="mt-1 text-sm text-slate-600">{{ __('ui.front.desktop.newsletter.subtitle') }}</p>
                </div>
                <form action="#" method="post" class="grid gap-2.5 sm:grid-cols-[1fr_auto]">
                    <input type="email" placeholder="{{ __('ui.front.desktop.newsletter.placeholder') }}" class="h-11 border border-slate-300 bg-white px-4 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500">
                    <button type="button" class="h-11 border border-slate-300 bg-slate-100 px-5 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:bg-slate-200">
                        {{ __('ui.front.desktop.newsletter.button') }}
                    </button>
                    <label class="sm:col-span-2 flex items-start gap-2 text-[11px] text-slate-500">
                        <input type="checkbox" class="mt-0.5 h-4 w-4 border-slate-400 text-slate-700 focus:ring-slate-500">
                        {{ __('ui.front.desktop.newsletter.consent') }}
                    </label>
                </form>
            </div>
        </section>

        <div class="mt-8 grid gap-0 border border-slate-200 md:grid-cols-3">
            <div class="flex items-center gap-4 border-b border-slate-200 px-6 py-5 md:border-b-0 md:border-r">
                <span class="inline-flex h-10 w-10 items-center justify-center border border-slate-300 bg-slate-50 text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M3 7h14l4 5v5a2 2 0 0 1-2 2h-1"></path>
                        <path d="M3 7v10a2 2 0 0 0 2 2h1"></path>
                        <circle cx="8" cy="18" r="2"></circle>
                        <circle cx="17" cy="18" r="2"></circle>
                    </svg>
                </span>
                <p class="text-sm font-semibold text-slate-700">{{ __('ui.front.desktop.benefits.shipping') }}</p>
            </div>
            <div class="flex items-center gap-4 border-b border-slate-200 px-6 py-5 md:border-b-0 md:border-r">
                <span class="inline-flex h-10 w-10 items-center justify-center border border-slate-300 bg-slate-50 text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M9 14 4 9l5-5"></path>
                        <path d="M20 15a6 6 0 0 0-6-6H4"></path>
                    </svg>
                </span>
                <p class="text-sm font-semibold text-slate-700">{{ __('ui.front.desktop.benefits.returns') }}</p>
            </div>
            <div class="flex items-center gap-4 px-6 py-5">
                <span class="inline-flex h-10 w-10 items-center justify-center border border-slate-300 bg-slate-50 text-slate-700">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <rect x="3" y="11" width="18" height="10" rx="2"></rect>
                        <path d="M7 11V8a5 5 0 0 1 10 0v3"></path>
                    </svg>
                </span>
                <p class="text-sm font-semibold text-slate-700">{{ __('ui.front.desktop.benefits.secure') }}</p>
            </div>
        </div>

        <div class="mt-12 grid gap-12 border-b border-slate-200 pb-10 lg:grid-cols-[1fr_1fr_1fr_1.15fr]">
            <div class="space-y-5">
                <h3 class="text-sm font-extrabold uppercase tracking-[0.16em] text-slate-900">{{ __('ui.front.desktop.footer.shop') }}</h3>
                <ul class="space-y-2.5 text-sm text-slate-600">
                    <li><a href="{{ route('shop.index') }}" class="transition hover:text-slate-900">{{ __('ui.front.desktop.nav.new') }}</a></li>
                    <li><a href="{{ route('shop.index') }}" class="transition hover:text-slate-900">{{ __('ui.front.desktop.nav.men') }}</a></li>
                    <li><a href="{{ route('shop.index') }}" class="transition hover:text-slate-900">{{ __('ui.front.desktop.nav.women') }}</a></li>
                    <li><a href="{{ route('shop.index') }}" class="transition hover:text-slate-900">{{ __('ui.front.desktop.nav.special') }}</a></li>
                    <li><a href="{{ route('categories.index') }}" class="transition hover:text-slate-900">{{ __('ui.front.desktop.footer.all_categories') }}</a></li>
                </ul>
            </div>

            <div class="space-y-5">
                <h3 class="text-sm font-extrabold uppercase tracking-[0.16em] text-slate-900">{{ __('ui.front.desktop.footer.help') }}</h3>
                <ul class="space-y-2.5 text-sm text-slate-600">
                    <li><a href="#" class="transition hover:text-slate-900">{{ __('ui.front.desktop.footer.shipping_delivery') }}</a></li>
                    <li><a href="#" class="transition hover:text-slate-900">{{ __('ui.front.desktop.footer.returns_claims') }}</a></li>
                    <li><a href="#" class="transition hover:text-slate-900">{{ __('ui.front.desktop.footer.payment_methods') }}</a></li>
                    <li><a href="#" class="transition hover:text-slate-900">{{ __('ui.front.desktop.nav.faq') }}</a></li>
                    <li><a href="{{ route('contact.create') }}" class="transition hover:text-slate-900">{{ __('ui.front.desktop.nav.contact') }}</a></li>
                </ul>
            </div>

            <div class="space-y-5">
                <h3 class="text-sm font-extrabold uppercase tracking-[0.16em] text-slate-900">{{ __('ui.front.desktop.footer.info') }}</h3>
                <ul class="space-y-2.5 text-sm text-slate-600">
                    <li><a href="{{ route('home') }}" class="transition hover:text-slate-900">{{ __('ui.front.desktop.footer.home') }}</a></li>
                    @if ($catalogFeatures->useBlog())
                        <li><a href="{{ route('blog.index') }}" class="transition hover:text-slate-900">{{ __('ui.front.desktop.nav.blog') }}</a></li>
                    @else
                        <li><a href="#" class="transition hover:text-slate-900">{{ __('ui.front.desktop.nav.blog') }}</a></li>
                    @endif
                    <li><a href="#" class="transition hover:text-slate-900">{{ __('ui.front.desktop.footer.about') }}</a></li>
                    <li><a href="#" class="transition hover:text-slate-900">{{ __('ui.front.desktop.nav.stores') }}</a></li>
                    <li><a href="#" class="transition hover:text-slate-900">{{ __('ui.front.desktop.footer.career') }}</a></li>
                </ul>
            </div>

            <div class="space-y-5">
                <h3 class="text-sm font-extrabold uppercase tracking-[0.16em] text-slate-900">{{ __('ui.front.desktop.footer.support') }}</h3>
                <div class="space-y-2 text-sm text-slate-600">
                    <p class="text-slate-500">{{ __('ui.front.desktop.footer.webshop_queries') }}</p>
                    <p><a href="tel:+385916651808" class="text-xl font-medium text-slate-900 transition hover:text-slate-700">091 665 18 08</a></p>
                    <p><a href="mailto:webshop@amds.hr" class="transition hover:text-slate-900">webshop@amds.hr</a></p>
                    <p><a href="mailto:kontakt@amds.hr" class="transition hover:text-slate-900">kontakt@amds.hr</a></p>
                    <p>{{ __('ui.front.desktop.footer.work_hours') }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="#" aria-label="{{ __('ui.front.desktop.social.facebook') }}" class="inline-flex h-10 w-10 items-center justify-center border border-slate-300 bg-slate-50 text-slate-700 transition hover:border-slate-500 hover:text-slate-900">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M13.5 22v-8h2.7l.5-3h-3.2V9.1c0-.9.4-1.6 1.8-1.6H17V4.8c-.3 0-1.3-.2-2.5-.2-2.5 0-4.2 1.5-4.2 4.4V11H7.5v3h2.8v8h3.2Z"></path>
                        </svg>
                    </a>
                    <a href="#" aria-label="{{ __('ui.front.desktop.social.instagram') }}" class="inline-flex h-10 w-10 items-center justify-center border border-slate-300 bg-slate-50 text-slate-700 transition hover:border-slate-500 hover:text-slate-900">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="3.5" y="3.5" width="17" height="17" rx="5"></rect>
                            <circle cx="12" cy="12" r="4.2"></circle>
                            <circle cx="17.4" cy="6.6" r="1"></circle>
                        </svg>
                    </a>
                    <a href="#" aria-label="{{ __('ui.front.desktop.social.tiktok') }}" class="inline-flex h-10 w-10 items-center justify-center border border-slate-300 bg-slate-50 text-slate-700 transition hover:border-slate-500 hover:text-slate-900">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M14 4c.7 1.8 2 2.9 4 3.3V10a7.3 7.3 0 0 1-4-1.2v6.2a5 5 0 1 1-4.3-5V12a2.7 2.7 0 1 0 1.3 2.3V4H14Z"></path>
                        </svg>
                    </a>
                    <a href="#" aria-label="{{ __('ui.front.desktop.social.youtube') }}" class="inline-flex h-10 w-10 items-center justify-center border border-slate-300 bg-slate-50 text-slate-700 transition hover:border-slate-500 hover:text-slate-900">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                            <path d="M21.6 8.3a2.9 2.9 0 0 0-2-2A43.2 43.2 0 0 0 12 6a43.2 43.2 0 0 0-7.6.4 2.9 2.9 0 0 0-2 2A30 30 0 0 0 2 12a30 30 0 0 0 .4 3.7 2.9 2.9 0 0 0 2 2 43.2 43.2 0 0 0 7.6.4 43.2 43.2 0 0 0 7.6-.4 2.9 2.9 0 0 0 2-2A30 30 0 0 0 22 12a30 30 0 0 0-.4-3.7ZM10 15.3V8.7L16 12l-6 3.3Z"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-5 py-7 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="inline-flex h-10 items-center border border-slate-200 bg-slate-50 px-4 text-[11px] font-bold uppercase tracking-[0.14em] text-slate-700">WSPay</span>
                <span class="inline-flex h-10 items-center border border-slate-200 bg-slate-50 px-4">
                    <img src="{{ asset('assets/payments/visa-brand.svg') }}" alt="Visa" class="h-5 w-auto" loading="lazy">
                </span>
                <span class="inline-flex h-10 items-center border border-slate-200 bg-slate-50 px-4">
                    <img src="{{ asset('assets/payments/mastercard-brand.svg') }}" alt="Mastercard" class="h-5 w-auto" loading="lazy">
                </span>
                <span class="inline-flex h-10 items-center border border-slate-200 bg-slate-50 px-4">
                    <img src="{{ asset('assets/payments/diners-brand.svg') }}" alt="Diners Club" class="h-5 w-auto" loading="lazy">
                </span>
                <span class="inline-flex h-10 items-center border border-slate-200 bg-slate-50 px-4">
                    <img src="{{ asset('assets/payments/maestro-brand.svg') }}" alt="Maestro" class="h-5 w-auto" loading="lazy">
                </span>
            </div>
            <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-slate-500">
                <a href="#" class="transition hover:text-slate-900">{{ __('ui.front.desktop.footer.terms') }}</a>
                <a href="#" class="transition hover:text-slate-900">{{ __('ui.front.desktop.footer.privacy') }}</a>
                <a href="#" class="transition hover:text-slate-900">{{ __('ui.front.desktop.footer.cookies') }}</a>
                <a href="#" class="transition hover:text-slate-900">{{ __('ui.front.desktop.footer.shipping_returns') }}</a>
                <a href="#" class="transition hover:text-slate-900">{{ __('ui.front.desktop.footer.secure_checkout') }}</a>
            </div>
        </div>

        <div class="border-t border-slate-200 pt-5 text-xs text-slate-500">
            © {{ now()->year }} AMDS Jeans. {{ __('ui.front.desktop.footer.copyright') }}
        </div>
    </div>
</footer>

<script defer src="{{ asset('front-theme/scripts/desktop-header-menu.js') }}"></script>
<script defer src="{{ asset('front-theme/scripts/header-search-panel.js') }}"></script>
@stack('scripts')
</body>
</html>
