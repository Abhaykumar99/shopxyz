@use('App\Support\IndianPhone')
@use('App\Support\Money')

<div class="flex flex-col gap-8 pb-24 lg:pb-8">
    <x-ui.breadcrumb :items="['Wholesale' => route('wholesale.index'), 'Request a quote' => null]" />

    @if ($submitted)
        <section aria-labelledby="sent-title" class="mx-auto flex w-full max-w-2xl flex-col items-center gap-5 rounded-sheet border border-line bg-surface p-6 text-center shadow-[var(--card-shadow)] sm:p-10">
            <span class="flex size-16 items-center justify-center rounded-full bg-pistachio-tint text-pistachio"><x-ui.icon name="circle-check" :size="36" /></span>
            <h1 id="sent-title" class="text-3xl">Thank you, your request is with us</h1>
            <p class="text-ink-soft">
                Reference <span class="figures font-semibold text-ink">{{ $submitted['reference'] }}</span>.
                We'll call {{ $submitted['details']['contact_name'] }} on {{ IndianPhone::format($submitted['details']['phone']) }} within one working day.
            </p>
            @if ($submitted['items'])
                <p class="figures text-ink-soft">
                    You attached {{ count($submitted['items']) }} {{ \Illuminate\Support\Str::plural('product', count($submitted['items'])) }}, about {{ Money::format($submitted['estimate']) }} at today's prices.
                </p>
            @endif
            <p class="text-ink-soft">
                Your bag is untouched, so you can still check out now and we'll adjust anything the quote changes.
            </p>
            <div class="flex flex-wrap justify-center gap-2">
                @if ($shop->whatsappLink())
                    <x-ui.button variant="secondary" icon="message-circle" :href="$shop->whatsappLink().'?text='.rawurlencode('Hi, I sent wholesale quote request '.$submitted['reference'].'.')" target="_blank" rel="noopener">Follow up on WhatsApp</x-ui.button>
                @endif
                <x-ui.button :href="route('wholesale.index')" wire:navigate>Back to wholesale prices</x-ui.button>
                <x-ui.button variant="ghost" wire:click="startAnother">Send another request</x-ui.button>
            </div>
        </section>
    @else
        <div class="grid gap-8 lg:grid-cols-[1.4fr_1fr] lg:items-start">
            <div class="flex flex-col gap-6">
                <header class="flex flex-col gap-3">
                    <h1 class="text-[2.2rem] leading-tight sm:text-4xl">Request a wholesale quote</h1>
                    <p class="max-w-2xl text-lg text-ink-soft">
                        This is for anything our price list can't answer. For ordinary bulk orders you don't need a quote:
                        <x-ui.link :href="route('wholesale.index')" wire:navigate>the wholesale prices</x-ui.link> are live and you
                        can order online with UPI or cash on delivery.
                    </p>
                </header>

                <form wire:submit="submit" class="grid gap-4 rounded-sheet border border-line bg-surface p-5 shadow-[var(--card-shadow)] sm:grid-cols-2 sm:p-6" novalidate>
                    <h2 class="text-xl sm:col-span-2">What do you need?</h2>
                    <x-ui.textarea
                        label="Tell us about it"
                        name="form.message"
                        wire:model="form.message"
                        rows="4"
                        maxlength="1000"
                        required
                        hint="Products, quantities, packing or branding, and any dates."
                        placeholder="For example: 200 sweet boxes for a wedding on 12 December, with printed name cards and our logo on the sleeve"
                        class="sm:col-span-2"
                    />
                    <x-ui.input label="Needed by" name="form.neededBy" wire:model="form.neededBy" type="date" :min="now()->addDay()->toDateString()" class="sm:col-span-2" />

                    <h2 class="text-xl sm:col-span-2">Your business</h2>
                    <x-ui.input label="Business or organisation name" name="form.businessName" wire:model="form.businessName" autocomplete="organization" maxlength="120" required />
                    <x-ui.select label="Type of business" name="form.businessType" wire:model="form.businessType" :options="$businessTypes" required />
                    <x-ui.input label="Contact person" name="form.contactName" wire:model="form.contactName" autocomplete="name" maxlength="80" required />
                    <x-ui.input label="Mobile number" name="form.phone" wire:model="form.phone" type="tel" prefix="+91" inputmode="numeric" autocomplete="tel-national" maxlength="14" required />
                    <x-ui.input label="Email" name="form.email" wire:model="form.email" type="email" autocomplete="email" maxlength="120" />
                    <x-ui.input label="GSTIN" name="form.gstin" wire:model="form.gstin" maxlength="15" autocomplete="off" hint="For a GST invoice." input-class="uppercase" />
                    <x-ui.input label="Delivery city" name="form.city" wire:model="form.city" autocomplete="address-level2" maxlength="60" required />
                    <x-ui.input label="Delivery pincode" name="form.pincode" wire:model="form.pincode" inputmode="numeric" autocomplete="postal-code" maxlength="6" required />

                    @if ($lines !== [])
                        <x-ui.checkbox
                            label="Attach what's in my bag"
                            name="attachBag"
                            wire:model="attachBag"
                            :hint="count($lines).' '.\Illuminate\Support\Str::plural('product', count($lines)).', about '.Money::format($estimate).'. Nothing is ordered and your bag is not emptied.'"
                            class="sm:col-span-2"
                        />
                    @endif

                    <p class="text-sm text-ink-soft sm:col-span-2">
                        We only use these details to prepare your quote.
                        @if ($shop->phone)
                            Prefer to talk? Call <a href="tel:{{ preg_replace('/\s+/', '', $shop->phone) }}" class="figures font-semibold text-brand">{{ $shop->phone }}</a>.
                        @endif
                    </p>
                    <div class="sm:col-span-2">
                        <x-ui.button type="submit" size="lg" icon="message-circle" loading="submit" class="w-full sm:w-auto">Send request</x-ui.button>
                    </div>
                </form>
            </div>

            <aside class="flex flex-col gap-4 lg:sticky lg:top-28">
                <section aria-labelledby="when-title" class="flex flex-col gap-3 rounded-sheet bg-mist p-5">
                    <h2 id="when-title" class="text-xl">When a quote helps</h2>
                    <ul class="flex flex-col gap-2 text-ink-soft">
                        @foreach ([
                            'Custom hampers or packing you don\'t see on the site',
                            'Your logo, name cards or branded sleeves',
                            'Quantities beyond the published slabs',
                            'A monthly or seasonal supply arrangement',
                            'Payment terms or a proforma invoice for your accounts',
                        ] as $reason)
                            <li class="flex items-start gap-2"><x-ui.icon name="check" :size="18" class="mt-1 shrink-0 text-brand" /> {{ $reason }}</li>
                        @endforeach
                    </ul>
                </section>

                @if ($lines !== [])
                    <section aria-labelledby="bag-title" class="flex flex-col gap-3 rounded-sheet border border-line bg-surface p-5">
                        <div class="flex items-center justify-between gap-3">
                            <h2 id="bag-title" class="text-xl">In your bag</h2>
                            <p class="figures text-sm text-ink-soft">{{ Money::format($estimate) }}</p>
                        </div>
                        <ul class="flex flex-col gap-2">
                            @foreach ($lines as $line)
                                <li wire:key="quote-bag-{{ $line->variant->sku }}" class="flex items-baseline justify-between gap-3">
                                    <span class="min-w-0 truncate">{{ $line->product->name }}</span>
                                    <span class="figures shrink-0 text-sm text-ink-soft">{{ $line->quantity }} × {{ Money::format($line->unitPrice()) }}</span>
                                </li>
                            @endforeach
                        </ul>
                        <x-ui.button variant="secondary" size="sm" :href="route('cart.show')" wire:navigate class="self-start">Review bag</x-ui.button>
                    </section>
                @endif

                <p class="text-sm text-ink-soft">
                    Ready to order instead?
                    <x-ui.link :href="route('wholesale.index')" wire:navigate>Browse wholesale prices</x-ui.link>
                    and add the quantity you need to your bag.
                </p>
            </aside>
        </div>
    @endif
</div>
