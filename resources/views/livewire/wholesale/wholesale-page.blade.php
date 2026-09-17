@use('App\Support\Money')

<div class="flex flex-col gap-12 pb-20 lg:gap-16 lg:pb-0">
    {{-- Hero for business buyers --}}
    <section aria-labelledby="wholesale-title" class="relative -mx-4 overflow-hidden bg-ink px-4 py-9 text-white sm:mx-0 sm:rounded-sheet sm:px-8 lg:px-12 lg:py-14">
        <div class="grid items-center gap-8 lg:grid-cols-[1.2fr_1fr]">
            <div class="flex flex-col gap-5">
                <span class="tag-shape inline-flex h-8 items-center self-start bg-accent pe-3 text-sm font-semibold text-ink">Wholesale</span>
                <h1 id="wholesale-title" class="text-[2.4rem] leading-[1.05] sm:text-5xl lg:text-6xl">
                    Bulk sweets, gifts and beauty for your business.
                </h1>
                <p class="max-w-xl text-lg text-white/80">
                    Price slabs for shops, wedding planners, hotels and corporate gifting. Send us your list and we'll confirm
                    a quote, packing and delivery within one working day.
                </p>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button href="#prices" size="lg" variant="inverse">See wholesale prices</x-ui.button>
                    <x-ui.button href="#enquiry" size="lg" variant="inverse-outline">Request a quote</x-ui.button>
                </div>
                <ul class="grid gap-2 text-white/85 sm:grid-cols-2">
                    <li class="flex items-center gap-2"><x-ui.icon name="check" :size="18" class="text-accent" /> Better prices as quantities grow</li>
                    <li class="flex items-center gap-2"><x-ui.icon name="check" :size="18" class="text-accent" /> Custom gift packing and cards</li>
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
            <h2 id="steps-title" class="text-3xl">How wholesale orders work</h2>
            <p class="text-ink-soft">No account needed. Prices below are per unit and include taxes; delivery is quoted separately.</p>
        </div>
        <ol class="grid gap-4 sm:grid-cols-3">
            @foreach ([
                ['Build your list', 'Add products at or above the minimum order. Prices update with each slab.'],
                ['Send the enquiry', 'Tell us your business, dates and any packing or branding needs.'],
                ['Get your quote', 'We call or WhatsApp within one working day to confirm price and delivery.'],
            ] as $index => [$title, $text])
                <li class="flex flex-col gap-2 rounded-card bg-surface p-4">
                    <span class="figures flex size-8 items-center justify-center rounded-full bg-brand font-semibold text-white">{{ $index + 1 }}</span>
                    <p class="font-semibold">{{ $title }}</p>
                    <p class="text-sm text-ink-soft">{{ $text }}</p>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Price list and enquiry list --}}
    <div class="grid items-start gap-6 lg:grid-cols-[1fr_22rem] lg:gap-8">
        <section id="prices" aria-labelledby="prices-title" class="flex min-w-0 scroll-mt-40 flex-col gap-4">
            <div class="flex flex-col gap-3">
                <h2 id="prices-title" class="text-3xl">Wholesale prices</h2>
                <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
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
                    <div class="flex h-10 items-center gap-2 rounded-full border border-line-strong bg-surface ps-3 pe-3 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand sm:max-w-sm xl:w-64">
                        <x-ui.icon name="search" :size="18" class="text-ink-soft" />
                        <input id="wholesale-search" type="search" wire:model.live.debounce.400ms="search" maxlength="60" placeholder="Search products" class="min-w-0 grow bg-transparent text-base focus:outline-none">
                    </div>
                </div>
            </div>

            @if ($items->isEmpty())
                <x-ui.card>
                    <x-ui.empty-state icon="search" title="No wholesale products match" :level="3">
                        Try another category, or describe what you need in the enquiry form below.
                    </x-ui.empty-state>
                </x-ui.card>
            @else
                <ul class="flex flex-col gap-3" wire:loading.class="opacity-60" wire:target="category, search">
                    @foreach ($items as $item)
                        @php
                            $sku = $item->sku();
                            $inList = $listQuantities[$sku] ?? 0;
                        @endphp
                        <li wire:key="wholesale-{{ $sku }}">
                            <article class="grid gap-4 rounded-card border border-line bg-surface p-4 shadow-[var(--card-shadow)] sm:grid-cols-[6rem_1fr] md:grid-cols-[6rem_1fr_15rem] md:items-center">
                                <x-shop.product-image :category="$item->product->category" alt="" class="hidden size-24 rounded-field sm:flex" />

                                <div class="flex min-w-0 flex-col gap-2">
                                    <div>
                                        <p class="text-sm text-ink-soft">{{ $item->product->brand }}</p>
                                        <h3 class="text-xl leading-tight">
                                            <a href="{{ route('shop.product', ['product' => $item->product->slug, 'option' => $item->product->hasChoices() ? $sku : null]) }}" class="hover:underline">{{ $item->product->name }}</a>
                                        </h3>
                                        <p class="text-sm text-ink-soft">Priced per {{ $item->unit }}@if ($item->product->hasChoices() && $item->product->hasSwatches()), shade {{ $item->variant->name }}@endif</p>
                                    </div>
                                    <ul class="figures flex flex-wrap gap-2 text-sm" aria-label="Price per {{ $item->unit }} by quantity">
                                        @foreach ($item->slabs as $index => $slab)
                                            @php $next = $item->slabs[$index + 1]['min'] ?? null; @endphp
                                            <li @class([
                                                'flex flex-col rounded-field border px-3 py-1.5',
                                                'border-brand bg-brand-tint' => $loop->last,
                                                'border-line' => ! $loop->last,
                                            ])>
                                                <span class="text-ink-soft">{{ $next ? "{$slab['min']} to ".($next - 1) : "{$slab['min']} or more" }}</span>
                                                <span @class(['font-semibold', 'text-brand-dark' => $loop->last])>{{ Money::format($slab['paise']) }} each</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <p class="flex flex-wrap items-center gap-2 text-sm">
                                        <x-ui.badge tone="brand">Minimum {{ $item->moq() }}</x-ui.badge>
                                        <span class="text-ink-soft">Retail {{ Money::format($item->variant->paise) }}</span>
                                        @if ($item->bestSavingPercent() > 0)
                                            <x-ui.badge tone="offer">Save up to {{ $item->bestSavingPercent() }}%</x-ui.badge>
                                        @endif
                                    </p>
                                </div>

                                <form wire:submit="addToEnquiry('{{ $sku }}')" class="flex flex-col gap-2 border-t border-line pt-3 md:border-t-0 md:border-s md:ps-4 md:pt-0">
                                    <label for="qty-{{ $sku }}" class="text-sm font-semibold">Quantity</label>
                                    <div class="flex gap-2">
                                        <input
                                            id="qty-{{ $sku }}"
                                            type="number"
                                            inputmode="numeric"
                                            min="{{ $item->moq() }}"
                                            max="{{ \App\Support\Demo\DemoWholesale::MAX_QUANTITY }}"
                                            step="1"
                                            wire:model="quantities.{{ $sku }}"
                                            class="figures h-11 w-24 rounded-field border border-line-strong bg-surface px-3 text-base"
                                        >
                                        <x-ui.button type="submit" icon="plus" loading="addToEnquiry('{{ $sku }}')" class="grow">Add</x-ui.button>
                                    </div>
                                    @if ($inList)
                                        <p class="flex items-center gap-1.5 text-sm text-pistachio"><x-ui.icon name="check" :size="16" /> {{ $inList }} in your enquiry</p>
                                    @endif
                                </form>
                            </article>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Enquiry list --}}
        <aside id="enquiry-list" aria-labelledby="list-title" class="scroll-mt-40 lg:sticky lg:top-32">
            <x-ui.card padding="lg" class="flex flex-col gap-4">
                <h2 id="list-title" class="text-2xl">Your enquiry</h2>
                @if ($lines === [])
                    <p class="text-ink-soft">Add products from the price list. You can also send an enquiry with just a message.</p>
                @else
                    <ul class="flex flex-col divide-y divide-line">
                        @foreach ($lines as $line)
                            @php $sku = $line['item']->sku(); @endphp
                            <li wire:key="line-{{ $sku }}" class="flex flex-col gap-2 py-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="font-medium leading-snug">{{ $line['item']->product->name }}</p>
                                        <p class="figures text-sm text-ink-soft">{{ Money::format($line['unit']) }} per {{ $line['item']->unit }}</p>
                                    </div>
                                    <x-ui.icon-button icon="trash" :label="'Remove '.$line['item']->product->name" class="-me-2 -mt-2 size-9 text-ink-soft" wire:click="removeFromEnquiry('{{ $sku }}')" />
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <label class="flex items-center gap-2 text-sm">
                                        <span class="sr-only">Quantity of {{ $line['item']->product->name }}</span>
                                        <input
                                            type="number"
                                            inputmode="numeric"
                                            min="{{ $line['item']->moq() }}"
                                            step="1"
                                            wire:model.live.debounce.600ms="listQuantities.{{ $sku }}"
                                            class="figures h-10 w-24 rounded-field border border-line-strong bg-surface px-3 text-base"
                                        >
                                        <span class="text-ink-soft">min {{ $line['item']->moq() }}</span>
                                    </label>
                                    <span class="figures font-semibold">{{ Money::format($line['total']) }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <dl class="figures flex items-center justify-between border-t border-line pt-3">
                        <dt class="font-semibold">Estimated total</dt>
                        <dd class="font-display text-2xl">{{ Money::format($estimate) }}</dd>
                    </dl>
                    <p class="text-sm text-ink-soft">An estimate at today's slab prices. Delivery and final pricing are confirmed in your quote.</p>
                @endif
                <x-ui.button href="#enquiry" block :variant="$lines === [] ? 'secondary' : 'primary'" icon-end="chevron-right">Continue to enquiry form</x-ui.button>
            </x-ui.card>
        </aside>
    </div>

    {{-- Enquiry form --}}
    <section id="enquiry" aria-labelledby="enquiry-title" class="scroll-mt-40">
        @if ($submitted)
            <x-ui.card padding="lg" class="mx-auto flex max-w-2xl flex-col items-center gap-4 text-center" role="status">
                <span class="flex size-16 items-center justify-center rounded-full bg-pistachio-tint text-pistachio"><x-ui.icon name="circle-check" :size="36" /></span>
                <h2 id="enquiry-title" class="text-3xl">Thank you, your enquiry is with us</h2>
                <p class="text-ink-soft">
                    Reference <span class="figures font-semibold text-ink">{{ $submitted['reference'] }}</span>.
                    We'll call {{ $submitted['details']['contact_name'] }} on {{ \App\Support\IndianPhone::format($submitted['details']['phone']) }} within one working day.
                </p>
                @if ($submitted['items'])
                    <p class="figures text-ink-soft">{{ count($submitted['items']) }} {{ \Illuminate\Support\Str::plural('product', count($submitted['items'])) }}, estimated {{ Money::format($submitted['estimate']) }}</p>
                @endif
                <div class="flex flex-wrap justify-center gap-2">
                    @if ($shop->whatsappLink())
                        <x-ui.button icon="message-circle" :href="$shop->whatsappLink().'?text='.rawurlencode('Hi, I sent wholesale enquiry '.$submitted['reference'].'.')" target="_blank" rel="noopener">Follow up on WhatsApp</x-ui.button>
                    @endif
                    <x-ui.button variant="secondary" wire:click="startNewEnquiry">Start a new enquiry</x-ui.button>
                </div>
            </x-ui.card>
        @else
            <div class="grid gap-6 rounded-sheet border border-line bg-surface p-5 shadow-[var(--card-shadow)] sm:p-8 lg:grid-cols-[1fr_2fr]">
                <div class="flex flex-col gap-3">
                    <h2 id="enquiry-title" class="text-3xl">Request a quote</h2>
                    <p class="text-ink-soft">
                        @if ($lines)
                            {{ count($lines) }} {{ \Illuminate\Support\Str::plural('product', count($lines)) }} in your enquiry, estimated {{ Money::format($estimate) }}.
                        @else
                            Your enquiry list is empty. Describe what you need in the message and we'll suggest products.
                        @endif
                    </p>
                    @if ($shop->phone)
                        <p class="text-sm text-ink-soft">Prefer to talk? Call <a href="tel:{{ preg_replace('/\s+/', '', $shop->phone) }}" class="figures font-semibold text-brand">{{ $shop->phone }}</a>.</p>
                    @endif
                </div>

                <form wire:submit="submit" class="grid gap-4 sm:grid-cols-2" novalidate>
                    <x-ui.input label="Business or organisation name" name="form.businessName" wire:model="form.businessName" autocomplete="organization" maxlength="120" required />
                    <x-ui.select label="Type of business" name="form.businessType" wire:model="form.businessType" :options="$businessTypes" required />
                    <x-ui.input label="Contact person" name="form.contactName" wire:model="form.contactName" autocomplete="name" maxlength="80" required />
                    <x-ui.input label="Mobile number" name="form.phone" wire:model="form.phone" type="tel" prefix="+91" inputmode="numeric" autocomplete="tel-national" maxlength="14" required />
                    <x-ui.input label="Email" name="form.email" wire:model="form.email" type="email" autocomplete="email" maxlength="120" />
                    <x-ui.input label="GSTIN" name="form.gstin" wire:model="form.gstin" maxlength="15" autocomplete="off" hint="For a GST invoice." input-class="uppercase" />
                    <x-ui.input label="Delivery city" name="form.city" wire:model="form.city" autocomplete="address-level2" maxlength="60" required />
                    <x-ui.input label="Delivery pincode" name="form.pincode" wire:model="form.pincode" inputmode="numeric" autocomplete="postal-code" maxlength="6" required />
                    <x-ui.input label="Needed by" name="form.neededBy" wire:model="form.neededBy" type="date" :min="now()->addDay()->toDateString()" class="sm:col-span-2" />
                    <x-ui.textarea
                        label="Message"
                        name="form.message"
                        wire:model="form.message"
                        rows="4"
                        maxlength="1000"
                        :required="$lines === []"
                        placeholder="For example: 200 sweet boxes for a wedding on 12 December, with name cards"
                        class="sm:col-span-2"
                    />
                    <div class="flex flex-col gap-2 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm text-ink-soft">We only use these details to prepare your quote.</p>
                        <x-ui.button type="submit" size="lg" icon="message-circle" loading="submit">Send enquiry</x-ui.button>
                    </div>
                </form>
            </div>
        @endif
    </section>

    {{-- Phone: enquiry summary in reach --}}
    @if ($lines && ! $submitted)
        <div class="pb-safe fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] z-20 border-t border-line bg-surface/95 px-4 py-2 backdrop-blur-sm lg:hidden">
            <div class="flex items-center gap-3">
                <div class="grow">
                    <p class="text-sm text-ink-soft">{{ count($lines) }} {{ \Illuminate\Support\Str::plural('product', count($lines)) }} in enquiry</p>
                    <p class="figures font-display text-lg">{{ Money::format($estimate) }}</p>
                </div>
                <x-ui.button href="#enquiry-list">Review</x-ui.button>
            </div>
        </div>
    @endif
</div>
