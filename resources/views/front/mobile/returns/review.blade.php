@extends('front.mobile.layouts.store')

@section('title', __('return_request.review.heading'))
@section('header_title', __('return_request.page_title'))
@section('page_title', __('return_request.review.heading'))
@section('body_class', 'mobile-commerce-body mobile-returns-commerce-body')

@push('head')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
@endpush

@section('content')
    <div class="card card-style bg-blue-light">
        <div class="content">
            <p class="font-12 color-highlight mb-n1">{{ __('return_request.review.eyebrow') }}</p>
            <h3 class="mb-2">{{ __('return_request.review.heading') }}</h3>
            <p class="mb-0 font-13">{{ __('return_request.review.subheading') }}</p>
        </div>
    </div>

    <div class="card card-style">
        <div class="content">
            <h4>{{ __('return_request.review.declaration_title') }}</h4>
            <p class="font-13 font-600 line-height-l">{{ $declaration }}</p>
            <div class="divider mt-3 mb-3"></div>
            @foreach ([
                __('return_request.form.full_name') => $withdrawal['full_name'],
                __('return_request.form.email') => $withdrawal['email'],
                __('return_request.form.phone') => $withdrawal['phone'],
                __('return_request.mail.address') => $withdrawal['address_line'].', '.$withdrawal['postal_code'].' '.$withdrawal['city'].', '.$withdrawal['country_code'],
                __('return_request.form.order_number') => $withdrawal['order_number'],
                __('return_request.form.contract_date') => $withdrawal['contract_date'],
                __('return_request.form.received_date') => $withdrawal['received_date'],
            ] as $label => $value)
                <p class="font-12 mb-2"><strong>{{ $label }}:</strong><br>{{ $value !== '' ? $value : __('return_request.review.not_provided') }}</p>
            @endforeach
            <p class="font-12 mb-2"><strong>{{ __('return_request.form.items') }}:</strong><br><span class="white-space-pre-line">{{ $withdrawal['items'] }}</span></p>
            <p class="font-12 mb-0"><strong>{{ __('return_request.form.note') }}:</strong><br>{{ $withdrawal['note'] !== '' ? $withdrawal['note'] : __('return_request.review.not_provided') }}</p>
        </div>
    </div>

    <div class="card card-style bg-yellow-light">
        <div class="content"><p class="font-13 mb-0">{{ __('return_request.review.confirmation_notice') }}</p></div>
    </div>

    <div class="card card-style">
        <div class="content">
            <form method="POST" action="{{ route('returns.store', ['returnRequestSlug' => __('return_request.slug')]) }}">
                @csrf
                <input type="hidden" name="draft_token" value="{{ $draftToken }}">
                <button type="submit" class="commerce-primary-action btn btn-full font-600" onclick="this.disabled=true; this.form.submit();">
                    {{ __('return_request.review.confirm') }}
                </button>
            </form>
            <a href="{{ route('returns.create', ['returnRequestSlug' => __('return_request.slug')]) }}" class="btn btn-full border border-gray-dark color-gray-dark mt-3">
                {{ __('return_request.review.edit') }}
            </a>
        </div>
    </div>
@endsection
