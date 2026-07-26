@extends('front.mobile.layouts.store')

@section('title', __('ui.auth.reset.page_title'))
@section('header_title', __('ui.auth.reset.heading'))
@section('page_title', __('ui.auth.reset.heading'))
@section('body_class', 'mobile-commerce-body mobile-auth-commerce-body')

@push('head')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
@endpush

@section('content')
    <div class="auth-mobile-form card card-style">
        <div class="content">
            <h3 class="mb-1">{{ __('ui.auth.reset.form_title') }}</h3>
            <p class="opacity-60 mb-3">{{ __('ui.auth.reset.intro') }}</p>

            <form method="POST" action="{{ route('front.auth.password.update') }}" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label for="reset-password-email-mobile" class="mb-1 d-block font-600 font-12">{{ __('ui.auth.fields.email') }}</label>
                    <input id="reset-password-email-mobile" type="email" name="email" value="{{ old('email', $email) }}" class="form-control rounded-0" autocomplete="email" required @error('email') aria-invalid="true" aria-describedby="reset-password-email-mobile-error" @enderror>
                    @error('email')
                        <p id="reset-password-email-mobile-error" class="mb-0 mt-1 font-600 font-12 color-red-dark">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="reset-password-password-mobile" class="mb-1 d-block font-600 font-12">{{ __('ui.auth.fields.password') }}</label>
                    <input id="reset-password-password-mobile" type="password" name="password" class="form-control rounded-0" autocomplete="new-password" autofocus required @error('password') aria-invalid="true" aria-describedby="reset-password-password-mobile-error" @enderror>
                    @error('password')
                        <p id="reset-password-password-mobile-error" class="mb-0 mt-1 font-600 font-12 color-red-dark">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="reset-password-confirmation-mobile" class="mb-1 d-block font-600 font-12">{{ __('ui.auth.fields.password_confirmation') }}</label>
                    <input id="reset-password-confirmation-mobile" type="password" name="password_confirmation" class="form-control rounded-0" autocomplete="new-password" required>
                </div>

                <button type="submit" class="commerce-primary-action btn btn-full font-600">{{ __('ui.auth.reset.submit') }}</button>
            </form>
        </div>
    </div>

    <div class="card card-style rounded-0">
        <div class="content">
            <p class="font-600 font-12 mb-1 opacity-60">{{ __('ui.auth.reset.side_eyebrow') }}</p>
            <h4 class="mb-2">{{ __('ui.auth.reset.side_title') }}</h4>
            <p class="mb-0">{{ __('ui.auth.reset.side_text') }}</p>
        </div>
    </div>
@endsection
