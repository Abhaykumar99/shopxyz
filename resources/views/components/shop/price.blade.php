@props([
    'paise',
    'mrp' => null,
    'size' => 'md',
])

@php
    $showMrp = $mrp !== null && $mrp > $paise;
@endphp

<span {{ $attributes->class('inline-flex flex-wrap items-baseline gap-x-2') }}>
    <data value="{{ $paise / 100 }}" @class([
        'figures font-display font-bold text-ink',
        'text-base' => $size === 'sm',
        'text-lg' => $size === 'md',
        'text-2xl' => $size === 'lg',
    ])>{{ \App\Support\Money::format($paise) }}</data>
    @if ($showMrp)
        <span class="figures text-sm text-ink-soft">
            <span class="sr-only">MRP</span>
            <s>{{ \App\Support\Money::format($mrp) }}</s>
        </span>
    @endif
</span>
