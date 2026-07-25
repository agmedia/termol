@extends('front.desktop.layouts.store')

@section('title', __('ui.auth.register.page_title'))
@section('body_class', 'commerce-body auth-commerce-body')
@section('main_class', 'commerce-main')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
@endpush

@section('content')
    @php
        $captchaSiteKey = trim((string) ($storeSettings['captcha']['recaptcha_v3_site_key'] ?? ''));
        $captchaEnabled = (bool) ($storeSettings['captcha']['recaptcha_v3_enabled'] ?? false) && $captchaSiteKey !== '';
    @endphp

    <section class="commerce-hero">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ __('ui.auth.register.heading') }}</h1>
        <p class="mt-2 text-slate-600">{{ __('ui.auth.register.subheading') }}</p>
    </section>

    <section class="auth-layout">
        <div class="auth-form-card border border-slate-200 p-6">
            <h2 class="text-xl font-bold text-slate-900">{{ __('ui.auth.register.form_title') }}</h2>

            <form
                method="POST"
                action="{{ route('front.auth.register.store') }}"
                class="mt-5 grid gap-4 md:grid-cols-2"
                novalidate
                @if($captchaEnabled) data-recaptcha-form data-recaptcha-site-key="{{ $captchaSiteKey }}" data-recaptcha-action="register_form" @endif
            >
                @csrf
                <input type="hidden" name="intended" value="{{ old('intended', (string) request('intended', route('account.dashboard'))) }}">
                <input type="hidden" name="recaptcha_token" value="" data-recaptcha-token>

                <div>
                    <label for="auth-register-first-name" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.auth.fields.first_name') }}</label>
                    <input id="auth-register-first-name" type="text" name="first_name" value="{{ old('first_name') }}" class="w-full px-3 text-sm" autocomplete="given-name" required>
                    @error('first_name')
                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="auth-register-last-name" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.auth.fields.last_name') }}</label>
                    <input id="auth-register-last-name" type="text" name="last_name" value="{{ old('last_name') }}" class="w-full px-3 text-sm" autocomplete="family-name" required>
                    @error('last_name')
                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="auth-register-email" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.auth.fields.email') }}</label>
                    <input id="auth-register-email" type="email" name="email" value="{{ old('email') }}" class="w-full px-3 text-sm" autocomplete="email" required>
                    @error('email')
                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="auth-register-password" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.auth.fields.password') }}</label>
                    <input id="auth-register-password" type="password" name="password" class="w-full px-3 text-sm" autocomplete="new-password" required>
                    @error('password')
                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="auth-register-password-confirmation" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.auth.fields.password_confirmation') }}</label>
                    <input id="auth-register-password-confirmation" type="password" name="password_confirmation" class="w-full px-3 text-sm" autocomplete="new-password" required>
                </div>

                <div class="md:col-span-2">
                    <button type="submit" class="commerce-primary-action w-full px-6 py-3">
                        {{ __('ui.auth.register.submit') }}
                    </button>
                    @error('recaptcha_token')
                        <p class="mt-2 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </form>
        </div>

        <aside class="auth-side-card border border-slate-200 bg-white p-6">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.auth.register.have_account_eyebrow') }}</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">{{ __('ui.auth.register.have_account_title') }}</h2>
            <p class="mt-3 text-sm text-slate-600">{{ __('ui.auth.register.have_account_text') }}</p>

            <a href="{{ route('front.auth.login', ['intended' => (string) request('intended', route('account.dashboard'))]) }}" class="commerce-secondary-action mt-5 px-6 py-2.5 text-sm">
                {{ __('ui.auth.register.go_to_login') }}
            </a>
        </aside>
    </section>

    @if ($captchaEnabled)
        @include('front.partials.recaptcha-v3', ['siteKey' => $captchaSiteKey])
    @endif
@endsection
