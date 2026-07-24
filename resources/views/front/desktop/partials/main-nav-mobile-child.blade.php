@php
    $children = collect($child['children'] ?? []);
    $depthClass = 'desktop-mobile-depth-'.min(4, max(0, (int) $level));
    $labelWeightClass = $level === 0 ? 'font-semibold' : 'font-medium';
    $leafWeightClass = $level === 0 ? 'font-medium' : 'font-light';
    $target = !empty($child['open_in_new_tab']) ? '_blank' : null;
    $rel = !empty($child['open_in_new_tab']) ? 'noopener noreferrer' : null;
@endphp

<li>
@if ($children->isNotEmpty())
        <details class="group/subnav desktop-mobile-menu-group" data-mobile-menu-accordion>
            <summary class="{{ $depthClass }} desktop-mobile-menu-row relative flex min-h-[52px] cursor-pointer list-none items-center py-3 pr-3 text-[13px] text-slate-700 hover:bg-slate-50 hover:text-slate-900">
                <a
                    href="{{ $child['url'] ?? '#' }}"
                    class="min-w-0 flex-1 truncate pr-11 {{ $labelWeightClass }}"
                    data-mobile-nav-link
                    @if($target) target="{{ $target }}" rel="{{ $rel }}" @endif
                >
                    {{ $child['label'] ?? '' }}
                </a>
                <span class="absolute right-3 top-1/2 inline-flex h-7 w-7 -translate-y-1/2 items-center justify-center border border-slate-300 bg-white text-slate-500 group-open/subnav:hidden" aria-hidden="true">
                    <x-fa-icon name="plus" class="h-3.5 w-3.5" />
                </span>
                <span class="absolute right-3 top-1/2 hidden h-7 w-7 -translate-y-1/2 items-center justify-center border border-slate-300 bg-white text-slate-500 group-open/subnav:inline-flex" aria-hidden="true">
                    <x-fa-icon name="minus" class="h-3.5 w-3.5" />
                </span>
            </summary>
            <ul class="desktop-mobile-menu-children">
                @foreach ($children as $nestedChild)
                    @include('front.desktop.partials.main-nav-mobile-child', ['child' => $nestedChild, 'level' => $level + 1])
                @endforeach
            </ul>
        </details>
    @else
        <a href="{{ $child['url'] ?? '#' }}" class="{{ $depthClass }} desktop-mobile-menu-row flex min-h-[52px] items-center py-3 text-[13px] {{ $leafWeightClass }} text-slate-700 hover:bg-slate-100 hover:text-slate-900">
            {{ $child['label'] ?? '' }}
        </a>
    @endif
</li>
