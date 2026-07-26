@extends('front.mobile.layouts.store')

@section('title', __('ui.auth.forgot.page_title'))
@section('header_title', __('ui.auth.forgot.heading'))
@section('page_title', __('ui.auth.forgot.heading'))
@section('body_class', 'mobile-commerce-body mobile-auth-commerce-body')

@push('head')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
@endpush

@section('content')
    <div class="auth-mobile-form card card-style">
        <div class="content">
            <h3 class="mb-1">{{ __('ui.auth.forgot.form_title') }}</h3>
            <p class="opacity-60 mb-3">{{ __('ui.auth.forgot.intro') }}</p>

            <form method="POST" action="{{ route('front.auth.password.email') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="forgot-password-email-mobile" class="mb-1 d-block font-600 font-12">{{ __('ui.auth.fields.email') }}</label>
                    <input id="forgot-password-email-mobile" type="email" name="email" value="{{ old('email') }}" class="form-control rounded-0" autocomplete="email" autofocus required @error('email') aria-invalid="true" aria-describedby="forgot-password-email-mobile-error" @enderror>
                    @error('email')
                        <p id="forgot-password-email-mobile-error" class="mb-0 mt-1 font-600 font-12 color-red-dark">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="commerce-primary-action btn btn-full font-600">{{ __('ui.auth.forgot.submit') }}</button>
            </form>
        </div>
    </div>

    <div class="card card-style rounded-0">
        <div class="content">
            <p class="font-600 font-12 mb-1 opacity-60">{{ __('ui.auth.forgot.back_eyebrow') }}</p>
            <h4 class="mb-2">{{ __('ui.auth.forgot.back_title') }}</h4>
            <p class="mb-3">{{ __('ui.auth.forgot.back_text') }}</p>
            <a href="{{ route('front.auth.login') }}" class="btn btn-full btn-border border-dark color-dark rounded-0 font-600">{{ __('ui.auth.forgot.back_action') }}</a>
        </div>
    </div>
@endsection
