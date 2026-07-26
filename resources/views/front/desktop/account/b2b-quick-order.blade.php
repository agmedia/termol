@extends('front.desktop.layouts.store')

@section('title', __('B2B brza kupnja'))
@section('body_class', 'commerce-body account-commerce-body')
@section('main_class', 'commerce-main')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
@endpush

@section('content')
    <section class="front-soft-hero mb-8 px-4 py-6 text-center sm:px-6">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-700">{{ $b2bAccount->company_name }}</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ __('B2B brza kupnja') }}</h1>
        <p class="mt-2 text-slate-600">{{ __('Unesite šifru, SKU ili barkod i dodajte više artikala odjednom po ugovorenim cijenama.') }}</p>
    </section>

    <div class="account-layout">
        @include('front.desktop.account.partials.nav', ['current' => 'b2b_quick_order'])

        <div class="min-w-0 space-y-8">
            <section class="border border-slate-200 bg-white">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">{{ __('Unos artikala') }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ __('Za artikle s varijantama unesite SKU konkretne varijante.') }}</p>
                    </div>
                    @if ($b2bAccount->contract_number)
                        <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-800">{{ __('Ugovor') }} {{ $b2bAccount->contract_number }}</span>
                    @endif
                </div>

                <form method="POST" action="{{ route('account.b2b.quick-order.store') }}">
                    @csrf
                    @error('items')
                        <p class="mx-5 mt-4 border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700">{{ $message }}</p>
                    @enderror

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[620px] text-sm">
                            <thead class="bg-slate-100/70 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="w-16 px-4 py-3">#</th>
                                    <th class="px-4 py-3">{{ __('Šifra / SKU / barkod') }}</th>
                                    <th class="w-44 px-4 py-3">{{ __('Količina') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for ($index = 0; $index < 10; $index++)
                                    <tr class="border-t border-slate-200">
                                        <td class="px-4 py-3 font-semibold text-slate-500">{{ $index + 1 }}</td>
                                        <td class="px-4 py-3">
                                            <input type="text" name="items[{{ $index }}][identifier]" value="{{ old('items.'.$index.'.identifier', $index === 0 ? (string) request('code') : '') }}" class="w-full border-slate-300 px-3 py-2 text-sm" data-quick-order-identifier autocomplete="off">
                                        </td>
                                        <td class="px-4 py-3">
                                            <input type="number" min="1" max="999" name="items[{{ $index }}][quantity]" value="{{ old('items.'.$index.'.quantity', 1) }}" class="w-full border-slate-300 px-3 py-2 text-sm">
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                    <div class="flex flex-wrap items-center gap-3 border-t border-slate-200 px-5 py-4">
                        <button type="submit" class="commerce-primary-action px-5 py-3 text-sm font-semibold">{{ __('Dodaj sve u košaricu') }}</button>
                        <a href="{{ route('cart.index') }}" class="commerce-secondary-action px-4 py-2.5 text-sm font-semibold">{{ __('Otvori košaricu') }}</a>
                    </div>
                </form>
            </section>

            @foreach ([
                [__('Često naručivani artikli'), $frequentProducts, __('Na temelju prethodnih narudžbi')],
                [__('Favoriti'), $favoriteProducts, __('Artikli spremljeni na listu želja')],
            ] as [$title, $products, $subtitle])
                <section>
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <h2 class="text-2xl font-bold text-slate-900">{{ $title }}</h2>
                            <p class="mt-1 text-sm text-slate-600">{{ $subtitle }}</p>
                        </div>
                    </div>

                    <div class="mt-4 overflow-hidden border border-slate-200 bg-white">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-100/70 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">{{ __('Artikl') }}</th>
                                    <th class="px-4 py-3">{{ __('Šifra') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Vaša cijena') }}</th>
                                    <th class="px-4 py-3 text-right">{{ __('Akcija') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $row)
                                    <tr class="border-t border-slate-200">
                                        <td class="px-4 py-3 font-semibold text-slate-900">{{ $row['name'] }}</td>
                                        <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $row['identifier'] }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                            {{ \App\Support\Currency::format((float) $row['price']['current_gross'], 'EUR') }}
                                            @if (($row['price']['price_source'] ?? '') === 'b2b')
                                                <span class="ml-1 rounded-full bg-cyan-50 px-2 py-0.5 text-[10px] uppercase text-cyan-800">B2B</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button type="button" data-quick-order-code="{{ $row['identifier'] }}" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">{{ __('Dodaj u unos') }}</button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ __('Još nema artikala u ovoj grupi.') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('click', function (event) {
            const button = event.target.closest('[data-quick-order-code]');
            if (!button) return;
            const inputs = Array.from(document.querySelectorAll('[data-quick-order-identifier]'));
            const target = inputs.find((input) => input.value.trim() === '') || inputs[0];
            if (!target) return;
            target.value = button.dataset.quickOrderCode || '';
            target.focus();
            target.scrollIntoView({behavior: 'smooth', block: 'center'});
        });
    </script>
@endpush
