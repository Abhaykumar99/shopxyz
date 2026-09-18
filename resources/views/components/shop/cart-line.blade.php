{{--
    One bag line. With `sku` inside a Livewire page, the stepper binds to
    `quantities.{sku}` and the remove button calls remove('{sku}').

    A line that has reached the product's minimum wholesale quantity is priced at
    its slab price (ADR-019): pass `wholesale`, `slab` and the next slab so the
    line can say so and show what a larger quantity would cost.
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
    'wholesale' => false,
    'slab' => null,
    'nextSlabUnits' => 0,
    'nextSlabPaise' => null,
    'madeToOrder' => false,
    'step' => 1,
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
                @if ($wholesale)
                    <p class="mt-1 flex flex-wrap items-center gap-2">
                        <x-ui.badge tone="brand" icon="boxes">Wholesale price</x-ui.badge>
                        @if ($slab)
                            <span class="figures text-sm text-ink-soft">{{ $slab }} units</span>
                        @endif
                    </p>
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

        @if ($madeToOrder)
            <p class="flex items-start gap-1.5 text-sm text-ink-soft">
                <x-ui.icon name="package" :size="16" class="mt-0.5 shrink-0" />
                Ordered in for you. We confirm the delivery date when we call about the order.
            </p>
        @endif

        @if ($nextSlabUnits > 0 && $nextSlabPaise)
            <p class="figures flex items-start gap-1.5 text-sm font-medium text-brand-dark">
                <x-ui.icon name="percent" :size="16" class="mt-0.5 shrink-0" />
                Add {{ $nextSlabUnits }} more to pay {{ \App\Support\Money::format($nextSlabPaise) }} each
            </p>
        @endif

        <div class="flex flex-wrap items-center justify-between gap-2">
            @if ($sku)
                <x-shop.quantity-stepper
                    wire:model.live.debounce.400ms="quantities.{{ $sku }}"
                    wire:key="stepper-{{ $sku }}-{{ $quantity }}"
                    :value="$quantity"
                    :max="max($max, $quantity)"
                    :step="$step"
                    :editable="$wholesale || $max > 20"
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
