@extends('front.mobile.layouts.store')

@section('title', __('contact.page_title'))
@section('header_title', __('contact.page_title'))
@section('page_title', __('contact.heading'))

@section('content')
    @php
        $captchaSiteKey = trim((string) ($storeSettings['captcha']['recaptcha_v3_site_key'] ?? ''));
        $captchaEnabled = (bool) ($storeSettings['captcha']['recaptcha_v3_enabled'] ?? false) && $captchaSiteKey !== '';
    @endphp

    <div class="card card-style rounded-0 bg-white border border-gray-light">
        <div class="content">
            <p class="font-12 color-highlight mb-n1">{{ __('contact.eyebrow') }}</p>
            <h2 class="mb-2">{{ __('contact.heading') }}</h2>
            <p class="mb-0">{{ __('contact.subheading') }}</p>
        </div>
    </div>

    <div class="card card-style rounded-0">
        <div class="content">
            <form
                method="POST"
                action="{{ route('contact.store') }}"
                @if($captchaEnabled) data-recaptcha-form data-recaptcha-site-key="{{ $captchaSiteKey }}" data-recaptcha-action="contact_form" @endif
            >
                @csrf
                <input type="hidden" name="recaptcha_token" value="" data-recaptcha-token>
                @error('recaptcha_token')
                    <div class="bg-red-light p-2 mb-3">
                        <p class="mb-0 color-red-dark font-12">{{ $message }}</p>
                    </div>
                @enderror

                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="contact-name" class="color-highlight">{{ __('contact.form.name') }}</label>
                    <input id="contact-name" type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required>
                    @error('name') <p class="font-11 color-red-dark mb-0 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="contact-email" class="color-highlight">{{ __('contact.form.email') }}</label>
                    <input id="contact-email" type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required>
                    @error('email') <p class="font-11 color-red-dark mb-0 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="contact-phone" class="color-highlight">{{ __('contact.form.phone') }}</label>
                    <input id="contact-phone" type="text" name="phone" value="{{ old('phone') }}">
                    @error('phone') <p class="font-11 color-red-dark mb-0 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="contact-subject" class="color-highlight">{{ __('contact.form.subject') }}</label>
                    <input id="contact-subject" type="text" name="subject" value="{{ old('subject') }}" required>
                    @error('subject') <p class="font-11 color-red-dark mb-0 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="input-style has-borders input-style-always-active no-icon mb-3">
                    <textarea id="contact-message" name="message" style="height:140px;" required>{{ old('message') }}</textarea>
                    <label for="contact-message" class="color-highlight">{{ __('contact.form.message') }}</label>
                    @error('message') <p class="font-11 color-red-dark mb-0 mt-1">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn btn-full btn-border border-dark-dark color-dark-dark font-600">{{ __('contact.form.submit') }}</button>
            </form>
        </div>
    </div>

    <div class="card card-style rounded-0">
        <div class="content">
            <h4 class="mb-2">{{ __('contact.direct.title') }}</h4>
            @if (!empty($storeSettings['footer']['email_support'] ?? ''))
                <p class="mb-1"><strong>{{ __('contact.direct.email') }}:</strong> {{ $storeSettings['footer']['email_support'] }}</p>
            @endif
            @if (!empty($storeSettings['footer']['phone'] ?? ''))
                <p class="mb-1"><strong>{{ __('contact.direct.phone') }}:</strong> {{ $storeSettings['footer']['phone'] }}</p>
            @endif
            <p class="mb-0"><strong>{{ __('contact.direct.response_time') }}:</strong> {{ (string) ($storeSettings['footer']['hours'] ?? __('contact.direct.response_fallback')) }}</p>
        </div>
    </div>

    @if ($captchaEnabled)
        @push('scripts')
            <script src="https://www.google.com/recaptcha/api.js?render={{ $captchaSiteKey }}"></script>
            <script>
                (function () {
                    const forms = document.querySelectorAll('[data-recaptcha-form]');
                    forms.forEach(function (form) {
                        form.addEventListener('submit', function (event) {
                            event.preventDefault();
                            const tokenInput = form.querySelector('[data-recaptcha-token]');
                            const siteKey = form.dataset.recaptchaSiteKey;
                            const action = form.dataset.recaptchaAction || 'contact_form';
                            if (!tokenInput || !window.grecaptcha || !siteKey) {
                                form.submit();
                                return;
                            }
                            grecaptcha.ready(function () {
                                grecaptcha.execute(siteKey, { action: action }).then(function (token) {
                                    tokenInput.value = token || '';
                                    form.submit();
                                });
                            });
                        }, { once: true });
                    });
                }());
            </script>
        @endpush
    @endif
@endsection
