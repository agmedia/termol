<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('front.partials.seo-meta')
    @include('front.partials.schema-markup')
    @include('front.partials.analytics')
    <link rel="preload" href="{{ asset('front-theme/styles/rising-sun-font.css') }}?v={{ filemtime(public_path('front-theme/styles/rising-sun-font.css')) }}" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{{ asset('front-theme/styles/rising-sun-font.css') }}?v={{ filemtime(public_path('front-theme/styles/rising-sun-font.css')) }}"></noscript>
    @if (!empty($storeSettings['branding']['favicons']['ico_url'] ?? null))
        <link rel="icon" href="{{ $storeSettings['branding']['favicons']['ico_url'] }}" sizes="any">
    @endif
    @if (!empty($storeSettings['branding']['favicons']['32_url'] ?? null))
        <link rel="icon" type="image/png" sizes="32x32" href="{{ $storeSettings['branding']['favicons']['32_url'] }}">
    @endif
    @if (!empty($storeSettings['branding']['favicons']['16_url'] ?? null))
        <link rel="icon" type="image/png" sizes="16x16" href="{{ $storeSettings['branding']['favicons']['16_url'] }}">
    @endif
    @if (!empty($storeSettings['branding']['favicons']['180_url'] ?? null))
        <link rel="apple-touch-icon" sizes="180x180" href="{{ $storeSettings['branding']['favicons']['180_url'] }}">
    @endif
    @if (!empty($storeSettings['branding']['favicons']['192_url'] ?? null))
        <link rel="icon" type="image/png" sizes="192x192" href="{{ $storeSettings['branding']['favicons']['192_url'] }}">
    @endif
    @if (!empty($storeSettings['branding']['favicons']['512_url'] ?? null))
        <link rel="icon" type="image/png" sizes="512x512" href="{{ $storeSettings['branding']['favicons']['512_url'] }}">
    @endif
    @if (empty($storeSettings['branding']['favicons']['ico_url'] ?? null) && !empty($storeSettings['branding']['favicon_url'] ?? null))
        <link rel="icon" href="{{ $storeSettings['branding']['favicon_url'] }}">
    @endif
    @include('front.partials.cookie-consent-head')
    <link rel="manifest" href="{{ route('front.manifest') }}">
    @stack('head')
    @vite(['resources/css/app.css'])
</head>
@php
    $cartSummary = app(\App\Services\Front\CartService::class)->summary();
    $wishlistCount = (int) ($wishlistSummary['item_count'] ?? 0);
    $mainNavigation = app(\App\Services\Front\NavigationMenuService::class)->forLocale((string) app()->getLocale());
@endphp
<body class="font-risingsun min-h-screen overflow-x-hidden bg-white text-slate-900 antialiased">
@if ((bool) ($storeSettings['announcement']['enabled'] ?? true))
    <div class="bg-black py-2 text-center text-xs font-semibold uppercase tracking-wide text-white">
        @php
            $announcementText = (string) ($storeSettings['announcement']['text'] ?? __('ui.front.desktop.promo_bar'));
            $announcementUrl = trim((string) ($storeSettings['announcement']['url'] ?? ''));
            $announcementNewTab = (bool) ($storeSettings['announcement']['new_tab'] ?? false);
        @endphp
        @if ($announcementUrl !== '')
            <a href="{{ $announcementUrl }}" class="hover:underline" @if($announcementNewTab) target="_blank" rel="noopener noreferrer" @endif>
                {{ $announcementText }}
            </a>
        @else
            {{ $announcementText }}
        @endif
    </div>
@endif

