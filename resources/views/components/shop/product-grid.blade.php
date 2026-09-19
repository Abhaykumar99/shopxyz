{{--
    Product cards with an "Add" action. Products with several shades or sizes link
    to the product page to choose; the rest add straight to the bag through the
    parent Livewire component's addToCart().
    `rail`: a horizontal, swipeable row on phones instead of a grid.
--}}
@props([
    'products',
    'rail' => false,
    'columns' => 'lg:grid-cols-4',
])

<ul {{ $attributes->class([
    'grid gap-3 sm:gap-4',
    '-mx-4 auto-cols-[46%] grid-flow-col overflow-x-auto overscroll-x-contain scroll-px-4 px-4 pb-2 [scrollbar-width:none] snap-x snap-mandatory sm:auto-cols-[31%] lg:mx-0 lg:grid-flow-row lg:auto-cols-auto lg:overflow-visible lg:scroll-px-0 lg:px-0 lg:pb-0 '.$columns => $rail,
    'grid-cols-2 sm:grid-cols-3 '.$columns => ! $rail,
]) }}>
    @foreach ($products as $product)
        @php
            $variant = $product->firstVariant();
            $choices = $product->variantCount();
            $choiceLabel = $product->hasChoices()
                ? $choices.' '.\Illuminate\Support\Str::plural(\Illuminate\Support\Str::lower($product->variant_label), $choices)
                : $variant?->name;
        @endphp
        @continue ($variant === null)
        <li wire:key="product-{{ $product->slug }}" @class(['flex', 'snap-start' => $rail])>
            <x-shop.product-card
                class="w-full"
                :name="$product->name"
                :url="route('shop.product', $product->slug)"
                :category="$product->rootCategorySlug()"
                :brand="$product->brand"
                :variant="$choiceLabel"
                :paise="$variant->price_paise"
                :mrp="$variant->mrp_paise"
                :in-stock="$product->inStock()"
            >
                <x-slot:action>
                    @if (! $product->inStock())
                        <x-ui.button size="sm" variant="secondary" block disabled>Out of stock</x-ui.button>
                    @elseif ($product->hasChoices())
                        <x-ui.button size="sm" variant="secondary" block :href="route('shop.product', $product->slug)">
                            Choose {{ \Illuminate\Support\Str::lower($product->variant_label) }}
                        </x-ui.button>
                    @else
                        <x-ui.button
                            size="sm"
                            variant="secondary"
                            icon="plus"
                            block
                            wire:click="addToCart('{{ $variant->sku }}')"
                            loading="addToCart('{{ $variant->sku }}')"
                            aria-label="Add {{ $product->name }} to bag"
                        >Add</x-ui.button>
                    @endif
                </x-slot:action>
            </x-shop.product-card>
        </li>
    @endforeach
</ul>
