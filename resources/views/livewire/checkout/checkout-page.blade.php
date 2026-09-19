@use('App\Support\IndianPhone')
@use('App\Support\Money')

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-2">
        <x-ui.breadcrumb :items="['Bag' => route('cart.show'), 'Checkout' => null]" />
        <h1 class="text-3xl font-bold">Checkout</h1>
    </div>

    <form wire:submit="placeOrder" class="grid items-start gap-6 lg:grid-cols-[1fr_22rem]" novalidate>
        <ol class="flex flex-col gap-4">
            {{-- 1. Mobile number --}}
            <li>
                <x-ui.card padding="lg" class="flex flex-col gap-4">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="flex items-center gap-3 text-xl font-bold">
                            <span class="figures flex size-8 items-center justify-center rounded-full bg-brand font-display text-base text-white">1</span>
                            Mobile number
                        </h2>
                        @if (! $editingPhone)
                            <x-ui.button variant="ghost" size="sm" icon="pencil" wire:click="$set('editingPhone', true)">Change</x-ui.button>
                        @endif
                    </div>

                    @if ($editingPhone)
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                            <x-ui.input
                                label="Mobile number"
                                name="phoneForm.phone"
                                wire:model="phoneForm.phone"
                                wire:keydown.enter.prevent="savePhone"
                                type="tel"
                                prefix="+91"
                                inputmode="numeric"
                                autocomplete="tel-national"
                                maxlength="14"
                                hint="The delivery partner calls this number."
                                required
                                class="grow [&_label]:sr-only"
                            />
                            <x-ui.button variant="secondary" wire:click="savePhone" loading="savePhone">Save number</x-ui.button>
                        </div>
                    @else
                        <p class="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <span class="figures font-semibold">{{ IndianPhone::format($customer->phone) }}</span>
                            <span class="text-sm text-ink-soft">Signed in as {{ $customer->email }}</span>
                        </p>
                    @endif
                </x-ui.card>
            </li>

            {{-- 2. Delivery address --}}
            <li>
                <x-ui.card padding="lg" class="flex flex-col gap-4">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="flex items-center gap-3 text-xl font-bold">
                            <span class="figures flex size-8 items-center justify-center rounded-full bg-brand font-display text-base text-white">2</span>
                            Deliver to
                        </h2>
                        <x-ui.button variant="ghost" size="sm" icon="plus" wire:click="newAddress" loading="newAddress">Add address</x-ui.button>
                    </div>

                    @if ($addresses === [])
                        <x-ui.empty-state icon="map-pin" title="Add a delivery address" :level="3">
                            We deliver within {{ $shop->deliveryArea ?? 'our area' }}.
                            <x-slot:action>
                                <x-ui.button wire:click="newAddress" icon="plus">Add address</x-ui.button>
                            </x-slot:action>
                        </x-ui.empty-state>
                    @else
                        <fieldset class="flex flex-col gap-3" @error('addressId') aria-describedby="address-error" @enderror>
                            <legend class="sr-only">Delivery address</legend>
                            @foreach ($addresses as $address)
                                @php $served = $shop->servesPincode($address->pincode); @endphp
                                <x-ui.radio-card
                                    wire:key="address-{{ $address->id }}"
                                    name="addressId"
                                    :value="$address->id"
                                    :id="'address-'.$address->id"
                                    :title="$address->recipient_name"
                                    wire:model.live="addressId"
                                    :disabled="! $served"
                                    :class="$served ? '' : 'opacity-60'"
                                >
                                    <x-shop.address-card
                                        class="mt-1"
                                        :label="$address->label"
                                        :name="null"
                                        :phone="$address->formattedPhone()"
                                        :lines="$address->lines()"
                                        :pincode="$address->pincode"
                                        :is-default="$address->is_default"
                                    />
                                    @unless ($served)
                                        <span class="mt-2 text-sm font-medium text-danger">We don't deliver to this pincode yet.</span>
                                    @endunless
                                </x-ui.radio-card>
                            @endforeach
                        </fieldset>
                    @endif
                    @error('addressId')
                        <p id="address-error" class="flex items-start gap-1.5 text-sm font-medium text-danger"><x-ui.icon name="circle-alert" :size="16" class="mt-0.5" />{{ $message }}</p>
                    @enderror

                    <x-ui.textarea label="Note for delivery" name="note" wire:model="note" rows="2" maxlength="200" placeholder="For example: call before arriving" />
                </x-ui.card>
            </li>

            {{-- 3. Payment --}}
            <li>
                <x-ui.card padding="lg" class="flex flex-col gap-4">
                    <h2 class="flex items-center gap-3 text-xl font-bold">
                        <span class="figures flex size-8 items-center justify-center rounded-full bg-brand font-display text-base text-white">3</span>
                        Payment
                    </h2>
                    <fieldset class="grid gap-3 sm:grid-cols-2">
                        <legend class="sr-only">Payment method</legend>
                        <x-shop.payment-option
                            method="cod"
                            name="paymentMethod"
                            wire:model.live="paymentMethod"
                            :disabled="! $summary['cod_available']"
                            :note="$summary['cod_available'] ? null : 'Not available above '.Money::format($shop->codMaxPaise).'.'"
                        />
                        <x-shop.payment-option method="upi" name="paymentMethod" wire:model.live="paymentMethod" />
                    </fieldset>
                    @error('paymentMethod')
                        <p class="text-sm font-medium text-danger">{{ $message }}</p>
                    @enderror
                    @unless ($summary['cod_available'])
                        <x-ui.alert tone="info" title="Cash on delivery is not available for this order">
                            Orders above {{ Money::format($shop->codMaxPaise) }} are paid by UPI. If you need to pay another way,
                            <x-ui.link :href="route('wholesale.quote')" wire:navigate>ask us for a quote</x-ui.link> and we'll arrange it.
                        </x-ui.alert>
                    @endunless
                    @if ($paymentMethod === 'upi')
                        <x-ui.alert>After you place the order, we show a QR code for the exact amount. Pay with any UPI app, then upload the screenshot and UTR number.</x-ui.alert>
                    @endif
                </x-ui.card>
            </li>

            {{-- 4. Review --}}
            <li>
                <x-ui.card padding="lg" class="flex flex-col gap-4">
                    <h2 class="flex items-center gap-3 text-xl font-bold">
                        <span class="figures flex size-8 items-center justify-center rounded-full bg-brand font-display text-base text-white">4</span>
                        Review items
                    </h2>
                    <ul class="flex flex-col divide-y divide-line">
                        @foreach ($lines as $line)
                            <li wire:key="review-{{ $line->variant->sku }}" class="flex items-center gap-3 py-3">
                                <x-shop.product-image :category="$line->product->category" :alt="$line->product->name" class="size-14 shrink-0 rounded-field" />
                                <div class="min-w-0 grow">
                                    <p class="truncate font-medium">{{ $line->product->name }}</p>
                                    <p class="figures text-sm text-ink-soft">{{ $line->variant->name }}, qty {{ $line->quantity }} &times; {{ Money::format($line->unitPrice()) }}</p>
                                    @if ($line->isWholesale())
                                        <x-ui.badge tone="brand" icon="boxes" class="mt-1">Wholesale price</x-ui.badge>
                                    @endif
                                </div>
                                <x-shop.price :paise="$line->total()" size="sm" />
                            </li>
                        @endforeach
                    </ul>
                    <x-ui.link :href="route('cart.show')" class="self-start">Edit bag</x-ui.link>
                </x-ui.card>
            </li>
        </ol>

        <x-ui.card padding="lg" class="flex flex-col gap-4 lg:sticky lg:top-32">
            <h2 class="text-xl font-bold">Order total</h2>
            <dl class="figures grid grid-cols-[1fr_auto] gap-y-2">
                <dt class="text-ink-soft">Items ({{ $summary['items'] }})</dt>
                <dd class="text-end">{{ Money::format($summary['mrp']) }}</dd>
                @if ($summary['discount'] > 0)
                    <dt class="text-ink-soft">You save</dt>
                    <dd class="text-end font-medium text-pistachio">−{{ Money::format($summary['discount']) }}</dd>
                @endif
                <dt class="text-ink-soft">Delivery</dt>
                <dd class="text-end">{{ $summary['delivery'] ? Money::format($summary['delivery']) : 'Free' }}</dd>
                <dt class="border-t border-line pt-3 font-display text-lg font-bold">To pay</dt>
                <dd class="border-t border-line pt-3 text-end font-display text-lg font-bold">{{ Money::format($summary['total']) }}</dd>
            </dl>

            <x-ui.button type="submit" size="lg" block loading="placeOrder">
                {{ $paymentMethod === 'upi' ? 'Place order and pay by UPI' : 'Place order' }}
            </x-ui.button>
            @if ($errors->isNotEmpty())
                <p class="text-sm font-medium text-danger" role="alert">Please fix the highlighted details above.</p>
            @endif
            <p class="text-sm text-ink-soft">
                By placing the order you agree to our
                <a href="{{ route('pages.show', 'cancellation-refunds') }}" class="underline hover:text-brand">cancellation policy</a>.
            </p>
        </x-ui.card>
    </form>

    <x-ui.modal name="checkout-address" title="New delivery address" sheet max-width="lg">
        <form wire:submit="saveAddress" id="checkout-address-form" novalidate>
            <x-shop.address-fields model="addressForm" :labels="$labels" :states="$states" />
        </form>
        <x-slot:footer>
            <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'checkout-address')">Cancel</x-ui.button>
            <x-ui.button type="submit" form="checkout-address-form" loading="saveAddress">Save address</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
