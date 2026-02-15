<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <title>{{ config('app.name', 'AG Shop') }} • Mobile</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 text-slate-900 antialiased">
        <div class="min-h-screen bg-[radial-gradient(circle_at_20%_0%,#d9efe9_0%,transparent_44%),radial-gradient(circle_at_80%_10%,#f6e7d9_0%,transparent_40%)]">
            <header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/95 backdrop-blur">
                <div class="mx-auto flex max-w-md items-center justify-between px-4 py-3">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-cyan-700">Mobile Storefront</p>
                        <h1 class="text-base font-bold tracking-tight text-cyan-900">AGSHOP</h1>
                    </div>
                    @auth
                        <a href="{{ route('admin.dashboard') }}" class="rounded-lg bg-cyan-700 px-3 py-2 text-xs font-semibold text-white">Admin</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-lg bg-cyan-700 px-3 py-2 text-xs font-semibold text-white">Sign in</a>
                    @endauth
                </div>
            </header>

            <main class="mx-auto max-w-md space-y-4 px-4 pb-24 pt-4">
                <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="bg-gradient-to-br from-cyan-900 via-cyan-800 to-teal-700 px-5 py-6 text-white">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-cyan-100">Hero</p>
                        <h2 class="mt-2 text-2xl font-semibold">Everyday Carry Pack</h2>
                        <p class="mt-2 text-sm text-cyan-50/95">Lightweight, durable, and built for daily movement.</p>
                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-xl font-bold">€89.00</span>
                            <button type="button" class="rounded-lg bg-white px-3 py-2 text-xs font-semibold text-cyan-900">Buy now</button>
                        </div>
                    </div>
                    <div class="h-28 bg-gradient-to-r from-cyan-100 to-slate-100"></div>
                </section>

                <section class="grid grid-cols-2 gap-3">
                    <article class="rounded-xl border border-orange-200 bg-orange-100/80 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-orange-900">Promo</p>
                        <p class="mt-1 text-sm font-semibold text-orange-950">Up to 35% off</p>
                    </article>
                    <article class="rounded-xl border border-emerald-200 bg-emerald-100/80 p-3">
                        <p class="text-[10px] font-semibold uppercase tracking-[0.12em] text-emerald-900">Message</p>
                        <p class="mt-1 text-sm font-semibold text-emerald-950">Daily essentials</p>
                    </article>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-600">Content Blocks</h3>
                        <a href="{{ route('admin.dashboard') }}" class="text-xs font-semibold text-cyan-800">Manage</a>
                    </div>
                    <div class="mt-3 space-y-3">
                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-xs font-semibold text-slate-900">2-card message block</p>
                            <p class="mt-1 text-xs text-slate-600">For key trust messages and fast highlights.</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-xs font-semibold text-slate-900">Products carousel block</p>
                            <p class="mt-1 text-xs text-slate-600">Swipe-ready cards for featured products.</p>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <p class="text-xs font-semibold text-slate-900">Blog grid block</p>
                            <p class="mt-1 text-xs text-slate-600">Three editorial cards with excerpts.</p>
                        </div>
                    </div>
                </section>
            </main>

            <nav class="fixed bottom-0 left-0 right-0 border-t border-slate-200 bg-white/95 backdrop-blur">
                <div class="mx-auto grid max-w-md grid-cols-4 px-2 py-2 text-center text-[11px] font-semibold text-slate-600">
                    <a href="#" class="rounded-md px-2 py-2 text-cyan-800">Home</a>
                    <a href="#" class="rounded-md px-2 py-2">Shop</a>
                    <a href="#" class="rounded-md px-2 py-2">Blog</a>
                    <a href="#" class="rounded-md px-2 py-2">Cart</a>
                </div>
            </nav>
        </div>
    </body>
</html>

