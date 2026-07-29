@extends('front.mobile.layouts.store')

@section('title', __('B2B brza kupnja'))
@section('header_title', __('B2B'))
@section('page_title', __('Brza kupnja'))
@section('body_class', 'mobile-commerce-body mobile-account-commerce-body')

@push('head')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
    <link rel="stylesheet" href="{{ asset('front-theme/styles/b2b-quick-order.css') }}?v={{ filemtime(public_path('front-theme/styles/b2b-quick-order.css')) }}">
@endpush

@section('content')
    <div class="card card-style">
        <div class="content">
            <p class="font-11 font-700 text-uppercase color-highlight mb-1">{{ $b2bAccount->company_name }}</p>
            <h3 class="mb-1">{{ __('B2B brza kupnja') }}</h3>
            <p class="mb-3">{{ __('Pretražujte po nazivu, šifri, SKU-u ili barkodu i odaberite artikl iz rezultata.') }}</p>
        </div>

        @include('front.shared.account.b2b-quick-order-form')
    </div>
@endsection

@push('scripts')
    <script defer src="{{ asset('front-theme/scripts/b2b-quick-order.js') }}?v={{ filemtime(public_path('front-theme/scripts/b2b-quick-order.js')) }}"></script>
@endpush
