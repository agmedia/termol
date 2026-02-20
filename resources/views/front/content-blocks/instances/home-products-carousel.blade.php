<section class="rounded-2xl border border-slate-200 bg-white p-6">
    @if(!empty($translation?->title))
        <h2 class="text-xl font-semibold text-slate-900">{{ $translation->title }}</h2>
    @endif
    @if(!empty($translation?->subtitle))
        <p class="mt-2 text-sm text-slate-600">{{ $translation->subtitle }}</p>
    @endif
</section>
