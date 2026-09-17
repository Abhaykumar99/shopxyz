{{-- Shop mark: the admin's logo, or a monogram tag with the shop name (ADR-013). --}}
@props([
    'href' => null,
    'compact' => false,
])

@php($tag = $href ? 'a' : 'span')

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->class('inline-flex min-w-0 items-center gap-2 text-ink') }}>
    @if ($shop->logoUrl())
        <img src="{{ $shop->logoUrl() }}" alt="{{ $shop->name }}" class="h-9 w-auto">
    @else
        <span aria-hidden="true" class="tag-shape figures inline-flex h-9 shrink-0 items-center bg-berry pe-2.5 font-display text-base font-bold text-white">{{ $shop->initials() }}</span>
        <span @class(['truncate font-display text-lg leading-tight font-bold', 'sr-only sm:not-sr-only' => $compact])>{{ $shop->name }}</span>
    @endif
</{{ $tag }}>
