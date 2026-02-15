@php
    $payload = $block->payload ?? [];
@endphp

<section class="rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 to-white p-8 md:p-10">
    <div class="mx-auto max-w-4xl">
        @if (!empty($translation?->title))
            <h2 class="text-2xl font-semibold tracking-tight text-slate-900 md:text-3xl">{{ $translation->title }}</h2>
        @endif
        @if (!empty($translation?->subtitle))
            <p class="mt-3 text-base text-slate-600">{{ $translation->subtitle }}</p>
        @endif
        @if (!empty($translation?->cta_label) && !empty($translation?->cta_url))
            <a href="{{ $translation->cta_url }}" class="mt-6 inline-flex rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                {{ $translation->cta_label }}
            </a>
        @endif
        @if (!empty($payload['note']))
            <p class="mt-4 text-xs uppercase tracking-[0.14em] text-slate-500">{{ $payload['note'] }}</p>
        @endif
    </div>
</section>

