@use('App\Support\Money')

@php
    $choiceWord = \Illuminate\Support\Str::lower($product->variantLabel);
@endphp

<div class="flex flex-col gap-10 pb-20 lg:pb-0">
    <x-ui.breadcrumb :items="$breadcrumb" class="-mb-6" />

    <div class="grid gap-6 lg:grid-cols-2 lg:gap-12">
        {{-- Gallery --}}
        <div class="flex flex-col gap-3 lg:sticky lg:top-32 lg:self-start">
            <div class="relative overflow-hidden rounded-sheet border border-line">
                <x-shop.product-image :category="$product->category" :alt="$product->name.', '.$variant->name" class="aspect-square w-full" />
                <x-shop.price-tag offer :paise="$variant->paise" :mrp="$variant->mrp" class="absolute top-4 left-0" />
            </div>
            <p class="text-center text-sm text-ink-soft">Product photos arrive when the shop adds them.</p>
        </div>

        {{-- Details --}}
        <div class="flex flex-col gap-6">
            <div class="flex flex-col gap-2">
                <a href="{{ route('shop.search', ['q' => $product->brand]) }}" class="self-start text-sm font-semibold tracking-wide text-brand hover:underline">{{ $product->brand }}</a>
                <h1 class="text-3xl font-bold sm:text-4xl">{{ $product->name }}</h1>
                <p class="text-lg text-ink-soft">{{ $product->summary }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <x-shop.price-tag :paise="$variant->paise" :mrp="$variant->mrp" size="lg" />
                @if ($variant->discountPercent() > 0)
                    <span class="text-sm font-semibold text-pistachio">You save {{ Money::format($variant->mrp - $variant->paise) }}</span>
                @endif
                <span class="w-full text-sm text-ink-soft">Price includes all taxes.</span>
            </div>

            @if ($product->hasChoices())
                <fieldset class="flex flex-col gap-3">
                    <legend class="mb-3 font-semibold">
                        {{ $product->variantLabel }}: <span class="font-normal">{{ $variant->name }}</span>
                    </legend>
                    <div role="radiogroup" aria-label="{{ $product->variantLabel }}" @class(['flex flex-wrap gap-3' => $product->hasSwatches(), 'grid grid-cols-2 gap-2 sm:grid-cols-3' => ! $product->hasSwatches()])>
                        @foreach ($product->variants as $option)
                            @php $selected = $option->sku === $variant->sku; @endphp
                            @if ($product->hasSwatches())
                                <button
                                    type="button"
                                    wire:key="option-{{ $option->sku }}"
                                    wire:click="selectVariant('{{ $option->sku }}')"
                                    role="radio"
                                    aria-checked="{{ $selected ? 'true' : 'false' }}"
                                    aria-label="{{ $option->name }}{{ $option->inStock() ? '' : ', out of stock' }}"
                                    title="{{ $option->name }}"
                                    @class([
                                        'relative flex size-12 items-center justify-center rounded-full border-2 transition',
                                        'border-brand ring-2 ring-brand-tint' => $selected,
                                        'border-line hover:border-line-strong' => ! $selected,
                                    ])
                                >
                                    <span class="size-9 rounded-full" style="background-color: {{ $option->swatch }}"></span>
                                    @unless ($option->inStock())
                                        <span aria-hidden="true" class="absolute h-0.5 w-10 rotate-45 bg-surface ring-1 ring-ink-soft"></span>
                                    @endunless
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:key="option-{{ $option->sku }}"
                                    wire:click="selectVariant('{{ $option->sku }}')"
                                    role="radio"
                                    aria-checked="{{ $selected ? 'true' : 'false' }}"
                                    @class([
                                        'flex min-h-14 flex-col items-start justify-center rounded-field border-2 px-3 py-2 text-start transition',
                                        'border-brand bg-brand-tint' => $selected,
                                        'border-line bg-surface hover:border-line-strong' => ! $selected,
                                    ])
                                >
                                    <span class="font-semibold">{{ $option->name }}</span>
                                    <span class="figures text-sm {{ $option->inStock() ? 'text-ink-soft' : 'text-danger' }}">
                                        {{ $option->inStock() ? Money::format($option->paise) : 'Out of stock' }}
                                    </span>
                                </button>
                            @endif
                        @endforeach
                    </div>
                </fieldset>
            @endif

            <div class="flex flex-col gap-4 rounded-sheet border border-line bg-surface p-4 sm:p-5">
                <p class="flex items-center gap-2 font-medium" aria-live="polite">
                    @if (! $variant->inStock())
                        <x-ui.icon name="circle-alert" class="text-danger" />
                        <span class="text-danger">Out of stock{{ $product->hasChoices() ? ' in this '.$choiceWord : '' }}</span>
                    @elseif ($variant->isLowStock())
                        <x-ui.icon name="clock" class="text-accent-ink" />
                        <span class="text-accent-ink">Only {{ $variant->stock }} left</span>
                    @else
                        <x-ui.icon name="circle-check" class="text-pistachio" />
                        <span class="text-pistachio">In stock</span>
                    @endif
                </p>

                @if ($variant->inStock())
                    <div class="flex flex-wrap items-center gap-3">
                        <x-shop.quantity-stepper wire:model="quantity" :value="$quantity" :max="$maxQuantity" :editable="$maxQuantity > 20" wire:key="qty-{{ $variant->sku }}" />
                        <x-ui.button size="lg" icon="shopping-bag" wire:click="add" loading="add" class="grow">Add to bag</x-ui.button>
                    </div>
                    <x-ui.button size="lg" variant="secondary" block wire:click="buyNow" loading="buyNow">Buy now</x-ui.button>
                    @if ($inBag)
                        <p class="text-sm text-ink-soft">
                            {{ $inBag }} already in your bag. <x-ui.link :href="route('cart.show')">View bag</x-ui.link>
                        </p>
                    @endif
                @else
                    <p class="text-ink-soft">
                        @if ($product->inStock())
                            Choose another {{ $choiceWord }} above, or ask us on WhatsApp when this one is back.
                        @else
                            Ask us on WhatsApp when this is back in stock.
                        @endif
                    </p>
                    @if ($shop->whatsappLink())
                        <x-ui.button variant="secondary" icon="message-circle" :href="$shop->whatsappLink().'?text='.rawurlencode('Hi, is '.$product->name.' ('.$variant->name.') back in stock?')" target="_blank" rel="noopener">Ask on WhatsApp</x-ui.button>
                    @endif
                @endif
            </div>

            @if ($wholesale)
                {{-- Bulk prices apply automatically from the minimum quantity (ADR-019) --}}
                <section aria-labelledby="bulk-title" class="flex flex-col gap-3 rounded-sheet border border-brand/30 bg-brand-tint/50 p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 id="bulk-title" class="text-xl">Buying in bulk?</h2>
                        <x-ui.badge tone="brand" icon="boxes">Wholesale</x-ui.badge>
                    </div>
                    <p class="text-ink-soft">
                        From {{ $wholesale->moq() }} units the wholesale price applies automatically, in your bag and at
                        checkout. Prices per {{ $wholesale->unit }}:
                    </p>
                    <x-shop.slab-table :item="$wholesale" layout="rows" :quantity="$quantity" />
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button icon="shopping-bag" wire:click="addWholesaleMinimum" loading="addWholesaleMinimum">
                            Add {{ max($quantity, $wholesale->moq()) }} to bag
                        </x-ui.button>
                        <x-ui.button variant="secondary" :href="route('wholesale.index')" wire:navigate>All wholesale prices</x-ui.button>
                    </div>
                    <p class="text-sm text-ink-soft">
                        Need custom packing or branding? <x-ui.link :href="route('wholesale.quote')" wire:navigate>Request a quote</x-ui.link>
                    </p>
                </section>
            @endif

            <ul class="grid gap-3 sm:grid-cols-2">
                <li class="flex items-start gap-3">
                    <x-ui.icon name="truck" class="mt-0.5 text-brand" />
                    <span>
                        <span class="block font-medium">{{ $shop->deliveryEta ?? 'Local delivery' }}</span>
                        <span class="text-sm text-ink-soft">
                            @if ($shop->freeDeliveryAbovePaise)
                                Free above {{ Money::format($shop->freeDeliveryAbovePaise) }}, otherwise {{ Money::format($shop->deliveryChargePaise) }}.
                            @endif
                        </span>
                    </span>
                </li>
                <li class="flex items-start gap-3">
                    <x-ui.icon name="banknote" class="mt-0.5 text-brand" />
                    <span>
                        <span class="block font-medium">Cash on delivery or UPI</span>
                        <span class="text-sm text-ink-soft">Pay when it arrives, or by any UPI app.</span>
                    </span>
                </li>
            </ul>

            @if ($product->highlights)
                <section aria-labelledby="highlights" class="flex flex-col gap-2">
                    <h2 id="highlights" class="text-xl font-bold">Highlights</h2>
                    <ul class="flex flex-col gap-1.5">
                        @foreach ($product->highlights as $highlight)
                            <li class="flex items-start gap-2"><x-ui.icon name="check" :size="18" class="mt-1 text-pistachio" />{{ $highlight }}</li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <section aria-labelledby="about" class="flex flex-col gap-2">
                <h2 id="about" class="text-xl font-bold">About this product</h2>
                <p class="max-w-prose text-ink-soft">{{ $product->description }}</p>
            </section>
        </div>
    </div>

    @if ($similar->isNotEmpty())
        <section aria-labelledby="similar-title" class="flex flex-col gap-4">
            <x-shop.section-heading id="similar-title" title="You may also like" :href="route('shop.category', $product->subcategory)" link-text="See more" />
            <x-shop.product-grid :products="$similar" rail />
        </section>
    @endif

    {{-- Phone: keep the main action in reach --}}
    @if ($variant->inStock())
        <div class="pb-safe fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] z-20 border-t border-line bg-surface/95 px-4 py-2 backdrop-blur-sm lg:hidden">
            <div class="mx-auto flex max-w-6xl items-center gap-3">
                <div class="min-w-0 grow">
                    <p class="truncate text-sm text-ink-soft">{{ $product->hasChoices() ? $variant->name : $product->name }}</p>
                    <x-shop.price :paise="$variant->paise" size="sm" />
                </div>
                <x-ui.button icon="shopping-bag" wire:click="add" loading="add">Add to bag</x-ui.button>
            </div>
        </div>
    @endif
</div>
