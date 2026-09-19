{{-- Three products arranged like a shop window, for hero banners. Decorative: the products are linked elsewhere. --}}
@props(['products'])

<div {{ $attributes->class('grid grid-cols-2 gap-4') }} aria-hidden="true">
    @foreach ($products->take(3) as $pick)
        <div @class(['overflow-hidden rounded-card bg-white/95 text-ink shadow-overlay', 'row-span-2' => $loop->first])>
            <x-shop.product-image :category="$pick->category" alt="" :class="$loop->first ? 'h-[calc(100%-4.5rem)] w-full' : 'aspect-[16/10] w-full'" />
            <div class="flex h-[4.5rem] flex-col justify-center px-3">
                <p class="truncate text-sm font-medium">{{ $pick->name }}</p>
                <p class="figures font-display text-lg">{{ \App\Support\Money::format($pick->firstVariant()?->price_paise ?? 0) }}</p>
            </div>
        </div>
    @endforeach
</div>
