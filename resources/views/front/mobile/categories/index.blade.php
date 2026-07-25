@extends('front.mobile.layouts.store')

@section('title', __('ui.category_index.page_title'))
@section('header_title', __('ui.category_index.page_title'))
@section('page_title', __('ui.category_index.page_title'))
@section('body_class', 'category-index-page')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/category-index.css') }}?v={{ filemtime(public_path('front-theme/styles/category-index.css')) }}">
@endpush

@section('content')
    <main class="category-index-mobile">
        <header class="category-index-mobile-header">
            <h1>{{ __('ui.category_index.page_title') }}</h1>
            <p>{{ $categories->count() }} {{ __('ui.cart.summary.total') }}</p>
        </header>

        <section class="category-index-section" aria-label="{{ __('ui.category_index.page_title') }}">
            @include('front.categories.index-grid')
        </section>
    </main>
@endsection
