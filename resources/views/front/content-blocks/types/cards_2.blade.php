@php
    $cards = $translation->payload['cards'] ?? $block->payload['cards'] ?? [];
@endphp

<section class="grid gap-4 md:grid-cols-2">
    @forelse ($cards as $card)
        <article class="rounded-2xl border border-slate-200 bg-white p-5">
            @if (!empty($card['title']))
                <h3 class="text-base font-semibold text-slate-900">{{ $card['title'] }}</h3>
            @endif
            @if (!empty($card['excerpt']))
                <p class="mt-2 text-sm text-slate-600">{{ $card['excerpt'] }}</p>
            @endif
            @if (!empty($card['url']) && !empty($card['label']))
                <a href="{{ $card['url'] }}" class="mt-4 inline-flex text-sm font-semibold text-slate-900 hover:text-slate-600">{{ $card['label'] }}</a>
            @endif
        </article>
    @empty
        <article class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-500 md:col-span-2">
            No cards configured for this block.
        </article>
    @endforelse
</section>

