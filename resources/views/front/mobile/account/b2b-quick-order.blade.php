@extends('front.mobile.layouts.store')

@section('title', __('B2B brza kupnja'))
@section('header_title', __('B2B'))
@section('page_title', __('Brza kupnja'))
@section('body_class', 'mobile-commerce-body mobile-account-commerce-body')

@push('head')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
    <link rel="stylesheet" href="{{ asset('front-theme/styles/b2b-quick-order.css') }}?v={{ filemtime(public_path('front-theme/styles/b2b-quick-order.css')) }}">
@endpush

@section('content')
    <div class="card card-style">
        <div class="content">
            <p class="font-11 font-700 text-uppercase color-highlight mb-1">{{ $b2bAccount->company_name }}</p>
            <h3 class="mb-1">{{ __('B2B brza kupnja') }}</h3>
            <p class="mb-3">{{ __('Pretražujte po nazivu, šifri, SKU-u ili barkodu i odaberite artikl iz rezultata.') }}</p>
        </div>

        @include('front.shared.account.b2b-quick-order-form')
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
                        <button type="button" data-quick-order-query="{{ $row['identifier'] }}" class="btn btn-xxs rounded-0 border border-gray-dark color-theme bg-white ms-auto">{{ __('Pronađi') }}</button>
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
    <script defer src="{{ asset('front-theme/scripts/b2b-quick-order.js') }}?v={{ filemtime(public_path('front-theme/scripts/b2b-quick-order.js')) }}"></script>
@endpush
