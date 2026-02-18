@extends('front.desktop.layouts.store')

@section('title', 'Blog')

@section('content')
    <section class="mb-8">
        <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">Blog</h1>
        <p class="mt-2 text-slate-600">Stories, guides, and product updates from the catalog team.</p>
    </section>

    @if ($topBlocks->isNotEmpty())
        <section class="mb-8">@include('components.content-placement', ['items' => $topBlocks])</section>
    @endif

    @if ($posts->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">No blog posts published yet.</div>
    @else
        <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($posts as $post)
                @php
                    $translation = $post->translations->firstWhere('locale', $locale)
                        ?? $post->translations->firstWhere('locale', $fallbackLocale);
                @endphp

                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ optional($post->published_at)->format('Y-m-d') ?: 'Draft' }}</p>
                    <h2 class="mt-2 text-xl font-bold text-slate-900">{{ $translation?->title ?? $post->code }}</h2>
                    <p class="mt-3 line-clamp-4 text-sm text-slate-600">{{ $translation?->excerpt ?: 'Open the post for full body content.' }}</p>
                    <a href="{{ route('blog.show', ['slug' => $translation?->slug ?? $post->id]) }}" class="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800">Read article</a>
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $posts->links() }}</div>
    @endif

    @if ($bottomBlocks->isNotEmpty())
        <section class="mt-10">@include('components.content-placement', ['items' => $bottomBlocks])</section>
    @endif
@endsection
