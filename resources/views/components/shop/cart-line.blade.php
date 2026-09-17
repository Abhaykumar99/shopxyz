{{--
    One bag line. With `sku` inside a Livewire page, the stepper binds to
    `quantities.{sku}` and the remove button calls remove('{sku}').
--}}
@props([
    'name',
    'url' => '#',
    'image' => null,
    'category' => null,
    'variant' => null,
    'paise',
    'mrp' => null,
    'quantity' => 1,
    'sku' => null,
    'max' => 10,
    'available' => true,
    'stock' => null,
])

<article {{ $attributes->class('flex gap-3 py-4') }}>
    <a href="{{ $url }}" class="shrink-0" tabindex="-1" aria-hidden="true">
        <x-shop.product-image :src="$image" :alt="$name" :category="$category" class="size-20 rounded-field sm:size-24" />
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
                @if ($mrp && $mrp > $paise)
                    <p class="figures text-sm text-ink-soft"><x-shop.price :paise="$paise" :mrp="$mrp" size="sm" /></p>
                @endif
            </div>
            <x-ui.icon-button
                icon="trash"
                :label="'Remove '.$name"
                class="-me-2 -mt-2 text-ink-soft"
                :wire:click="$sku ? 'remove(\''.$sku.'\')' : null"
            />
        </div>

        @unless ($available)
            <p class="flex items-start gap-1.5 text-sm font-medium text-danger">
                <x-ui.icon name="circle-alert" :size="16" class="mt-0.5" />
                {{ $stock ? "Only {$stock} left. Lower the quantity to continue." : 'Out of stock. Remove it to continue.' }}
            </p>
        @endunless

        <div class="flex flex-wrap items-center justify-between gap-2">
            @if ($sku)
                <x-shop.quantity-stepper
                    wire:model.live.debounce.400ms="quantities.{{ $sku }}"
                    wire:key="stepper-{{ $sku }}-{{ $quantity }}"
                    :value="$quantity"
                    :max="max($max, $quantity)"
                    :label="'Quantity of '.$name"
                />
            @else
                <x-shop.quantity-stepper :value="$quantity" :label="'Quantity of '.$name" />
            @endif
            <div class="text-end">
                <x-shop.price :paise="$paise * $quantity" />
                @if ($quantity > 1)
                    <p class="figures text-xs text-ink-soft">{{ \App\Support\Money::format($paise) }} each</p>
                @endif
            </div>
        </div>
    </div>
</article>
