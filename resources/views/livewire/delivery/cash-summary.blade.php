@use('App\Support\Money')

<div class="flex flex-col gap-3">
    <x-ui.card padding="lg" class="flex flex-col gap-2 bg-accent-tint">
        <h2 class="text-lg font-semibold">To hand over at the shop</h2>
        <p class="figures font-display text-4xl font-bold">{{ Money::format($cash['to_hand_over']) }}</p>
        <p class="text-ink-soft">
            From {{ count($cash['cod_deliveries']) }} {{ \Illuminate\Support\Str::plural('cash delivery', count($cash['cod_deliveries'])) }} today.
            The shop counts it with you and marks it received.
        </p>
    </x-ui.card>

    <x-ui.card>
        <x-slot:header>
            <h2 class="text-lg font-semibold">Today</h2>
            <span class="figures text-sm text-ink-soft">{{ $cash['deliveries'] }} delivered</span>
        </x-slot:header>
        <dl class="figures flex flex-col divide-y divide-line">
            <div class="flex items-center justify-between gap-3 py-2">
                <dt class="text-ink-soft">Cash collected</dt>
                <dd class="font-semibold">{{ Money::format($cash['collected']) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 py-2">
                <dt class="text-ink-soft">Already handed over</dt>
                <dd class="font-semibold text-pistachio">{{ Money::format($cash['handed_over']) }}</dd>
            </div>
            <div class="flex items-center justify-between gap-3 py-2">
                <dt class="text-ink-soft">Paid by UPI (no cash)</dt>
                <dd class="font-semibold">{{ $cash['upi_deliveries'] }} {{ \Illuminate\Support\Str::plural('order', $cash['upi_deliveries']) }}</dd>
            </div>
        </dl>
    </x-ui.card>

    @if ($cash['cod_deliveries'] === [])
        <x-ui.card>
            <x-ui.empty-state icon="banknote" title="No cash collected yet" :level="2">
                Cash deliveries you complete today will be listed here.
            </x-ui.empty-state>
        </x-ui.card>
    @else
        <h2 class="mt-1 px-1 text-lg font-semibold">Cash deliveries</h2>
        <x-ui.card padding="none" class="divide-y divide-line px-4">
            @foreach ($cash['cod_deliveries'] as $job)
                <div wire:key="cash-{{ $job->number }}" class="flex items-center justify-between gap-3 py-3">
                    <div class="min-w-0">
                        <a href="{{ route('delivery.order', $job->number) }}" wire:navigate class="figures font-semibold hover:underline">{{ $job->number }}</a>
                        <p class="truncate text-sm text-ink-soft">
                            {{ $job->customerName }} · {{ $job->timeFor(\App\Enums\DeliveryStep::Delivered) }}
                        </p>
                    </div>
                    <div class="text-end">
                        <x-shop.price :paise="$job->cashCollectedPaise" size="sm" />
                        @if ($job->handedOver)
                            <p class="text-xs font-medium text-pistachio">Handed over</p>
                        @else
                            <p class="text-xs text-ink-soft">With you</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </x-ui.card>
    @endif

    <x-ui.alert tone="info" title="How the handover works">
        Give the cash to the shop at the end of your round. They mark each order received, and anything still
        showing here is money you are holding.
    </x-ui.alert>
</div>
