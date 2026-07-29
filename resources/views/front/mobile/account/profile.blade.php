@extends('front.mobile.layouts.store')

@section('title', __('ui.account.profile.page_title'))
@section('header_title', __('ui.front.desktop.account'))
@section('page_title', __('ui.account.nav.edit_account'))
@section('body_class', 'mobile-commerce-body mobile-account-commerce-body mobile-account-profile-commerce-body')

@push('head')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
@endpush

@section('content')
    <div data-address-autofill data-address-source="{{ $placesAssetUrl }}">
        <div class="card card-style">
            <div class="content">
                <div class="d-flex mb-1">
                    <h4 class="mb-0">{{ __('ui.account.profile.personal_info') }}</h4>
                    <a href="{{ route('account.dashboard') }}" class="ms-auto font-12 color-theme">{{ __('ui.account.nav.dashboard') }}</a>
                </div>
                <p class="font-12 opacity-70 mb-3">{{ __('ui.account.profile.subtitle') }}</p>

                <form method="POST" action="{{ route('account.profile.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="input-style has-borders no-icon input-style-always-active mb-3">
                        <label for="profile-name" class="color-highlight">{{ __('ui.account.fields.display_name') }}</label>
                        <input id="profile-name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                    </div>
                    <div class="input-style has-borders no-icon input-style-always-active mb-3">
                        <label for="profile-email" class="color-highlight">{{ __('ui.account.fields.email') }}</label>
                        <input id="profile-email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                    </div>
                    <div class="row mb-0">
                        <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="profile-first" class="color-highlight">{{ __('ui.account.fields.first_name') }}</label><input id="profile-first" type="text" name="first_name" value="{{ old('first_name', $user->profile?->first_name) }}"></div></div>
                        <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="profile-last" class="color-highlight">{{ __('ui.account.fields.last_name') }}</label><input id="profile-last" type="text" name="last_name" value="{{ old('last_name', $user->profile?->last_name) }}"></div></div>
                    </div>
                    <div class="row mb-0">
                        <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="profile-phone" class="color-highlight">{{ __('ui.account.fields.phone') }}</label><input id="profile-phone" type="text" name="phone" value="{{ old('phone', $user->profile?->phone) }}"></div></div>
                        <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="profile-company" class="color-highlight">{{ __('ui.account.fields.company') }}</label><input id="profile-company" type="text" name="company" value="{{ old('company', $user->profile?->company) }}"></div></div>
                    </div>
                    <div class="row mb-0">
                        <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="profile-oib" class="color-highlight">{{ __('ui.account.fields.oib') }}</label><input id="profile-oib" type="text" name="oib" value="{{ old('oib', $user->profile?->oib) }}"></div></div>
                        <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="profile-birthday" class="color-highlight">{{ __('ui.account.fields.birthday') }}</label><input id="profile-birthday" type="date" name="birthday" value="{{ old('birthday', optional($user->profile?->birthday)->format('Y-m-d')) }}"></div></div>
                    </div>

                    @php
                        $genderValue = old('gender', $user->profile?->gender);
                    @endphp
                    <div class="mb-3">
                        <p class="font-12 color-highlight mb-2">{{ __('ui.account.fields.gender') }}</p>
                        <label class="me-3"><input type="radio" name="gender" value="female" @checked($genderValue === 'female')> {{ __('ui.account.gender.female') }}</label>
                        <label><input type="radio" name="gender" value="male" @checked($genderValue === 'male')> {{ __('ui.account.gender.male') }}</label>
                    </div>

                    <button type="submit" class="btn btn-full gradient-blue font-600 rounded-s">{{ __('ui.account.actions.save_profile') }}</button>
                </form>
            </div>
        </div>

        <div class="card card-style">
            <div class="content">
                <h4 class="mb-3">{{ __('ui.account.profile.preferences') }}</h4>
                <form method="POST" action="{{ route('account.preferences.update') }}">
                    @csrf
                    @method('PUT')

                    <label class="d-flex mb-3">
                        <input type="checkbox" name="newsletter_opt_in" value="1" class="me-2" @checked((bool) old('newsletter_opt_in', $user->profile?->newsletter_opt_in))>
                        <span class="font-13">{{ __('ui.account.profile.newsletter_opt_in') }}</span>
                    </label>
                    <label class="d-flex mb-3">
                        <input type="checkbox" name="gdpr_marketing_opt_in" value="1" class="me-2" @checked((bool) old('gdpr_marketing_opt_in', $preferencePayload['gdpr_marketing_opt_in'] ?? false))>
                        <span class="font-13">{{ __('ui.account.profile.gdpr_marketing') }}</span>
                    </label>
                    <label class="d-flex mb-3">
                        <input type="checkbox" name="gdpr_personalization_opt_in" value="1" class="me-2" @checked((bool) old('gdpr_personalization_opt_in', $preferencePayload['gdpr_personalization_opt_in'] ?? false))>
                        <span class="font-13">{{ __('ui.account.profile.gdpr_personalization') }}</span>
                    </label>

                    <button type="submit" class="btn btn-full btn-border border-gray-dark color-gray-dark font-600 rounded-s">{{ __('ui.account.actions.save_preferences') }}</button>
                </form>
            </div>
        </div>

        @php
            $addressForms = [
                'billing' => $billing,
                'shipping' => $shipping,
            ];
        @endphp

        @foreach ($addressForms as $type => $address)
            <div class="card card-style">
                <div class="content">
                    <h4 class="mb-3">{{ __('ui.account.address.title', ['type' => __('ui.account.address.types.'.$type)]) }}</h4>

                    <form method="POST" action="{{ route('account.addresses.update', ['type' => $type]) }}" data-address-scope="{{ $type }}">
                        @csrf
                        @method('PUT')

                        <div class="row mb-0">
                            <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-first" class="color-highlight">{{ __('ui.account.fields.first_name') }}</label><input id="{{ $type }}-first" type="text" name="first_name" value="{{ old('first_name', $address?->first_name) }}"></div></div>
                            <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-last" class="color-highlight">{{ __('ui.account.fields.last_name') }}</label><input id="{{ $type }}-last" type="text" name="last_name" value="{{ old('last_name', $address?->last_name) }}"></div></div>
                        </div>

                        @if ($type === 'billing')
                            <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-company" class="color-highlight">{{ __('ui.account.fields.company') }}</label><input id="{{ $type }}-company" type="text" name="company" value="{{ old('company', $address?->company) }}"></div>
                        @endif
                        <div class="row mb-0">
                            <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-oib" class="color-highlight">{{ __('ui.account.fields.oib') }}</label><input id="{{ $type }}-oib" type="text" name="oib" value="{{ old('oib', $address?->oib) }}"></div></div>
                            <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-vat" class="color-highlight">{{ __('ui.account.fields.vat_id') }}</label><input id="{{ $type }}-vat" type="text" name="vat_id" value="{{ old('vat_id', $address?->vat_id) }}"></div></div>
                        </div>

                        <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-phone" class="color-highlight">{{ __('ui.account.fields.phone') }}</label><input id="{{ $type }}-phone" type="text" name="phone" value="{{ old('phone', $address?->phone) }}"></div>
                        <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-line1" class="color-highlight">{{ __('ui.account.fields.address_line_1') }}</label><input id="{{ $type }}-line1" type="text" name="address_line_1" value="{{ old('address_line_1', $address?->address_line_1) }}" required></div>

                        <div class="row mb-0">
                            <div class="col-4"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-postal" class="color-highlight">{{ __('ui.account.fields.postal_code') }}</label><input id="{{ $type }}-postal" type="text" name="postal_code" value="{{ old('postal_code', $address?->postal_code) }}" data-address-postal required></div></div>
                            <div class="col-8"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-city" class="color-highlight">{{ __('ui.account.fields.city') }}</label><input id="{{ $type }}-city" type="text" name="city" value="{{ old('city', $address?->city) }}" data-address-city required></div></div>
                        </div>

                        <div class="input-style has-borders no-icon input-style-always-active mb-3">
                            <label for="{{ $type }}-country" class="color-highlight">{{ __('ui.account.fields.country_code') }}</label>
                            <select id="{{ $type }}-country" name="country_code" data-address-country required>
                                @foreach ($countryOptions as $countryOption)
                                    <option value="{{ $countryOption['code'] }}" @selected(old('country_code', $address?->country_code ?? 'HR') === $countryOption['code'])>{{ $countryOption['label'] }}</option>
                                @endforeach
                            </select>
                            <span><i class="fa fa-chevron-down"></i></span>
                        </div>

                        <button type="submit" class="btn btn-full gradient-blue font-600 rounded-s">{{ __('ui.account.actions.save_address', ['type' => __('ui.account.address.types.'.$type)]) }}</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('scripts')
    <script defer src="{{ asset('front-theme/scripts/address-autofill.js') }}?v={{ filemtime(public_path('front-theme/scripts/address-autofill.js')) }}"></script>
@endpush
