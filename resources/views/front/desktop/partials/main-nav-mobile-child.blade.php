@php
    $children = collect($child['children'] ?? []);
    $padding = 1.5 + ($level * 0.9);
@endphp

<li class="border-b border-slate-200">
@if ($children->isNotEmpty())
        <details class="group/subnav">
            <summary class="flex cursor-pointer list-none items-center justify-between py-3 pr-3 text-[13px] text-slate-700 hover:bg-slate-50 hover:text-slate-900" style="padding-left: {{ $padding }}rem;">
                <span class="truncate pr-2">{{ $child['label'] ?? '' }}</span>
                <span class="inline-flex h-8 w-8 items-center justify-center text-[28px] font-semibold leading-none text-slate-400 group-open/subnav:hidden">+</span>
                <span class="hidden h-8 w-8 items-center justify-center text-[28px] font-semibold leading-none text-slate-400 group-open/subnav:inline-flex">-</span>
            </summary>
            <div>
                <a href="{{ $child['url'] ?? '#' }}" class="block border-t border-slate-200 py-3 text-[13px] font-semibold text-slate-600 hover:bg-slate-50" style="padding-left: {{ $padding }}rem;">
                    Otvori {{ $child['label'] ?? '' }}
                </a>
                <ul>
                    @foreach ($children as $nestedChild)
                        @include('front.desktop.partials.main-nav-mobile-child', ['child' => $nestedChild, 'level' => $level + 1])
                    @endforeach
                </ul>
            </div>
        </details>
    @else
        <a href="{{ $child['url'] ?? '#' }}" class="block py-3 text-[13px] text-slate-700 hover:bg-slate-100 hover:text-slate-900" style="padding-left: {{ $padding }}rem;">
            {{ $child['label'] ?? '' }}
        </a>
    @endif
</li>
