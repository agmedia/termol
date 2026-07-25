@extends('front.mobile.layouts.store')

@section('title', __('ui.checkout.corvus.redirect_page_title'))
@section('header_title', __('ui.checkout.corvus.redirect_page_title'))
@section('page_title', __('ui.checkout.corvus.redirect_page_title'))

@push('head')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/checkout.css') }}?v={{ filemtime(public_path('front-theme/styles/checkout.css')) }}">
@endpush

@section('content')
    <div class="card card-style rounded-0">
        <div class="content">
            <h4 class="mb-2">{{ __('ui.checkout.corvus.redirect_title') }}</h4>
            <p class="font-13 opacity-70 mb-3">{{ __('ui.checkout.corvus.redirect_subtitle') }}</p>

            <form id="corvus-redirect-form-mobile" method="POST" action="{{ $formData['action_url'] }}">
                @foreach (($formData['payload'] ?? []) as $field => $value)
                    <input type="hidden" name="{{ $field }}" value="{{ (string) $value }}">
                @endforeach

                <button type="submit" class="checkout-primary-button btn btn-margins btn-full font-13 btn-l font-600">
                    {{ __('ui.checkout.corvus.redirect_button') }}
                </button>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            window.setTimeout(function () {
                var form = document.getElementById('corvus-redirect-form-mobile');
                if (form) {
                    form.submit();
                }
            }, 120);
        </script>
    @endpush
@endsection
