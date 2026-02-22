@php
    $children = collect($child['children'] ?? []);
    $padding = 0.6 + ($level * 0.95);
@endphp

@if ($children->isNotEmpty())
    <details class="border-bottom">
        <summary class="list-unstyled d-flex align-items-center justify-content-between py-2 pe-2" style="padding-left: {{ $padding }}rem;">
            <span class="d-flex align-items-center">
                <i class="fa fa-angle-right opacity-50 me-2"></i>
                <span class="font-500">{{ $child['label'] ?? '' }}</span>
            </span>
            <span class="opacity-60 menu-toggle-plus menu-toggle-sign">+</span>
            <span class="opacity-60 menu-toggle-minus menu-toggle-sign">-</span>
        </summary>
        <div class="pb-2">
            <a href="{{ $child['url'] ?? '#' }}" class="close-menu d-block border-top border-bottom py-1.5 text-decoration-none" style="padding-left: {{ $padding + 0.9 }}rem;">
                <span class="font-600">Otvori {{ $child['label'] ?? '' }}</span>
            </a>
            @foreach ($children as $nestedChild)
                @include('front.mobile.partials.menu-main-child', ['child' => $nestedChild, 'level' => $level + 1])
            @endforeach
        </div>
    </details>
@else
    <a href="{{ $child['url'] ?? '#' }}" class="close-menu d-flex align-items-center border-bottom py-1 text-decoration-none" style="padding-left: {{ $padding }}rem;">
        <i class="fa fa-angle-right opacity-50 me-2"></i>
        <span class="font-500">{{ $child['label'] ?? '' }}</span>
    </a>
@endif
