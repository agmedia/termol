@extends('front.mobile.layouts.store')

@section('title', __('return_request.page_title'))
@section('header_title', __('return_request.page_title'))
@section('page_title', __('return_request.heading'))
@section('body_class', 'mobile-commerce-body mobile-returns-commerce-body')

@push('head')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
@endpush

@section('content')
    @php
        $captchaSiteKey = trim((string) ($storeSettings['captcha']['recaptcha_v3_site_key'] ?? ''));
        $captchaEnabled = (bool) ($storeSettings['captcha']['recaptcha_v3_enabled'] ?? false) && $captchaSiteKey !== '';
    @endphp

    <div class="returns-mobile-header card card-style bg-white">
        <div class="content">
            <p class="font-12 color-highlight mb-n1">{{ __('return_request.eyebrow') }}</p>
            <h2 class="mb-2">{{ __('return_request.heading') }}</h2>
            <p class="mb-0">{{ __('return_request.subheading') }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="card card-style bg-green-light">
            <div class="content"><p class="mb-0 font-13 color-green-dark">{{ session('status') }}</p></div>
        </div>
    @endif
    @if (session('warning'))
        <div class="card card-style bg-yellow-light">
            <div class="content"><p class="mb-0 font-13 color-brown-dark">{{ session('warning') }}</p></div>
        </div>
    @endif
    @error('draft')
        <div class="card card-style bg-red-light"><div class="content"><p class="mb-0 font-13 color-red-dark">{{ $message }}</p></div></div>
    @enderror

    <div class="card card-style bg-blue-light">
        <div class="content">
            <p class="font-13 font-600 mb-1">{{ __('return_request.intro') }}</p>
            <p class="font-12 mb-0">{{ __('return_request.scope_note') }}</p>
        </div>
    </div>

    <div class="returns-mobile-form card card-style">
        <div class="content">
            <form
                method="POST"
                action="{{ route('returns.review', ['returnRequestSlug' => __('return_request.slug')]) }}"
                data-withdrawal-form
                @if($captchaEnabled) data-recaptcha-site-key="{{ $captchaSiteKey }}" data-recaptcha-action="contract_withdrawal_form" @endif
            >
                @csrf
                <input type="hidden" name="recaptcha_token" value="" data-recaptcha-token>

                <h4 class="mb-3">{{ __('return_request.form.identity_section') }}</h4>
                @foreach ([
                    ['full_name', 'text', 'name', true],
                    ['email', 'email', 'email', true],
                    ['phone', 'tel', 'tel', false],
                    ['address_line', 'text', 'street-address', true],
                    ['postal_code', 'text', 'postal-code', true],
                    ['city', 'text', 'address-level2', true],
                    ['country_code', 'text', 'country', true],
                ] as [$field, $type, $autocomplete, $required])
                    <div class="input-style has-borders no-icon input-style-always-active mb-3">
                        <label for="withdrawal-{{ $field }}" class="color-highlight">{{ __('return_request.form.'.$field) }}</label>
                        <input
                            id="withdrawal-{{ $field }}"
                            type="{{ $type }}"
                            name="{{ $field }}"
                            value="{{ old($field, $prefill[$field] ?? ($field === 'country_code' ? 'HR' : '')) }}"
                            autocomplete="{{ $autocomplete }}"
                            @if($required) required @endif
                            @if($field === 'country_code') minlength="2" maxlength="2" pattern="[A-Za-z]{2}" class="text-uppercase" @endif
                        >
                        @if ($field === 'email') <p class="font-11 color-gray-dark mb-0 mt-1">{{ __('return_request.form.email_help') }}</p> @endif
                        @error($field) <p class="font-11 color-red-dark mb-0 mt-1">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <h4 class="mb-3 mt-4">{{ __('return_request.form.contract_section') }}</h4>
                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="withdrawal-order-number" class="color-highlight">{{ __('return_request.form.order_number') }}</label>
                    <input id="withdrawal-order-number" type="text" name="order_number" value="{{ old('order_number') }}" required maxlength="80">
                    @error('order_number') <p class="font-11 color-red-dark mb-0 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="withdrawal-contract-date" class="color-highlight">{{ __('return_request.form.contract_date') }}</label>
                    <input id="withdrawal-contract-date" type="date" name="contract_date" value="{{ old('contract_date') }}" max="{{ now()->toDateString() }}">
                    @error('contract_date') <p class="font-11 color-red-dark mb-0 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="withdrawal-received-date" class="color-highlight">{{ __('return_request.form.received_date') }}</label>
                    <input id="withdrawal-received-date" type="date" name="received_date" value="{{ old('received_date') }}" max="{{ now()->toDateString() }}">
                    @error('received_date') <p class="font-11 color-red-dark mb-0 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="input-style has-borders input-style-always-active no-icon mb-3">
                    <textarea id="withdrawal-items" name="items" style="height:150px;" placeholder="{{ __('return_request.form.items_placeholder') }}" required maxlength="5000">{{ old('items') }}</textarea>
                    <label for="withdrawal-items" class="color-highlight">{{ __('return_request.form.items') }}</label>
                    @error('items') <p class="font-11 color-red-dark mb-0 mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="input-style has-borders input-style-always-active no-icon mb-3">
                    <textarea id="withdrawal-note" name="note" style="height:110px;" placeholder="{{ __('return_request.form.note_placeholder') }}" maxlength="5000">{{ old('note') }}</textarea>
                    <label for="withdrawal-note" class="color-highlight">{{ __('return_request.form.note') }}</label>
                    @error('note') <p class="font-11 color-red-dark mb-0 mt-1">{{ $message }}</p> @enderror
                </div>

                <p class="font-11 color-gray-dark">{{ __('return_request.form.privacy_note') }}</p>
                @error('recaptcha_token') <p class="font-11 color-red-dark mb-2">{{ $message }}</p> @enderror
                <button type="submit" class="commerce-primary-action btn btn-full font-600" data-submit-button>{{ __('return_request.form.review_submit') }}</button>
            </form>
        </div>
    </div>

    <div class="card card-style rounded-0">
        <div class="content">
            <h4 class="mb-2">{{ __('return_request.help.title') }}</h4>
            <ul class="mb-0 ps-3">
                @foreach (__('return_request.help.items') as $item)
                    <li class="font-13 mb-2">{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    </div>

    @if ($captchaEnabled)
        @push('scripts')
            <script src="https://www.google.com/recaptcha/api.js?render={{ $captchaSiteKey }}"></script>
        @endpush
    @endif
    @push('scripts')
        <script>
            document.querySelectorAll('[data-withdrawal-form]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    const input = form.querySelector('[data-recaptcha-token]');
                    const key = form.dataset.recaptchaSiteKey;
                    if (!input || !key || !window.grecaptcha || input.value) return;
                    event.preventDefault();
                    if (!form.reportValidity()) return;
                    const button = form.querySelector('[data-submit-button]');
                    if (button) button.disabled = true;
                    grecaptcha.ready(function () {
                        grecaptcha.execute(key, { action: form.dataset.recaptchaAction }).then(function (token) {
                            input.value = token || '';
                            form.submit();
                        });
                    });
                });
            });
        </script>
    @endpush
@endsection
