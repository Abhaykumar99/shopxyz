@props([
    'href' => null,
    'icon' => null,
    'tone' => 'default',
])

@php
    $classes = [
        'flex min-h-11 w-full items-center gap-3 px-4 text-start text-base hover:bg-mist',
        $tone === 'danger' ? 'text-danger' : 'text-ink',
    ];
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
@else
    <button type="button" {{ $attributes->class($classes) }}>
@endif
    @if ($icon)
        <x-ui.icon :name="$icon" class="{{ $tone === 'danger' ? '' : 'text-ink-soft' }}" />
    @endif
    <span>{{ $slot }}</span>
@if ($href)
    </a>
@else
    </button>
@endif