<style>
    @media (min-width: 1024px) {
        .site-main-header-row {
            height: 80px;
            transition: height .2s ease;
        }

        .site-main-logo {
            height: 60px;
            transition: height .2s ease;
        }

        .site-main-header.is-sticky .site-main-header-row {
            height: 60px;
        }

        .site-main-header.is-sticky .site-main-logo {
            height: 50px;
        }
    }

    .front-soft-hero {
        position: relative;
        background: #fff;
    }

    .front-soft-hero::before {
        content: none;
    }

    .front-soft-hero::after {
        content: "";
        position: absolute;
        left: 0;
        right: 0;
        bottom: 0;
        height: 1px;
        background: rgba(15, 23, 42, 0.08);
        pointer-events: none;
    }

    .front-soft-hero > * {
        position: relative;
        z-index: 1;
    }

    [data-header-search-panel] {
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
        transition: max-height .22s ease, opacity .18s ease;
    }

    [data-header-search-panel].is-open {
        max-height: 5.75rem;
        overflow: visible;
        opacity: 1;
        pointer-events: auto;
    }

    .header-search-form {
        position: relative;
    }

    .header-search-suggestions {
        position: absolute;
        left: 0;
        right: 0;
        top: calc(100% + 0.65rem);
        z-index: 70;
        overflow: hidden;
        border: 1px solid #d9e1ea;
        background: #fff;
        box-shadow: 0 22px 40px rgba(15, 23, 42, .14);
    }

    .header-search-suggestions[hidden] {
        display: none !important;
    }

    .header-search-suggestions-meta {
        padding: .8rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
        font-size: .88rem;
        font-weight: 700;
        color: #0f172a;
    }

    .header-search-suggestions-list {
        max-height: min(60vh, 27rem);
        overflow: auto;
    }

    .header-search-suggestion {
        display: grid;
        grid-template-columns: 3.5rem minmax(0, 1fr) auto;
        align-items: center;
        gap: .9rem;
        width: 100%;
        padding: .8rem 1rem;
        border-bottom: 1px solid #eef2f7;
        background: #fff;
        text-align: left;
        transition: background-color .14s ease;
    }

    .header-search-suggestion:last-child {
        border-bottom: 0;
    }

    .header-search-suggestion:hover,
    .header-search-suggestion.is-active {
        background: #f8fafc;
    }

    .header-search-suggestion-thumb {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 3.5rem;
        height: 4.25rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        overflow: hidden;
    }

    .header-search-suggestion-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .header-search-suggestion-copy {
        min-width: 0;
    }

    .header-search-suggestion-title {
        display: block;
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.3;
        color: #0f172a;
        word-break: break-word;
    }

    .header-search-suggestion-sku {
        display: block;
        margin-top: .2rem;
        font-size: .8rem;
        color: #64748b;
    }

    .header-search-suggestion-prices {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: .2rem;
        white-space: nowrap;
        text-align: right;
    }

    .header-search-suggestion-price {
        font-size: .95rem;
        font-weight: 700;
        color: #0f172a;
    }

    .header-search-suggestion-old-price {
        font-size: .8rem;
        color: #94a3b8;
        text-decoration: line-through;
    }

    .header-search-suggestions-empty,
    .header-search-suggestions-loading {
        padding: 1rem;
        font-size: .9rem;
        color: #334155;
    }

    .header-search-suggestions-footer {
        border-top: 1px solid #e2e8f0;
        background: #fff;
    }

    .header-search-suggestions-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        padding: .9rem 1rem;
        font-size: .85rem;
        font-weight: 700;
        color: #0f172a;
        transition: background-color .14s ease, color .14s ease;
    }

    .header-search-suggestions-link:hover {
        background: #f8fafc;
        color: #334155;
    }

    @media (max-width: 1023px) {
        [data-header-search-panel].is-open {
            max-height: 5.25rem;
        }

        .header-search-suggestions {
            top: calc(100% + 0.55rem);
            left: -0.25rem;
            right: -0.25rem;
        }

        .header-search-suggestion {
            grid-template-columns: 3rem minmax(0, 1fr);
            gap: .75rem;
            padding: .75rem;
        }

        .header-search-suggestion-thumb {
            width: 3rem;
            height: 3.8rem;
        }

        .header-search-suggestion-prices {
            grid-column: 2;
            align-items: flex-start;
            text-align: left;
        }
    }
</style>

