@props([
    'icon',
    'label',
    'href' => null,
    'variant' => 'ghost',
    'count' => null,
    'type' => 'button',
])

@php
    $classes = [
        'relative inline-flex size-11 shrink-0 items-center justify-center rounded-full transition-colors',
        match ($variant) {
            'primary' => 'bg-berry text-white hover:bg-berry-dark',
            'secondary' => 'border border-line-strong bg-surface text-ink hover:bg-mist',
            default => 'text-ink hover:bg-mist',
        },
    ];
    $accessibleLabel = $count ? "{$label} ({$count})" : $label;
@endphp

@if ($href)
    <a href="{{ $href }}" aria-label="{{ $accessibleLabel }}" {{ $attributes->class($classes) }}>
@else
    <button type="{{ $type }}" aria-label="{{ $accessibleLabel }}" {{ $attributes->class($classes) }}>
@endif
    <x-ui.icon :name="$icon" :size="22" />
    @if ($count)
        <span aria-hidden="true" class="figures absolute -top-0.5 -right-0.5 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-berry px-1 text-xs font-semibold text-white ring-2 ring-surface">
            {{ $count > 99 ? '99+' : $count }}
        </span>
    @endif
@if ($href)
    </a>
@else
    </button>
@endif
