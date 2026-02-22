@extends('front.desktop.layouts.store')

@section('title', __('ui.account.profile.page_title'))

@section('content')
    @include('front.desktop.account.partials.breadcrumbs', ['items' => [
        ['label' => __('ui.account.breadcrumb.home'), 'url' => route('home')],
        ['label' => __('ui.account.breadcrumb.account'), 'url' => route('account.dashboard')],
        ['label' => __('ui.account.profile.title')],
    ]])

    <section class="mb-8 border border-slate-200 bg-slate-100 px-6 py-6 text-center">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ __('ui.account.profile.title') }}</h1>
        <p class="mt-2 text-slate-600">{{ __('ui.account.profile.subtitle') }}</p>
    </section>

    <div class="grid gap-6 lg:grid-cols-[260px_minmax(0,1fr)]" data-address-autofill data-address-source="{{ $placesAssetUrl }}">
        @include('front.desktop.account.partials.nav', ['current' => 'profile'])

        <div class="space-y-8">
            <form method="POST" action="{{ route('account.profile.update') }}" class="border border-slate-200 bg-white p-6">
                @csrf
                @method('PUT')

                <h2 class="text-xl font-bold text-slate-900">{{ __('ui.account.profile.personal_info') }}</h2>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.display_name') }}</label><input type="text" name="name" value="{{ old('name', $user->name) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.email') }}</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.first_name') }}</label><input type="text" name="first_name" value="{{ old('first_name', $user->profile?->first_name) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.last_name') }}</label><input type="text" name="last_name" value="{{ old('last_name', $user->profile?->last_name) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.phone') }}</label><input type="text" name="phone" value="{{ old('phone', $user->profile?->phone) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.company') }}</label><input type="text" name="company" value="{{ old('company', $user->profile?->company) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.oib') }}</label><input type="text" name="oib" value="{{ old('oib', $user->profile?->oib) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                    <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.birthday') }}</label><input type="date" name="birthday" value="{{ old('birthday', optional($user->profile?->birthday)->format('Y-m-d')) }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0"></div>
                </div>

                <fieldset class="mt-4 border border-slate-200 p-3">
                    <legend class="px-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ __('ui.account.fields.gender') }}</legend>
                    @php
                        $genderValue = old('gender', $user->profile?->gender);
                    @endphp
                    <div class="flex items-center gap-6 text-sm text-slate-800">
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="gender" value="female" @checked($genderValue === 'female') class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0">
                            <span>{{ __('ui.account.gender.female') }}</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="radio" name="gender" value="male" @checked($genderValue === 'male') class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0">
                            <span>{{ __('ui.account.gender.male') }}</span>
                        </label>
                    </div>
                </fieldset>

                <button type="submit" class="mt-5 h-11 border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 hover:border-slate-500 hover:bg-slate-50">{{ __('ui.account.actions.save_profile') }}</button>
            </form>

            <form method="POST" action="{{ route('account.preferences.update') }}" class="border border-slate-200 bg-white p-6">
                @csrf
                @method('PUT')

                <h2 class="text-xl font-bold text-slate-900">{{ __('ui.account.profile.preferences') }}</h2>
                <div class="mt-4 space-y-2 text-sm text-slate-700">
                    <label class="flex items-center gap-2"><input type="checkbox" name="newsletter_opt_in" value="1" @checked((bool) old('newsletter_opt_in', $user->profile?->newsletter_opt_in)) class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0"> {{ __('ui.account.profile.newsletter_opt_in') }}</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="gdpr_marketing_opt_in" value="1" @checked((bool) old('gdpr_marketing_opt_in', $preferencePayload['gdpr_marketing_opt_in'] ?? false)) class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0"> {{ __('ui.account.profile.gdpr_marketing') }}</label>
                    <label class="flex items-center gap-2"><input type="checkbox" name="gdpr_personalization_opt_in" value="1" @checked((bool) old('gdpr_personalization_opt_in', $preferencePayload['gdpr_personalization_opt_in'] ?? false)) class="h-4 w-4 border-slate-300 text-slate-900 focus:ring-0"> {{ __('ui.account.profile.gdpr_personalization') }}</label>
                </div>

                <button type="submit" class="mt-5 h-11 border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 hover:border-slate-500 hover:bg-slate-50">{{ __('ui.account.actions.save_preferences') }}</button>
            </form>

            <div class="grid gap-6 lg:grid-cols-2">
                @php
                    $addressForms = [
                        'billing' => $billing,
                        'shipping' => $shipping,
                    ];
                @endphp

                @foreach ($addressForms as $type => $address)
                    <form method="POST" action="{{ route('account.addresses.update', ['type' => $type]) }}" class="border border-slate-200 bg-white p-6" data-address-scope="{{ $type }}">
                        @csrf
                        @method('PUT')

                        <h2 class="text-xl font-bold text-slate-900">{{ __('ui.account.address.title', ['type' => __('ui.account.address.types.'.$type)]) }}</h2>
                        <div class="mt-4 grid gap-3">
                            <input type="text" name="first_name" value="{{ old('first_name', $address?->first_name) }}" placeholder="{{ __('ui.account.fields.first_name') }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0">
                            <input type="text" name="last_name" value="{{ old('last_name', $address?->last_name) }}" placeholder="{{ __('ui.account.fields.last_name') }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0">
                            <input type="text" name="company" value="{{ old('company', $address?->company) }}" placeholder="{{ __('ui.account.fields.company') }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0">
                            <input type="text" name="oib" value="{{ old('oib', $address?->oib) }}" placeholder="{{ __('ui.account.fields.oib') }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0">
                            <input type="text" name="vat_id" value="{{ old('vat_id', $address?->vat_id) }}" placeholder="{{ __('ui.account.fields.vat_id') }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0">
                            <input type="text" name="phone" value="{{ old('phone', $address?->phone) }}" placeholder="{{ __('ui.account.fields.phone') }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0">
                            <input type="text" name="address_line_1" value="{{ old('address_line_1', $address?->address_line_1) }}" placeholder="{{ __('ui.account.fields.address_line_1') }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" required>
                            <input type="text" name="postal_code" value="{{ old('postal_code', $address?->postal_code) }}" placeholder="{{ __('ui.account.fields.postal_code') }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-postal required>
                            <input type="text" name="city" value="{{ old('city', $address?->city) }}" placeholder="{{ __('ui.account.fields.city') }}" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-city required>
                            <select name="state" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-county>
                                <option value="">{{ __('ui.account.fields.select_county') }}</option>
                                @foreach ($countyOptions as $countyOption)
                                    <option value="{{ $countyOption }}" @selected(old('state', $address?->state) === $countyOption)>{{ $countyOption }}</option>
                                @endforeach
                            </select>
                            <select name="country_code" class="h-11 w-full border-slate-300 text-sm focus:border-slate-500 focus:ring-0" data-address-country required>
                                @foreach ($countryOptions as $countryOption)
                                    <option value="{{ $countryOption['code'] }}" @selected(old('country_code', $address?->country_code ?? 'HR') === $countryOption['code'])>{{ $countryOption['label'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="mt-4 h-11 border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-800 hover:border-slate-500 hover:bg-slate-50">{{ __('ui.account.actions.save_address', ['type' => __('ui.account.address.types.'.$type)]) }}</button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script defer src="{{ asset('front-theme/scripts/address-autofill.js') }}?v={{ filemtime(public_path('front-theme/scripts/address-autofill.js')) }}"></script>
@endpush
