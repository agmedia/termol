@extends('front.mobile.layouts.store')

@section('title', 'Profile Settings')
@section('header_title', 'Account')
@section('page_title', 'Profile')

@section('content')
    <div class="card card-style">
        <div class="content">
            <div class="d-flex mb-1">
                <h4 class="mb-0">Personal Info</h4>
                <a href="{{ route('account.dashboard') }}" class="ms-auto font-12 color-highlight">Dashboard</a>
            </div>
            <p class="font-12 opacity-70 mb-3">Update customer details and communication preferences.</p>

            <form method="POST" action="{{ route('account.profile.update') }}">
                @csrf
                @method('PUT')

                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="profile-name" class="color-highlight">Display name</label>
                    <input id="profile-name" type="text" name="name" value="{{ old('name', $user->name) }}" required>
                </div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="profile-email" class="color-highlight">Email</label>
                    <input id="profile-email" type="email" name="email" value="{{ old('email', $user->email) }}" required>
                </div>
                <div class="row mb-0">
                    <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="profile-first" class="color-highlight">First name</label><input id="profile-first" type="text" name="first_name" value="{{ old('first_name', $user->profile?->first_name) }}"></div></div>
                    <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="profile-last" class="color-highlight">Last name</label><input id="profile-last" type="text" name="last_name" value="{{ old('last_name', $user->profile?->last_name) }}"></div></div>
                </div>
                <div class="row mb-0">
                    <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="profile-phone" class="color-highlight">Phone</label><input id="profile-phone" type="text" name="phone" value="{{ old('phone', $user->profile?->phone) }}"></div></div>
                    <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="profile-company" class="color-highlight">Company</label><input id="profile-company" type="text" name="company" value="{{ old('company', $user->profile?->company) }}"></div></div>
                </div>
                <div class="row mb-0">
                    <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="profile-oib" class="color-highlight">OIB</label><input id="profile-oib" type="text" name="oib" value="{{ old('oib', $user->profile?->oib) }}"></div></div>
                    <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="profile-gender" class="color-highlight">Gender</label><input id="profile-gender" type="text" name="gender" value="{{ old('gender', $user->profile?->gender) }}"></div></div>
                </div>
                <div class="input-style has-borders no-icon input-style-always-active mb-3">
                    <label for="profile-birthday" class="color-highlight">Birthday</label>
                    <input id="profile-birthday" type="date" name="birthday" value="{{ old('birthday', optional($user->profile?->birthday)->format('Y-m-d')) }}">
                </div>

                <button type="submit" class="btn btn-full gradient-blue font-600 rounded-s">Save Profile</button>
            </form>
        </div>
    </div>

    <div class="card card-style">
        <div class="content">
            <h4 class="mb-3">GDPR & Newsletter</h4>
            <form method="POST" action="{{ route('account.preferences.update') }}">
                @csrf
                @method('PUT')

                <label class="d-flex mb-3">
                    <input type="checkbox" name="newsletter_opt_in" value="1" class="me-2" @checked((bool) old('newsletter_opt_in', $user->profile?->newsletter_opt_in))>
                    <span class="font-13">Newsletter opt-in</span>
                </label>
                <label class="d-flex mb-3">
                    <input type="checkbox" name="gdpr_marketing_opt_in" value="1" class="me-2" @checked((bool) old('gdpr_marketing_opt_in', $preferencePayload['gdpr_marketing_opt_in'] ?? false))>
                    <span class="font-13">GDPR marketing consent</span>
                </label>
                <label class="d-flex mb-3">
                    <input type="checkbox" name="gdpr_personalization_opt_in" value="1" class="me-2" @checked((bool) old('gdpr_personalization_opt_in', $preferencePayload['gdpr_personalization_opt_in'] ?? false))>
                    <span class="font-13">GDPR personalization consent</span>
                </label>

                <button type="submit" class="btn btn-full btn-border border-gray-dark color-gray-dark font-600 rounded-s">Save Preferences</button>
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
                <h4 class="mb-3">{{ ucfirst($type) }} Address</h4>

                <form method="POST" action="{{ route('account.addresses.update', ['type' => $type]) }}">
                    @csrf
                    @method('PUT')

                    <div class="row mb-0">
                        <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-first" class="color-highlight">First name</label><input id="{{ $type }}-first" type="text" name="first_name" value="{{ old('first_name', $address?->first_name) }}"></div></div>
                        <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-last" class="color-highlight">Last name</label><input id="{{ $type }}-last" type="text" name="last_name" value="{{ old('last_name', $address?->last_name) }}"></div></div>
                    </div>

                    <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-company" class="color-highlight">Company</label><input id="{{ $type }}-company" type="text" name="company" value="{{ old('company', $address?->company) }}"></div>
                    <div class="row mb-0">
                        <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-oib" class="color-highlight">OIB</label><input id="{{ $type }}-oib" type="text" name="oib" value="{{ old('oib', $address?->oib) }}"></div></div>
                        <div class="col-6"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-vat" class="color-highlight">VAT ID</label><input id="{{ $type }}-vat" type="text" name="vat_id" value="{{ old('vat_id', $address?->vat_id) }}"></div></div>
                    </div>

                    <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-phone" class="color-highlight">Phone</label><input id="{{ $type }}-phone" type="text" name="phone" value="{{ old('phone', $address?->phone) }}"></div>
                    <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-line1" class="color-highlight">Address line 1</label><input id="{{ $type }}-line1" type="text" name="address_line_1" value="{{ old('address_line_1', $address?->address_line_1) }}" required></div>
                    <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-line2" class="color-highlight">Address line 2</label><input id="{{ $type }}-line2" type="text" name="address_line_2" value="{{ old('address_line_2', $address?->address_line_2) }}"></div>

                    <div class="row mb-0">
                        <div class="col-4"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-postal" class="color-highlight">Postal</label><input id="{{ $type }}-postal" type="text" name="postal_code" value="{{ old('postal_code', $address?->postal_code) }}" required></div></div>
                        <div class="col-5"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-city" class="color-highlight">City</label><input id="{{ $type }}-city" type="text" name="city" value="{{ old('city', $address?->city) }}" required></div></div>
                        <div class="col-3"><div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-country" class="color-highlight">Country</label><input id="{{ $type }}-country" type="text" name="country_code" value="{{ old('country_code', $address?->country_code ?? 'HR') }}" maxlength="2" required></div></div>
                    </div>

                    <div class="input-style has-borders no-icon input-style-always-active mb-3"><label for="{{ $type }}-state" class="color-highlight">State</label><input id="{{ $type }}-state" type="text" name="state" value="{{ old('state', $address?->state) }}"></div>

                    <button type="submit" class="btn btn-full gradient-blue font-600 rounded-s">Save {{ ucfirst($type) }} Address</button>
                </form>
            </div>
        </div>
    @endforeach
@endsection
