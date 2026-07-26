@extends('front.desktop.layouts.store')

@section('title', __('B2B brza kupnja'))
@section('body_class', 'commerce-body account-commerce-body')
@section('main_class', 'commerce-main')

@push('styles')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
    <link rel="stylesheet" href="{{ asset('front-theme/styles/b2b-quick-order.css') }}?v={{ filemtime(public_path('front-theme/styles/b2b-quick-order.css')) }}">
@endpush

@section('content')
    <section class="front-soft-hero mb-8 px-4 py-6 text-center sm:px-6">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-cyan-700">{{ $b2bAccount->company_name }}</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ __('B2B brza kupnja') }}</h1>
        <p class="mt-2 text-slate-600">{{ __('Pronađite artikle po nazivu, šifri, SKU-u ili barkodu i dodajte ih po ugovorenim cijenama.') }}</p>
    </section>

    <div class="account-layout">
        @include('front.desktop.account.partials.nav', ['current' => 'b2b_quick_order'])

        <div class="min-w-0 space-y-8">
            <section class="border border-slate-200 bg-white">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900">{{ __('Unos artikala') }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ __('Odaberite proizvod ili konkretnu varijantu iz rezultata pretrage.') }}</p>
                    </div>
                    @if ($b2bAccount->contract_number)
                        <span class="rounded-full bg-cyan-50 px-3 py-1 text-xs font-semibold text-cyan-800">{{ __('Ugovor') }} {{ $b2bAccount->contract_number }}</span>
                    @endif
                </div>

                @include('front.shared.account.b2b-quick-order-form')
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
                                            @if (!empty($row['price']['is_b2b_price']))
                                                <span class="ml-1 rounded-full bg-cyan-50 px-2 py-0.5 text-[10px] uppercase text-cyan-800">B2B</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button type="button" data-quick-order-query="{{ $row['identifier'] }}" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">{{ __('Pronađi i dodaj') }}</button>
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
    <script defer src="{{ asset('front-theme/scripts/b2b-quick-order.js') }}?v={{ filemtime(public_path('front-theme/scripts/b2b-quick-order.js')) }}"></script>
@endpush
