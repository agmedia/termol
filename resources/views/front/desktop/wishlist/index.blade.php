@extends('front.desktop.layouts.store')

@section('title', __('ui.wishlist.page_title'))
@section('main_class', 'w-full px-0 py-8')

@section('content')
    <section class="px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ __('ui.wishlist.title') }}</h1>
        <p class="mt-2 text-slate-600">{{ __('ui.wishlist.subtitle') }}</p>
    </section>

    <section class="px-4 py-6 sm:px-6 lg:px-8">
        @if ($products->isEmpty())
            <div class="border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">
                {{ __('ui.wishlist.empty') }}
            </div>
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
                @foreach ($products as $product)
                    @include('front.desktop.partials.product-card', ['product' => $product, 'locale' => $locale, 'fallbackLocale' => $fallbackLocale, 'flat' => true])
                @endforeach
            </div>
        @endif
    </section>
@endsection
