@foreach ($items as $item)
    @php
        $block = $item['block'];
        $translation = $item['translation'];
        $slot = $item['slot'];
        $partial = 'front.content-blocks.types.'.$block->type;
    @endphp

    @if (view()->exists($partial))
        @include($partial, ['block' => $block, 'translation' => $translation, 'slot' => $slot])
    @else
        @include('front.content-blocks.types.custom', ['block' => $block, 'translation' => $translation, 'slot' => $slot])
    @endif
@endforeach

