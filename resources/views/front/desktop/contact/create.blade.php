@extends('front.desktop.layouts.store')

@section('title', __('contact.page_title'))

@section('content')
    @php
        $captchaSiteKey = trim((string) ($storeSettings['captcha']['recaptcha_v3_site_key'] ?? ''));
        $captchaEnabled = (bool) ($storeSettings['captcha']['recaptcha_v3_enabled'] ?? false) && $captchaSiteKey !== '';
    @endphp

    <section class="mb-8">
        <p class="mb-3 text-center text-xs uppercase tracking-[0.14em] text-slate-500">
            {{ __('ui.front.desktop.footer.home') }} <span class="mx-1">/</span> {{ __('contact.page_title') }}
        </p>
        <div class="border border-slate-200 bg-slate-100 px-6 py-10 text-center sm:px-10">
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">{{ __('contact.eyebrow') }}</p>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-slate-900">{{ __('contact.heading') }}</h1>
            <p class="mx-auto mt-3 max-w-2xl text-base text-slate-600">{{ __('contact.subheading') }}</p>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <form
            method="POST"
            action="{{ route('contact.store') }}"
            class="border border-slate-200 bg-white p-6 sm:p-8"
            @if($captchaEnabled) data-recaptcha-form data-recaptcha-site-key="{{ $captchaSiteKey }}" data-recaptcha-action="contact_form" @endif
        >
            @csrf
            <input type="hidden" name="recaptcha_token" value="" data-recaptcha-token>
            @error('recaptcha_token')
                <p class="mb-4 border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ $message }}</p>
            @enderror

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('contact.form.name') }}</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('contact.form.email') }}</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>
                    @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="mt-4">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('contact.form.phone') }}</label>
                <input type="text" name="phone" value="{{ old('phone') }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0">
                @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="mt-4">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('contact.form.subject') }}</label>
                <input type="text" name="subject" value="{{ old('subject') }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>
                @error('subject') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="mt-4">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('contact.form.message') }}</label>
                <textarea name="message" rows="8" class="w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>{{ old('message') }}</textarea>
                @error('message') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="mt-6 inline-flex h-11 items-center justify-center border border-slate-900 bg-slate-900 px-6 text-sm font-semibold text-white transition hover:bg-slate-700">
                {{ __('contact.form.submit') }}
            </button>
        </form>

        <aside class="space-y-4">
            <div class="border border-slate-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-slate-900">{{ __('contact.direct.title') }}</h2>
                <ul class="mt-3 space-y-2 text-sm text-slate-600">
                    @if (!empty($storeSettings['footer']['email_support'] ?? ''))
                        <li><span class="font-semibold text-slate-900">{{ __('contact.direct.email') }}:</span> {{ $storeSettings['footer']['email_support'] }}</li>
                    @endif
                    @if (!empty($storeSettings['footer']['phone'] ?? ''))
                        <li><span class="font-semibold text-slate-900">{{ __('contact.direct.phone') }}:</span> {{ $storeSettings['footer']['phone'] }}</li>
                    @endif
                    <li><span class="font-semibold text-slate-900">{{ __('contact.direct.response_time') }}:</span> {{ (string) ($storeSettings['footer']['hours'] ?? __('contact.direct.response_fallback')) }}</li>
                </ul>
            </div>

            <div class="border border-slate-200 bg-slate-100 p-6">
                <h3 class="text-sm font-bold uppercase tracking-wide text-slate-700">{{ __('contact.help.title') }}</h3>
                <p class="mt-2 text-sm text-slate-700">{{ __('contact.help.body') }}</p>
            </div>
        </aside>
    </section>

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
