@props([
    'name',
    'url' => '#',
    'image' => null,
    'category' => null,
    'brand' => null,
    'variant' => null,
    'paise',
    'mrp' => null,
    'inStock' => true,
])

<article {{ $attributes->class('group relative flex flex-col overflow-hidden rounded-card border border-line bg-surface') }}>
    <div class="relative">
        <x-shop.product-image :src="$image" :alt="$name" :category="$category" class="aspect-square w-full" />
        <x-shop.price-tag offer :paise="$paise" :mrp="$mrp" size="sm" class="absolute top-2.5 left-0" />
        @unless ($inStock)
            <span class="absolute inset-x-0 bottom-0 bg-ink/80 py-1 text-center text-sm font-semibold text-white">Out of stock</span>
        @endunless
    </div>

    <div class="flex grow flex-col gap-1 p-3">
        @if ($brand)
            <p class="text-xs text-ink-soft">{{ $brand }}</p>
        @endif
        <h3 class="line-clamp-2 font-sans text-base leading-snug font-medium text-ink">
            <a href="{{ $url }}" class="after:absolute after:inset-0 hover:underline">{{ $name }}</a>
        </h3>
        @if ($variant)
            <p class="text-sm text-ink-soft">{{ $variant }}</p>
        @endif
        <x-shop.price :paise="$paise" :mrp="$mrp" class="mt-auto pt-1" />
    </div>

    @isset($action)
        <div class="relative z-10 px-3 pb-3">
            {{ $action }}
        </div>
    @endisset
</article>
