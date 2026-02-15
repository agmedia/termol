<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'AG Shop') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[#eef3f7] text-slate-800 antialiased">
        <div class="min-h-screen bg-[radial-gradient(circle_at_20%_0%,#d6efe7_0%,transparent_34%),radial-gradient(circle_at_90%_15%,#fbe2d0_0%,transparent_30%),radial-gradient(circle_at_50%_100%,#dbe9ff_0%,transparent_36%)]">
            <div class="sticky top-0 z-50">
                <div class="border-b border-cyan-800/20 bg-cyan-800 text-cyan-50">
                    <div class="mx-auto flex max-w-7xl items-center justify-between gap-3 px-4 py-2 text-xs font-medium sm:px-6 lg:px-8">
                        <p>Free shipping over 60 EUR • Same-day dispatch • 30-day returns</p>
                        <a href="#" class="rounded-full bg-white/20 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.15em] hover:bg-white/30">Limited Offer</a>
                    </div>
                </div>

                <header class="border-b border-slate-200/80 bg-white/95 backdrop-blur">
                    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                        <a href="/" class="text-lg font-bold tracking-tight text-cyan-900">AGSHOP</a>

                        <nav class="hidden items-center gap-7 text-sm font-medium md:flex">
                            <a href="#" class="text-slate-700 hover:text-cyan-800">Home</a>
                            <a href="#" class="text-slate-700 hover:text-cyan-800">Collection</a>
                            <a href="#" class="text-slate-700 hover:text-cyan-800">Essentials</a>
                            <a href="#" class="text-slate-700 hover:text-cyan-800">Journal</a>
                            <a href="#" class="text-slate-700 hover:text-cyan-800">Sale</a>
                        </nav>

                        <div class="flex items-center gap-2">
                            <button type="button" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">Cart 0</button>
                            @auth
                                <a href="{{ route('admin.dashboard') }}" class="rounded-xl bg-cyan-700 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-800">Admin</a>
                            @else
                                <a href="{{ route('login') }}" class="rounded-xl bg-cyan-700 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-800">Sign in</a>
                            @endauth
                        </div>
                    </div>
                </header>
            </div>

            <main class="mx-auto max-w-7xl px-4 pb-12 pt-10 sm:px-6 lg:px-8">
                <section class="grid gap-6 lg:grid-cols-12">
                    <article class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-lg shadow-slate-300/30 lg:col-span-8">
                        <div class="grid md:grid-cols-2">
                            <div class="bg-gradient-to-br from-cyan-900 via-cyan-800 to-teal-700 p-8 text-white sm:p-10">
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-cyan-100">Main Product Widget</p>
                                <h1 class="mt-4 text-3xl font-semibold leading-tight tracking-tight">Everyday Carry Pack</h1>
                                <p class="mt-4 text-sm text-cyan-50/95">Minimal profile, high capacity, water-resistant shell. Built for workdays and quick weekends.</p>
                                <div class="mt-6 flex items-center gap-3">
                                    <span class="text-2xl font-semibold">€89.00</span>
                                    <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold">In stock</span>
                                </div>
                                <div class="mt-8 flex gap-3">
                                    <a href="#" class="rounded-xl bg-white px-5 py-3 text-sm font-semibold text-cyan-900 hover:bg-cyan-50">Buy now</a>
                                    <a href="#" class="rounded-xl border border-cyan-300/50 px-5 py-3 text-sm font-semibold text-white hover:bg-cyan-700">Details</a>
                                </div>
                            </div>
                            <div class="bg-gradient-to-br from-slate-100 to-cyan-50 p-8">
                                <div class="mx-auto h-80 max-w-xs rounded-2xl border border-cyan-200/60 bg-gradient-to-br from-cyan-200 to-slate-200 shadow-md"></div>
                            </div>
                        </div>
                    </article>

                    <div class="grid gap-6 lg:col-span-4">
                        <article class="rounded-2xl border border-orange-200 bg-gradient-to-br from-orange-100 to-amber-100 p-6 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-orange-900">Promo Banner</p>
                            <h2 class="mt-2 text-xl font-semibold text-orange-950">Up to 35% off</h2>
                            <p class="mt-2 text-sm text-orange-900/80">Weekend pricing across selected bags and travel tools.</p>
                            <a href="#" class="mt-4 inline-block rounded-lg bg-orange-900 px-4 py-2 text-sm font-semibold text-orange-50 hover:bg-orange-950">Shop offer</a>
                        </article>

                        <article class="rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-100 to-cyan-100 p-6 shadow-sm">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-900">Store Message</p>
                            <h2 class="mt-2 text-xl font-semibold text-emerald-950">Designed for daily use</h2>
                            <p class="mt-2 text-sm text-emerald-900/80">Comfort-focused ergonomics with understated, premium finishing.</p>
                        </article>
                    </div>
                </section>

                <section class="mt-10">
                    <div class="mb-4 flex items-end justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-semibold tracking-tight text-slate-900">Content Blocks</h2>
                            <p class="mt-1 text-sm text-slate-600">Structured storefront sections (replacement for legacy widgets).</p>
                        </div>
                        <a href="{{ route('admin.dashboard') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Manage blocks</a>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-2">
                        <article class="rounded-2xl border border-cyan-200 bg-white p-6 shadow-sm">
                            <div class="flex items-start gap-4">
                                <div class="rounded-xl bg-cyan-100 p-3 text-cyan-800">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M4 8h16v10H4z" stroke="currentColor" stroke-width="1.8"/><path d="M8 8V6a4 4 0 0 1 8 0v2" stroke="currentColor" stroke-width="1.8"/></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">2-column Icon Message</p>
                                    <h3 class="mt-1 text-xl font-semibold text-slate-900">Gift-ready packaging</h3>
                                    <p class="mt-2 text-sm text-slate-600">Each order ships in premium recyclable packaging prepared for gifting.</p>
                                </div>
                            </div>
                        </article>

                        <article class="rounded-2xl border border-violet-200 bg-white p-6 shadow-sm">
                            <div class="flex items-start gap-4">
                                <div class="rounded-xl bg-violet-100 p-3 text-violet-700">
                                    <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none"><path d="M3 12h18" stroke="currentColor" stroke-width="1.8"/><path d="M7 7h10M7 17h6" stroke="currentColor" stroke-width="1.8"/></svg>
                                </div>
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">2-column Icon Message</p>
                                    <h3 class="mt-1 text-xl font-semibold text-slate-900">Weekly curated drops</h3>
                                    <p class="mt-2 text-sm text-slate-600">Fresh picks from best-selling categories every Friday.</p>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                @php
                    $blogs = [
                        ['tag' => 'Guide', 'title' => 'How to Choose Your Everyday Pack', 'excerpt' => 'Fit, capacity, and smart pocket planning explained in 5 minutes.'],
                        ['tag' => 'Journal', 'title' => 'Nylon vs Canvas Materials', 'excerpt' => 'Durability and care notes to help choose the right texture.'],
                        ['tag' => 'Tips', 'title' => 'Minimal 3-day Travel Setup', 'excerpt' => 'A simple framework to pack lighter without missing essentials.'],
                    ];
                @endphp

                <section class="mt-10">
                    <div class="mb-4 flex items-end justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-semibold tracking-tight text-slate-900">3-column Blog Widget</h2>
                            <p class="mt-1 text-sm text-slate-600">Image placeholder, meta with icon tone, and excerpt.</p>
                        </div>
                        <a href="#" class="text-sm font-semibold text-cyan-800 hover:text-cyan-900">Read all</a>
                    </div>

                    <div class="grid gap-5 lg:grid-cols-3">
                        @foreach ($blogs as $blog)
                            <article class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <div class="h-44 bg-gradient-to-br from-slate-300 via-slate-200 to-cyan-100"></div>
                                <div class="p-5">
                                    <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-cyan-800">
                                        {{ $blog['tag'] }}
                                    </div>
                                    <h3 class="text-lg font-semibold leading-tight text-slate-900">{{ $blog['title'] }}</h3>
                                    <p class="mt-2 text-sm text-slate-600">{{ $blog['excerpt'] }}</p>
                                    <a href="#" class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-cyan-800 hover:text-cyan-900">Read more</a>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="mt-10 rounded-3xl border border-slate-200 bg-gradient-to-r from-cyan-900 to-indigo-900 p-7 text-white shadow-lg">
                    <h2 class="text-2xl font-semibold tracking-tight">Newsletter + Early Access</h2>
                    <p class="mt-2 max-w-2xl text-sm text-cyan-50/90">Get launch alerts, member pricing, and curated recommendations each week.</p>
                    <form class="mt-5 flex flex-col gap-3 sm:flex-row">
                        <input type="email" placeholder="you@example.com" class="w-full rounded-xl border border-white/30 bg-white/95 px-4 py-3 text-sm text-slate-800 outline-none ring-cyan-200/40 focus:ring" />
                        <button type="button" class="rounded-xl bg-amber-300 px-5 py-3 text-sm font-semibold text-amber-950 hover:bg-amber-200">Subscribe</button>
                    </form>
                </section>
            </main>

            <footer class="mt-12 border-t border-slate-200 bg-slate-900 text-slate-300">
                <div class="mx-auto grid max-w-7xl grid-cols-2 gap-8 px-4 py-12 sm:px-6 lg:grid-cols-4 lg:px-8">
                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-100">Info</h3>
                        <ul class="mt-3 space-y-2 text-sm text-slate-400">
                            <li><a href="#" class="hover:text-white">About us</a></li>
                            <li><a href="#" class="hover:text-white">Shipping</a></li>
                            <li><a href="#" class="hover:text-white">Returns</a></li>
                            <li><a href="#" class="hover:text-white">Privacy</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-100">Shop</h3>
                        <ul class="mt-3 space-y-2 text-sm text-slate-400">
                            <li><a href="#" class="hover:text-white">New arrivals</a></li>
                            <li><a href="#" class="hover:text-white">Best sellers</a></li>
                            <li><a href="#" class="hover:text-white">Categories</a></li>
                            <li><a href="#" class="hover:text-white">Gift cards</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-100">Support</h3>
                        <ul class="mt-3 space-y-2 text-sm text-slate-400">
                            <li><a href="#" class="hover:text-white">FAQ</a></li>
                            <li><a href="#" class="hover:text-white">Contact</a></li>
                            <li><a href="#" class="hover:text-white">Terms</a></li>
                            <li><a href="#" class="hover:text-white">Track order</a></li>
                        </ul>
                    </div>

                    <div>
                        <h3 class="text-sm font-semibold uppercase tracking-[0.18em] text-slate-100">Trusted Payments</h3>
                        <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                            <span class="rounded-md bg-slate-800 px-2.5 py-1.5">VISA</span>
                            <span class="rounded-md bg-slate-800 px-2.5 py-1.5">Mastercard</span>
                            <span class="rounded-md bg-slate-800 px-2.5 py-1.5">PayPal</span>
                            <span class="rounded-md bg-slate-800 px-2.5 py-1.5">Apple Pay</span>
                            <span class="rounded-md bg-slate-800 px-2.5 py-1.5">Google Pay</span>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-800">
                    <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-2 px-4 py-4 text-xs text-slate-500 sm:px-6 md:flex-row lg:px-8">
                        <p>&copy; {{ now()->year }} {{ config('app.name', 'AG Shop') }}. All rights reserved.</p>
                        <p>Inspired by Tailwind storefront page structure.</p>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>
