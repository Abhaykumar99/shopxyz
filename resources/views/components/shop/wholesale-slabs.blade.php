{{-- A wholesale product's price slabs as a card (used in banners). Decorative summary: the price list is on /wholesale. --}}
@props(['item'])

<div {{ $attributes->class('rounded-sheet bg-white p-6 text-ink') }} aria-hidden="true">
    <div class="flex items-center gap-3">
        <x-shop.product-image :category="$item->product->rootCategorySlug()" alt="" class="size-16 rounded-card" />
        <div>
            <p class="text-sm text-ink-soft">{{ $item->product->brand }}</p>
            <p class="font-display text-xl">{{ $item->product->name }}, {{ $item->unit }}</p>
        </div>
    </div>
    <ul class="figures mt-4 flex flex-col divide-y divide-line">
        @foreach ($item->slabs as $index => $slab)
            @php $next = $item->slabs[$index + 1]['min'] ?? null; @endphp
            <li @class(['flex justify-between gap-3 py-2', 'rounded-field bg-brand-tint px-2 font-semibold text-brand-dark' => $loop->last])>
                <span @class(['text-ink-soft' => ! $loop->last])>{{ $next ? "{$slab['min']} to ".($next - 1) : "{$slab['min']} or more" }}</span>
                <span class="font-semibold">{{ \App\Support\Money::format($slab['paise']) }} each</span>
            </li>
        @endforeach
    </ul>
    <p class="mt-3 text-sm text-ink-soft">Retail price {{ \App\Support\Money::format($item->variant->price_paise) }}</p>
</div>
