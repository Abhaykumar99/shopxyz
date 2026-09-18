@use('App\Enums\OrderStatus')
@use('App\Enums\PaymentMethod')
@use('App\Enums\PaymentStatus')
@use('App\Support\Money')

<div class="flex flex-col gap-5">
    <div class="flex flex-col gap-3">
        <x-ui.breadcrumb :items="['Your orders' => route('account.orders'), $order->number => null]" />
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-col gap-1">
                <h1 class="figures text-3xl font-bold">{{ $order->number }}</h1>
                <p class="text-ink-soft">Placed {{ $order->placedAt->format('l, j F Y \a\t g:i a') }}</p>
            </div>
            <x-ui.status-pill :tone="$order->status->tone()">{{ $order->status->label() }}</x-ui.status-pill>
        </div>
    </div>

    <div class="grid items-start gap-5 lg:grid-cols-[1fr_22rem]">
        <div class="flex flex-col gap-5">
            {{-- Payment needs the customer --}}
            @if ($order->needsPaymentProof())
                <x-ui.alert :tone="$order->paymentStatus === PaymentStatus::Rejected ? 'danger' : 'warning'" :title="$order->paymentStatus === PaymentStatus::Rejected ? 'We couldn\'t match your payment' : 'Payment details needed'">
                    <p>{{ $order->rejectionReason ?? 'Pay by UPI and send us the screenshot and UTR so we can confirm your order.' }}</p>
                    <x-ui.button :href="route('orders.pay', $order->number)" class="mt-3" icon="qr-code">
                        {{ $order->paymentStatus === PaymentStatus::Rejected ? 'Send payment details again' : 'Pay now' }}
                    </x-ui.button>
                </x-ui.alert>
            @elseif ($order->paymentStatus === PaymentStatus::PendingVerification)
                <x-ui.alert title="We're checking your payment">
                    We're matching UTR <span class="figures font-semibold">{{ $order->utr }}</span> with our UPI account. This usually takes under an hour.
                </x-ui.alert>
            @endif

            {{-- Delivery code while on the way --}}
            @if ($order->visibleDeliveryCode())
                <section aria-labelledby="code-title" class="flex flex-col items-center gap-2 rounded-sheet bg-brand p-5 text-center text-white">
                    <h2 id="code-title" class="font-sans text-base font-semibold text-white/85">Your delivery code</h2>
                    <p class="figures font-display text-5xl font-bold tracking-[0.3em]">
                        <span aria-hidden="true">{{ $order->visibleDeliveryCode() }}</span>
                        <span class="sr-only">{{ implode(' ', str_split($order->visibleDeliveryCode())) }}</span>
                    </p>
                    <p class="max-w-sm text-sm text-white/85">Share this code with the delivery partner only when you have your parcel.</p>
                </section>
            @endif

            <x-ui.card padding="lg" class="flex flex-col gap-5">
                <h2 class="text-xl font-bold">Order progress</h2>
                <x-shop.order-tracker :steps="$order->trackerSteps()" :failed="in_array($order->status, [OrderStatus::DeliveryFailed, OrderStatus::Cancelled], true)" />

                @if ($order->deliveryPartner && $order->isActive())
                    <div class="flex items-center gap-3 rounded-card bg-mist p-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-full bg-surface text-brand"><x-ui.icon name="bike" /></span>
                        <div class="min-w-0 grow">
                            <p class="font-semibold">{{ $order->deliveryPartner['name'] }}</p>
                            <p class="text-sm text-ink-soft">Your delivery partner</p>
                        </div>
                        <x-ui.button variant="secondary" size="sm" icon="phone" :href="'tel:'.preg_replace('/\s+/', '', $order->deliveryPartner['phone'])">Call</x-ui.button>
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card padding="lg" class="flex flex-col gap-3">
                <h2 class="text-xl font-bold">Items ({{ $order->itemCount() }})</h2>
                <ul class="flex flex-col divide-y divide-line">
                    @foreach ($order->items as $item)
                        <li wire:key="item-{{ $item['sku'] }}" class="flex items-center gap-3 py-3">
                            <x-shop.product-image :category="$item['category']" alt="" class="size-16 shrink-0 rounded-field" />
                            <div class="min-w-0 grow">
                                <a href="{{ route('shop.product', $item['slug']) }}" class="font-medium hover:underline">{{ $item['name'] }}</a>
                                <p class="figures text-sm text-ink-soft">{{ $item['variant'] }}, qty {{ $item['quantity'] }} &times; {{ \App\Support\Money::format($item['paise']) }}</p>
                                @if ($item['wholesale'] ?? false)
                                    <x-ui.badge tone="brand" icon="boxes" class="mt-1">Wholesale price</x-ui.badge>
                                @endif
                            </div>
                            <x-shop.price :paise="$item['paise'] * $item['quantity']" size="sm" />
                        </li>
                    @endforeach
                </ul>
                <x-ui.button variant="secondary" icon="shopping-bag" wire:click="buyAgain" loading="buyAgain" class="self-start">Buy these again</x-ui.button>
            </x-ui.card>
        </div>

        <div class="flex flex-col gap-5 lg:sticky lg:top-32">
            <x-ui.card padding="lg" class="flex flex-col gap-3">
                <h2 class="text-lg font-bold">Payment</h2>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <span>{{ $order->paymentMethod->label() }}</span>
                    <x-ui.status-pill :tone="$order->paymentStatus->tone()">{{ $order->paymentStatus->label() }}</x-ui.status-pill>
                </div>
                <dl class="figures grid grid-cols-[1fr_auto] gap-y-2 border-t border-line pt-3">
                    <dt class="text-ink-soft">Items at MRP</dt>
                    <dd class="text-end">{{ Money::format($order->mrpTotal()) }}</dd>
                    @if ($order->discount() > 0)
                        <dt class="text-ink-soft">You saved</dt>
                        <dd class="text-end text-pistachio">−{{ Money::format($order->discount()) }}</dd>
                    @endif
                    <dt class="text-ink-soft">Delivery</dt>
                    <dd class="text-end">{{ $order->delivery ? Money::format($order->delivery) : 'Free' }}</dd>
                    <dt class="border-t border-line pt-2 font-display text-lg font-bold">Total</dt>
                    <dd class="border-t border-line pt-2 text-end font-display text-lg font-bold">{{ Money::format($order->total()) }}</dd>
                </dl>
                @if ($order->paymentMethod === PaymentMethod::Cod && $order->isActive())
                    <p class="text-sm text-ink-soft">Keep {{ Money::format($order->total()) }} ready in cash when the parcel arrives.</p>
                @endif
                @if ($order->invoiceNumber)
                    <p class="flex items-start gap-2 text-sm text-ink-soft">
                        <x-ui.icon name="receipt-indian-rupee" :size="18" class="mt-0.5" />
                        <span>Invoice <span class="figures">{{ $order->invoiceNumber }}</span>. Download opens soon.</span>
                    </p>
                @endif
            </x-ui.card>

            <x-ui.card padding="lg" class="flex flex-col gap-2">
                <h2 class="text-lg font-bold">Delivery address</h2>
                <x-shop.address-card :name="$order->address->name" :label="$order->address->label" :phone="$order->address->formattedPhone()" :lines="$order->address->lines()" :pincode="$order->address->pincode" />
                @if ($order->note)
                    <p class="mt-2 rounded-field bg-mist p-3 text-sm"><span class="font-semibold">Your note:</span> {{ $order->note }}</p>
                @endif
            </x-ui.card>

            <div class="flex flex-col gap-2">
                @if ($order->canBeCancelled())
                    <x-ui.button variant="secondary" icon="x" x-on:click="$dispatch('open-modal', 'cancel-order')" block>Cancel order</x-ui.button>
                @endif
                @if ($shop->whatsappLink())
                    <x-ui.button variant="ghost" icon="message-circle" :href="$shop->whatsappLink().'?text='.rawurlencode('Hi, I need help with order '.$order->number)" target="_blank" rel="noopener" block>Get help on WhatsApp</x-ui.button>
                @endif
            </div>
        </div>
    </div>

    @if ($order->canBeCancelled())
        <x-ui.modal name="cancel-order" title="Cancel this order?" max-width="sm">
            <p>Order <span class="figures font-semibold">{{ $order->number }}</span> hasn't been packed yet, so you can still cancel it.</p>
            @if ($order->paymentStatus === PaymentStatus::PendingVerification || $order->paymentStatus === PaymentStatus::Verified)
                <p class="mt-2 text-ink-soft">If you've already paid by UPI, we'll contact you about the refund.</p>
            @endif
            <x-slot:footer>
                <x-ui.button variant="secondary" x-on:click="$dispatch('close-modal', 'cancel-order')">Keep order</x-ui.button>
                <x-ui.button variant="danger" wire:click="cancel" loading="cancel">Cancel order</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
