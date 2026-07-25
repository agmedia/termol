@php
    $children = collect($child['children'] ?? []);
    $padding = 1.2 + ($level * 0.9);
    $imageUrl = trim((string) ($child['image_url'] ?? ''));
    $mainCategoryClass = $level === 0 ? 'mobile-nav-row--main-category' : '';
@endphp

@if ($children->isNotEmpty())
    <details class="mobile-nav-details">
        <summary class="mobile-nav-row mobile-nav-row--child {{ $mainCategoryClass }}" style="padding-left: {{ $padding }}rem;">
            <span class="mobile-nav-row-content">
                @if ($imageUrl !== '')
                    <span class="mobile-nav-category-thumb" aria-hidden="true">
                        <img src="{{ $imageUrl }}" alt="" loading="lazy" decoding="async">
                    </span>
                @endif
                <span class="mobile-nav-row-label">{{ $child['label'] ?? '' }}</span>
            </span>
            <span class="menu-toggle-plus menu-toggle-sign">+</span>
            <span class="menu-toggle-minus menu-toggle-sign">-</span>
        </summary>
        <div class="mobile-nav-children">
            <a href="{{ $child['url'] ?? '#' }}" class="close-menu mobile-nav-row mobile-nav-row--view-all text-decoration-none" style="padding-left: {{ $padding + 0.9 }}rem;">
                <span>Otvori {{ $child['label'] ?? '' }}</span>
            </a>
            @foreach ($children as $nestedChild)
                @include('front.mobile.partials.menu-main-child', ['child' => $nestedChild, 'level' => $level + 1])
            @endforeach
        </div>
    </details>
@else
    <a href="{{ $child['url'] ?? '#' }}" class="close-menu mobile-nav-row mobile-nav-row--child {{ $mainCategoryClass }} text-decoration-none" style="padding-left: {{ $padding }}rem;">
        <span class="mobile-nav-row-content">
            @if ($imageUrl !== '')
                <span class="mobile-nav-category-thumb" aria-hidden="true">
                    <img src="{{ $imageUrl }}" alt="" loading="lazy" decoding="async">
                </span>
            @endif
            <span class="mobile-nav-row-label">{{ $child['label'] ?? '' }}</span>
        </span>
    </a>
@endif
