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

    <div class="card card-style rounded-0">
        <div class="content">
            <h2 class="mb-1">{{ __('B2B registracija') }}</h2>
            <p class="mb-4">{{ __('Nakon provjere dobit ćete pristup ugovorenim cijenama, brzoj kupnji i ponovnom naručivanju.') }}</p>

            <form method="POST" action="{{ route('front.auth.b2b-register.store') }}"
                @if($captchaEnabled) data-recaptcha-form data-recaptcha-site-key="{{ $captchaSiteKey }}" data-recaptcha-action="register_form" @endif>
                @csrf
                <input type="hidden" name="recaptcha_token" value="" data-recaptcha-token>

                @foreach ($fields as $field)
                    <div class="mb-3">
                        <label class="mb-1 d-block font-600 font-12">{{ $field[1] }}</label>
                        <input type="{{ $field[2] }}" name="{{ $field[0] }}" value="{{ old($field[0]) }}" class="form-control rounded-0" @required(! in_array($field[0], ['vat_id', 'address_line_2'], true))>
                        @error($field[0]) <p class="mb-0 mt-1 font-600 font-12 color-red-dark">{{ $message }}</p> @enderror
                    </div>
                @endforeach

                <div class="mb-3">
                    <label class="mb-1 d-block font-600 font-12">{{ __('Država') }}</label>
                    <select name="country_code" class="form-select rounded-0">
                        <option value="HR">Hrvatska</option>
                        <option value="SI">Slovenija</option>
                        <option value="BA">Bosna i Hercegovina</option>
                        <option value="RS">Srbija</option>
                        <option value="DE">Njemačka</option>
                        <option value="AT">Austrija</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="mb-1 d-block font-600 font-12">{{ __('Lozinka') }}</label>
                    <input type="password" name="password" class="form-control rounded-0" required>
                    @error('password') <p class="mb-0 mt-1 font-600 font-12 color-red-dark">{{ $message }}</p> @enderror
                </div>
                <div class="mb-3">
                    <label class="mb-1 d-block font-600 font-12">{{ __('Potvrda lozinke') }}</label>
                    <input type="password" name="password_confirmation" class="form-control rounded-0" required>
                </div>

                <label class="d-flex gap-2 mb-3 font-12">
                    <input type="checkbox" name="terms_accepted" value="1" class="mt-1" required>
                    <span>{{ __('Potvrđujem točnost poslovnih podataka i prihvaćam provjeru zahtjeva.') }}</span>
                </label>
                @error('terms_accepted') <p class="mb-2 font-600 font-12 color-red-dark">{{ $message }}</p> @enderror

                <button type="submit" class="commerce-primary-action btn btn-full font-600">{{ __('Pošalji B2B zahtjev') }}</button>
                @error('recaptcha_token') <p class="mb-0 mt-2 font-600 font-12 color-red-dark">{{ $message }}</p> @enderror
            </form>
        </div>
    </div>

    @if ($captchaEnabled)
        @include('front.partials.recaptcha-v3', ['siteKey' => $captchaSiteKey])
    @endif
@endsection
