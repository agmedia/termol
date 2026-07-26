@extends('front.mobile.layouts.store')

@section('title', __('B2B brza kupnja'))
@section('header_title', __('B2B'))
@section('page_title', __('Brza kupnja'))
@section('body_class', 'mobile-commerce-body mobile-account-commerce-body')

@push('head')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
@endpush

@section('content')
    <div class="card card-style">
        <div class="content">
            <p class="font-11 font-700 text-uppercase color-highlight mb-1">{{ $b2bAccount->company_name }}</p>
            <h3 class="mb-1">{{ __('B2B brza kupnja') }}</h3>
            <p class="mb-3">{{ __('Unesite šifru, SKU ili barkod. Za varijante koristite SKU varijante.') }}</p>

            <form method="POST" action="{{ route('account.b2b.quick-order.store') }}">
                @csrf
                @error('items') <p class="font-12 font-600 color-red-dark">{{ $message }}</p> @enderror

                @for ($index = 0; $index < 8; $index++)
                    <div class="row mb-2">
                        <div class="col-8 pe-1">
                            <label class="font-11 opacity-60 mb-1">{{ __('Šifra') }} {{ $index + 1 }}</label>
                            <input type="text" name="items[{{ $index }}][identifier]" value="{{ old('items.'.$index.'.identifier', $index === 0 ? (string) request('code') : '') }}" class="form-control rounded-0" data-quick-order-identifier>
                        </div>
                        <div class="col-4 ps-1">
                            <label class="font-11 opacity-60 mb-1">{{ __('Količina') }}</label>
                            <input type="number" min="1" max="999" name="items[{{ $index }}][quantity]" value="{{ old('items.'.$index.'.quantity', 1) }}" class="form-control rounded-0">
                        </div>
                    </div>
                @endfor

                <button type="submit" class="commerce-primary-action btn btn-full font-600 mt-3">{{ __('Dodaj sve u košaricu') }}</button>
            </form>
        </div>
    </div>

    @foreach ([
        [__('Često naručivano'), $frequentProducts],
        [__('Favoriti'), $favoriteProducts],
    ] as [$title, $products])
        <div class="card card-style">
            <div class="content">
                <h4 class="mb-3">{{ $title }}</h4>
                @forelse ($products as $row)
                    <div class="d-flex align-items-center">
                        <div class="pe-2">
                            <h6 class="font-13 mb-1">{{ $row['name'] }}</h6>
                            <p class="font-11 opacity-60 mb-0">{{ $row['identifier'] }} · {{ \App\Support\Currency::format((float) $row['price']['current_gross'], 'EUR') }}</p>
                        </div>
                        <button type="button" data-quick-order-code="{{ $row['identifier'] }}" class="btn btn-xxs rounded-0 border border-gray-dark color-theme bg-white ms-auto">{{ __('Dodaj') }}</button>
                    </div>
                    @if (! $loop->last)<div class="divider my-2"></div>@endif
                @empty
                    <p class="mb-0 opacity-60">{{ __('Još nema artikala u ovoj grupi.') }}</p>
                @endforelse
            </div>
        </div>
    @endforeach
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
