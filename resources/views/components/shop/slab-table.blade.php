{{--
    Quantity price slabs for a wholesale product (ADR-019).

    `layout="grid"` puts the slabs side by side inside a product card;
    `layout="rows"` lists them under each other for a product page.
    `quantity` highlights the slab that quantity is priced at; without it the
    cheapest slab is highlighted as the best price.
--}}
@props([
    'item',
    'layout' => 'grid',
    'quantity' => null,
])

@php
    $active = $quantity !== null && $quantity >= $item->moq()
        ? $item->slabFor((int) $quantity)['min']
        : ($quantity === null ? $item->slabs[array_key_last($item->slabs)]['min'] : null);
@endphp

<ul
    @class([
        'figures' => true,
        'grid grid-cols-3 gap-2 text-center text-sm' => $layout === 'grid',
        'flex flex-col divide-y divide-line' => $layout === 'rows',
    ])
    aria-label="Price per {{ $item->unit }} by quantity"
>
    @foreach ($item->slabs as $index => $slab)
        <li @class([
            'flex' => true,
            'flex-col justify-center rounded-field border px-2 py-2' => $layout === 'grid',
            'border-brand bg-brand-tint' => $layout === 'grid' && $slab['min'] === $active,
            'border-line' => $layout === 'grid' && $slab['min'] !== $active,
            'items-center justify-between gap-3 py-2' => $layout === 'rows',
            'font-semibold text-brand-dark' => $layout === 'rows' && $slab['min'] === $active,
        ])>
            <span @class([
                'text-xs text-ink-soft' => $layout === 'grid',
                'text-ink-soft' => $layout === 'rows' && $slab['min'] !== $active,
            ])>
                {{ $layout === 'rows' ? \Illuminate\Support\Str::replace(['–', '+'], [' to ', ' or more'], $item->slabRange($index)) : $item->slabRange($index) }}
            </span>
            <span @class(['font-semibold', 'text-brand-dark' => $layout === 'grid' && $slab['min'] === $active])>
                {{ \App\Support\Money::format($slab['paise']) }}{{ $layout === 'rows' ? ' each' : '' }}
            </span>
        </li>
    @endforeach
</ul>
