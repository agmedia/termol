@props([
    'name',
    'style' => 'solid',
])

@php
    $resolvedStyle = in_array($style, ['solid', 'regular', 'brands'], true) ? $style : 'solid';
    $resolvedName = preg_replace('/[^a-z0-9-]/', '', strtolower((string) $name));
    $spriteUrl = asset("front-theme/fonts/sprites/{$resolvedStyle}.svg").'#'.$resolvedName;
@endphp

<svg
    {{ $attributes->class(['fa6-icon'])->merge(['fill' => 'currentColor']) }}
    aria-hidden="true"
    focusable="false"
>
    <use href="{{ $spriteUrl }}"></use>
</svg>
