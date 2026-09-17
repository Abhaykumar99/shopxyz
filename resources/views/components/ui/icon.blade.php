@props([
    'name',
    'size' => 20,
    'label' => null,
])

<svg
    xmlns="http://www.w3.org/2000/svg"
    width="{{ $size }}"
    height="{{ $size }}"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="2"
    stroke-linecap="round"
    stroke-linejoin="round"
    @if ($label) role="img" aria-label="{{ $label }}" @else aria-hidden="true" focusable="false" @endif
    {{ $attributes->class('shrink-0') }}
>{{-- Trusted markup: read from the committed SVG files in resources/icons. --}}{!! \App\Support\Icon::inner($name) !!}</svg>
