@php
    $reviewCount = (int) ($count ?? 0);
    $reviewAverage = max(0, min(5, (float) ($average ?? 0)));
    $reviewHref = trim((string) ($href ?? ''));
    $reviewCompact = (string) ($size ?? 'default') === 'compact';
    $reviewClass = trim((string) ($class ?? ''));
    $reviewFillPercentage = $reviewAverage > 0 ? ($reviewAverage / 5) * 100 : 0;
    $reviewCountLabel = trans_choice('ui.product.reviews_count', $reviewCount, ['count' => $reviewCount]);
    $reviewAriaLabel = __('ui.product.reviews_summary_aria', [
        'count' => $reviewCount,
        'rating' => number_format($reviewAverage, 1, '.', ''),
    ]);
    $reviewTag = $reviewHref !== '' ? 'a' : 'div';
@endphp

@if ($reviewCount > 0)
    <{{ $reviewTag }}
        @if ($reviewHref !== '') href="{{ $reviewHref }}" @endif
        @if ($reviewClass !== '') class="{{ $reviewClass }}" @endif
        style="display:inline-flex;align-items:center;gap:{{ $reviewCompact ? '8px' : '10px' }};max-width:100%;color:#475569;text-decoration:none;"
        aria-label="{{ $reviewAriaLabel }}"
    >
        <span
            aria-hidden="true"
            style="position:relative;display:inline-block;flex-shrink:0;font-size:{{ $reviewCompact ? '12px' : '13px' }};line-height:1;letter-spacing:1.6px;color:#cbd5e1;white-space:nowrap;"
        >
            ★★★★★
            <span style="position:absolute;left:0;top:0;overflow:hidden;width:{{ number_format($reviewFillPercentage, 2, '.', '') }}%;color:#0f172a;white-space:nowrap;">★★★★★</span>
        </span>
        <span
            style="font-size:{{ $reviewCompact ? '12px' : '13px' }};font-weight:600;line-height:1.15;color:#475569;{{ $reviewHref !== '' ? 'text-decoration:underline;text-underline-offset:2px;' : '' }}"
        >
            {{ $reviewCountLabel }}
        </span>
    </{{ $reviewTag }}>
@endif
