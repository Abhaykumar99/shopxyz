@use('App\Support\Money')

<div class="flex flex-col gap-6 pb-24 lg:pb-0">
    <div class="flex flex-wrap items-end justify-between gap-2">
        <h1 class="text-3xl font-bold">
            Your bag
            @if ($summary['items'])
                <span class="figures text-lg font-medium text-ink-soft">({{ $summary['items'] }} {{ \Illuminate\Support\Str::plural('item', $summary['items']) }})</span>
            @endif
        </h1>
        @if ($lines)
            <x-ui.link :href="route('shop.home')">Continue shopping</x-ui.link>
        @endif
    </div>

    @if ($lines === [])
        <x-ui.card>
            <x-ui.empty-state icon="shopping-bag" title="Your bag is empty">
                Browse fresh sweets, beauty favourites and ready-to-give hampers.
                <x-slot:action>
                    <div class="flex flex-wrap justify-center gap-2">
                        <x-ui.button :href="route('shop.home')">Start shopping</x-ui.button>
                        <x-ui.button variant="secondary" :href="route('account.orders')">Your orders</x-ui.button>
                    </div>
                </x-slot:action>
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <div class="grid items-start gap-6 lg:grid-cols-[1fr_22rem]">
            <div class="flex flex-col gap-4">
                @if ($summary['free_delivery_shortfall'] > 0)
                    <div class="flex flex-col gap-2 rounded-card border border-line bg-surface p-4">
                        <p>Add <strong class="figures">{{ Money::format($summary['free_delivery_shortfall']) }}</strong> more for <strong>free delivery</strong>.</p>
                        <div class="h-2 overflow-hidden rounded-full bg-mist" role="progressbar" aria-label="Progress to free delivery" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $freeDeliveryProgress }}">
                            <div class="h-full rounded-full bg-brand transition-[width]" style="width: {{ $freeDeliveryProgress }}%"></div>
                        </div>
                    </div>
                @elseif ($summary['delivery'] === 0)
                    <x-ui.alert tone="success">Your order qualifies for free delivery.</x-ui.alert>
                @endif

                @if ($blocked)
                    <x-ui.alert tone="danger" title="Some items are no longer available">
                        Update the items marked below to continue to checkout.
                    </x-ui.alert>
                @endif

                <h2 class="sr-only">Items in your bag</h2>
                <x-ui.card padding="none" class="divide-y divide-line px-4">
                    @foreach ($lines as $line)
                        <x-shop.cart-line
                            wire:key="line-{{ $line->variant->sku }}"
                            :sku="$line->variant->sku"
                            :name="$line->product->name"
                            :url="route('shop.product', ['product' => $line->product->slug, 'option' => $line->product->hasChoices() ? $line->variant->sku : null])"
                            :category="$line->product->category"
                            :variant="$line->product->hasChoices() || $line->variant->name !== 'Standard' ? $line->variant->name : null"
                            :paise="$line->variant->paise"
                            :mrp="$line->variant->mrp"
                            :quantity="$line->quantity"
                            :max="$line->maxQuantity()"
                            :available="$line->isAvailable()"
                            :stock="$line->variant->stock"
                        />
                    @endforeach
                </x-ui.card>
            </div>

            <x-ui.card class="flex flex-col gap-4 lg:sticky lg:top-32" padding="lg">
                <h2 class="text-xl font-bold">Order summary</h2>
                <dl class="figures grid grid-cols-[1fr_auto] gap-y-2" wire:loading.class="opacity-60" wire:target="quantities, remove">
                    <dt class="text-ink-soft">Items at MRP</dt>
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

                @unless ($summary['meets_minimum'])
                    <x-ui.alert tone="warning">The minimum order is {{ Money::format($shop->minOrderPaise) }}. Add a little more to check out.</x-ui.alert>
                @endunless

                @if ($blocked || ! $summary['meets_minimum'])
                    <x-ui.button size="lg" block disabled>Checkout</x-ui.button>
                @else
                    <x-ui.button size="lg" block :href="route('checkout.show')" icon-end="chevron-right">Checkout</x-ui.button>
                @endif

                <ul class="flex flex-col gap-2 text-sm text-ink-soft">
                    <li class="flex items-center gap-2"><x-ui.icon name="banknote" :size="18" /> Cash on delivery or UPI</li>
                    <li class="flex items-center gap-2"><x-ui.icon name="truck" :size="18" /> {{ $shop->deliveryEta ?? 'Local delivery by our team' }}</li>
                </ul>
            </x-ui.card>
        </div>

        {{-- Phone: total and checkout in reach --}}
        <div class="pb-safe fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] z-20 border-t border-line bg-surface/95 px-4 py-2 backdrop-blur-sm lg:hidden">
            <div class="flex items-center gap-3">
                <div class="grow">
                    <p class="text-sm text-ink-soft">To pay</p>
                    <x-shop.price :paise="$summary['total']" />
                </div>
                @if ($blocked || ! $summary['meets_minimum'])
                    <x-ui.button disabled>Checkout</x-ui.button>
                @else
                    <x-ui.button :href="route('checkout.show')" icon-end="chevron-right">Checkout</x-ui.button>
                @endif
            </div>
        </div>
    @endif
</div>
