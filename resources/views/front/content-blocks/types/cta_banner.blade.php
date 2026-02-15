<section class="rounded-2xl border border-slate-200 bg-slate-900 px-6 py-7 text-white">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-xl font-semibold">{{ $translation->title ?? $block->name }}</h2>
            @if (!empty($translation?->subtitle))
                <p class="mt-2 text-sm text-slate-300">{{ $translation->subtitle }}</p>
            @endif
        </div>
        @if (!empty($translation?->cta_label) && !empty($translation?->cta_url))
            <a href="{{ $translation->cta_url }}" class="inline-flex rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-100">
                {{ $translation->cta_label }}
            </a>
        @endif
    </div>
</section>

