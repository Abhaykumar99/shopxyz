@use('App\Support\Money')

<div class="flex flex-col gap-12 pb-24 lg:gap-16 lg:pb-8">
    {{-- Hero for business buyers --}}
    <section aria-labelledby="wholesale-title" class="relative -mx-4 overflow-hidden bg-ink px-4 py-9 text-white sm:mx-0 sm:rounded-sheet sm:px-8 lg:px-12 lg:py-14">
        <div class="grid items-center gap-8 lg:grid-cols-[1.2fr_1fr]">
            <div class="flex flex-col gap-5">
                <span class="tag-shape inline-flex h-8 items-center self-start bg-accent pe-3 text-sm font-semibold text-ink">Wholesale</span>
                <h1 id="wholesale-title" class="text-[2.4rem] leading-[1.05] sm:text-5xl lg:text-6xl">
                    Bulk sweets, gifts and beauty for your business.
                </h1>
                <p class="max-w-xl text-lg text-white/80">
                    Order online at trade prices, the same way as any other order: add the quantity you need to your bag, pay by
                    UPI or cash on delivery, and follow the order until it reaches your counter.
                </p>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button href="#prices" size="lg" variant="inverse">See wholesale prices</x-ui.button>
                    <x-ui.button size="lg" variant="inverse-outline" :href="route('wholesale.quote')" wire:navigate>Request a quote</x-ui.button>
                </div>
                <ul class="grid gap-2 text-white/85 sm:grid-cols-2">
                    <li class="flex items-center gap-2"><x-ui.icon name="check" :size="18" class="text-accent" /> Better prices as quantities grow</li>
                    <li class="flex items-center gap-2"><x-ui.icon name="check" :size="18" class="text-accent" /> No account or approval needed</li>
                    <li class="flex items-center gap-2"><x-ui.icon name="check" :size="18" class="text-accent" /> Delivery across {{ $shop->deliveryArea ?? 'the city' }}</li>
                    <li class="flex items-center gap-2"><x-ui.icon name="check" :size="18" class="text-accent" /> GST invoice on request</li>
                </ul>
            </div>

            {{-- A price slab, shown the way customers will read it --}}
            <x-shop.wholesale-slabs :item="$featured" class="shadow-overlay" />
        </div>
    </section>

    {{-- Who we work with --}}
    <section aria-labelledby="who-title" class="flex flex-col gap-4">
        <x-shop.section-heading id="who-title" title="Who we work with">
            Regular stock, one-off events or seasonal gifting, in quantities that suit you.
        </x-shop.section-heading>
        <ul class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['store', 'Retail shops', 'Restock mithai, chocolates and beauty basics at trade prices.'],
                ['gift', 'Weddings and events', 'Sweet boxes and return gifts, packed to your theme.'],
                ['package', 'Corporate gifting', 'Diwali and year-end hampers with your logo card.'],
                ['receipt-indian-rupee', 'Hotels and cafés', 'Welcome sweets and guest amenities every month.'],
            ] as [$icon, $title, $text])
                <li class="flex flex-col gap-3 rounded-card border border-line bg-surface p-5 shadow-[var(--card-shadow)]">
                    <span class="flex size-11 items-center justify-center rounded-full bg-brand-tint text-brand"><x-ui.icon :name="$icon" /></span>
                    <h3 class="text-xl">{{ $title }}</h3>
                    <p class="text-ink-soft">{{ $text }}</p>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- How it works --}}
    <section aria-labelledby="steps-title" class="grid gap-6 rounded-sheet bg-mist p-6 lg:grid-cols-[1fr_2fr] lg:p-10">
        <div class="flex flex-col gap-3">
            <h2 id="steps-title" class="text-3xl">How wholesale ordering works</h2>
            <p class="text-ink-soft">
                The same bag, checkout and tracking as any other order. Prices are per unit and include taxes; delivery is added at checkout.
            </p>
            <p class="text-sm text-ink-soft">
                Need custom pricing, packing or branding?
                <x-ui.link :href="route('wholesale.quote')" wire:navigate>Request a quote</x-ui.link> instead.
            </p>
        </div>
        <ol class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['Add bulk quantities', 'Pick the quantity you need. The slab price applies from the minimum quantity upwards.'],
                ['Check out as usual', 'Pay by UPI, or by cash on delivery up to '.Money::format($shop->codMaxPaise).'.'],
                ['Track to your counter', 'Follow packing and delivery in your account, with a GST invoice on request.'],
            ] as $index => [$title, $text])
                <li class="flex flex-col gap-2 rounded-card bg-surface p-4">
                    <span class="figures flex size-8 items-center justify-center rounded-full bg-brand font-semibold text-white">{{ $index + 1 }}</span>
                    <p class="font-semibold">{{ $title }}</p>
                    <p class="text-sm text-ink-soft">{{ $text }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Price list --}}
    <section id="prices" aria-labelledby="prices-title" class="flex scroll-mt-40 flex-col gap-5">
        <div class="flex flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div class="flex flex-col gap-1">
                    <h2 id="prices-title" class="text-3xl">Wholesale prices</h2>
                    <p class="text-ink-soft">Per-unit prices by quantity. Add what you need to your bag and check out.</p>
                </div>
                @if ($bagCount > 0)
                    <x-ui.button variant="secondary" icon="shopping-bag" :href="route('cart.show')" wire:navigate>
                        Go to bag ({{ $bagCount }})
                    </x-ui.button>
                @endif
            </div>

            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <nav aria-label="Wholesale categories" class="-mx-4 overflow-x-auto px-4 no-scrollbar sm:mx-0 sm:px-0">
                    <ul class="flex min-w-max gap-2">
                        @foreach ($categories as $value => $label)
                            <li>
                                <button
                                    type="button"
                                    wire:click="$set('category', '{{ $value }}')"
                                    aria-pressed="{{ $category === $value ? 'true' : 'false' }}"
                                    @class([
                                        'inline-flex min-h-10 items-center rounded-full border px-4 text-sm font-medium transition-colors',
                                        'border-brand bg-brand text-white' => $category === $value,
                                        'border-line bg-surface hover:border-brand hover:text-brand' => $category !== $value,
                                    ])
                                >{{ $label }}</button>
                            </li>
                        @endforeach
                    </ul>
                </nav>
                <label for="wholesale-search" class="sr-only">Search wholesale products</label>
                <div class="flex h-10 items-center gap-2 rounded-full border border-line-strong bg-surface px-3 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand md:w-72">
                    <x-ui.icon name="search" :size="18" class="text-ink-soft" />
                    <input id="wholesale-search" type="search" wire:model.live.debounce.400ms="search" maxlength="60" placeholder="Search products" class="min-w-0 grow bg-transparent text-base focus:outline-none">
                </div>
            </div>
        </div>

        <p class="figures text-sm text-ink-soft" aria-live="polite">
            {{ $items->count() }} {{ \Illuminate\Support\Str::plural('product', $items->count()) }}
        </p>

        @if ($items->isEmpty())
            <x-ui.card>
                <x-ui.empty-state icon="search" title="No wholesale products match" :level="3">
                    Try another category, or ask us for something specific in a quote request.
                    <x-slot:action>
                        <x-ui.button :href="route('wholesale.quote')" wire:navigate>Request a quote</x-ui.button>
                    </x-slot:action>
                </x-ui.empty-state>
            </x-ui.card>
        @else
            <ul class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3" wire:loading.class="opacity-60" wire:target="category, search">
                @foreach ($items as $item)
                    @php
                        $sku = $item->sku();
                        $bagQuantity = $inBag[$sku] ?? 0;
                        $typed = max($item->moq(), (int) ($quantities[$sku] ?? $item->moq()));
                        $unitPrice = $item->unitPriceFor($typed);
                        $toNextSlab = $item->unitsToNextSlab($typed);
                    @endphp
                    <li wire:key="wholesale-{{ $sku }}" class="flex">
                        <article class="flex w-full flex-col gap-4 rounded-card border border-line bg-surface p-5 shadow-[var(--card-shadow)]">
                            <div class="flex items-start gap-3 sm:min-h-24">
                                <x-shop.product-image :category="$item->product->rootCategorySlug()" alt="" class="size-16 shrink-0 rounded-field" />
                                <div class="min-w-0">
                                    <p class="text-sm text-ink-soft">{{ $item->product->brand }}</p>
                                    <h3 class="text-xl leading-tight">
                                        <a href="{{ route('shop.product', ['product' => $item->product->slug, 'option' => $item->product->hasChoices() ? $sku : null]) }}" wire:navigate class="hover:underline">{{ $item->product->name }}</a>
                                    </h3>
                                    <p class="text-sm text-ink-soft">Priced per {{ $item->unit }}</p>
                                </div>
                            </div>

                            <x-shop.slab-table :item="$item" :quantity="$typed" />

                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <x-ui.badge tone="brand">Minimum {{ $item->moq() }}</x-ui.badge>
                                <span class="figures text-ink-soft">Retail {{ Money::format($item->variant->price_paise) }}</span>
                                @if ($item->bestSavingPercent() > 0)
                                    <x-ui.badge tone="offer">Save up to {{ $item->bestSavingPercent() }}%</x-ui.badge>
                                @endif
                            </div>

                            <div class="mt-auto flex flex-col gap-1 border-t border-line pt-4">
                                <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                                    <label for="qty-{{ $sku }}" class="text-sm font-semibold">Quantity</label>
                                    @if ($bagQuantity)
                                        <span class="figures flex items-center gap-1.5 text-sm font-medium text-pistachio">
                                            <x-ui.icon name="check" :size="16" /> {{ $bagQuantity }} in your bag
                                        </span>
                                    @endif
                                </div>
                                <form wire:submit="addBulkToCart('{{ $sku }}')" class="flex items-end gap-2">
                                    <input
                                        id="qty-{{ $sku }}"
                                        type="number"
                                        inputmode="numeric"
                                        min="{{ $item->moq() }}"
                                        max="{{ \App\Support\Demo\DemoWholesale::MAX_QUANTITY }}"
                                        step="1"
                                        wire:model.live.debounce.500ms="quantities.{{ $sku }}"
                                        class="figures h-11 w-24 shrink-0 rounded-field border border-line-strong bg-surface px-3 text-base"
                                    >
                                    <x-ui.button type="submit" icon="shopping-bag" loading="addBulkToCart('{{ $sku }}')" class="grow">Add to bag</x-ui.button>
                                </form>
                                <p class="figures text-sm text-ink-soft" aria-live="polite">
                                    {{ Money::format($unitPrice) }} each · <span class="font-semibold text-ink">{{ Money::format($unitPrice * $typed) }}</span>
                                    @if ($toNextSlab > 0)
                                        · add {{ $toNextSlab }} more for {{ Money::format($item->slabAfter($typed)['paise']) }} each
                                    @endif
                                </p>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{-- Optional: a quote for anything the price list cannot answer --}}
    <section aria-labelledby="quote-title" class="flex flex-col gap-4 rounded-sheet border border-line bg-surface p-6 shadow-[var(--card-shadow)] lg:flex-row lg:items-center lg:justify-between lg:p-8">
        <div class="flex flex-col gap-2">
            <h2 id="quote-title" class="text-2xl">Need something the price list can't answer?</h2>
            <p class="max-w-2xl text-ink-soft">
                Custom hampers, your logo on the packing, quantities beyond the slabs, a monthly supply arrangement or payment
                terms: send us the details and we'll come back with a quote. Everything else you can simply order online.
            </p>
        </div>
        <x-ui.button variant="secondary" icon="message-circle" :href="route('wholesale.quote')" wire:navigate class="shrink-0">Request a quote</x-ui.button>
    </section>
</div>
