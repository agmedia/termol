@extends('front.mobile.layouts.store')

@section('title', __('B2B registracija'))
@section('header_title', __('B2B registracija'))
@section('page_title', __('Poslovni korisnici'))
@section('body_class', 'mobile-commerce-body')

@push('head')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
@endpush

@section('content')
    @php
        $captchaSiteKey = trim((string) ($storeSettings['captcha']['recaptcha_v3_site_key'] ?? ''));
        $captchaEnabled = (bool) ($storeSettings['captcha']['recaptcha_v3_enabled'] ?? false) && $captchaSiteKey !== '';
        $fields = [
            ['first_name', __('Ime'), 'text'],
            ['last_name', __('Prezime'), 'text'],
            ['email', __('E-mail'), 'email'],
            ['phone', __('Telefon'), 'text'],
            ['company_name', __('Naziv tvrtke'), 'text'],
            ['oib', __('OIB'), 'text'],
            ['vat_id', __('PDV ID (opcionalno)'), 'text'],
            ['address_line_1', __('Adresa'), 'text'],
            ['address_line_2', __('Dodatak adresi (opcionalno)'), 'text'],
            ['postal_code', __('Poštanski broj'), 'text'],
            ['city', __('Grad'), 'text'],
        ];
    @endphp

    <div class="card card-style rounded-0" data-address-autofill data-address-source="{{ $placesAssetUrl }}">
        <div class="content">
            <h2 class="mb-1">{{ __('B2B registracija') }}</h2>
            <p class="mb-4">{{ __('Nakon provjere dobit ćete pristup ugovorenim cijenama, brzoj kupnji i ponovnom naručivanju.') }}</p>

            <form method="POST" action="{{ route('front.auth.b2b-register.store') }}" novalidate data-address-scope="billing"
                @if($captchaEnabled) data-recaptcha-form data-recaptcha-site-key="{{ $captchaSiteKey }}" data-recaptcha-action="register_form" @endif>
                @csrf
                <input type="hidden" name="recaptcha_token" value="" data-recaptcha-token>

                @foreach ($fields as $field)
                    <div class="mb-3">
                        <label for="b2b-mobile-{{ $field[0] }}" class="mb-1 d-block font-600 font-12">{{ $field[1] }}</label>
                        <input id="b2b-mobile-{{ $field[0] }}" type="{{ $field[2] }}" name="{{ $field[0] }}" value="{{ old($field[0]) }}" class="form-control rounded-0 @error($field[0]) border-danger @enderror" @if($field[0] === 'postal_code') data-address-postal inputmode="numeric" @endif @if($field[0] === 'city') data-address-city @endif @required(! in_array($field[0], ['vat_id', 'address_line_2'], true)) @error($field[0]) aria-invalid="true" aria-describedby="b2b-mobile-{{ $field[0] }}-error" @enderror>
                        @error($field[0])
                            <p id="b2b-mobile-{{ $field[0] }}-error" class="mb-0 mt-1 font-600 font-12 color-red-dark" aria-live="polite">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <div class="mb-3">
                    <label for="b2b-mobile-country" class="mb-1 d-block font-600 font-12">{{ __('Država') }}</label>
                    <select id="b2b-mobile-country" name="country_code" class="form-select rounded-0 @error('country_code') border-danger @enderror" data-address-country required @error('country_code') aria-invalid="true" aria-describedby="b2b-mobile-country-error" @enderror>
                        @foreach ($countryOptions as $countryOption)
                            <option value="{{ $countryOption['code'] }}" @selected(old('country_code', 'HR') === $countryOption['code'])>{{ $countryOption['label'] }}</option>
                        @endforeach
                    </select>
                    @error('country_code')
                        <p id="b2b-mobile-country-error" class="mb-0 mt-1 font-600 font-12 color-red-dark" aria-live="polite">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="b2b-mobile-password" class="mb-1 d-block font-600 font-12">{{ __('Lozinka') }}</label>
                    <input id="b2b-mobile-password" type="password" name="password" class="form-control rounded-0 @error('password') border-danger @enderror" required @error('password') aria-invalid="true" aria-describedby="b2b-mobile-password-error" @enderror>
                    @error('password')
                        <p id="b2b-mobile-password-error" class="mb-0 mt-1 font-600 font-12 color-red-dark" aria-live="polite">{{ $message }}</p>
                    @enderror
                </div>
                <div class="mb-3">
                    <label for="b2b-mobile-password-confirmation" class="mb-1 d-block font-600 font-12">{{ __('Potvrda lozinke') }}</label>
                    <input id="b2b-mobile-password-confirmation" type="password" name="password_confirmation" class="form-control rounded-0 @error('password_confirmation') border-danger @enderror" required @error('password_confirmation') aria-invalid="true" aria-describedby="b2b-mobile-password-confirmation-error" @enderror>
                    @error('password_confirmation')
                        <p id="b2b-mobile-password-confirmation-error" class="mb-0 mt-1 font-600 font-12 color-red-dark" aria-live="polite">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="d-flex gap-2 mb-0 font-12">
                        <input type="checkbox" name="terms_accepted" value="1" class="mt-1" @checked(old('terms_accepted')) required @error('terms_accepted') aria-invalid="true" aria-describedby="b2b-mobile-terms-error" @enderror>
                        <span>{{ __('Potvrđujem točnost poslovnih podataka i prihvaćam provjeru zahtjeva.') }}</span>
                    </label>
                    @error('terms_accepted')
                        <p id="b2b-mobile-terms-error" class="mb-0 mt-1 font-600 font-12 color-red-dark" aria-live="polite">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="commerce-primary-action btn btn-full font-600">{{ __('Pošalji B2B zahtjev') }}</button>
                @error('recaptcha_token')
                    <p class="mb-0 mt-2 font-600 font-12 color-red-dark" aria-live="polite">{{ $message }}</p>
                @enderror
            </form>
        </div>
    </div>

    @if ($captchaEnabled)
        @include('front.partials.recaptcha-v3', ['siteKey' => $captchaSiteKey])
    @endif
@endsection

@push('scripts')
    <script defer src="{{ asset('front-theme/scripts/address-autofill.js') }}?v={{ filemtime(public_path('front-theme/scripts/address-autofill.js')) }}"></script>
@endpush
