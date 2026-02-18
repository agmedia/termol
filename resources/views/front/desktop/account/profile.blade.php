@extends('front.desktop.layouts.store')

@section('title', 'Profile Settings')

@section('content')
    <section class="mb-8 flex items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Profile settings</h1>
            <p class="mt-2 text-slate-600">Manage personal info, GDPR switches, newsletter and default addresses.</p>
        </div>
        <a href="{{ route('account.dashboard') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100">Back to dashboard</a>
    </section>

    <div class="space-y-8">
        <form method="POST" action="{{ route('account.profile.update') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <h2 class="text-xl font-bold text-slate-900">Personal info</h2>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Display name</label><input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full rounded-lg border-slate-300 text-sm" required></div>
                <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label><input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full rounded-lg border-slate-300 text-sm" required></div>
                <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">First name</label><input type="text" name="first_name" value="{{ old('first_name', $user->profile?->first_name) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Last name</label><input type="text" name="last_name" value="{{ old('last_name', $user->profile?->last_name) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Phone</label><input type="text" name="phone" value="{{ old('phone', $user->profile?->phone) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Company</label><input type="text" name="company" value="{{ old('company', $user->profile?->company) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">OIB</label><input type="text" name="oib" value="{{ old('oib', $user->profile?->oib) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Birthday</label><input type="date" name="birthday" value="{{ old('birthday', optional($user->profile?->birthday)->format('Y-m-d')) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
                <div><label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Gender</label><input type="text" name="gender" value="{{ old('gender', $user->profile?->gender) }}" class="w-full rounded-lg border-slate-300 text-sm"></div>
            </div>

            <button type="submit" class="mt-5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save profile</button>
        </form>

        <form method="POST" action="{{ route('account.preferences.update') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf
            @method('PUT')

            <h2 class="text-xl font-bold text-slate-900">GDPR and newsletter</h2>
            <div class="mt-4 space-y-2 text-sm text-slate-700">
                <label class="flex items-center gap-2"><input type="checkbox" name="newsletter_opt_in" value="1" @checked((bool) old('newsletter_opt_in', $user->profile?->newsletter_opt_in)) class="rounded border-slate-300"> Newsletter opt-in</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="gdpr_marketing_opt_in" value="1" @checked((bool) old('gdpr_marketing_opt_in', $preferencePayload['gdpr_marketing_opt_in'] ?? false)) class="rounded border-slate-300"> GDPR marketing consent</label>
                <label class="flex items-center gap-2"><input type="checkbox" name="gdpr_personalization_opt_in" value="1" @checked((bool) old('gdpr_personalization_opt_in', $preferencePayload['gdpr_personalization_opt_in'] ?? false)) class="rounded border-slate-300"> GDPR personalization consent</label>
            </div>

            <button type="submit" class="mt-5 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save preferences</button>
        </form>

        <div class="grid gap-6 lg:grid-cols-2">
            @php
                $addressForms = [
                    'billing' => $billing,
                    'shipping' => $shipping,
                ];
            @endphp

            @foreach ($addressForms as $type => $address)
                <form method="POST" action="{{ route('account.addresses.update', ['type' => $type]) }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    @csrf
                    @method('PUT')

                    <h2 class="text-xl font-bold text-slate-900">{{ ucfirst($type) }} address</h2>
                    <div class="mt-4 grid gap-3">
                        <input type="text" name="first_name" value="{{ old('first_name', $address?->first_name) }}" placeholder="First name" class="w-full rounded-lg border-slate-300 text-sm">
                        <input type="text" name="last_name" value="{{ old('last_name', $address?->last_name) }}" placeholder="Last name" class="w-full rounded-lg border-slate-300 text-sm">
                        <input type="text" name="company" value="{{ old('company', $address?->company) }}" placeholder="Company" class="w-full rounded-lg border-slate-300 text-sm">
                        <input type="text" name="oib" value="{{ old('oib', $address?->oib) }}" placeholder="OIB" class="w-full rounded-lg border-slate-300 text-sm">
                        <input type="text" name="vat_id" value="{{ old('vat_id', $address?->vat_id) }}" placeholder="VAT ID" class="w-full rounded-lg border-slate-300 text-sm">
                        <input type="text" name="phone" value="{{ old('phone', $address?->phone) }}" placeholder="Phone" class="w-full rounded-lg border-slate-300 text-sm">
                        <input type="text" name="address_line_1" value="{{ old('address_line_1', $address?->address_line_1) }}" placeholder="Address line 1" class="w-full rounded-lg border-slate-300 text-sm" required>
                        <input type="text" name="address_line_2" value="{{ old('address_line_2', $address?->address_line_2) }}" placeholder="Address line 2" class="w-full rounded-lg border-slate-300 text-sm">
                        <input type="text" name="postal_code" value="{{ old('postal_code', $address?->postal_code) }}" placeholder="Postal code" class="w-full rounded-lg border-slate-300 text-sm" required>
                        <input type="text" name="city" value="{{ old('city', $address?->city) }}" placeholder="City" class="w-full rounded-lg border-slate-300 text-sm" required>
                        <input type="text" name="state" value="{{ old('state', $address?->state) }}" placeholder="State" class="w-full rounded-lg border-slate-300 text-sm">
                        <input type="text" name="country_code" value="{{ old('country_code', $address?->country_code ?? 'HR') }}" placeholder="Country code" class="w-full rounded-lg border-slate-300 text-sm" maxlength="2" required>
                    </div>

                    <button type="submit" class="mt-4 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save {{ $type }} address</button>
                </form>
            @endforeach
        </div>
    </div>
@endsection
