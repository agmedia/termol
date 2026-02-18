@extends('front.desktop.layouts.store')

@php
    $translation = $category->translations->firstWhere('locale', $locale)
        ?? $category->translations->firstWhere('locale', $fallbackLocale);
@endphp

@section('title', ($translation?->name ?? 'Category').' Products')

@section('content')
    <section class="mb-8">
        <a href="{{ route('categories.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">← Back to categories</a>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-900">{{ $translation?->name ?? $category->code }}</h1>
        <p class="mt-2 max-w-3xl text-slate-600">{{ $translation?->description ?: 'Products assigned to this category.' }}</p>
    </section>

    @if ($topBlocks->isNotEmpty())
        <section class="mb-8">
            @include('components.content-placement', ['items' => $topBlocks])
        </section>
    @endif

    @if ($products->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">No products in this category yet.</div>
    @else
        <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($products as $product)
                @include('front.desktop.partials.product-card', ['product' => $product, 'locale' => $locale, 'fallbackLocale' => $fallbackLocale])
            @endforeach
        </div>

        <div class="mt-6">
            {{ $products->links() }}
        </div>
    @endif

    @if ($bottomBlocks->isNotEmpty())
        <section class="mt-10">
            @include('components.content-placement', ['items' => $bottomBlocks])
        </section>
    @endif
@endsection
