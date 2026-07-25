@extends('front.mobile.layouts.store')

@section('title', 'Brendovi')
@section('header_title', 'Brendovi')
@section('page_title', 'Brendovi')
@section('body_class', 'brand-directory-page')

@section('content')
    @php
        $brandCount = $manufacturerGroups->flatten(1)->count();
    @endphp

    <main class="brand-directory-mobile">
        <header class="brand-directory-mobile-header">
            <h1>Brendovi</h1>
            <p>{{ $brandCount }} brendova</p>
        </header>

        @include('front.partials.manufacturer-directory')
    </main>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('front-theme/styles/manufacturer-directory.css') }}?v={{ filemtime(public_path('front-theme/styles/manufacturer-directory.css')) }}">
@endpush

@push('scripts')
    <script defer src="{{ asset('front-theme/scripts/manufacturer-directory.js') }}?v={{ filemtime(public_path('front-theme/scripts/manufacturer-directory.js')) }}"></script>
@endpush
