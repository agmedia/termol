@extends('front.desktop.layouts.store')

@php
    $translation = $post->translations->firstWhere('locale', $locale)
        ?? $post->translations->firstWhere('locale', $fallbackLocale);
@endphp

@section('title', $translation?->title ?? 'Blog post')

@section('content')
    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <a href="{{ route('blog.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-800">← Back to blog</a>

        <p class="mt-4 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ optional($post->published_at)->format('Y-m-d') ?: 'Draft' }}</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-900">{{ $translation?->title ?? $post->code }}</h1>
        <p class="mt-4 text-slate-700">{{ $translation?->excerpt }}</p>

        <div class="prose mt-6 max-w-none prose-slate">
            {!! $translation?->body_html ?: '<p>No body content available.</p>' !!}
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="mt-10">
            <h2 class="text-2xl font-bold text-slate-900">Related posts</h2>
            <div class="mt-4 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($related as $post)
                    @php
                        $relatedTranslation = $post->translations->firstWhere('locale', $locale)
                            ?? $post->translations->firstWhere('locale', $fallbackLocale);
                    @endphp

                    <a href="{{ route('blog.show', ['slug' => $relatedTranslation?->slug ?? $post->id]) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                        <h3 class="text-lg font-semibold text-slate-900">{{ $relatedTranslation?->title ?? $post->code }}</h3>
                        <p class="mt-2 line-clamp-3 text-sm text-slate-600">{{ $relatedTranslation?->excerpt ?: 'Open post details.' }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
@endsection
