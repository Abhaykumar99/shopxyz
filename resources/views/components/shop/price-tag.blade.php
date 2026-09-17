{{--
    The signature shelf tag. `offer` renders the marigold discount tag,
    otherwise it renders the price on a berry tag.
--}}
@props([
    'paise' => null,
    'mrp' => null,
    'offer' => false,
    'size' => 'md',
])

@php
    $percent = $mrp !== null && $paise !== null ? \App\Support\Money::discountPercent($mrp, $paise) : 0;
@endphp

@if ($offer)
    @if ($percent > 0)
        <span {{ $attributes->class([
            'tag-shape figures inline-flex items-center bg-marigold pe-2.5 font-display font-bold text-ink',
            $size === 'sm' ? 'h-6 text-xs' : 'h-7 text-sm',
        ]) }}>{{ $percent }}% off</span>
    @endif
@else
    <span {{ $attributes->class([
        'tag-shape inline-flex items-center gap-2 bg-berry-tint pe-3',
        $size === 'lg' ? 'h-11' : 'h-9',
    ]) }}>
        <x-shop.price :paise="$paise" :mrp="$mrp" :size="$size === 'lg' ? 'lg' : 'md'" />
    </span>
@endif
