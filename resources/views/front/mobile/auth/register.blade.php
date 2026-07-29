@extends('front.mobile.layouts.store')

@section('title', __('ui.auth.register.page_title'))
@section('header_title', __('ui.auth.register.heading'))
@section('page_title', __('ui.auth.register.heading'))
@section('body_class', 'mobile-commerce-body mobile-auth-commerce-body')

@push('head')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
@endpush

@section('content')
    @php
        $captchaSiteKey = trim((string) ($storeSettings['captcha']['recaptcha_v3_site_key'] ?? ''));
        $captchaEnabled = (bool) ($storeSettings['captcha']['recaptcha_v3_enabled'] ?? false) && $captchaSiteKey !== '';
    @endphp

    <div class="auth-mobile-form card card-style" data-address-autofill data-address-source="{{ $placesAssetUrl }}">
        <div class="content">
            <h3 class="mb-1">{{ __('ui.auth.register.form_title') }}</h3>
            <p class="opacity-60 mb-3">{{ __('ui.auth.register.subheading') }}</p>

            <form
                method="POST"
                action="{{ route('front.auth.register.store') }}"
                novalidate
                data-address-scope="billing"
                @if($captchaEnabled) data-recaptcha-form data-recaptcha-site-key="{{ $captchaSiteKey }}" data-recaptcha-action="register_form" @endif
            >
                @csrf
                <input type="hidden" name="intended" value="{{ old('intended', (string) request('intended', route('account.dashboard'))) }}">
                <input type="hidden" name="recaptcha_token" value="" data-recaptcha-token>

                <div class="mb-3">
                    <label class="mb-1 d-block font-600 font-12">{{ __('ui.auth.fields.first_name') }}</label>
                    <input type="text" name="first_name" value="{{ old('first_name') }}" class="form-control rounded-0" autocomplete="given-name" required>
                    @error('first_name')
                        <p class="mb-0 mt-1 font-600 font-12" style="color:#e11d48;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="mb-1 d-block font-600 font-12">{{ __('ui.auth.fields.last_name') }}</label>
                    <input type="text" name="last_name" value="{{ old('last_name') }}" class="form-control rounded-0" autocomplete="family-name" required>
                    @error('last_name')
                        <p class="mb-0 mt-1 font-600 font-12" style="color:#e11d48;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="mb-1 d-block font-600 font-12">{{ __('ui.auth.fields.email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" class="form-control rounded-0" autocomplete="email" required>
                    @error('email')
                        <p class="mb-0 mt-1 font-600 font-12" style="color:#e11d48;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="mb-1 d-block font-600 font-12">{{ __('ui.auth.fields.phone') }}</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" class="form-control rounded-0" autocomplete="tel" required>
                    @error('phone')
                        <p class="mb-0 mt-1 font-600 font-12" style="color:#e11d48;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="mb-1 d-block font-600 font-12">{{ __('ui.auth.fields.address') }}</label>
                    <input type="text" name="address_line_1" value="{{ old('address_line_1') }}" class="form-control rounded-0" autocomplete="street-address" required>
                    @error('address_line_1')
                        <p class="mb-0 mt-1 font-600 font-12" style="color:#e11d48;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="mb-1 d-block font-600 font-12">{{ __('ui.auth.fields.postal_code') }}</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code') }}" class="form-control rounded-0" autocomplete="postal-code" inputmode="numeric" data-address-postal required>
                    @error('postal_code')
                        <p class="mb-0 mt-1 font-600 font-12" style="color:#e11d48;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="mb-1 d-block font-600 font-12">{{ __('ui.auth.fields.city') }}</label>
                    <input type="text" name="city" value="{{ old('city') }}" class="form-control rounded-0" autocomplete="address-level2" data-address-city required>
                    @error('city')
                        <p class="mb-0 mt-1 font-600 font-12" style="color:#e11d48;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="mb-1 d-block font-600 font-12">{{ __('ui.auth.fields.country') }}</label>
                    <select name="country_code" class="form-select rounded-0" autocomplete="country" data-address-country required>
                        @foreach ($countryOptions as $countryOption)
                            <option value="{{ $countryOption['code'] }}" @selected(old('country_code', 'HR') === $countryOption['code'])>{{ $countryOption['label'] }}</option>
                        @endforeach
                    </select>
                    @error('country_code')
                        <p class="mb-0 mt-1 font-600 font-12" style="color:#e11d48;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="mb-1 d-block font-600 font-12">{{ __('ui.auth.fields.password') }}</label>
                    <input type="password" name="password" class="form-control rounded-0" autocomplete="new-password" required>
                    @error('password')
                        <p class="mb-0 mt-1 font-600 font-12" style="color:#e11d48;">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="mb-1 d-block font-600 font-12">{{ __('ui.auth.fields.password_confirmation') }}</label>
                    <input type="password" name="password_confirmation" class="form-control rounded-0" autocomplete="new-password" required>
                </div>

                <div class="mb-3">
                    <label class="d-flex gap-2 mb-0 font-12">
                        <input type="checkbox" name="terms_accepted" value="1" class="mt-1" @checked(old('terms_accepted')) required @error('terms_accepted') aria-invalid="true" aria-describedby="auth-mobile-register-terms-error" @enderror>
                        <span>
                            {{ __('ui.auth.register.terms_prefix') }}
                            <a href="{{ route('pages.show', ['slug' => 'uvjeti-koristenja']) }}" class="font-600 text-underline" target="_blank" rel="noopener noreferrer">{{ __('ui.auth.register.terms_link') }}</a>.
                        </span>
                    </label>
                    @error('terms_accepted')
                        <p id="auth-mobile-register-terms-error" class="mb-0 mt-1 font-600 font-12" style="color:#e11d48;">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="commerce-primary-action btn btn-full font-600">{{ __('ui.auth.register.submit') }}</button>
                @error('recaptcha_token')
                    <p class="mb-0 mt-2 font-600 font-12" style="color:#e11d48;">{{ $message }}</p>
                @enderror
            </form>
        </div>
    </div>

    <div class="card card-style rounded-0">
        <div class="content">
            <p class="font-600 font-12 mb-1 opacity-60">{{ __('ui.auth.register.have_account_eyebrow') }}</p>
            <h4 class="mb-2">{{ __('ui.auth.register.have_account_title') }}</h4>
            <p class="mb-3">{{ __('ui.auth.register.have_account_text') }}</p>
            <a href="{{ route('front.auth.login', ['intended' => (string) request('intended', route('account.dashboard'))]) }}" class="btn btn-full btn-border border-dark color-dark rounded-0 font-600">{{ __('ui.auth.register.go_to_login') }}</a>
            <div class="divider mt-4 mb-3"></div>
            <h5 class="mb-2">{{ __('Kupujete za tvrtku?') }}</h5>
            <p class="mb-3">{{ __('Zatražite B2B račun za ugovorene cijene i brzu kupnju.') }}</p>
            <a href="{{ route('front.auth.b2b-register') }}" class="btn btn-full btn-border border-highlight color-highlight rounded-0 font-600">{{ __('B2B registracija') }}</a>
        </div>
    </div>

    @if ($captchaEnabled)
        @include('front.partials.recaptcha-v3', ['siteKey' => $captchaSiteKey])
    @endif
@endsection

@push('scripts')
    <script defer src="{{ asset('front-theme/scripts/address-autofill.js') }}?v={{ filemtime(public_path('front-theme/scripts/address-autofill.js')) }}"></script>
@endpush