<header class="site-main-header sticky top-0 z-40 bg-white">
    <div class="border-b border-slate-200 bg-white">
        <div class="site-main-header-row flex h-[60px] w-full items-stretch justify-between pl-2 pr-0 sm:pl-4 sm:pr-0 lg:pl-5 lg:pr-0 xl:pl-5 xl:pr-0">
            <a href="{{ route('home') }}" class="inline-flex h-full items-center text-2xl font-black tracking-tight text-slate-900 sm:text-4xl">
                @if (!empty($storeSettings['branding']['logo_url'] ?? null))
                    <img src="{{ $storeSettings['branding']['logo_url'] }}" alt="{{ $storeSettings['branding']['store_name'] ?? config('app.name', 'AG Shop') }}" class="site-main-logo h-10 w-auto object-contain" width="176" height="44">
                @else
                    {{ (string) ($storeSettings['branding']['store_name'] ?? config('app.name', 'AG Shop')) }}
                @endif
            </a>

            <nav class="relative hidden flex-1 items-center justify-center gap-3 px-3 text-[13px] font-semibold uppercase tracking-wide text-slate-900 lg:flex xl:gap-6 xl:px-5 xl:text-sm">
                @include('front.desktop.partials.main-nav')
            </nav>

            <div class="hidden h-full items-stretch border-l border-slate-200 lg:flex">
                @php
                    $activeLocale = (string) ($frontLocale ?? app()->getLocale());
                    $switchLanguage = collect($frontLanguages ?? [])->first(
                        static fn (array $language): bool => (string) ($language['code'] ?? '') !== $activeLocale
                    );
                @endphp
                @if ($switchLanguage)
                    <div class="inline-flex w-[76px] items-center justify-center border-r border-slate-200 text-xs font-semibold uppercase tracking-wide text-slate-700">
                        <a
                            href="{{ route('front.locale.switch', ['code' => $switchLanguage['code']]) }}"
                            class="text-slate-500 hover:text-black"
                            hreflang="{{ $switchLanguage['code'] }}"
                        >
                            {{ strtoupper((string) $switchLanguage['code']) }}
                        </a>
                    </div>
                @endif

                <button type="button" class="inline-flex w-[76px] items-center justify-center border-r border-slate-200 text-slate-700 transition hover:bg-slate-50 hover:text-black" aria-label="{{ __('ui.front.desktop.search') }}" data-header-search-toggle>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m20 20-3.5-3.5"></path>
                    </svg>
                </button>

                @auth
                    <a href="{{ route('account.dashboard') }}" class="inline-flex min-w-[136px] items-center justify-center gap-2 border-r border-slate-200 px-4 text-sm text-slate-700 transition hover:bg-slate-50 hover:text-black">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 20c1.6-3.2 4.3-5 8-5s6.4 1.8 8 5"></path>
                        </svg>
                        {{ __('ui.front.desktop.account') }}
                    </a>
                @else
                    <a href="{{ route('front.auth.login') }}" class="inline-flex min-w-[136px] items-center justify-center gap-2 border-r border-slate-200 px-4 text-sm text-slate-700 transition hover:bg-slate-50 hover:text-black">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 20c1.6-3.2 4.3-5 8-5s6.4 1.8 8 5"></path>
                        </svg>
                        {{ __('ui.front.desktop.account') }}
                    </a>
                @endauth

                <a href="{{ route('wishlist.index') }}" class="relative inline-flex h-full w-[76px] items-center justify-center border-r border-slate-200 text-slate-700 transition hover:bg-slate-50 hover:text-black {{ $wishlistCount > 0 ? '' : 'hidden' }}" aria-label="{{ __('ui.front.desktop.favorites') }}" data-wishlist-link>
                    <svg class="block h-5 w-5 text-current" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20.8 8.6c0 5.9-8.8 10.9-8.8 10.9S3.2 14.5 3.2 8.6a4.8 4.8 0 0 1 8.8-2.7 4.8 4.8 0 0 1 8.8 2.7Z"></path>
                    </svg>
                    <span class="absolute right-3 top-4 z-10 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-black px-1 text-xs font-bold text-white" data-wishlist-count>
                        {{ $wishlistCount }}
                    </span>
                </a>

                <a href="{{ route('cart.index') }}" class="relative inline-flex h-full w-[76px] items-center justify-center border-r border-slate-200 text-slate-900 transition hover:bg-slate-50 hover:text-black" aria-label="{{ __('ui.front.desktop.cart') }}">
                    <svg class="block h-6 w-6 text-current" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 9h10l-1 10H8L7 9Z"></path>
                        <path d="M9 9V7a3 3 0 0 1 6 0v2"></path>
                    </svg>
                    <span class="absolute right-3 top-4 z-10 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-black px-1 text-xs font-bold text-white" data-cart-count>
                        {{ (int) $cartSummary['item_qty'] }}
                    </span>
                </a>
            </div>

            <div class="flex h-full items-stretch border-l border-slate-200 lg:hidden">
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
                    <a href="{{ route('front.auth.login') }}" class="inline-flex w-12 items-center justify-center border-r border-slate-200 text-slate-700 transition hover:bg-slate-50 hover:text-black sm:w-14 lg:w-16" aria-label="{{ __('ui.front.desktop.sign_in') }}">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="12" cy="8" r="4"></circle>
                            <path d="M4 20c1.6-3.2 4.3-5 8-5s6.4 1.8 8 5"></path>
                        </svg>
                    </a>
                @endauth

                <a href="{{ route('wishlist.index') }}" class="relative inline-flex w-12 items-center justify-center border-r border-slate-200 text-slate-700 transition hover:bg-slate-50 hover:text-black sm:w-14 lg:w-16 {{ $wishlistCount > 0 ? '' : 'hidden' }}" aria-label="{{ __('ui.front.desktop.favorites') }}" data-wishlist-link>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M20.8 8.6c0 5.9-8.8 10.9-8.8 10.9S3.2 14.5 3.2 8.6a4.8 4.8 0 0 1 8.8-2.7 4.8 4.8 0 0 1 8.8 2.7Z"></path>
                    </svg>
                    <span class="absolute right-0.5 top-2.5 inline-flex h-4 min-w-4 items-center justify-center rounded-full bg-black px-1 text-[10px] font-bold text-white" data-wishlist-count>
                        {{ $wishlistCount }}
                    </span>
                </a>

                <a href="{{ route('cart.index') }}" class="relative inline-flex h-full w-12 items-center justify-center border-r border-slate-200 text-slate-900 transition hover:bg-slate-50 hover:text-black sm:w-14 lg:w-16" aria-label="{{ __('ui.front.desktop.cart') }}">
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

