@use('App\Enums\PaymentMethod')
@use('App\Enums\PaymentStatus')
@use('App\Support\Money')

<div class="mx-auto flex w-full max-w-xl flex-col gap-6">
    <div class="flex flex-col items-center gap-3 text-center">
        <span class="flex size-16 items-center justify-center rounded-full bg-pistachio-tint text-pistachio">
            <x-ui.icon name="circle-check" :size="36" />
        </span>
        <h1 class="text-3xl font-bold">
            @if ($order->paymentStatus === PaymentStatus::PendingVerification)
                Thank you, we're checking your payment
            @else
                Thank you, your order is placed
            @endif
        </h1>
        <p class="text-ink-soft">
            Order <span class="figures font-semibold text-ink">{{ $order->number }}</span>
            for <span class="figures font-semibold text-ink">{{ Money::format($order->total()) }}</span>
        </p>
    </div>

    <x-ui.card padding="lg" class="flex flex-col gap-4">
        <h2 class="text-lg font-bold">What happens next</h2>
        <ol class="flex flex-col gap-3">
            @if ($order->paymentStatus === PaymentStatus::PendingVerification)
                <li class="flex gap-3"><span class="figures flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-tint text-sm font-bold text-brand-dark">1</span>We match your UTR with our UPI account, usually within an hour.</li>
            @else
                <li class="flex gap-3"><span class="figures flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-tint text-sm font-bold text-brand-dark">1</span>We confirm your order and start packing.</li>
            @endif
            <li class="flex gap-3"><span class="figures flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-tint text-sm font-bold text-brand-dark">2</span>A delivery partner brings it to {{ $order->address->line2 ?? $order->address->city }}, {{ $order->address->pincode }}.</li>
            <li class="flex gap-3"><span class="figures flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-tint text-sm font-bold text-brand-dark">3</span>Share the delivery code from your order page when they arrive{{ $order->paymentMethod === PaymentMethod::Cod ? ', and pay '.Money::format($order->total()).' in cash' : '' }}.</li>
        </ol>
    </x-ui.card>

    <div class="flex flex-col gap-2 sm:flex-row">
        <x-ui.button :href="route('account.order', $order->number)" size="lg" icon="package" class="grow">Track this order</x-ui.button>
        <x-ui.button :href="route('shop.home')" size="lg" variant="secondary" class="grow">Continue shopping</x-ui.button>
    </div>
</div>
