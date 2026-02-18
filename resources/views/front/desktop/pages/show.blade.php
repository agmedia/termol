@extends('front.desktop.layouts.store')

@php
    $translation = $page->translations->firstWhere('locale', $locale)
        ?? $page->translations->firstWhere('locale', $fallbackLocale);
@endphp

@section('title', $translation?->title ?? 'Page')

@section('content')
    @if ($topBlocks->isNotEmpty())
        <section class="mb-8">@include('components.content-placement', ['items' => $topBlocks])</section>
    @endif

    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">{{ $translation?->title ?? $page->code }}</h1>
        @if (!empty($translation?->excerpt))
            <p class="mt-3 text-slate-600">{{ $translation->excerpt }}</p>
        @endif

        <div class="prose mt-6 max-w-none prose-slate">
            {!! $translation?->body_html ?: '<p>This page has no body content.</p>' !!}
        </div>
    </article>

    @if ($bottomBlocks->isNotEmpty())
        <section class="mt-10">@include('components.content-placement', ['items' => $bottomBlocks])</section>
    @endif
@endsection