@php
    $searchAutocompleteEnabled = (bool) app(\App\Services\Settings\SystemSettingsService::class)->get('store_search_autocomplete_enabled', false);
@endphp
<div class="border-b border-slate-200 bg-white" data-header-search-panel>
    <div class="px-4 py-3 sm:px-6 lg:px-8">
        <form
            method="GET"
            action="{{ route('shop.index') }}"
            class="header-search-form flex items-center gap-2"
            autocomplete="off"
            data-header-search-form
            data-autocomplete-enabled="{{ $searchAutocompleteEnabled ? '1' : '0' }}"
            data-autocomplete-endpoint="{{ $searchAutocompleteEnabled ? route('search.autocomplete') : '' }}"
            data-autocomplete-results-label="{{ __('ui.shop.search_autocomplete.results', ['count' => '__COUNT__']) }}"
            data-autocomplete-empty-label="{{ __('ui.shop.search_autocomplete.no_results', ['query' => '__QUERY__']) }}"
            data-autocomplete-loading-label="{{ __('ui.shop.search_autocomplete.searching') }}"
            data-autocomplete-view-all-label="{{ __('ui.shop.search_autocomplete.view_all') }}"
        >
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
                autocomplete="off"
                autocapitalize="none"
                autocorrect="off"
                spellcheck="false"
                data-header-search-input
            >
            <button type="submit" class="inline-flex h-[42px] items-center justify-center border border-slate-900 bg-slate-900 px-4 text-sm font-semibold text-white hover:bg-slate-700">
                {{ __('ui.shop.filters.search') }}
            </button>
            <div class="header-search-suggestions" data-header-search-suggestions hidden>
                <div class="header-search-suggestions-meta" data-header-search-suggestions-meta></div>
                <div class="header-search-suggestions-loading" data-header-search-loading hidden></div>
                <div class="header-search-suggestions-empty" data-header-search-empty hidden></div>
                <div class="header-search-suggestions-list" data-header-search-suggestions-list></div>
                <div class="header-search-suggestions-footer" data-header-search-footer hidden>
                    <a href="{{ route('shop.index') }}" class="header-search-suggestions-link" data-header-search-view-all></a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="pointer-events-none fixed inset-0 z-[60] lg:hidden" data-mobile-menu-root>
    <button type="button" class="absolute inset-0 bg-black/45 opacity-0 transition-opacity duration-300" aria-label="{{ __('ui.front.desktop.close_navigation') }}" data-mobile-menu-close></button>
    <aside class="absolute inset-y-0 left-0 flex w-[90vw] max-w-md -translate-x-full flex-col bg-white shadow-2xl transition-transform duration-300 ease-out" data-mobile-menu-panel>
        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-4">
            <span class="text-xl font-black tracking-tight text-slate-900">{{ (string) ($storeSettings['branding']['store_name'] ?? config('app.name', 'AG Shop')) }}</span>
            <button type="button" class="inline-flex h-10 w-10 items-center justify-center border border-slate-200 text-slate-700 transition hover:bg-slate-50 hover:text-black" aria-label="{{ __('ui.front.desktop.close_navigation') }}" data-mobile-menu-close>
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 6l12 12M18 6L6 18"></path>
                </svg>
            </button>
        </div>
        @include('front.desktop.partials.main-nav-mobile')
    </aside>
