@extends('front.mobile.layouts.store')

@section('title', 'Blog')
@section('header_title', 'News')
@section('page_title', 'Blog')

@section('content')
    @if ($topBlocks->isNotEmpty())
        @include('components.content-placement', ['items' => $topBlocks])
    @endif

    <div class="card card-style">
        <div class="content mb-0">
            <p class="mb-n1 color-highlight font-600">Articles</p>
            <h2 class="font-800">Latest Posts</h2>
            <p>AppKit style article listing for blog module.</p>

            @forelse ($posts as $post)
                @php
                    $translation = $post->translations->firstWhere('locale', $locale)
                        ?? $post->translations->firstWhere('locale', $fallbackLocale);
                @endphp
                <a href="{{ route('blog.show', ['slug' => $translation?->slug ?? $post->id]) }}" class="d-flex mb-4 d-block">
                    <div class="me-auto">
                        <h5 class="font-600 pt-2 mb-1">{{ $translation?->title ?? $post->code }}</h5>
                        <span class="color-highlight opacity-60 font-12">{{ optional($post->published_at)->format('Y-m-d') ?: 'Draft' }}</span>
                    </div>
                    <div class="ms-3 mb-2">
                        <img src="{{ asset('front-theme/images/pictures/16s.jpg') }}" class="rounded-sm" width="86" alt="Post">
                    </div>
                </a>
            @empty
                <p class="mb-0">No blog posts available.</p>
            @endforelse
        </div>
    </div>

    @if ($posts->hasPages())
        <div class="card card-style"><div class="content">{{ $posts->links('pagination::bootstrap-5') }}</div></div>
    @endif

    @if ($bottomBlocks->isNotEmpty())
        @include('components.content-placement', ['items' => $bottomBlocks])
    @endif
@endsection
