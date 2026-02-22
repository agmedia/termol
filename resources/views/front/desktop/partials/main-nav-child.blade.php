@php
    $children = collect($child['children'] ?? []);
    $padding = 0.75 + ($level * 0.85);
@endphp
<li>
    <a href="{{ $child['url'] ?? '#' }}" class="flex items-center justify-between rounded-md px-2 py-2 text-slate-700 transition hover:bg-slate-100 hover:text-slate-900" style="padding-left: {{ $padding }}rem;">
        <span>{{ $child['label'] ?? '' }}</span>
        @if ($children->isNotEmpty())
            <svg class="h-3.5 w-3.5 text-slate-400" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                <path d="M8 6l4 4-4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        @endif
    </a>

    @if ($children->isNotEmpty())
        <ul class="space-y-1 pb-1">
            @foreach ($children as $nestedChild)
                @include('front.desktop.partials.main-nav-child', ['child' => $nestedChild, 'level' => $level + 1])
            @endforeach
        </ul>
    @endif
</li>