</div>

<main class="@yield('main_class', 'mx-auto w-full max-w-7xl px-6 py-8')">
    @include('front.desktop.partials.flash')
    @yield('content')
</main>

@include('front.partials.analytics-ecommerce')

<footer class="{{ request()->routeIs('home') ? 'mt-0' : 'mt-5' }} border-t border-slate-200 bg-white">
    <div class="mx-auto w-full max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <section class="px-0 py-5">
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

        @php
            $footerColumnsRaw = collect($storeSettings['footer']['link_columns'] ?? [])->take(3)->values();
            $footerColumns = collect([1, 2, 3])->map(function (int $index) use ($footerColumnsRaw) {
                $defaultTitle = match ($index) {
                    1 => (string) __('ui.front.desktop.footer.shop'),
                    2 => (string) __('ui.front.desktop.footer.help'),
                    default => (string) __('ui.front.desktop.footer.info'),
                };
                $row = $footerColumnsRaw->get($index - 1);
                $title = trim((string) (is_array($row) ? ($row['title'] ?? '') : '')) ?: $defaultTitle;
                $links = collect(is_array($row) ? ($row['links'] ?? []) : [])
                    ->filter(fn ($link) => is_array($link) && trim((string) ($link['url'] ?? '')) !== '')
                    ->map(fn ($link) => [
                        'label' => (string) ($link['label'] ?? ''),
                        'url' => (string) ($link['url'] ?? '#'),
                    ])
                    ->filter(fn (array $link) => trim($link['label']) !== '')
                    ->values()
                    ->all();

                return ['title' => $title, 'links' => $links];
            })->values();
        @endphp

        <div class="mt-10 border-y border-slate-200 lg:hidden">
            @foreach ($footerColumns as $column)
                <details class="group border-b border-slate-200">
                    <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-4 text-base font-semibold text-slate-900">
                        {{ $column['title'] }}
                        <span class="inline-flex h-6 w-6 items-center justify-center text-[21px] font-light leading-none text-slate-500 group-open:hidden">+</span>
                        <span class="hidden h-6 w-6 items-center justify-center text-[21px] font-light leading-none text-slate-500 group-open:inline-flex">−</span>
                    </summary>
                    <ul class="space-y-2.5 px-4 pb-4 text-sm text-slate-600">
                        @foreach ($column['links'] as $link)
                            <li><a href="{{ $link['url'] }}" class="transition hover:text-slate-900">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </details>
            @endforeach

            <details class="group">
                <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-4 text-base font-semibold text-slate-900">
                    {{ __('ui.front.desktop.footer.support') }}
                    <span class="inline-flex h-6 w-6 items-center justify-center text-[21px] font-light leading-none text-slate-500 group-open:hidden">+</span>
                    <span class="hidden h-6 w-6 items-center justify-center text-[21px] font-light leading-none text-slate-500 group-open:inline-flex">−</span>
                </summary>
                <div class="space-y-2 px-4 pb-4 text-sm text-slate-600">
                    <p class="text-slate-500">{{ __('ui.front.desktop.footer.webshop_queries') }}</p>
                    @if (!empty($storeSettings['footer']['phone'] ?? ''))
                        <p><a href="tel:{{ preg_replace('/\\s+/', '', (string) $storeSettings['footer']['phone']) }}" class="text-base font-medium text-slate-900 transition hover:text-slate-700">{{ $storeSettings['footer']['phone'] }}</a></p>
                    @endif
                    @if (!empty($storeSettings['footer']['email_sales'] ?? ''))
                        <p><a href="mailto:{{ $storeSettings['footer']['email_sales'] }}" class="transition hover:text-slate-900">{{ $storeSettings['footer']['email_sales'] }}</a></p>
                    @endif
                    @if (!empty($storeSettings['footer']['email_support'] ?? ''))
                        <p><a href="mailto:{{ $storeSettings['footer']['email_support'] }}" class="transition hover:text-slate-900">{{ $storeSettings['footer']['email_support'] }}</a></p>
                    @endif
                    <p>{{ (string) ($storeSettings['footer']['hours'] ?? __('ui.front.desktop.footer.work_hours')) }}</p>
                </div>
            </details>
        </div>

        <div class="mt-12 hidden gap-12 border-b border-slate-200 pb-10 lg:grid lg:grid-cols-[1fr_1fr_1fr_1.15fr]">
            @foreach ($footerColumns as $column)
                <div class="space-y-5">
                    <h3 class="text-sm font-extrabold uppercase tracking-[0.16em] text-slate-900">{{ $column['title'] }}</h3>
                    <ul class="space-y-2.5 text-sm text-slate-600">
                        @foreach ($column['links'] as $link)
                            <li><a href="{{ $link['url'] }}" class="transition hover:text-slate-900">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="space-y-5">
                <h3 class="text-sm font-extrabold uppercase tracking-[0.16em] text-slate-900">{{ __('ui.front.desktop.footer.support') }}</h3>
                <div class="space-y-2 text-sm text-slate-600">
                    <p class="text-slate-500">{{ __('ui.front.desktop.footer.webshop_queries') }}</p>
                    @if (!empty($storeSettings['footer']['phone'] ?? ''))
                        <p><a href="tel:{{ preg_replace('/\\s+/', '', (string) $storeSettings['footer']['phone']) }}" class="text-xl font-medium text-slate-900 transition hover:text-slate-700">{{ $storeSettings['footer']['phone'] }}</a></p>
                    @endif
                    @if (!empty($storeSettings['footer']['email_sales'] ?? ''))
                        <p><a href="mailto:{{ $storeSettings['footer']['email_sales'] }}" class="transition hover:text-slate-900">{{ $storeSettings['footer']['email_sales'] }}</a></p>
                    @endif
                    @if (!empty($storeSettings['footer']['email_support'] ?? ''))
                        <p><a href="mailto:{{ $storeSettings['footer']['email_support'] }}" class="transition hover:text-slate-900">{{ $storeSettings['footer']['email_support'] }}</a></p>
                    @endif
                    <p>{{ (string) ($storeSettings['footer']['hours'] ?? __('ui.front.desktop.footer.work_hours')) }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @if (!empty($storeSettings['branding']['social']['facebook']['url'] ?? '') && (bool) ($storeSettings['branding']['social']['facebook']['enabled'] ?? true))
                        <a href="{{ (string) $storeSettings['branding']['social']['facebook']['url'] }}" aria-label="{{ __('ui.front.desktop.social.facebook') }}" class="inline-flex h-10 w-10 items-center justify-center border border-slate-300 bg-slate-50 text-slate-700 transition hover:border-slate-500 hover:text-slate-900" target="_blank" rel="noopener noreferrer">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M13.5 22v-8h2.7l.5-3h-3.2V9.1c0-.9.4-1.6 1.8-1.6H17V4.8c-.3 0-1.3-.2-2.5-.2-2.5 0-4.2 1.5-4.2 4.4V11H7.5v3h2.8v8h3.2Z"></path>
                            </svg>
                        </a>
                    @endif
                    @if (!empty($storeSettings['branding']['social']['instagram']['url'] ?? '') && (bool) ($storeSettings['branding']['social']['instagram']['enabled'] ?? true))
                        <a href="{{ (string) $storeSettings['branding']['social']['instagram']['url'] }}" aria-label="{{ __('ui.front.desktop.social.instagram') }}" class="inline-flex h-10 w-10 items-center justify-center border border-slate-300 bg-slate-50 text-slate-700 transition hover:border-slate-500 hover:text-slate-900" target="_blank" rel="noopener noreferrer">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <rect x="3.5" y="3.5" width="17" height="17" rx="5"></rect>
                                <circle cx="12" cy="12" r="4.2"></circle>
                                <circle cx="17.4" cy="6.6" r="1"></circle>
                            </svg>
                        </a>
                    @endif
                    @if (!empty($storeSettings['branding']['social']['tiktok']['url'] ?? '') && (bool) ($storeSettings['branding']['social']['tiktok']['enabled'] ?? true))
                        <a href="{{ (string) $storeSettings['branding']['social']['tiktok']['url'] }}" aria-label="{{ __('ui.front.desktop.social.tiktok') }}" class="inline-flex h-10 w-10 items-center justify-center border border-slate-300 bg-slate-50 text-slate-700 transition hover:border-slate-500 hover:text-slate-900" target="_blank" rel="noopener noreferrer">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M14 4c.7 1.8 2 2.9 4 3.3V10a7.3 7.3 0 0 1-4-1.2v6.2a5 5 0 1 1-4.3-5V12a2.7 2.7 0 1 0 1.3 2.3V4H14Z"></path>
                            </svg>
                        </a>
                    @endif
                    @if (!empty($storeSettings['branding']['social']['youtube']['url'] ?? '') && (bool) ($storeSettings['branding']['social']['youtube']['enabled'] ?? true))
                        <a href="{{ (string) $storeSettings['branding']['social']['youtube']['url'] }}" aria-label="{{ __('ui.front.desktop.social.youtube') }}" class="inline-flex h-10 w-10 items-center justify-center border border-slate-300 bg-slate-50 text-slate-700 transition hover:border-slate-500 hover:text-slate-900" target="_blank" rel="noopener noreferrer">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M21.6 8.3a2.9 2.9 0 0 0-2-2A43.2 43.2 0 0 0 12 6a43.2 43.2 0 0 0-7.6.4 2.9 2.9 0 0 0-2 2A30 30 0 0 0 2 12a30 30 0 0 0 .4 3.7 2.9 2.9 0 0 0 2 2 43.2 43.2 0 0 0 7.6.4 43.2 43.2 0 0 0 7.6-.4 2.9 2.9 0 0 0 2-2A30 30 0 0 0 22 12a30 30 0 0 0-.4-3.7ZM10 15.3V8.7L16 12l-6 3.3Z"></path>
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="py-7">
            <div class="flex flex-wrap items-center justify-center gap-2.5">
                <span class="inline-flex h-12 w-28 items-center justify-center px-1">
                    <img src="{{ asset('assets/payments/corvus-logo.svg') }}" alt="Corvus Pay" class="block h-6 w-auto object-contain" loading="lazy" width="116" height="24">
                </span>
                <span class="inline-flex h-12 w-28 items-center justify-center px-1">
                    <img src="{{ asset('assets/payments/visa-brand.svg') }}" alt="Visa" class="block h-6 w-auto object-contain" loading="lazy" width="74" height="24">
                </span>
                <span class="inline-flex h-12 w-28 items-center justify-center px-1">
                    <img src="{{ asset('assets/payments/mastercard-brand.svg') }}" alt="Mastercard" class="block h-6 w-auto object-contain" loading="lazy" width="31" height="24">
                </span>
                <span class="inline-flex h-12 w-28 items-center justify-center px-1">
                    <img src="{{ asset('assets/payments/diners-brand.svg') }}" alt="Diners Club" class="block h-6 w-auto object-contain" loading="lazy" width="93" height="24">
                </span>
                <span class="inline-flex h-12 w-28 items-center justify-center px-1">
                    <img src="{{ asset('assets/payments/maestro-brand.svg') }}" alt="Maestro" class="block h-6 w-auto object-contain" loading="lazy" width="31" height="24">
                </span>
                <span class="inline-flex h-12 w-28 items-center justify-center px-1">
                    <img src="{{ asset('assets/payments/applepay.svg') }}" alt="Apple Pay" class="block h-6 w-auto object-contain" loading="lazy" width="38" height="24">
                </span>
                <span class="inline-flex h-12 w-28 items-center justify-center px-1">
                    <img src="{{ asset('assets/payments/googlepay.svg') }}" alt="Google Pay" class="block h-6 w-auto object-contain" loading="lazy" width="45" height="24">
                </span>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-slate-200 pt-5 text-xs text-slate-500 lg:flex-row lg:items-center lg:justify-between">
            <div>
                @php
                    $copyrightText = trim((string) ($storeSettings['footer']['bottom_copyright_text'] ?? ''));
                    if ($copyrightText === '') {
                        $copyrightText = (string) __('ui.front.desktop.footer.copyright');
                    }
                    $storeName = (string) ($storeSettings['branding']['store_name'] ?? config('app.name', 'AG Shop'));
                    $bottomLinks = collect($storeSettings['footer']['bottom_links'] ?? [])
                        ->filter(fn ($link) => is_array($link) && trim((string) ($link['url'] ?? '')) !== '' && trim((string) ($link['label'] ?? '')) !== '')
                        ->map(fn ($link) => ['label' => (string) $link['label'], 'url' => (string) $link['url']])
                        ->values()
                        ->all();
                    if ($bottomLinks === []) {
                        $bottomLinks = [

                            ['label' => (string) __('ui.front.desktop.footer.agmedia'), 'url' => 'https://www.agmedia.hr'],
                        ];
                    }
                @endphp
                © {{ now()->year }} {{ $storeName }}. {{ $copyrightText }}
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                @foreach ($bottomLinks as $link)
                    <a href="{{ $link['url'] }}" class="transition hover:text-slate-900">{{ $link['label'] }}</a>
                @endforeach
            </div>
        </div>
    </div>
</footer>

<script>
    (function () {
        const mainHeader = document.querySelector('.site-main-header');
        const headerRow = mainHeader ? mainHeader.querySelector('.site-main-header-row') : null;
        if (mainHeader) {
            let syncFrame = 0;

            const syncHeaderBottom = function () {
                const rect = mainHeader.getBoundingClientRect();
                const bottom = Math.max(0, Math.round(rect.bottom));
                document.documentElement.style.setProperty('--site-header-bottom', bottom + 'px');
            };

            const syncDuringTransition = function (durationMs) {
                const duration = Math.max(0, durationMs || 0);
                const startedAt = performance.now();

                if (syncFrame) {
                    cancelAnimationFrame(syncFrame);
                    syncFrame = 0;
                }

                const tick = function (now) {
                    syncHeaderBottom();
                    if (now - startedAt < duration) {
                        syncFrame = requestAnimationFrame(tick);
                    } else {
                        syncFrame = 0;
                    }
                };

                syncFrame = requestAnimationFrame(tick);
            };

            const updateHeaderState = function () {
                const nextSticky = window.scrollY > 0;
                const changed = mainHeader.classList.contains('is-sticky') !== nextSticky;
                mainHeader.classList.toggle('is-sticky', nextSticky);

                syncHeaderBottom();
                if (changed) {
                    // Keep dropdown top aligned while header height animates (90px -> 60px and back).
                    syncDuringTransition(260);
                }
            };

            updateHeaderState();
            window.addEventListener('scroll', updateHeaderState, { passive: true });
            window.addEventListener('resize', updateHeaderState);
            window.addEventListener('orientationchange', updateHeaderState);

            if (headerRow) {
                headerRow.addEventListener('transitionrun', function (event) {
                    if (event.propertyName === 'height') {
                        syncDuringTransition(260);
                    }
                });
                headerRow.addEventListener('transitionend', function (event) {
                    if (event.propertyName === 'height') {
                        syncHeaderBottom();
                    }
                });
            }
        }

        const onLoadScripts = [];

        onLoadScripts.push(@json(asset('front-theme/scripts/desktop-header-menu.js').'?v='.filemtime(public_path('front-theme/scripts/desktop-header-menu.js'))));
        onLoadScripts.push(@json(asset('front-theme/scripts/header-search-panel.js').'?v='.filemtime(public_path('front-theme/scripts/header-search-panel.js'))));
        onLoadScripts.push(@json(asset('front-theme/scripts/wishlist-toggle.js').'?v='.filemtime(public_path('front-theme/scripts/wishlist-toggle.js'))));

        function loadScript(src, module) {
            const s = document.createElement('script');
            s.src = src;
            if (module) {
                s.type = 'module';
            } else {
                s.defer = true;
            }
            document.body.appendChild(s);
        }

        let scriptsLoaded = false;
        function loadOnReady() {
            if (scriptsLoaded) {
                return;
            }
            scriptsLoaded = true;
            onLoadScripts.forEach(function (src) {
                loadScript(src, false);
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', loadOnReady, { once: true });
        } else {
            loadOnReady();
        }
        window.addEventListener('pageshow', loadOnReady);
    })();
</script>
@include('front.partials.cookie-consent')
@stack('scripts')
</body>
</html>
