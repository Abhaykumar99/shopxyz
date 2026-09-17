{{-- Product photo, or a category-coloured placeholder when there is no photo yet. --}}
@props([
    'src' => null,
    'alt' => '',
    'category' => null,
])

@php
    [$tint, $icon] = match ($category) {
        'confectionery' => ['bg-accent-tint text-accent-ink', 'candy'],
        'gifts' => ['bg-pistachio-tint text-pistachio', 'gift'],
        'cosmetics' => ['bg-brand-tint text-brand', 'sparkles'],
        default => ['bg-mist text-ink-soft', 'package'],
    };
@endphp

@if ($src)
    <img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" decoding="async" {{ $attributes->class('object-cover') }}>
@else
    <div role="img" aria-label="{{ $alt }}" {{ $attributes->class(['flex items-center justify-center', $tint]) }}>
        <x-ui.icon :name="$icon" :size="40" class="opacity-70" />
    </div>
@endif
