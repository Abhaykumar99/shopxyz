@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'icon' => null,
    'iconEnd' => null,
    'loading' => null,
    'block' => false,
])

@php
    $classes = [
        'inline-flex items-center justify-center gap-2 rounded-field font-semibold whitespace-nowrap transition-colors select-none',
        'disabled:cursor-not-allowed disabled:opacity-50 aria-disabled:pointer-events-none aria-disabled:opacity-50',
        match ($size) {
            'sm' => 'min-h-9 px-3 text-sm',
            'lg' => 'min-h-13 px-6 text-lg',
            default => 'min-h-11 px-4 text-base',
        },
        match ($variant) {
            'secondary' => 'border border-line-strong bg-surface text-ink hover:bg-mist',
            'ghost' => 'text-berry hover:bg-berry-tint',
            'danger' => 'bg-danger text-white hover:bg-danger/90',
            default => 'bg-berry text-white hover:bg-berry-dark',
        },
        'w-full' => $block,
    ];
    $iconSize = match ($size) {
        'sm' => 16,
        'lg' => 22,
        default => 20,
    };
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-ui.icon :name="$icon" :size="$iconSize" />
        @endif
        <span>{{ $slot }}</span>
        @if ($iconEnd)
            <x-ui.icon :name="$iconEnd" :size="$iconSize" />
        @endif
    </a>
@else
    <button
        type="{{ $type }}"
        @if ($loading) wire:loading.attr="disabled" wire:target="{{ $loading }}" @endif
        {{ $attributes->class($classes) }}
    >
        @if ($loading)
            <span wire:loading.flex wire:target="{{ $loading }}" class="hidden">
                <x-ui.icon name="loader-circle" :size="$iconSize" class="animate-spin" />
            </span>
        @endif
        @if ($icon)
            <span class="flex" @if ($loading) wire:loading.remove wire:target="{{ $loading }}" @endif>
                <x-ui.icon :name="$icon" :size="$iconSize" />
            </span>
        @endif
        <span>{{ $slot }}</span>
        @if ($iconEnd)
            <x-ui.icon :name="$iconEnd" :size="$iconSize" />
        @endif
    </button>
@endif
