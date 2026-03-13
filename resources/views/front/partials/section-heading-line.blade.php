@php
    $side = ($side ?? 'left') === 'right' ? 'right' : 'left';
    $wrapperClass = trim((string) ($wrapperClass ?? 'flex min-w-0 flex-1 items-center gap-2.5 md:gap-3'));
    $iconClass = trim((string) ($iconClass ?? 'h-3.5 w-3.5 shrink-0 text-slate-400 md:h-4 md:w-4'));
    $lineClass = trim((string) ($lineClass ?? 'h-px flex-1'));
    $lineColor = trim((string) ($lineColor ?? '#cbd5e1'));
    $lineStyle = "background-image: repeating-linear-gradient(to right, {$lineColor} 0, {$lineColor} 18px, transparent 18px, transparent 28px);";
@endphp

@if ($side === 'left')
    <span class="{{ $wrapperClass }}">
        <svg class="{{ $iconClass }}" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.55" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="6" cy="6" r="2.1"></circle>
            <circle cx="6" cy="14" r="2.1"></circle>
            <path d="M8 7.5 16 15.5"></path>
            <path d="M8 12.5 16 4.5"></path>
        </svg>
        <span class="{{ $lineClass }}" style="{{ $lineStyle }}"></span>
    </span>
@else
    <span class="{{ $wrapperClass }}">
        <span class="{{ $lineClass }}" style="{{ $lineStyle }}"></span>
        <svg class="{{ $iconClass }}" style="transform: rotate(180deg);" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.55" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <circle cx="6" cy="6" r="2.1"></circle>
            <circle cx="6" cy="14" r="2.1"></circle>
            <path d="M8 7.5 16 15.5"></path>
            <path d="M8 12.5 16 4.5"></path>
        </svg>
    </span>
@endif
