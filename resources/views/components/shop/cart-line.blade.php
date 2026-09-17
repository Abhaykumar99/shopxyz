@props([
    'name',
    'url' => '#',
    'image' => null,
    'category' => null,
    'variant' => null,
    'paise',
    'mrp' => null,
    'quantity' => 1,
])

<article {{ $attributes->class('flex gap-3 py-4') }}>
    <a href="{{ $url }}" class="shrink-0" tabindex="-1" aria-hidden="true">
        <x-shop.product-image :src="$image" :alt="$name" :category="$category" class="size-20 rounded-field" />
    </a>

    <div class="flex min-w-0 grow flex-col gap-2">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <h3 class="font-sans text-base leading-snug font-medium">
                    <a href="{{ $url }}" class="hover:underline">{{ $name }}</a>
                </h3>
                @if ($variant)
                    <p class="text-sm text-ink-soft">{{ $variant }}</p>
                @endif
            </div>
            <x-ui.icon-button icon="trash" :label="'Remove '.$name" class="-me-2 -mt-2 text-ink-soft" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-2">
            <x-shop.quantity-stepper :value="$quantity" :label="'Quantity of '.$name" />
            <div class="text-end">
                <x-shop.price :paise="$paise * $quantity" />
                @if ($quantity > 1)
                    <p class="figures text-xs text-ink-soft">{{ \App\Support\Money::format($paise) }} each</p>
                @endif
            </div>
        </div>
    </div>
</article>
