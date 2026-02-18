@extends('front.mobile.layouts.store')

@php
    $translation = $page->translations->firstWhere('locale', $locale)
        ?? $page->translations->firstWhere('locale', $fallbackLocale);
@endphp

@section('title', $translation?->title ?? 'Page')
@section('header_title', 'Page')
@section('page_title', $translation?->title ?? 'Page')

@section('content')
    @if ($topBlocks->isNotEmpty())
        @include('components.content-placement', ['items' => $topBlocks])
    @endif

    <div class="card card-style">
        <div class="content">
            <h2>{{ $translation?->title ?? $page->code }}</h2>
            @if (!empty($translation?->excerpt))
                <p>{{ $translation->excerpt }}</p>
            @endif
            <div class="font-13">{!! $translation?->body_html ?: '<p>No content available.</p>' !!}</div>
        </div>
    </div>

    @if ($bottomBlocks->isNotEmpty())
        @include('components.content-placement', ['items' => $bottomBlocks])
    @endif
@endsection
