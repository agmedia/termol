@extends('front.desktop.layouts.store')

@section('title', config('app.name', 'AG Shop').' Store')

@section('content')
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="grid lg:grid-cols-2">
            <div class="p-10 lg:p-14">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">New Collection</p>
                <h1 class="mt-4 text-4xl font-extrabold tracking-tight text-slate-900 lg:text-5xl">
                    Clean fashion essentials for every day
                </h1>
                <p class="mt-5 max-w-xl text-base text-slate-600">
                    Discover curated styles, premium materials and timeless silhouettes.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('shop.index') }}" class="inline-flex items-center rounded-lg bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-700">
                        Shop now
                    </a>
                    <a href="{{ route('categories.index') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        Browse categories
                    </a>
                </div>
            </div>

            <div class="bg-gradient-to-br from-amber-100 via-orange-100 to-rose-100"></div>
        </div>
    </section>
@endsection
