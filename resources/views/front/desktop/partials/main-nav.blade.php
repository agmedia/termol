@php
    $hasNavigation = !empty($mainNavigation ?? []);
@endphp

@if ($hasNavigation)
    @foreach ($mainNavigation as $item)
        @php
            $children = collect($item['children'] ?? []);
            $hasChildren = $children->isNotEmpty();
            $href = (string) ($item['url'] ?? '#');
            $target = !empty($item['open_in_new_tab']) ? '_blank' : null;
            $rel = !empty($item['open_in_new_tab']) ? 'noopener noreferrer' : null;
        @endphp

        @if ($hasChildren)
            <div class="group/nav relative">
                <a href="{{ $href }}" class="inline-flex items-center gap-1 py-6 hover:text-slate-600" @if($target) target="{{ $target }}" rel="{{ $rel }}" @endif>
                    <span>{{ $item['label'] }}</span>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </a>

                <div class="invisible absolute left-1/2 top-full z-50 mt-1 w-[22rem] -translate-x-1/2 rounded-xl border border-slate-200 bg-white p-3 opacity-0 shadow-xl transition-all duration-150 group-hover/nav:visible group-hover/nav:opacity-100">
                    <ul class="max-h-[60vh] space-y-1 overflow-y-auto pr-1 text-[12px] font-semibold uppercase tracking-wide text-slate-800">
                        @foreach ($children as $child)
                            @include('front.desktop.partials.main-nav-child', ['child' => $child, 'level' => 0])
                        @endforeach
                    </ul>
                </div>
            </div>
        @else
            <a href="{{ $href }}" class="py-6 hover:text-slate-600" @if($target) target="{{ $target }}" rel="{{ $rel }}" @endif>{{ $item['label'] }}</a>
        @endif
    @endforeach
@else
    <a href="{{ route('shop.index') }}" class="py-6 hover:text-slate-600">{{ __('ui.front.desktop.nav.new') }}</a>
    <a href="{{ route('categories.index') }}" class="py-6 hover:text-slate-600">Kategorije</a>
    @if ($catalogFeatures->useBlog())
        <a href="{{ route('blog.index') }}" class="py-6 hover:text-slate-600">{{ __('ui.front.desktop.nav.blog') }}</a>
    @endif
    <a href="{{ route('faq.index') }}" class="py-6 hover:text-slate-600">{{ __('ui.front.desktop.nav.faq') }}</a>
    <a href="{{ route('contact.create') }}" class="py-6 hover:text-slate-600">{{ __('ui.front.desktop.nav.contact') }}</a>
@endif
