@extends('front.mobile.layouts.store')

@section('title', $title)
@section('header_title', __('B2B'))
@section('page_title', $title)
@section('body_class', 'mobile-commerce-body mobile-account-commerce-body')

@push('head')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/commerce-pages.css') }}?v={{ filemtime(public_path('front-theme/styles/commerce-pages.css')) }}">
@endpush

@section('content')
    <div class="card card-style">
        <div class="content">
            <p class="font-11 font-700 text-uppercase color-highlight mb-1">{{ $b2bAccount->company_name }}</p>
            <h3 class="mb-1">{{ $title }}</h3>
            <p class="mb-0">{{ $subtitle }}</p>
        </div>
    </div>

    <div class="card card-style">
        <div class="content">
            @forelse ($products as $row)
                <div class="d-flex align-items-center">
                    <div class="pe-2">
                        <h6 class="font-13 mb-1">{{ $row['name'] }}</h6>
                        <p class="font-11 opacity-60 mb-0">
                            {{ $row['identifier'] }} · {{ \App\Support\Currency::format((float) $row['price']['current_gross'], 'EUR') }}
                        </p>
                    </div>
                    <a href="{{ route('account.b2b.quick-order', ['code' => $row['identifier']]) }}" class="btn btn-xxs rounded-0 border border-gray-dark color-theme bg-white ms-auto">
                        {{ __('Dodaj') }}
                    </a>
                </div>
                @if (! $loop->last)<div class="divider my-2"></div>@endif
            @empty
                <p class="mb-0 opacity-60">{{ __('Još nema artikala u ovoj grupi.') }}</p>
            @endforelse
        </div>
    </div>
@endsection
